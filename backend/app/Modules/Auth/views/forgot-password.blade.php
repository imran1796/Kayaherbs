<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Ecommerce Admin</title>
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
            <p class="login-box-msg">Enter your email to receive a reset link</p>

            <form method="post" action="{{ route('admin.password.email') }}">
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

                <div class="row align-items-center">
                    <div class="col-7">
                        <a href="{{ route('admin.login') }}">Back to sign in</a>
                    </div>
                    <div class="col-5">
                        <button type="submit" class="btn btn-primary btn-block">Send Link</button>
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
