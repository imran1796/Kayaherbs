<?php

namespace App\Modules\User\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserService $service
    ) {}

    public function index()
    {
        return view('user::index', [
            'users' => $this->service->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        return view('user::create', [
            'roles' => $this->assignableRoles($request),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->service->create($request->validated());

        return ApiResponse::success(
            new UserResource($user),
            'User created successfully.',
            201
        );
    }

    public function edit(Request $request, int $id)
    {
        return view('user::edit', [
            'user' => $this->service->findOrFail($id),
            'roles' => $this->assignableRoles($request),
        ]);
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->service->update($id, $request->validated());

        return ApiResponse::success(
            new UserResource($user),
            'User updated successfully.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function assignableRoles(Request $request): array
    {
        $roles = config('rbac.roles', []);

        if (! $request->user()?->hasRole('super_admin')) {
            unset($roles['super_admin']);
        }

        return collect($roles)
            ->mapWithKeys(fn (array $role, string $name): array => [$name => $role['label'] ?? $name])
            ->all();
    }
}
