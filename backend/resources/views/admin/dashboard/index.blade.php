@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'AdminLTE 4 is now driving the admin frontend shell.')

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box text-bg-primary stat-card">
                <div class="inner">
                    <h3>{{ $stats['total_users'] }}</h3>
                    <p>Total registered users</p>
                </div>
                <div class="small-box-icon">
                    <span class="app-icon" aria-hidden="true">U</span>
                </div>
                <a href="{{ route('admin.users.index') }}" class="small-box-footer link-light link-underline-opacity-0">
                    Open users <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box text-bg-success stat-card">
                <div class="inner">
                    <h3>{{ $stats['active_users'] }}</h3>
                    <p>Active user accounts</p>
                </div>
                <div class="small-box-icon">
                    <span class="app-icon" aria-hidden="true">A</span>
                </div>
                <a href="{{ route('admin.users.index') }}" class="small-box-footer link-light link-underline-opacity-0">
                    Review accounts <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box text-bg-warning stat-card">
                <div class="inner">
                    <h3>{{ $stats['inactive_users'] }}</h3>
                    <p>Inactive or disabled users</p>
                </div>
                <div class="small-box-icon">
                    <span class="app-icon" aria-hidden="true">I</span>
                </div>
                <a href="{{ route('admin.users.index') }}" class="small-box-footer link-dark link-underline-opacity-0">
                    Check status <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Admin frontend integrated</h3>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        The admin area now uses the supplied AdminLTE 4 template as a shared Laravel layout, so
                        new pages can plug into the same header, sidebar, footer, and styling.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h4 class="h6">Reusable layout</h4>
                                <p class="mb-0 text-secondary">
                                    Dashboard and user management now extend one Blade layout instead of each page
                                    shipping its own custom HTML shell.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h4 class="h6">Template assets published</h4>
                                <p class="mb-0 text-secondary">
                                    AdminLTE CSS, JS, and image assets are available from Laravel's public
                                    directory for direct use in admin views.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
                        Go to user management
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Recent users</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentUsers as $user)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <div class="small text-secondary">{{ $user->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-secondary">
                                            No users found yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
