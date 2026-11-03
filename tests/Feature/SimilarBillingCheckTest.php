<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimilarBillingCheckTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function similar_billing_check_returns_matches_for_close_name(): void
    {
        $admin = User::where('role_id', 1)->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user in database.');
        }

        User::create([
            'role_id' => 3,
            'first_name' => 'Acme',
            'last_name' => 'Promotions LLC',
            'name' => 'Acme Promotions LLC',
            'email' => 'acme_similar_test_'.uniqid().'@example.com',
            'phone' => '5551112222',
            'password' => Hash::make('password'),
            'status' => '0',
            'address' => '123 Main St, Austin TX',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.clients.check-similar-billings'), [
                '_token' => 'test-token',
                'first_name' => 'Acme',
                'last_name' => 'Promotions',
                'email' => 'different_'.uniqid().'@example.com',
                'phone' => '5559998888',
                'address' => '456 Other Rd',
            ]);

        $response->assertOk();
        $response->assertJsonPath('has_matches', true);
    }
}
