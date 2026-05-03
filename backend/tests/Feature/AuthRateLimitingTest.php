<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_login_is_rate_limited_by_email_and_ip(): void
    {
        config([
            'auth_rate_limits.login.max_attempts' => 2,
            'auth_rate_limits.login.decay_minutes' => 1,
        ]);

        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/api/v1/auth/customer/login', [
                'email' => 'customer@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'wrong-password',
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'too_many_requests');

        $this->assertDatabaseHas('audit_events', [
            'event' => 'auth.rate_limited',
            'outcome' => 'throttled',
            'guard' => 'sanctum',
        ]);
    }

    public function test_admin_login_is_rate_limited_by_email_and_ip(): void
    {
        config([
            'auth_rate_limits.login.max_attempts' => 1,
            'auth_rate_limits.login.decay_minutes' => 1,
        ]);

        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->postJson('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->postJson('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'too_many_requests');

        $this->assertDatabaseHas('audit_events', [
            'event' => 'auth.rate_limited',
            'outcome' => 'throttled',
            'guard' => 'web',
        ]);
    }

    public function test_customer_registration_is_rate_limited_by_ip(): void
    {
        config([
            'auth_rate_limits.register.max_attempts' => 1,
            'auth_rate_limits.register.decay_minutes' => 1,
        ]);

        $this->postJson('/api/v1/auth/customer/register', [
            'name' => 'Customer One',
            'email' => 'customer-one@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/customer/register', [
            'name' => 'Customer Two',
            'email' => 'customer-two@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'too_many_requests');
    }

    public function test_password_reset_requests_are_rate_limited_by_email_and_ip(): void
    {
        Notification::fake();

        config([
            'auth_rate_limits.password_reset.max_attempts' => 1,
            'auth_rate_limits.password_reset.decay_minutes' => 1,
        ]);

        User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/customer/password/forgot', [
            'email' => 'customer@example.com',
        ])->assertOk();

        $this->postJson('/api/v1/auth/customer/password/forgot', [
            'email' => 'customer@example.com',
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'too_many_requests');
    }

    public function test_authenticated_session_actions_are_rate_limited_by_user_and_ip(): void
    {
        config([
            'auth_rate_limits.session.max_attempts' => 1,
            'auth_rate_limits.session.decay_minutes' => 1,
        ]);

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $token = $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/customer/me')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/auth/customer/me')
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'too_many_requests');
    }
}
