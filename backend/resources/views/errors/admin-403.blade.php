<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied | Ecommerce Admin</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.css') }}">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="login-logo">
        <a href="{{ route('admin.dashboard') }}" class="text-decoration-none"><b>Ecommerce</b> Admin</a>
    </div>

    <div class="card">
        <div class="card-body login-card-body text-center">
            <h1 class="h4 mb-3">Access denied</h1>
            <p class="login-box-msg mb-4">
                Your admin account does not have permission to open this page.
            </p>

            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-block">
                Back to dashboard
            </a>
        </div>
    </div>
</div>
</body>
</html>
