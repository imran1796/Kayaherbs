# B1.10 Internal Coding Standards For Modules

These standards keep every module consistent, simple, and easy to maintain.

## Core Rules

1. Controllers stay thin.
2. Business rules go in services.
3. Data access goes in repositories.
4. Validation goes in request classes.
5. API responses use `App\Core\Support\ApiResponse`.
6. Shared reusable technical code goes in `app/Core`.
7. Module-specific code stays inside `app/Modules/<ModuleName>`.
8. Read configuration through `config()`, not `env()`, outside config files.
9. Module bindings and module view namespaces belong in the module provider.
10. Web/admin routes belong in `routes/web.php`, and API routes belong in `routes/api.php` inside the module.

## Controllers

- Controllers should coordinate the request, not hold business logic.
- Keep controller methods focused on validation, authorization, calling the service, and returning the response/view.
- Do not place database query chains or business workflows directly in controllers.
- Do not manually format common API errors inside controllers; let the global exception handler do that.
- Do not add local `try/catch` blocks in controllers by default; only use them when a controller truly needs custom handling.

## Requests

- Use request classes for validation when an endpoint accepts structured input.
- Keep validation rules close to the module in `Requests/`.
- Use clear rule names and error-causing constraints that match business expectations.

## Services

- Services own business workflows, rules, and write operations.
- Use `BaseService::transaction()` for write flows that should be atomic.
- Use database transactions only where multiple writes must succeed or fail together.
- Do not wrap read-only flows in transactions by default.
- Use local `try/catch` only when special recovery, custom exception translation, or additional business-specific handling is needed.
- Services may coordinate multiple repositories or domain operations.
- Services should not return raw framework responses.

## Repositories

- Repositories own data access and persistence queries.
- Reuse `BaseRepository` for common CRUD behavior where it helps.
- Repository interfaces belong in `Repositories/Contracts`.
- Keep repositories focused on persistence, not business decision-making.

## Responses

- API controllers should return `ApiResponse::success()` for normal API responses.
- API errors should rely on the global exception mapping in `bootstrap/app.php`.
- Blade views for admin/web should be rendered from module `views/` using the module view namespace.

## Providers And Registration

- Module service providers live in `Providers/`.
- Bind module interfaces there.
- Load module views there.
- Register module providers and route files through `config/modules.php`.

## Configuration

- Use `config()` in application code.
- Use `env()` only in `config/*.php`.
- Keep infrastructure and deployment settings in config and environment variables.
- Keep admin-editable business settings in the database later through the settings module.

## Logging And Traceability

- Do not invent custom trace logic inside modules.
- Use the global `X-Request-Id` header and `trace_id` request attribute.
- Let the global middleware and exception handler manage request logging and API exception formatting.
- Prefer global request logging for cross-cutting request/response tracing.
- Add local business logs only for important domain events such as order placement, payment failure, stock adjustment, or settings change.

## Naming And Layout

- Follow the standard module layout already defined in `PROJECT_STRUCTURE.md`.
- Prefer clear, feature-based names such as `UserService`, `UserRepository`, `StoreUserRequest`.
- Add extra module folders only when the feature truly needs them.

## Practical Rule

If a behavior is used by multiple modules, consider moving the technical abstraction into `app/Core`.
If a behavior belongs to one business feature, keep it in that module.
