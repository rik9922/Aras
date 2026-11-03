<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationPreferenceApiTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function user_can_read_and_update_push_preference(): void
    {
        if (! Schema::hasTable('notification_preferences')) {
            $this->markTestSkipped('notification_preferences table is not available.');
        }

        $suffix = uniqid('', true);
        $user = User::create([
            'name' => 'Prefs User',
            'email' => "prefs_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 1,
            'status' => '1',
        ]);

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/notification_preferences')
            ->assertOk()
            ->assertJsonPath('data.push_enabled', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/v1/notification_preferences', [
                'push_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.push_enabled', false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'push_enabled' => 0,
        ]);
    }
}
