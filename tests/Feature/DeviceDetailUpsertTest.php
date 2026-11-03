<?php

namespace Tests\Feature;

use App\Models\DeviceDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceDetailUpsertTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function save_device_details_keeps_one_row_per_user_and_updates_existing(): void
    {
        if (! Schema::hasTable('device_details')) {
            $this->markTestSkipped('device_details table is not available.');
        }

        $suffix = uniqid('', true);
        $user = User::create([
            'name' => 'Device User',
            'email' => "device_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/v1/save_device_details', [
                'device_token' => 'token-v1',
                'platform' => 'ios',
                'is_user_loggedin' => 1,
                'push_opt_in' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.token_stored', true);

        $this->assertSame(1, DeviceDetail::where('user_id', $user->id)->count());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/v1/save_device_details', [
                'device_token' => 'token-v2',
                'platform' => 'android',
                'is_user_loggedin' => 1,
                'push_opt_in' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.token_stored', true);

        $this->assertSame(1, DeviceDetail::where('user_id', $user->id)->count());

        $record = DeviceDetail::where('user_id', $user->id)->first();
        $this->assertSame('token-v2', $record->device_token);
        $this->assertSame('android', $record->platform);
    }

    #[Test]
    public function clear_for_user_clears_the_single_device_row(): void
    {
        if (! Schema::hasTable('device_details')) {
            $this->markTestSkipped('device_details table is not available.');
        }

        $suffix = uniqid('', true);
        $user = User::create([
            'name' => 'Clear Device User',
            'email' => "clear_device_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 1,
            'status' => '1',
        ]);

        DeviceDetail::upsertForUser((int) $user->id, [
            'device_token' => 'active-token',
            'platform' => 'ios',
            'is_user_loggedin' => 1,
        ]);

        DeviceDetail::clearForUser((int) $user->id);

        $this->assertSame(1, DeviceDetail::where('user_id', $user->id)->count());
        $record = DeviceDetail::where('user_id', $user->id)->first();
        $this->assertNull($record->device_token);
        $this->assertSame(0, (int) $record->is_user_loggedin);
    }
}
