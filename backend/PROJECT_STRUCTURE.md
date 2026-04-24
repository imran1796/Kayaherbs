# B1.1 Simple Modular Laravel Folder Structure

Status: Finalized with a simple module-first structure.

The project will stay close to normal Laravel, while each business module keeps its own controllers, requests, resources, services, repositories, routes, views, providers, and models.

## Final Structure

```text
app/
  Core/
    Services/
    Repositories/
      Contracts/
    Support/

  Modules/
    User/
      Controllers/
      Requests/
      Resources/
      Services/
        Contracts/
      Repositories/
        Contracts/
      routes/
        web.php
        api.php
      views/
      Providers/
      Models/
```

Normal Laravel folders remain in place:

```text
app/
  Http/
  Models/
  Providers/

routes/
  web.php
  api.php
  admin.php
  console.php

resources/
  views/
```

## Rules

- Keep shared reusable code in `app/Core`.
- Keep module-specific code in `app/Modules/<ModuleName>`.
- Put module web/admin routes in `app/Modules/<ModuleName>/routes/web.php`.
- Put module API routes in `app/Modules/<ModuleName>/routes/api.php`.
- Register module route files from Laravel's normal `routes/admin.php` and `routes/api.php`.
- Put module Blade views in `app/Modules/<ModuleName>/views` and load them with a view namespace.
- Put module-specific service providers in `app/Modules/<ModuleName>/Providers`.
- Put module-owned Eloquent models in `app/Modules/<ModuleName>/Models`.
- Only add extra folders when a module actually needs them.

## Core Usage

- `app/Core/Repositories/BaseRepository.php` provides common Eloquent methods such as `paginate`, `findOrFail`, `create`, `update`, and `delete`.
- `app/Core/Services/BaseService.php` provides shared service helpers, currently transaction wrapping for write operations.
- `app/Core/Support/ApiResponse.php` provides the standard API success/error response shape.
- `app/Core/Support/LogRequestAndCatchExceptions.php` provides global request logging and rethrows exceptions to Laravel's central exception handler.

Use `Core` only for reusable technical code. If a class belongs to one business feature, keep it inside that module.

## Registration Flow

Laravel loads the main route files from `bootstrap/app.php`:

```text
bootstrap/app.php
  routes/web.php
  routes/api.php
```

Module routes are then included from the normal Laravel route entrypoints:

```text
routes/web.php
  routes/admin.php
    config/modules.php
      app/Modules/User/routes/web.php

routes/api.php
  config/modules.php
    app/Modules/User/routes/api.php
```

Laravel service providers are registered from `bootstrap/providers.php`:

```text
bootstrap/providers.php
  App\Providers\AppServiceProvider
  config/modules.php
    App\Modules\User\Providers\UserServiceProvider
```

Module-specific bindings and view namespaces belong in the module provider, not in `AppServiceProvider`.

## B1.2 Module Registration Pattern

Every enabled module is registered in `config/modules.php`:

```php
'user' => [
    'name' => 'User',
    'provider' => App\Modules\User\Providers\UserServiceProvider::class,
    'routes' => [
        'web' => 'Modules/User/routes/web.php',
        'api' => 'Modules/User/routes/api.php',
    ],
],
```

When adding a new module:

1. Create `app/Modules/<ModuleName>`.
2. Create `Providers/<ModuleName>ServiceProvider.php` if the module has bindings, views, events, or other bootstrapping.
3. Create `routes/web.php` for admin/web routes if needed.
4. Create `routes/api.php` for API routes if needed.
5. Add one entry to `config/modules.php`.
6. Keep module bindings and module view namespaces inside the module provider.

## B1.4 Global Exception Handling

API requests are detected by `expectsJson()` or the `api/*` path and return the standard `ApiResponse::error()` shape:

```json
{
  "success": false,
  "message": "Resource not found.",
  "errors": [],
  "code": "not_found"
}
```

Current API exception mapping:

