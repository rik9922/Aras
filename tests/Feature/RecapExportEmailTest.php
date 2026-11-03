<?php

namespace Tests\Feature;

use App\Mail\RecapExportMail;
use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecapExportEmailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function admin_receives_recap_export_email_at_login_address(): void
    {
        Mail::fake();

        $admin = User::where('role_id', 1)->whereNotNull('email')->first();
        $this->assertNotNull($admin, 'Need an admin user with email in the database.');

        $event = Event::create([
            'event_name' => 'Export email test event',
            'event_status' => '1',
        ]);

        $recap = Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.recap.export-email', $recap->id));

        $response->assertRedirect();
        $this->assertContains('success', $response->getSession()->get('_flash.old', []));

        Mail::assertSent(RecapExportMail::class, function (RecapExportMail $mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    #[Test]
    public function client_cannot_export_recap_for_another_clients_event(): void
    {
        Mail::fake();

        $client = User::where('role_id', 3)->whereNotNull('email')->first();
        $otherClient = User::where('role_id', 3)
            ->whereNotNull('email')
            ->when($client, fn ($q) => $q->where('id', '!=', $client->id))
            ->first();
        $this->assertNotNull($client, 'Need a client user with email in the database.');
        $this->assertNotNull($otherClient, 'Need a second client user to test recap export authorization.');

        $event = Event::create([
            'event_name' => 'Other client event',
            'event_status' => '1',
            'client_id' => $otherClient->id,
        ]);

        $recap = Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $client->id,
        ]);

        $response = $this->actingAs($client)->post(route('client.recap.export-email', $recap->id));

        $response->assertRedirect();
        $this->assertContains('error', $response->getSession()->get('_flash.old', []));
        Mail::assertNothingSent();
    }
}
