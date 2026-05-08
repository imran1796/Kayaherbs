<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="name" class="form-label">Role name</label>
            <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name ?? '') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <p class="text-secondary">Role names are normalized to lowercase snake case when saved.</p>
        @isset($role)
            @if (in_array($role->name, ['super_admin', 'admin'], true))
                <div class="alert alert-warning">This baseline role has a protected permission set.</div>
            @elseif (in_array($role->name, ['manager', 'support', 'customer'], true))
                <div class="alert alert-info">This baseline role name is protected, but its permissions can be adjusted.</div>
            @endif
        @endisset

        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h4 class="h6 mb-0">Permission Matrix</h4>
                <span class="text-secondary">Grouped by module and action.</span>
            </div>
            @php($selectedPermissions = old('permissions', isset($role) ? $role->permissions->pluck('name')->all() : []))
            @error('permissions')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('permissions.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

            <div class="row g-3">
                @forelse ($permissionMatrix as $module => $permissions)
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h5 class="h6">{{ str($module)->replace('_', ' ')->title() }}</h5>
                            <div class="row g-2">
                                @foreach ($permissions as $permission => $label)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input
                                                id="permission_{{ str_replace(['.', '-'], '_', $permission) }}"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission }}"
                                                class="form-check-input"
                                                @checked(in_array($permission, $selectedPermissions, true))
                                            >
                                            <label class="form-check-label" for="permission_{{ str_replace(['.', '-'], '_', $permission) }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="border rounded p-4 text-center text-secondary">No permissions are available yet.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $button }}</button>
    </div>
</div>
