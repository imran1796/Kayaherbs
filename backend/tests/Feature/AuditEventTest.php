<?php

namespace Tests\Feature;

use App\Core\Services\AuditLogger;
use App\Models\AuditEvent;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_events_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('audit_events'));
    }

    public function test_admin_login_success_and_failure_are_audited(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertDatabaseHas('audit_events', [
            'event' => 'admin.login.failed',
            'outcome' => 'failure',
            'auditable_type' => $admin->getMorphClass(),
            'auditable_id' => $admin->id,
            'guard' => 'web',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'admin.login.succeeded',
            'actor_type' => $admin->getMorphClass(),
            'actor_id' => $admin->id,
            'guard' => 'web',
        ]);
    }

    public function test_customer_register_login_and_logout_are_audited(): void
    {
        $this->postJson('/api/v1/auth/customer/register', [
            'name' => 'Customer One',
            'email' => 'customer@example.com',
            'phone' => '01700000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $customer = User::query()->where('email', 'customer@example.com')->firstOrFail();

        $token = $this->postJson('/api/v1/auth/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ])->json('data.access_token');

        $this->withToken($token)
            ->postJson('/api/v1/auth/customer/logout')
            ->assertOk();

        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.registered',
            'actor_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'rbac.role.assigned',
            'auditable_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.login.succeeded',
            'actor_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.logout',
            'actor_id' => $customer->id,
        ]);
    }

    public function test_password_reset_request_completion_and_failure_are_audited(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('old-password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/customer/password/forgot', [
            'email' => 'customer@example.com',
        ])->assertOk();

        $this->postJson('/api/v1/auth/customer/password/reset', [
            'email' => 'customer@example.com',
            'token' => 'invalid-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable();

        $token = Password::broker()->createToken($customer);

        $this->postJson('/api/v1/auth/customer/password/reset', [
            'email' => 'customer@example.com',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.password_reset.requested',
            'auditable_id' => $customer->id,
            'outcome' => 'sent',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.password_reset.failed',
            'auditable_id' => $customer->id,
            'outcome' => 'failure',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.password_reset.completed',
            'actor_id' => $customer->id,
        ]);
    }

    public function test_access_denied_is_audited(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/users')
            ->assertForbidden();

        $this->assertDatabaseHas('audit_events', [
            'event' => 'auth.access_denied',
            'actor_id' => $admin->id,
            'outcome' => 'denied',
        ]);
    }

    public function test_audit_metadata_redacts_secrets(): void
    {
        app(AuditLogger::class)->record('audit.test', metadata: [
            'email' => 'user@example.com',
            'password' => 'secret',
            'token' => 'raw-token',
        ]);

        $event = AuditEvent::query()->where('event', 'audit.test')->firstOrFail();

        $this->assertSame('user@example.com', $event->metadata['email']);
        $this->assertSame('[redacted]', $event->metadata['password']);
        $this->assertSame('[redacted]', $event->metadata['token']);
    }
}
