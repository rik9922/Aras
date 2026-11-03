<?php

namespace Tests\Feature;

use App\Http\Controllers\Ambassador\RecapController;
use App\Mail\SummitedRecapMail;
use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use App\Services\AmbassadorRecapListingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SubmittedRecapNotificationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function submitted_recap_mail_links_reviewers_to_their_recap_review_route(): void
    {
        Config::set('app.url', 'https://aras.betaplanets.com');

        $recap = new Recap(['id' => 123]);
        $recap->setRelation('ambassador', new User([
            'name' => 'Test Ambassador',
            'email' => 'ambassador@test.local',
        ]));
        $recap->setRelation('event', new Event([
            'id' => 456,
            'event_name' => 'Review Link Event',
            'date' => '2030-06-15',
        ]));
        $recap->created_at = now();

        $submittedUser = new User([
            'name' => 'Submitter',
            'email' => 'submitter@test.local',
        ]);

        $adminMail = new SummitedRecapMail($recap, $submittedUser, new User([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'role_id' => 1,
        ]));

        $managerMail = new SummitedRecapMail($recap, $submittedUser, new User([
            'name' => 'Manager',
            'email' => 'manager@test.local',
            'role_id' => 6,
        ]));

        $this->assertSame('https://aras.betaplanets.com/admin/show-recap/123', $adminMail->actionUrl);
        $this->assertSame('https://aras.betaplanets.com/manager/show-recap/123', $managerMail->actionUrl);
    }

    #[Test]
    public function submitted_recap_notification_is_sent_to_admins_and_the_event_manager(): void
    {
        Mail::fake();

        $suffix = uniqid('', true);

        $admin = User::create([
            'name' => 'Recap Admin',
            'email' => "recap_admin_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 1,
            'status' => '1',
        ]);

        $manager = new User([
            'id' => 999001,
            'name' => 'Recap Manager',
            'email' => "recap_manager_{$suffix}@test.local",
            'role_id' => 6,
            'status' => '1',
        ]);

        $ambassador = User::create([
            'name' => 'Recap Ambassador',
            'email' => "recap_ambassador_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = new Event([
            'id' => 999101,
            'event_name' => 'Submitted recap notification event',
            'date' => '2030-06-15',
        ]);
        $event->setRelation('manager', $manager);

        $recap = new Recap([
            'id' => 999201,
            'ambassador_id' => $ambassador->id,
            'created_by' => $ambassador->id,
            'is_draft' => false,
        ]);
        $recap->created_at = now();
        $recap->setRelation('event', $event);
        $recap->setRelation('ambassador', $ambassador);

        $this->actingAs($ambassador);

        $controller = new RecapController(app(AmbassadorRecapListingService::class));
        $method = new ReflectionMethod($controller, 'notifyAdminsOfRecapSubmission');
        $method->setAccessible(true);
        $method->invoke($controller, $recap);

        Mail::assertSent(SummitedRecapMail::class, fn (SummitedRecapMail $mail) => $mail->hasTo($admin->email));
        Mail::assertSent(SummitedRecapMail::class, fn (SummitedRecapMail $mail) => $mail->hasTo($manager->email));
    }
}
