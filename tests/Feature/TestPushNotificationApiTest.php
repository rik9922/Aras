<?php

namespace Tests\Feature;

use App\Models\DeviceDetail;
use App\Models\User;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestPushNotificationApiTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function test_push_requires_email(): void
    {
        $this->postJson('/api/test-push', [])
            ->assertStatus(422);
    }

    #[Test]
    public function test_push_returns_not_found_for_unknown_email(): void
    {
        $this->postJson('/api/test-push', [
            'email' => 'nobody_'.uniqid('', true).'@test.local',
        ])
            ->assertStatus(404)
            ->assertJsonPath('status', 'failed');
    }

    #[Test]
    public function test_push_reports_missing_device_tokens(): void
    {
        if (! Schema::hasTable('device_details')) {
            $this->markTestSkipped('device_details table is not available.');
        }

        $suffix = uniqid('', true);
        $user = User::create([
            'name' => 'Manager Push',
            'email' => "mgr_push_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $this->postJson('/api/test-push', [
            'email' => $user->email,
            'message' => 'Hello manager',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_type', 'no_device_tokens')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.active_device_tokens', 0);
    }

    #[Test]
    public function test_push_dispatches_when_device_token_exists(): void
    {
        if (! Schema::hasTable('device_details')) {
            $this->markTestSkipped('device_details table is not available.');
        }

        $suffix = uniqid('', true);
        $user = User::create([
            'name' => 'Manager With Device',
            'email' => "mgr_device_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        DeviceDetail::create([
            'user_id' => $user->id,
            'device_token' => 'test-subscription-'.uniqid('', true),
            'platform' => 'ios',
            'is_user_loggedin' => 1,
        ]);

        $mock = $this->mock(NotificationDispatchService::class);
        $mock->shouldReceive('countActiveDeviceTokens')
            ->once()
            ->with($user->id)
            ->andReturn(1);
        $mock->shouldReceive('dispatch')
            ->once()
            ->andReturn(null);

        $this->postJson('/api/test-push', [
            'email' => $user->email,
            'message' => 'Probe',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.push_attempted', true);
    }
}
