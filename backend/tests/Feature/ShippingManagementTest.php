<?php

namespace Tests\Feature;

use App\Models\DeliveryRate;
use App\Models\DeliveryZone;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShippingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_delivery_zone_and_rate_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('delivery_zones'));
        $this->assertTrue(Schema::hasTable('delivery_rates'));
        $this->assertTrue(Schema::hasColumn('delivery_rates', 'amount'));
    }

    public function test_admin_can_manage_delivery_zones_and_rates(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/api/v1/shipping/zones', [
                'name' => 'Dhaka City',
                'code' => 'dhaka-city',
                'country' => 'bd',
                'cities' => ['Dhaka'],
                'status' => 'active',
                'sort_order' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Dhaka City')
            ->assertJsonPath('data.country', 'BD');

        $zone = DeliveryZone::query()->where('code', 'dhaka-city')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/shipping/rates', [
                'delivery_zone_id' => $zone->id,
                'name' => 'Inside Dhaka',
                'code' => 'inside-dhaka',
                'amount' => 60,
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', '60.00');

        $rate = DeliveryRate::query()->where('code', 'inside-dhaka')->firstOrFail();

        $this->actingAs($admin)
            ->putJson('/api/v1/shipping/rates/'.$rate->id, [
                'delivery_zone_id' => $zone->id,
                'name' => 'Inside Dhaka Updated',
                'code' => 'inside-dhaka',
                'amount' => 70,
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.amount', '70.00');

        $this->actingAs($admin)
            ->getJson('/api/v1/shipping/zones')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'dhaka-city');
    }

    public function test_shipping_routes_are_registered(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('admin.shipping.index'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.shipping.zones.data'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.shipping.rates.data'));
        $this->assertNotNull(Route::getRoutes()->getByName('api.v1.shipping.zones.index'));
        $this->assertNotNull(Route::getRoutes()->getByName('api.v1.shipping.rates.index'));
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
