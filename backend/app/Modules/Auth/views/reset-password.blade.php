<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | Ecommerce Admin</title>
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
            <p class="login-box-msg">Choose a new admin password</p>

            <form method="post" action="{{ route('admin.password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="input-group mb-3">
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $email) }}"
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
                        autocomplete="new-password"
                    >
                    <div class="input-group-text">
                        <span aria-hidden="true">*</span>
                    </div>
                    @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="input-group mb-3">
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="Confirm password"
                        autocomplete="new-password"
                    >
                    <div class="input-group-text">
                        <span aria-hidden="true">*</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
@include('admin.layouts.toast-scripts')
</body>
</html>
