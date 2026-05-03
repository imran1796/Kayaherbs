<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\StoreSetting;
use App\Models\User;
use App\Modules\Setting\Services\ModuleToggleService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleToggleSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_view_module_toggles_page(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('admin.settings.module-toggles.edit'));
    }

    public function test_admin_can_update_module_toggles(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->putJson('/admin/settings/module-toggles', [
                'catalog' => 1,
                'inventory' => 1,
                'checkout' => 1,
                'coupons' => 1,
                'reviews' => 0,
                'blog' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.coupons', true)
            ->assertJsonPath('data.reviews', false);

        $this->assertTrue((bool) StoreSetting::query()->where('key', 'store.module.coupons_enabled')->firstOrFail()->value);
        $this->assertFalse((bool) StoreSetting::query()->where('key', 'store.module.reviews_enabled')->firstOrFail()->value);

        $this->assertDatabaseHas('audit_events', [
            'event' => 'module_toggles.updated',
            'actor_id' => $admin->id,
            'outcome' => 'success',
        ]);

        $event = AuditEvent::query()->where('event', 'module_toggles.updated')->firstOrFail();
        $this->assertContains('coupons', $event->metadata['changed']);
        $this->assertContains('reviews', $event->metadata['changed']);
    }

    public function test_manager_can_view_but_cannot_update_module_toggles(): void
    {
        $manager = $this->adminWithRole('manager');

        $this->actingAs($manager)
            ->putJson('/admin/settings/module-toggles', ['catalog' => 1])
            ->assertForbidden();
    }

    public function test_module_toggle_api_updates_and_refreshes_cache(): void
    {
        $admin = $this->adminWithRole('admin');
        $token = $admin->createToken('admin-api')->plainTextToken;

        app(ModuleToggleService::class)->getToggles();
        $this->assertTrue(Cache::has(config('store.settings.cache_key').'.modules'));

        $this->withToken($token)
            ->putJson('/api/v1/settings/module-toggles', [
                'catalog' => true,
                'inventory' => true,
                'checkout' => true,
                'coupons' => true,
                'reviews' => false,
                'blog' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.coupons', true)
            ->assertJsonPath('data.reviews', false);

        $this->assertTrue(Cache::has(config('store.settings.cache_key').'.modules'));
    }

    public function test_module_toggle_routes_have_expected_boundaries(): void
    {
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.module-toggles.edit'), 'auth');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.module-toggles.edit'), 'admin');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.module-toggles.edit'), 'can:modules.view');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.module-toggles.update'), 'can:modules.update');

        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.module-toggles.show'), 'auth:sanctum');
        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.module-toggles.show'), 'admin');
        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.module-toggles.show'), 'can:modules.view');
        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.module-toggles.update'), 'can:modules.update');
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

    /**
     * @return list<string>
     */
    private function middlewareFor(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, "Route [{$routeName}] is not registered.");

        return $route->gatherMiddleware();
    }

    /**
     * @param  list<string>  $middleware
     */
    private function assertRouteHasMiddleware(array $middleware, string $expected): void
    {
        $this->assertTrue(
            collect($middleware)->contains(fn (string $entry): bool => $entry === $expected || str_starts_with($entry, $expected)),
            'Expected middleware ['.$expected.'] not found in ['.implode(', ', $middleware).'].'
        );
    }
}
