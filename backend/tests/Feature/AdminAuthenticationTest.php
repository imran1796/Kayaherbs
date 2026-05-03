<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_dashboard_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login')
            ->assertSessionHas('session_expired', true);
    }

    public function test_admin_login_screen_shows_session_expired_message(): void
    {
        $this->withSession(['session_expired' => true])
            ->get('/admin/login')
            ->assertOk()
            ->assertSee('Your session has expired. Please sign in again.');
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_user_cannot_login_to_admin_panel(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->post('/admin/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_admin_cannot_login_to_admin_panel(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'status' => 'inactive',
        ]);

        $this->post('/admin/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();

        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_request_password_reset_link(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->post('/admin/forgot-password', [
            'email' => 'admin@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo($admin, ResetPassword::class);
    }

    public function test_admin_password_reset_does_not_send_to_customer_account(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'customer@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->post('/admin/forgot-password', [
            'email' => 'customer@example.com',
        ])->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_admin_can_reset_password_with_valid_token(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('old-password'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $token = Password::broker()->createToken($admin);

        $this->post('/admin/reset-password', [
            'email' => 'admin@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertRedirect('/admin/login')
            ->assertSessionHas('status');

        $admin->refresh();

        $this->assertTrue(Hash::check('new-password', $admin->password));
    }

    public function test_customer_cannot_reset_password_through_admin_flow(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('old-password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $token = Password::broker()->createToken($customer);

        $this->post('/admin/reset-password', [
            'email' => 'customer@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');

        $customer->refresh();

        $this->assertTrue(Hash::check('old-password', $customer->password));
    }
}
