@extends('admin.layouts.app')

@section('title', 'Roles')
@section('page_title', 'Roles')
@section('page_subtitle', 'Manage admin access roles before assigning permissions.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Roles</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title">Roles</h3>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-primary">{{ $roles->total() }} total</span>
                    @can('roles.create')
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-primary">Create role</a>
                    @endcan
                </div>
            </div>
            @cannot('roles.create')
                <div class="alert alert-info mb-0 mt-3">You can review roles, but creating roles requires additional permission.</div>
            @endcannot
            @cannot('roles.update')
                <div class="alert alert-warning mb-0 mt-3">Role edits are unavailable for your current permission level.</div>
            @endcannot
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Guard</th>
                            <th>Users</th>
                            <th>Permissions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="fw-semibold">{{ str($role->name)->replace('_', ' ')->title() }}</td>
                                <td>{{ $role->guard_name }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td class="text-end">
                                    @can('roles.update')
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @else
                                        <span class="text-secondary">View only</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">No roles found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($roles->hasPages())
            <div class="card-footer clearfix">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
@endsection

@if (session('status'))
    @push('scripts')
        <script>
            adminToast(@json(session('status')));
        </script>
    @endpush
@endif
