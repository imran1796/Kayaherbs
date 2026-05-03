<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\StoreSetting;
use App\Models\User;
use App\Modules\Setting\Repositories\StoreSettingRepository;
use App\Modules\Setting\Services\StoreProfileService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StoreProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_store_settings_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('store_settings'));
        $this->assertTrue(Schema::hasColumn('store_settings', 'key'));
        $this->assertTrue(Schema::hasColumn('store_settings', 'value'));
        $this->assertTrue(Schema::hasColumn('store_settings', 'group'));
    }

    public function test_store_setting_repository_can_be_resolved(): void
    {
        $this->assertInstanceOf(
            StoreSettingRepository::class,
            app(StoreSettingRepository::class)
        );
    }

    public function test_store_profile_defaults_come_from_store_config(): void
    {
        $profile = app(StoreProfileService::class)->getProfile();

        $this->assertSame(config('store.defaults.name'), $profile['name']);
        $this->assertSame(config('store.defaults.support_email'), $profile['support_email']);
        $this->assertSame(config('store.defaults.currency'), $profile['currency']);
        $this->assertSame(config('store.defaults.timezone'), $profile['timezone']);
        $this->assertSame('BD', $profile['country']);
    }

    public function test_admin_can_view_store_profile_settings_page(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('admin.settings.store-profile.edit'));
    }

    public function test_admin_can_update_store_profile_settings(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->putJson('/admin/settings/store-profile', $this->validPayload([
                'name' => 'Kaya Herbs',
                'support_email' => 'support@kayaherbs.test',
                'currency' => 'bdt',
                'country' => 'bd',
                'seo_title_template' => '{page_title} | Kaya Herbs',
                'seo_robots' => 'index,follow',
                'privacy_policy_title' => 'Privacy Policy',
                'privacy_policy_content' => 'We collect only necessary data to process orders.',
            ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Kaya Herbs');

        $this->assertSame('Kaya Herbs', StoreSetting::query()->where('key', 'store.profile.name')->firstOrFail()->value);
        $this->assertSame('BDT', StoreSetting::query()->where('key', 'store.profile.currency')->firstOrFail()->value);
        $this->assertSame('BD', StoreSetting::query()->where('key', 'store.profile.country')->firstOrFail()->value);
        $this->assertSame('{page_title} | Kaya Herbs', StoreSetting::query()->where('key', 'store.profile.seo_title_template')->firstOrFail()->value);
        $this->assertSame('Privacy Policy', StoreSetting::query()->where('key', 'store.profile.privacy_policy_title')->firstOrFail()->value);

        $profile = app(StoreProfileService::class)->getProfile();

        $this->assertSame('Kaya Herbs', $profile['name']);
        $this->assertSame('support@kayaherbs.test', $profile['support_email']);

        $this->assertDatabaseHas('audit_events', [
            'event' => 'store_profile.updated',
            'actor_id' => $admin->id,
            'outcome' => 'success',
        ]);

        $auditEvent = AuditEvent::query()->where('event', 'store_profile.updated')->firstOrFail();

        $this->assertContains('name', $auditEvent->metadata['changed']);
        $this->assertContains('support_email', $auditEvent->metadata['changed']);
    }

    public function test_manager_can_view_but_cannot_update_store_profile_settings(): void
    {
        $manager = $this->adminWithRole('manager');

        $this->actingAs($manager)
            ->putJson('/admin/settings/store-profile', $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_profile_api_rejects_customer_token(): void
    {
        $customer = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $customer->assignRole('customer');

        $this->withToken($customer->createToken('storefront', ['customer'])->plainTextToken)
            ->getJson('/api/v1/settings/store-profile')
            ->assertForbidden();
    }

    public function test_store_profile_api_requires_action_specific_permissions(): void
    {
        $manager = $this->adminWithRole('manager');
        $managerToken = $manager->createToken('admin-api')->plainTextToken;

        $this->withToken($managerToken)
            ->getJson('/api/v1/settings/store-profile')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($managerToken)
            ->putJson('/api/v1/settings/store-profile', $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_profile_api_updates_and_refreshes_cache(): void
    {
        $admin = $this->adminWithRole('admin');
        $token = $admin->createToken('admin-api')->plainTextToken;

        app(StoreProfileService::class)->getProfile();
        $this->assertTrue(Cache::has(config('store.settings.cache_key')));

        $this->withToken($token)
            ->putJson('/api/v1/settings/store-profile', $this->validPayload([
                'name' => 'Kaya Herbs API',
                'support_email' => 'api-support@kayaherbs.test',
                'seo_meta_description' => 'Premium herbs and wellness essentials.',
                'terms_conditions_content' => 'Using this site means you agree to our terms.',
            ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Kaya Herbs API')
            ->assertJsonPath('data.support_email', 'api-support@kayaherbs.test')
            ->assertJsonPath('data.seo_meta_description', 'Premium herbs and wellness essentials.')
            ->assertJsonPath('data.terms_conditions_content', 'Using this site means you agree to our terms.');

        $this->assertTrue(Cache::has(config('store.settings.cache_key')));
        $this->assertSame('Kaya Herbs API', app(StoreProfileService::class)->getProfile()['name']);
    }

    public function test_store_profile_routes_have_expected_boundaries(): void
    {
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.store-profile.edit'), 'auth');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.store-profile.edit'), 'admin');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.store-profile.edit'), 'can:settings.view');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.settings.store-profile.update'), 'can:settings.update');

        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.store-profile.show'), 'auth:sanctum');
        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.store-profile.show'), 'admin');
        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.store-profile.show'), 'can:settings.view');
        $this->assertRouteHasMiddleware($this->middlewareFor('api.v1.settings.store-profile.update'), 'can:settings.update');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kaya Herbs',
            'legal_name' => 'Kaya Herbs Limited',
            'support_email' => 'support@kayaherbs.test',
            'support_phone' => '+8801700000000',
            'address_line_1' => 'House 10, Road 5',
            'address_line_2' => 'Banani',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1213',
            'country' => 'BD',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'website_url' => 'https://kayaherbs.test',
            'seo_title_template' => '{page_title} | {store_name}',
            'seo_meta_description' => 'Shop trusted quality products.',
            'seo_meta_keywords' => 'ecommerce,store,shopping',
            'seo_robots' => 'index,follow',
            'seo_canonical_base_url' => 'https://kayaherbs.test',
            'privacy_policy_title' => 'Privacy Policy',
            'privacy_policy_content' => 'Default privacy policy content.',
            'terms_conditions_title' => 'Terms & Conditions',
            'terms_conditions_content' => 'Default terms and conditions content.',
            'refund_policy_title' => 'Refund Policy',
            'refund_policy_content' => 'Default refund policy content.',
            'shipping_policy_title' => 'Shipping Policy',
            'shipping_policy_content' => 'Default shipping policy content.',
        ], $overrides);
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
