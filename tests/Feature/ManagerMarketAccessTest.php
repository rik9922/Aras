<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ManagerMarketAccessTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function deactivated_managers_cannot_view_markets(): void
    {
        $manager = $this->createDeactivatedManager();
        Market::create([
            'name' => 'Hidden Market '.uniqid(),
            'status' => Market::STATUS_ACTIVE,
        ]);

        $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($manager))
            ->getJson('/api/auth/v1/admin/get_markets')
            ->assertForbidden()
            ->assertJsonPath('error_type', 'inactive_account');
    }

    #[Test]
    public function deactivated_managers_cannot_log_in_through_the_api(): void
    {
        $manager = $this->createDeactivatedManager();

        $this->postJson('/api/login', [
            'email' => $manager->email,
            'password' => 'password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error_type', 'inactive_account');
    }

    #[Test]
    public function deactivated_managers_cannot_log_in_through_the_web(): void
    {
        $manager = $this->createDeactivatedManager();
        $csrfToken = 'manager-login-test-token';

        $this->withSession(['_token' => $csrfToken])->from('/login')->post('/login-user', [
            '_token' => $csrfToken,
            'email' => $manager->email,
            'password' => 'password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function manager_can_be_created_without_market_ids(): void
    {
        $admin = User::create([
            'name' => 'Manager Test Admin',
            'email' => 'manager-test-admin-'.uniqid().'@test.local',
            'password' => 'password',
            'role_id' => 1,
            'status' => '1',
        ]);
        $email = 'manager-without-markets-'.uniqid().'@test.local';

        $response = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($admin))
            ->postJson('/api/auth/v1/admin/add_update_manager', [
                'first_name' => 'No',
                'last_name' => 'Markets',
                'email' => $email,
                'password' => 'password',
                'phone' => '1234567890',
                'address' => '123 Test Street',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'status' => '1',
            ])
            ->assertOk();

        $manager = User::where('email', $email)->firstOrFail();
        $this->assertSame([], $response->json('data.manager_markets'));
        $this->assertCount(0, $manager->managerMarkets);
    }

    #[Test]
    public function manager_can_be_updated_without_existing_coordinates(): void
    {
        $admin = User::create([
            'name' => 'Manager Test Admin',
            'email' => 'manager-update-admin-'.uniqid().'@test.local',
            'password' => 'password',
            'role_id' => 1,
            'status' => '1',
        ]);
        $manager = User::create([
            'name' => 'Legacy Manager',
            'first_name' => 'Legacy',
            'last_name' => 'Manager',
            'email' => 'legacy-manager-'.uniqid().'@test.local',
            'password' => 'password',
            'phone' => '4145883571',
            'address' => '123 Test Street',
            'role_id' => 6,
            'status' => '1',
        ]);

        $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($admin))
            ->postJson('/api/auth/v1/admin/add_update_manager', [
                'id' => $manager->id,
                'first_name' => 'Updated',
                'last_name' => 'Manager',
                'phone' => '4145883571',
                'address' => '123 Test Street',
                'status' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Updated');
    }

    private function createDeactivatedManager(): User
    {
        return User::create([
            'name' => 'Deactivated Manager',
            'email' => 'deactivated-manager-'.uniqid().'@test.local',
            'password' => 'password',
            'role_id' => 6,
            'status' => '0',
        ]);
    }
}
