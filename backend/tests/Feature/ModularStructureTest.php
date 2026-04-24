<?php

namespace Tests\Feature;

use Tests\TestCase;

class ModularStructureTest extends TestCase
{
    public function test_b1_1_simple_modular_laravel_structure_exists(): void
    {
        $paths = [
            app_path('Core'),
            app_path('Core/Services/BaseService.php'),
            app_path('Core/Repositories/BaseRepository.php'),
            app_path('Core/Repositories/Contracts/BaseRepositoryInterface.php'),
            app_path('Core/Support/ApiResponse.php'),
            app_path('Modules'),
            app_path('Modules/README.md'),
            base_path('config/store.php'),
            base_path('config/modules.php'),
            app_path('Modules/User/Controllers/UserController.php'),
            app_path('Modules/User/Controllers/UserManagementController.php'),
            app_path('Modules/User/Requests/StoreUserRequest.php'),
            app_path('Modules/User/Requests/UpdateUserRequest.php'),
            app_path('Modules/User/Resources/UserResource.php'),
            app_path('Modules/User/Services'),
            app_path('Modules/User/Services/Contracts'),
            app_path('Modules/User/Services/UserService.php'),
            app_path('Modules/User/Repositories'),
            app_path('Modules/User/Repositories/Contracts/UserRepositoryInterface.php'),
            app_path('Modules/User/Repositories/UserRepository.php'),
            app_path('Modules/User/routes/web.php'),
            app_path('Modules/User/routes/api.php'),
            app_path('Modules/User/views/index.blade.php'),
            app_path('Modules/User/Providers/UserServiceProvider.php'),
            app_path('Modules/User/Models'),
            base_path('MODULE_CODING_STANDARDS.md'),
            base_path('routes/admin.php'),
            base_path('routes/api.php'),
            base_path('PROJECT_STRUCTURE.md'),
        ];

        foreach ($paths as $path) {
            $this->assertFileExists($path, "{$path} should exist for the simplified modular structure.");
        }
    }

    public function test_b1_2_module_registration_pattern_is_configured(): void
    {
        $modules = require base_path('config/modules.php');

        $this->assertArrayHasKey('user', $modules);
        $this->assertSame(
            \App\Modules\User\Providers\UserServiceProvider::class,
            $modules['user']['provider']
        );
        $this->assertSame('Modules/User/routes/web.php', $modules['user']['routes']['web']);
        $this->assertSame('Modules/User/routes/api.php', $modules['user']['routes']['api']);
        $this->assertFileExists(app_path($modules['user']['routes']['web']));
        $this->assertFileExists(app_path($modules['user']['routes']['api']));
    }
}
