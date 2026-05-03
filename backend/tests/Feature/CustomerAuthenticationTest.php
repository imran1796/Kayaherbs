<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receive_token(): void
    {
        $this->postJson('/api/v1/auth/customer/register', [
            'name' => 'Customer One',
            'email' => 'customer@example.com',
            'phone' => '01700000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'customer@example.com')
            ->assertJsonStructure([
                'data' => ['access_token'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $customer = User::query()->where('email', 'customer@example.com')->firstOrFail();

        $this->assertTrue($customer->hasRole('customer'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_customer_can_login_and_receive_token(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'customer@example.com')
            ->assertJsonStructure([
                'data' => ['access_token', 'expires_at'],
            ]);

        $issuedToken = $customer->tokens()->first();

        $this->assertSame(['customer'], $issuedToken->abilities);
        $this->assertTrue($issuedToken->expires_at->isFuture());
    }

    public function test_admin_cannot_login_through_customer_auth(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/customer/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'validation_failed');
    }

    public function test_inactive_customer_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'inactive',
        ]);

        $this->postJson('/api/v1/auth/customer/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'validation_failed');
    }

    public function test_customer_can_view_profile_with_token(): void
    {
        $token = $this->customerToken();

        $this->withToken($token)
            ->getJson('/api/v1/auth/customer/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'customer@example.com');
    }

    public function test_customer_can_logout_and_revoke_current_token(): void
    {
        $token = $this->customerToken();

        $this->withToken($token)
            ->postJson('/api/v1/auth/customer/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_customer_can_logout_from_all_devices(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $firstToken = $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken;
        $customer->createToken('storefront', ['customer'], now()->addHour());

        $this->withToken($firstToken)
            ->postJson('/api/v1/auth/customer/logout-all')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_customer_profile_requires_token(): void
    {
        $this->getJson('/api/v1/auth/customer/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_customer_profile_rejects_expired_token(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $token = $customer->createToken('storefront', ['customer'], now()->subMinute())->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/customer/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_customer_profile_rejects_inactive_customer_token(): void
    {
        $customer = User::factory()->create([
            'email' => 'inactive@example.com',
            'is_admin' => false,
            'status' => 'inactive',
        ]);

        $token = $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/customer/me')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_customer_profile_rejects_admin_token(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $token = $admin->createToken('storefront', ['customer'], now()->addHour())->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/customer/me')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_customer_profile_rejects_token_without_customer_ability(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $token = $customer->createToken('storefront', ['profile:read'], now()->addHour())->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/customer/me')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_customer_login_enforces_configured_active_token_limit(): void
    {
        config(['auth_lifecycle.customer_tokens.max_active_tokens' => 2]);

        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/v1/auth/customer/login', [
                'email' => 'customer@example.com',
                'password' => 'password123',
            ])->assertOk();
        }

        $this->assertDatabaseCount('personal_access_tokens', 2);
    }

    public function test_customer_can_request_password_reset_link(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/customer/password/forgot', [
            'email' => 'customer@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'If the email exists, a password reset link has been sent.');

        Notification::assertSentTo($customer, ResetPassword::class);
    }

    public function test_customer_password_reset_does_not_send_to_admin_account(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/customer/password/forgot', [
            'email' => 'admin@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertNothingSent();
    }

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('old-password'),
            'is_admin' => false,
            'status' => 'active',
        ]);
        $customer->createToken('storefront');

        $token = Password::broker()->createToken($customer);

        $this->postJson('/api/v1/auth/customer/password/reset', [
            'email' => 'customer@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password reset successfully.');

        $customer->refresh();

        $this->assertTrue(Hash::check('new-password', $customer->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_customer_cannot_reset_password_with_invalid_token(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/customer/password/reset', [
            'email' => 'customer@example.com',
            'token' => 'invalid-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'validation_failed');
    }

    private function customerToken(): string
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        return $this->postJson('/api/v1/auth/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ])->json('data.access_token');
    }
}
