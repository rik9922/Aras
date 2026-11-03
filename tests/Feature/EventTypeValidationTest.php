<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EventTypeValidationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function web_event_creation_rejects_a_missing_event_type_before_insert(): void
    {
        $admin = $this->createAdmin();
        $csrfToken = 'event-type-validation-token';

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->from('/admin/event/create')
            ->post('/admin/store-event', ['_token' => $csrfToken])
            ->assertRedirect('/admin/event/create')
            ->assertSessionHasErrors(['event_type_id' => 'Event Type is required.']);
    }

    #[Test]
    public function api_event_creation_rejects_a_missing_event_type_before_insert(): void
    {
        $admin = $this->createAdmin();

        $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($admin))
            ->postJson('/api/auth/v1/admin/add_event', [])
            ->assertUnprocessable()
            ->assertJsonPath('data.event_type_id.0', 'Event Type is required.');
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Event Type Test Admin',
            'email' => 'event-type-admin-'.uniqid().'@test.local',
            'password' => 'password',
            'role_id' => 1,
            'status' => '1',
        ]);
    }
}
