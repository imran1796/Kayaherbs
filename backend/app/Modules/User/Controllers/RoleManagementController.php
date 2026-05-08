<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\StoreRoleRequest;
use App\Modules\User\Requests\UpdateRoleRequest;
use App\Modules\User\Services\RoleService;
use Illuminate\Http\RedirectResponse;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly RoleService $roles
    ) {}

    public function index()
    {
        return view('user::roles.index', [
            'roles' => $this->roles->paginate(),
        ]);
    }

    public function create()
    {
        return view('user::roles.create', [
            'permissionMatrix' => $this->roles->permissionMatrix(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roles->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function edit(int $id)
    {
        return view('user::roles.edit', [
            'role' => $this->roles->findOrFail($id),
            'permissionMatrix' => $this->roles->permissionMatrix(),
        ]);
    }

    public function update(UpdateRoleRequest $request, int $id): RedirectResponse
    {
        $this->roles->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated successfully.');
    }
}
