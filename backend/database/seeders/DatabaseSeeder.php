<?php

namespace Database\Seeders;

use App\Core\Services\AuditLogger;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RbacSeeder::class);

        $admin = User::query()->firstOrNew([
            'email' => config('admin.seed.email'),
        ]);

        $admin->fill([
            'name' => config('admin.seed.name'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        if (! $admin->exists) {
            $admin->forceFill([
                'email_verified_at' => now(),
                'password' => config('admin.seed.password'),
                'remember_token' => Str::random(10),
            ]);
        }

        $admin->save();
        $admin->assignRole('super_admin');
        app(AuditLogger::class)->record(
            'rbac.role.assigned',
            actor: $admin,
            auditable: $admin,
            metadata: ['role' => 'super_admin', 'source' => 'database_seeder'],
            guard: 'web'
        );
    }
}
