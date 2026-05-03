<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Ecommerce Admin</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.css') }}">
    @include('admin.layouts.toast-styles')
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="login-logo">
        <a href="{{ route('admin.login') }}" class="text-decoration-none"><b>Ecommerce</b> Admin</a>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sign in to manage the store</p>

            @if (session('session_expired'))
                <div class="alert alert-warning" role="alert">
                    Your session has expired. Please sign in again.
                </div>
            @endif

            <form method="post" action="{{ route('admin.login.store') }}">
                @csrf

                <div class="input-group mb-3">
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="Email"
                        autocomplete="email"
                        autofocus
                    >
                    <div class="input-group-text">
                        <span aria-hidden="true">@</span>
                    </div>
                    @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="input-group mb-3">
                    <input
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Password"
                        autocomplete="current-password"
                    >
                    <div class="input-group-text">
                        <span aria-hidden="true">*</span>
                    </div>
                    @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="{{ route('admin.password.request') }}" class="d-block mt-2">Forgot password?</a>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
@include('admin.layouts.toast-scripts')
@if (session('status'))
    <script>
        adminToast(@json(session('status')));
    </script>
@endif
</body>
</html>
