@extends('admin.layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')
@section('page_subtitle', '')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Users</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title">Users</h3>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-primary">{{ $users->total() }} total</span>
                    @can('users.create')
                        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">Create user</a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Roles</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="fw-semibold">#{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td>
                                    @forelse ($user->getRoleNames() as $role)
                                        <span class="badge text-bg-light border me-1">{{ str($role)->replace('_', ' ')->title() }}</span>
                                    @empty
                                        <span class="text-secondary">No roles</span>
                                    @endforelse
                                </td>
                                <td>{{ $user->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    @can('users.update')
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">No users found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer clearfix">
                {{ $users->links() }}
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
