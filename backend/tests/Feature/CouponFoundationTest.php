<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CouponFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_coupon_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('coupons'));
        $this->assertTrue(Schema::hasColumn('coupons', 'code'));
        $this->assertTrue(Schema::hasColumn('coupons', 'discount_type'));
        $this->assertTrue(Schema::hasColumn('coupons', 'starts_at'));
        $this->assertTrue(Schema::hasColumn('coupons', 'ends_at'));
    }

    public function test_admin_can_create_coupon_with_normalized_unique_code(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Welcome Discount',
                'code' => 'welcome10',
                'discount_type' => Coupon::DISCOUNT_PERCENTAGE,
                'discount_value' => 10,
                'status' => Coupon::STATUS_ACTIVE,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'WELCOME10')
            ->assertJsonPath('data.discount_type', Coupon::DISCOUNT_PERCENTAGE)
            ->assertJsonPath('data.discount_value', '10.00')
            ->assertJsonPath('data.lifecycle_status', Coupon::STATUS_ACTIVE);

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Duplicate Welcome',
                'code' => 'welcome10',
                'discount_type' => Coupon::DISCOUNT_FIXED,
                'discount_value' => 50,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_coupon_discount_types_are_validated(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Free Delivery',
                'code' => 'FREESHIP',
                'discount_type' => Coupon::DISCOUNT_FREE_DELIVERY,
                'status' => Coupon::STATUS_ACTIVE,
            ])
            ->assertCreated()
            ->assertJsonPath('data.discount_value', '0.00');

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Bad Coupon',
                'code' => 'BADTYPE',
                'discount_type' => 'buy_one_get_one',
                'discount_value' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('discount_type');

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Too Much',
                'code' => 'TOOMUCH',
                'discount_type' => Coupon::DISCOUNT_PERCENTAGE,
                'discount_value' => 150,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('discount_value');
    }

    public function test_coupon_lifecycle_status_uses_status_and_schedule_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-05 12:00:00'));

        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Future Coupon',
                'code' => 'FUTURE',
                'discount_type' => Coupon::DISCOUNT_FIXED,
                'discount_value' => 100,
                'status' => Coupon::STATUS_ACTIVE,
                'starts_at' => '2026-05-06 00:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.lifecycle_status', 'scheduled');

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Expired Coupon',
                'code' => 'EXPIRED',
                'discount_type' => Coupon::DISCOUNT_FIXED,
                'discount_value' => 100,
                'status' => Coupon::STATUS_ACTIVE,
                'ends_at' => '2026-05-05 11:59:59',
            ])
            ->assertCreated()
            ->assertJsonPath('data.lifecycle_status', 'expired');

        $future = Coupon::query()->where('code', 'FUTURE')->firstOrFail();

        $this->actingAs($admin)
            ->patchJson("/api/v1/coupons/{$future->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.status', Coupon::STATUS_INACTIVE)
            ->assertJsonPath('data.lifecycle_status', Coupon::STATUS_INACTIVE);

        Carbon::setTestNow();
    }

    public function test_coupon_routes_are_registered(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('api.v1.coupons.index'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.coupons.index'));
        $this->assertNotNull(Route::getRoutes()->getByName('api.v1.coupons.activate'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.coupons.data'));
    }

    public function test_coupon_list_screen_is_registered_with_form_and_status_controls(): void
    {
        $route = Route::getRoutes()->getByName('admin.coupons.index');

        $this->assertNotNull($route);
        $this->assertContains('can:coupons.view', $route->gatherMiddleware());

        $view = file_get_contents(app_path('Modules/Promotion/views/coupons/index.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('Coupon List', $view);
        $this->assertStringContainsString('Create Coupon', $view);
        $this->assertStringContainsString('Save coupon', $view);
        $this->assertStringContainsString('discount_type', $view);
        $this->assertStringContainsString('minimum_order_value', $view);
        $this->assertStringContainsString('starts_at', $view);
        $this->assertStringContainsString('usage_limit', $view);
        $this->assertStringContainsString('eligible_product_ids', $view);
        $this->assertStringContainsString('eligible_category_ids', $view);
        $this->assertStringContainsString('Coupon Usage Detail', $view);
        $this->assertStringContainsString('Top Coupon Performance', $view);
        $this->assertStringContainsString('Recent Coupon Audit', $view);
        $this->assertStringContainsString('couponReport', $view);
        $this->assertStringContainsString('couponAudits', $view);
        $this->assertStringContainsString('coupon-form-errors', $view);
        $this->assertStringContainsString('No coupons created yet.', $view);
        $this->assertStringContainsString('View only', $view);
        $this->assertStringContainsString('coupons.create permission', $view);
        $this->assertStringContainsString('coupons.update permission', $view);
        $this->assertStringContainsString('edit-coupon', $view);
        $this->assertStringContainsString('view-coupon-usage', $view);
        $this->assertStringContainsString('toggle-coupon-status', $view);
        $this->assertStringContainsString('delete-coupon', $view);
        $this->assertStringContainsString('admin.coupons.store', $view);
        $this->assertStringContainsString('admin.coupons.update', $view);
        $this->assertStringContainsString('admin.coupons.activate', $view);
        $this->assertStringContainsString('admin.coupons.deactivate', $view);
        $this->assertStringContainsString('admin.coupons.destroy', $view);
    }

    public function test_coupon_audit_feed_service_returns_recent_coupon_audits(): void
    {
        $admin = $this->adminWithRole('admin');

        $coupon = Coupon::query()->create([
            'name' => 'Audit Feed Coupon',
            'code' => 'AUDITFEED',
            'discount_type' => Coupon::DISCOUNT_FIXED,
            'discount_value' => 20,
            'status' => Coupon::STATUS_ACTIVE,
        ]);

        AuditEvent::query()->create([
            'event' => 'coupon.updated',
            'outcome' => 'success',
            'actor_type' => $admin->getMorphClass(),
            'actor_id' => $admin->id,
            'auditable_type' => $coupon->getMorphClass(),
            'auditable_id' => $coupon->id,
            'guard' => 'web',
            'metadata' => ['code' => 'AUDITFEED'],
            'created_at' => now(),
        ]);

        $audits = app(\App\Modules\Promotion\Services\CouponAuditService::class)->recent();

        $this->assertSame('coupon.updated', $audits[0]['event']);
        $this->assertSame('AUDITFEED', $audits[0]['metadata']['code']);
    }

    public function test_coupon_management_actions_are_audited(): void
    {
        $admin = $this->adminWithRole('admin');

        $couponId = $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Audit Coupon',
                'code' => 'AUDIT10',
                'discount_type' => Coupon::DISCOUNT_PERCENTAGE,
                'discount_value' => 10,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/coupons/{$couponId}", [
                'name' => 'Audit Coupon Updated',
                'code' => 'AUDIT10',
                'discount_type' => Coupon::DISCOUNT_PERCENTAGE,
                'discount_value' => 15,
                'status' => Coupon::STATUS_INACTIVE,
            ])
            ->assertOk();

        $this->actingAs($admin)->patchJson("/api/v1/coupons/{$couponId}/activate")->assertOk();
        $this->actingAs($admin)->patchJson("/api/v1/coupons/{$couponId}/deactivate")->assertOk();
        $this->actingAs($admin)->deleteJson("/api/v1/coupons/{$couponId}")->assertOk();

        foreach ([
            'coupon.created',
            'coupon.updated',
            'coupon.activated',
            'coupon.deactivated',
            'coupon.deleted',
        ] as $event) {
            $this->assertDatabaseHas('audit_events', [
                'event' => $event,
                'actor_id' => $admin->id,
            ]);
        }

        $updated = AuditEvent::query()->where('event', 'coupon.updated')->firstOrFail();
        $this->assertContains('discount_value', $updated->metadata['changed']);
    }

    private function adminWithRole(string $role): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->assignRole($role);

        return $admin;
    }
}
