<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CategoryDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_category_table_and_relationships_exist(): void
    {
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasColumn('categories', 'parent_id'));
        $this->assertTrue(Schema::hasColumn('categories', 'name'));
        $this->assertTrue(Schema::hasColumn('categories', 'slug'));

        $parent = Category::factory()->create([
            'name' => 'Herbs',
            'slug' => 'herbs',
        ]);
        $child = Category::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'Organic Herbs',
            'slug' => 'organic-herbs',
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->first()->is($child));
    }

    public function test_admin_can_manage_categories_from_admin_panel(): void
    {
        $admin = $this->adminWithRole('admin');
        $parent = Category::factory()->create([
            'name' => 'Root Category',
            'slug' => 'root-category',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/categories', [
                'parent_id' => $parent->id,
                'name' => 'Wellness Tea',
                'slug' => 'wellness-tea',
                'description' => 'Herbal tea blends.',
                'sort_order' => 5,
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Wellness Tea');

        $category = Category::query()->where('slug', 'wellness-tea')->firstOrFail();

        $this->assertSame($parent->id, $category->parent_id);
        $this->assertSame('Wellness Tea', $category->name);

        $this->actingAs($admin)
            ->getJson('/admin/categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Wellness Tea');

        $this->actingAs($admin)
            ->putJson('/admin/categories/'.$category->id, [
                'parent_id' => null,
                'name' => 'Wellness Tea Updated',
                'slug' => 'wellness-tea-updated',
                'description' => 'Updated category.',
                'sort_order' => 8,
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Wellness Tea Updated');

        $category->refresh();

        $this->assertNull($category->parent_id);
        $this->assertSame('Wellness Tea Updated', $category->name);
        $this->assertSame('wellness-tea-updated', $category->slug);
        $this->assertSame(8, $category->sort_order);
        $this->assertSame('inactive', $category->status);

        $this->actingAs($admin)
            ->deleteJson('/admin/categories/'.$category->id)
            ->assertOk();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_admin_category_api_supports_relationships(): void
    {
        $admin = $this->adminWithRole('admin');
        $token = $admin->createToken('admin-api')->plainTextToken;
        $parent = Category::factory()->create([
            'name' => 'Root Herbs',
            'slug' => 'root-herbs',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/categories', [
                'parent_id' => $parent->id,
                'name' => 'Fresh Herbs',
                'slug' => 'fresh-herbs',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Fresh Herbs')
            ->assertJsonPath('data.parent_id', $parent->id);

        $category = Category::query()->where('slug', 'fresh-herbs')->firstOrFail();

        $this->withToken($token)
            ->getJson('/api/v1/categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('data.parent.name', 'Root Herbs');
    }

    public function test_manager_can_view_but_cannot_mutate_categories(): void
    {
        $manager = $this->adminWithRole('manager');
        $category = Category::factory()->create();
        $token = $manager->createToken('admin-api')->plainTextToken;

        $this->actingAs($manager)
            ->postJson('/admin/categories', [
                'name' => 'Denied',
                'slug' => 'denied-web',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->getJson('/api/v1/categories')
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/categories', [
                'name' => 'Denied',
                'slug' => 'denied',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->putJson('/admin/categories/'.$category->id, [
                'name' => 'Denied',
                'slug' => 'denied-update',
            ])
            ->assertForbidden();
    }

    public function test_category_routes_have_expected_boundaries(): void
    {
        foreach ([
            'admin.categories.index' => 'categories.view',
            'admin.categories.data' => 'categories.view',
            'admin.categories.show' => 'categories.view',
            'admin.categories.store' => 'categories.create',
            'admin.categories.update' => 'categories.update',
            'admin.categories.destroy' => 'categories.delete',
        ] as $routeName => $permission) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:'.$permission);
        }

        foreach ([
            'api.v1.categories.index' => 'categories.view',
            'api.v1.categories.show' => 'categories.view',
            'api.v1.categories.store' => 'categories.create',
            'api.v1.categories.update' => 'categories.update',
            'api.v1.categories.destroy' => 'categories.delete',
        ] as $routeName => $permission) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth:sanctum');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:'.$permission);
        }
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
