# Modules

Each folder in this directory is one business module.

## Simple Module Template

```text
app/Modules/<ModuleName>/
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

Use only what the module needs. Do not add empty advanced folders like `Jobs`, `Events`, `Policies`, or `Enums` until the feature needs them.

## Conventions

- Put admin/web routes in `routes/web.php`.
- Put API routes in `routes/api.php`.
- Put service interfaces in `Services/Contracts`.
- Put repository interfaces in `Repositories/Contracts`.
- Put module service providers in `Providers`.
- Put module-owned Eloquent models in `Models`.
- Register module provider and route files in `config/modules.php`.
- Keep shared reusable helpers in `app/Core`, not inside a module.
- Let API exceptions bubble to Laravel's global exception handler; do not catch and format exceptions inside module controllers.
- Do not create custom request trace IDs inside modules; use the global `X-Request-Id` header and `trace_id` request attribute.
- Read settings via `config()`, not `env()`, from module code. Business-editable values should come from the future settings module, not hardcoded env reads inside modules.
- Follow `MODULE_CODING_STANDARDS.md` for controller/service/repository/request responsibilities.

## Registration Example

```php
// config/modules.php
'user' => [
    'name' => 'User',
    'provider' => App\Modules\User\Providers\UserServiceProvider::class,
    'routes' => [
        'web' => 'Modules/User/routes/web.php',
        'api' => 'Modules/User/routes/api.php',
    ],
],
```