- `ValidationException` returns `422 validation_failed`.
- `AuthenticationException` returns `401 unauthenticated`.
- `AuthorizationException` returns `403 forbidden`.
- `ModelNotFoundException` and missing API routes return `404 not_found`.
- `ThrottleRequestsException` returns `429 too_many_requests`.
- Unexpected server errors return `500 server_error`.

Admin/web requests keep Laravel's normal web exception pages. The request logging middleware logs failed requests and rethrows exceptions so `bootstrap/app.php` remains the single response-mapping location.

## B1.6 Logging And Trace Correlation

Every request receives a trace ID:

- If the request sends `X-Request-Id`, that value is reused.
- If the request does not send `X-Request-Id`, the middleware generates a UUID.
- The trace ID is stored on the request as `trace_id`.
- Request logs include `trace_id`.
- Responses include the `X-Request-Id` header.
- API responses created through `ApiResponse` also include the `X-Request-Id` header.

Current request logs include:

- `trace_id`
- `method`
- `path`
- `ip`
- `user_id`
- `status`
- `duration_ms`
- exception class/message for failed requests

This is intentionally lightweight. No external tracing service, database table, or dashboard is required for B1.6.

## B1.7 Environment Configuration Structure

Environment and deployment-controlled settings live in `config/*.php` and `.env`:

- Infrastructure settings stay in Laravel config such as `config/app.php`, `config/database.php`, `config/cache.php`, `config/queue.php`, `config/logging.php`, and `config/services.php`.
- Module registration stays in `config/modules.php`.
- Project-level store bootstrap defaults and settings conventions live in `config/store.php`.
- `env()` is used only inside config files.
- Application and module code should read configuration through `config()`.

Current environment keys:

- `APP_*` for application/runtime defaults such as URL and timezone.
- `DB_*`, `CACHE_*`, `QUEUE_*`, `MAIL_*` for infrastructure and integrations.
- `STORE_*` for store bootstrap defaults and settings cache behavior.

Current `config/store.php` conventions:

- `store.defaults.*` contains deployment/bootstrap defaults such as name, currency, timezone, and support email.
- `store.settings.table` defines the future database table for admin-editable settings.
- `store.settings.cache_key` and `store.settings.cache_ttl` define the shared cache convention for later settings reads.

Rule of use:

- Keep infrastructure, secrets, and deployment defaults in `config + .env`.
- Keep admin-editable business settings in the database later through the settings module.
- Use env-backed store defaults only as bootstrap values until database settings exist.

## B1.10 Internal Coding Standards

The internal coding standard is defined in `MODULE_CODING_STANDARDS.md`.

Summary rules:

- Controllers stay thin.
- Business rules go in services.
- Data access goes in repositories.
- Validation goes in request classes.
- API responses use `ApiResponse`.
- Shared technical code goes in `app/Core`.
- Module-specific code stays inside the module.
- Configuration is read with `config()`, not `env()`, outside config files.
- Module bindings and view namespaces belong in module providers.
- Module routes stay in `routes/web.php` and `routes/api.php`.

## Current User Module

```text
app/Modules/User/
  Controllers/
    UserController.php
    UserManagementController.php
  Requests/
    StoreUserRequest.php
    UpdateUserRequest.php
  Resources/
    UserResource.php
  Services/
    Contracts/
    UserService.php
  Repositories/
    Contracts/
      UserRepositoryInterface.php
    UserRepository.php
  routes/
    api.php
    web.php
  views/
    index.blade.php
  Providers/
    UserServiceProvider.php
  Models/
```

## B1.1 Acceptance Checklist

- `app/Core` exists for shared services, repositories, and support helpers.
- `app/Modules/User` follows the simplified module folder structure.
- User admin and API routes are owned by the module.
- User admin view is owned by the module.
- User repository binding and view namespace are registered by `UserServiceProvider`.
- User module provider and route files are registered through `config/modules.php`.
- API exceptions use the global response mapping in `bootstrap/app.php`.
- Request logs and responses include `X-Request-Id` trace correlation.
- Environment and store bootstrap defaults are organized through `config/*.php` and `.env`.
- Internal module coding rules are defined in `MODULE_CODING_STANDARDS.md`.
- Existing admin and API behavior still works after simplification.
