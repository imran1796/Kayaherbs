<?php

namespace App\Modules\User\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $service
    ) {}

    public function index(Request $request)
    {
        $users = $this->service->paginate((int) $request->integer('per_page', 15));

        return ApiResponse::success(
            UserResource::collection($users),
            'Users fetched successfully.',
            200,
            [
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]
        );
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

    public function show(int $id)
    {
        $user = $this->service->findOrFail($id);

        return ApiResponse::success(new UserResource($user), 'User fetched successfully.');
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->service->update($id, $request->validated());

        return ApiResponse::success(new UserResource($user), 'User updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return ApiResponse::success(null, 'User deleted successfully.');
    }
}
