@extends('admin.layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Management')
@section('page_subtitle', 'This page keeps using the module service layer while adopting the AdminLTE admin shell.')

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
                    <p class="text-secondary mb-0 mt-1">Sample admin listing powered by the existing user module.</p>
                </div>
                <span class="badge text-bg-primary">{{ $users->total() }} total</span>
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
                            <th>Joined</th>
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
                                <td>{{ $user->created_at?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">No users found yet.</td>
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
