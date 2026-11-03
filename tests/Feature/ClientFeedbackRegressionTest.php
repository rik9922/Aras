<?php

namespace Tests\Feature;

use App\Exports\AdminRecapEvent;
use App\Exports\EventsExport;
use App\Exports\SingleRecapDetailExport;
use App\Mail\RecapExportMail;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\EventLocation;
use App\Models\Recap;
use App\Models\User;
use App\Services\LocationStateService;
use App\Services\RecapExportService;
use App\Services\RecapFormService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientFeedbackRegressionTest extends TestCase
{
    use DatabaseTransactions;

    private function user(int $role, string $status = '1'): User
    {
        return User::create(['name' => 'Feedback Test '.uniqid(), 'email' => uniqid('feedback-').'@test.local', 'password' => bcrypt('test-password'), 'role_id' => $role, 'status' => $status, 'wage' => 20]);
    }

    private function event(array $attributes = []): Event
    {
        $event = Event::query()->orderBy('id')->firstOrFail()->replicate();
        $event->forceFill(array_merge(['event_name' => 'Feedback Test '.uniqid(), 'start_time' => '2026-12-05 10:00:00', 'end_time' => '2026-12-05 12:00:00', 'event_status' => '0', 'payment_type' => 'No purchase', 'recap_needed' => 1, 'timezone' => 'America/Chicago', 'position' => 1], $attributes))->saveQuietly();
        return $event;
    }

    private function api(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user));
    }

    #[Test]
    public function approved_recap_cannot_be_changed_through_either_api_or_a_new_draft(): void
    {
        $ba = $this->user(5);
        $event = $this->event();
        $recap = Recap::create(['event_id' => $event->id, 'ambassador_id' => $ba->id, 'is_approved' => true, 'is_draft' => false]);
        $this->api($ba)->postJson('/api/auth/v1/ambassador/add_update_recap', ['id' => $recap->id])->assertForbidden();
        $this->api($ba)->postJson('/api/auth/v1/admin/add_update_recap', ['id' => $recap->id])->assertForbidden();
        $this->api($ba)->postJson('/api/auth/v1/ambassador/add_update_recap', ['event_id' => $event->id])->assertForbidden();
        $this->api($ba)->postJson('/api/auth/v1/ambassador/save_recap_draft', ['event_id' => $event->id])->assertForbidden();
        $this->api($ba)->getJson('/api/auth/v1/ambassador/get_recap_by_id?id='.$recap->id)->assertOk();
        $this->assertTrue($recap->fresh()->is_approved);
        $this->assertSame(1, Recap::where('event_id', $event->id)->count());
    }

    #[Test]
    public function approved_recap_web_edit_and_update_are_forbidden_for_its_owner(): void
    {
        $ba = $this->user(5);
        $recap = Recap::create(['event_id' => $this->event()->id, 'ambassador_id' => $ba->id, 'is_approved' => true]);
        $this->actingAs($ba)->get(route('ambassador.edit-recap', $recap->id))->assertForbidden();
        $this->actingAs($ba)->post(route('ambassador.update-recap'), ['recap_id' => $recap->id])->assertForbidden();
    }

    #[Test]
    public function deactivated_manager_with_an_existing_token_cannot_view_markets(): void
    {
        $manager = $this->user(6);
        $this->api($manager);
        $manager->update(['status' => '0']);
        $this->getJson('/api/auth/v1/admin/get_market_data')->assertForbidden()->assertJsonPath('error_type', 'inactive_account');
    }

    #[Test]
    public function deactivated_manager_with_a_web_session_is_logged_out(): void
    {
        $manager = $this->user(6, '0');
        $this->actingAs($manager)->get('/manager/markets')->assertRedirect('/login');
    }

    #[Test]
    public function manager_radios_return_only_the_chosen_status(): void
    {
        $admin = $this->user(1);
        $this->user(6, '0');
        $this->user(6, '1');
        foreach ([0 => '1', 1 => '0'] as $inactive => $status) {
            $response = $this->api($admin)->getJson('/api/auth/v1/admin/get_managers?is_inactive='.$inactive.'&length=50');
            $response->assertOk();
            $rows = $response->json('data.data');
            $this->assertNotEmpty($rows);
            foreach ($rows as $row) $this->assertSame($status, $row['status']);
        }
    }

    #[Test]
    public function timing_is_yes_no_and_consumer_count_can_exceed_25(): void
    {
        $rules = array_intersect_key(RecapFormService::baseRules(null), array_flip(['timing_successful', 'total_interaction', 'interaction_age']));
        $this->assertTrue(Validator::make(['timing_successful' => '0', 'total_interaction' => 350, 'interaction_age' => 21], $rules)->passes());
        $this->assertTrue(Validator::make(['timing_successful' => '1', 'total_interaction' => 1000, 'interaction_age' => 25], $rules)->passes());
        $this->assertTrue(Validator::make(['timing_successful' => 'yesterday', 'total_interaction' => -1, 'interaction_age' => 20], $rules)->fails());
    }

    #[Test]
    public function comdata_requires_a_receipt_but_an_existing_receipt_can_be_retained(): void
    {
        $event = new Event(['payment_type' => 'Comdata']);
        $rules = ['receipt' => RecapFormService::baseRules($event)['receipt']];
        $this->assertTrue(Validator::make([], $rules)->fails());
        $recap = new Recap(['receipt' => 'recap/receipt.jpg']);
        $this->assertTrue(Validator::make([], ['receipt' => RecapFormService::baseRules($event, $recap)['receipt']])->passes());
        $this->assertTrue(RecapFormService::receiptRequired(new Event(['payment_type' => '3'])));
        $this->assertTrue(Validator::make([], ['receipt' => RecapFormService::baseRules(new Event(['payment_type' => 'No purchase']))['receipt']])->passes());
    }

    #[Test]
    public function removing_all_off_premise_receipts_requires_a_replacement_for_comdata(): void
    {
        $this->app['request']->merge(['off_premise_attachments_present' => 1, 'existing_op_event_receipts' => []]);
        $event = new Event(['payment_type' => 'Comdata']);
        $recap = new Recap(['op_receipts' => json_encode(['recap/old.jpg'])]);
        $rules = ['op_event_receipts' => RecapFormService::offPremiseRules($event, $recap)['op_event_receipts']];
        $this->assertTrue(Validator::make([], $rules)->fails());
        $this->app['request']->merge(['existing_op_event_receipts' => ['recap/old.jpg']]);
        $this->assertTrue(Validator::make([], ['op_event_receipts' => RecapFormService::offPremiseRules($event, $recap)['op_event_receipts']])->passes());
    }

    #[Test]
    public function state_choices_are_complete_unique_and_match_abbreviations_and_full_names(): void
    {
        $service = app(LocationStateService::class);
        $states = $service->options();
        $this->assertCount(51, $states);
        $this->assertCount(51, $states->unique('id'));
        $this->assertSame(['WI', 'WISCONSIN'], $service->aliases('Wisconsin'));
        $this->assertSame(['WI', 'WISCONSIN'], $service->aliases('wi'));
        $this->assertSame('WI', $service->options('Wisconsin')->first()->id);
    }

    #[Test]
    public function events_export_supports_real_date_location_staffing_and_overview_filters(): void
    {
        $location = EventLocation::create(['name' => 'Feedback Venue', 'state' => 'WI']);
        $event = $this->event(['event_location_id' => $location->id]);
        $ba = $this->user(5);
        AssignAmbassador::create(['event_id' => $event->id, 'ambassador_id' => $ba->id, 'status' => '1']);
        Recap::create(['event_id' => $event->id, 'ambassador_id' => $ba->id]);
        $export = new EventsExport(['event_id' => $event->id, 'location_id' => $location->id, 'location_state' => 'Wisconsin', 'startDate' => '2026-12-05', 'endDate' => '2026-12-05', 'ambassador_name_id' => $ba->id, 'overview_needed' => true]);
        $row = $export->query()->firstOrFail();
        $this->assertSame($event->id, $row->id);
        $mapped = $export->map($row);
        $this->assertSame('Feedback Venue', $mapped['Location']);
        $this->assertSame($ba->name, $mapped['Assigned']);
        $this->assertSame(0, (new EventsExport(['event_id' => $event->id, 'not_fully_staffed' => true]))->query()->count());
        $bytes = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $this->assertStringStartsWith('PK', $bytes);
    }

    #[Test]
    public function recap_export_and_email_include_answers_and_photos_in_the_message(): void
    {
        $event = $this->event();
        $ba = $this->user(5);
        $recap = Recap::create(['event_id' => $event->id, 'ambassador_id' => $ba->id, 'recap_type' => 'off_premise', 'is_draft' => false, 'sampling_number' => 85, 'consumer_interactions' => 95, 'products_sold' => json_encode([['type' => 'wine', 'name' => 'Test wine', 'sold' => 8, 'regular_price' => 12.5]]), 'traffic_flow' => 'Busy throughout', 'account_feedback' => 'Account feedback test', 'consumer_comments_suggestions' => 'Consumer feedback test', 'consumer_photo' => json_encode(['recap/feedback-photo.jpg'])]);
        $export = new AdminRecapEvent(['event_id' => $event->id, 'startDate' => '2026-12-05', 'endDate' => '2026-12-05']);
        $this->assertSame($recap->id, $export->query()->firstOrFail()->id);
        $row = array_combine($export->headings(), $export->map($recap));
        $this->assertSame('85', $row['Sampling number']);
        $this->assertSame('95', $row['Attendance / consumer interactions']);
        $this->assertSame('8', $row['Total products sold']);
        $this->assertStringContainsString('Test wine', $row['Products sold details']);
        $this->assertSame('Account feedback test', $row['Account feedback']);
        $this->assertStringContainsString('recap/feedback-photo.jpg', $row['Consumer photos']);
        $mail = new RecapExportMail($recap);
        $html = $mail->render();
        $this->assertStringContainsString('Account feedback test', $html);
        $this->assertStringContainsString('Consumer feedback test', $html);
        $this->assertStringContainsString('<img src="', $html);
        $this->assertStringContainsString('recap/feedback-photo.jpg', $html);
        $this->assertSame([], $mail->attachments());
        Mail::fake();
        config(['mail.default' => 'smtp']);
        app(RecapExportService::class)->emailExportToUser($ba, $recap);
        Mail::assertSent(RecapExportMail::class, fn ($mail) => $mail->hasTo($ba->email) && $mail->excelContent === '');
    }

    #[Test]
    public function pay_forecast_details_exist_before_the_ambassador_submits_a_recap(): void
    {
        $ba = $this->user(5);
        $event = $this->event();
        AssignAmbassador::create(['event_id' => $event->id, 'ambassador_id' => $ba->id, 'status' => '1', 'override_pay' => 5]);
        $this->api($ba)->getJson('/api/auth/v1/ambassador/get_pay_forecast_detail?id='.$event->id)->assertOk()->assertJsonPath('data.event.id', $event->id)->assertJsonPath('data.event.ambassador_names', $ba->name);
        $response = $this->api($ba)->getJson('/api/auth/v1/ambassador/get_pay_forecast?start_date=2026-12-05&end_date=2026-12-05');
        $response->assertOk()->assertJsonPath('data.data.0.event_id', $event->id)->assertJsonPath('data.data.0.totalPay', '$45.00');
    }

    #[Test]
    public function event_cards_include_manager_pending_applicants_and_filtered_counts(): void
    {
        $admin = $this->user(1);
        $manager = $this->user(6);
        $event = $this->event(['manager_id' => $manager->id]);
        AssignAmbassador::create(['event_id' => $event->id, 'ambassador_id' => $this->user(5)->id, 'status' => '0']);
        $this->api($admin)->getJson('/api/auth/v1/admin/get_event_list?manager_id='.$manager->id.'&include_filters=0&start=0&length=10')
            ->assertOk()->assertJsonPath('data.data.0.manager_name', $manager->name)->assertJsonPath('data.data.0.pending_applicants_count', 1)->assertJsonPath('data.recordsFiltered', 1);
    }

    #[Test]
    public function local_recap_photos_are_embedded_as_inline_mime_parts(): void
    {
        $path = public_path('recap/feedback-'.uniqid().'.png');
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0VQAAAAASUVORK5CYII='));
        try {
            $recap = Recap::create(['event_id' => $this->event()->id, 'ambassador_id' => $this->user(5)->id, 'consumer_photo' => json_encode(['recap/'.basename($path)])]);
            $mail = new RecapExportMail($recap);
            $this->assertStringContainsString('src="cid:recap-', $mail->render());
            $message = new \Symfony\Component\Mime\Email();
            foreach ($mail->callbacks as $callback) $callback($message);
            $parts = $message->getAttachments();
            $this->assertCount(1, $parts);
            $this->assertSame('inline', $parts[0]->getDisposition());
            $this->assertSame('image', $parts[0]->getMediaType());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function sled_department_seed_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\ClientFeedbackDepartmentsSeeder::class);
        $this->seed(\Database\Seeders\ClientFeedbackDepartmentsSeeder::class);
        foreach (['SLED need to send', 'SLED sent', 'SLED denied'] as $name) {
            $this->assertSame(1, \DB::table('event_departments')->where('name', $name)->count());
        }
    }
    #[Test]
    public function market_cleanup_preserves_linked_markets_and_only_archives_unused_duplicates(): void
    {
        $suffix = uniqid();
        $unused = \App\Models\Market::create(['name' => 'Test City '.$suffix]);
        \App\Models\Market::create(['name' => 'Test City '.$suffix.' Off Premise']);
        $linked = \App\Models\Market::create(['name' => 'Linked City '.$suffix]);
        \App\Models\Market::create(['name' => 'Linked City '.$suffix.' On Premise']);
        $this->event(['market_id' => $linked->id]);
        $this->artisan('markets:archive-unused-duplicates')->assertSuccessful();
        $this->assertNotNull(\App\Models\Market::find($unused->id));
        $this->artisan('markets:archive-unused-duplicates', ['--apply' => true, '--id' => [$unused->id, $linked->id]])->assertSuccessful();
        $this->assertSoftDeleted('markets', ['id' => $unused->id]);
        $this->assertNotNull(\App\Models\Market::find($linked->id));
    }

    #[Test]
    public function the_legacy_admin_recap_endpoint_cannot_omit_a_comdata_receipt(): void
    {
        $admin = $this->user(1);
        $event = $this->event(['payment_type' => 'Comdata']);
        $this->api($admin)->postJson('/api/auth/v1/admin/add_update_recap', ['event_id' => $event->id])->assertUnprocessable();
    }

}
