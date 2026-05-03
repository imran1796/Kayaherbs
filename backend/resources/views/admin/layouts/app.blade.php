<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel') | Ecommerce Admin</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.css') }}">
    @include('admin.layouts.toast-styles')
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .brand-link .brand-image {
            float: none;
            margin-inline-end: .5rem;
        }

        .sidebar-wrapper {
            overflow-y: auto;
        }

        .app-content-header {
            padding-bottom: 0;
        }

        .app-content-header .breadcrumb {
            margin-bottom: 0;
        }

        .stat-card .small-box-icon {
            inset-inline-end: 1rem;
            top: 1rem;
            font-size: 2rem;
            opacity: .25;
        }

        .content-subtitle {
            color: var(--bs-secondary-color);
            margin-bottom: 0;
        }

        .table th {
            white-space: nowrap;
        }

        .app-icon {
            align-items: center;
            display: inline-flex;
            font-size: 1rem;
            font-style: normal;
            font-weight: 700;
            justify-content: center;
            line-height: 1;
        }

        .nav-icon.app-icon {
            font-size: .95rem;
        }

        .toggle-icon {
            font-size: 1.25rem;
        }

        .small-box-icon .app-icon {
            font-size: 2rem;
        }

        .topbar-meta {
            align-items: center;
            display: flex;
            gap: .75rem;
        }

        .topbar-chip {
            align-items: center;
            background: var(--bs-secondary-bg);
            border-radius: 999px;
            color: var(--bs-secondary-color);
            display: inline-flex;
            font-size: .875rem;
            font-weight: 600;
            gap: .5rem;
            padding: .35rem .8rem;
        }
    </style>
    @stack('styles')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <span class="app-icon toggle-icon" aria-hidden="true">|||</span>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="{{ route('admin.users.index') }}" class="nav-link">Users</a>
                </li>
                @can('settings.view')
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ route('admin.settings.store-profile.edit') }}" class="nav-link">Settings</a>
                    </li>
                @endcan
                @can('modules.view')
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ route('admin.settings.module-toggles.edit') }}" class="nav-link">Modules</a>
                    </li>
                @endcan
            </ul>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <div class="nav-link topbar-meta">
                        <span class="topbar-chip d-none d-md-inline-flex">{{ auth()->user()->name }}</span>
                        <span class="topbar-chip">{{ now()->format('d M Y') }}</span>
                        <span class="topbar-chip d-none d-md-inline-flex">Admin Panel</span>
                    </div>
                </li>
                <li class="nav-item">
                    <form method="post" action="{{ route('admin.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="nav-link btn btn-link">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    @include('admin.layouts.sidebar')

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h3 class="mb-1">@yield('page_title', 'Dashboard')</h3>
                        <p class="content-subtitle">@yield('page_subtitle', 'Admin overview')</p>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                            @yield('breadcrumbs')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">AdminLTE 4 integrated into Laravel admin</div>
        <strong>
            Copyright &copy; {{ now()->year }}
            <a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Ecommerce Admin</a>.
        </strong>
        All rights reserved.
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('vendor/adminlte/js/adminlte.js') }}"></script>
@include('admin.layouts.toast-scripts')
@stack('scripts')
</body>
</html>
