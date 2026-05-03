# B1.10 Internal Coding Standards For Modules

These standards keep every module consistent, simple, and easy to maintain.

## Main Direction

Build simple, standard Laravel code that a normal Laravel developer can read quickly.

- Prefer the simplest working design that matches the existing project.
- Do not over-engineer with extra layers, abstractions, traits, events, actions, DTOs, pipelines, or helper classes unless the feature clearly needs them.
- Add a new class only when it has a clear job and reduces real duplication or confusion.
- Keep business rules explicit and readable. A little repeated code is acceptable if it keeps the feature easier to understand.
- Use Laravel conventions first. Custom patterns come second.
- Code should feel professional and maintainable, not clever.

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
11. Keep each slice small and easy to review.
12. Do not create extra infrastructure for a future feature before the current feature actually needs it.
13. When a module adds admin/API actions, add the needed permissions to `config/rbac.php` in the same slice.

## Default Module Shape

Use this shape for normal modules:

```txt
Controller -> Service -> Repository -> Eloquent model relationships
```

- Controller: receives request, calls service, returns view/API response.
- Service: owns business rules, workflow, validation decisions after request validation, and transactions.
- Repository: owns Eloquent queries and persistence.
- Models: own relationships, casts, simple scopes, and simple computed attributes.

Keep it simple:

- Prefer one service per module area before splitting into many services.
- Prefer one repository per module area before splitting into many repositories.
- Do not create interfaces for repositories unless the module provider already needs bindings or there is a real reason to swap implementations.
- Do not create action classes, pipelines, DTOs, abstract base services, or extra helper layers for ordinary CRUD/workflow code.
- If two methods are easy to read with small duplication, do not introduce an abstraction just to remove three lines.
- If a file becomes hard to scan, split by business area, not by clever technical pattern.

## Controllers

- Controllers should coordinate the request, not hold business logic.
- Keep controller methods focused on validation, authorization, calling the service, and returning the response/view.
- Do not place database query chains or business workflows directly in controllers.
- Do not manually format common API errors inside controllers; let the global exception handler do that.
- Do not add local `try/catch` blocks in controllers by default; only use them when a controller truly needs custom handling.
- Simple read-only endpoints may use a clear model query directly when adding a repository/service would make the code harder to follow.

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
- Do not create services for tiny read-only screens that only list data and have no business workflow.

## Repositories

- Repositories own data access and persistence queries.
- Write clear Eloquent queries directly inside the repository.
- Do not extend a base repository by default.
- Do not create repository interfaces by default.
- Keep repositories focused on persistence, not business decision-making.
- Do not create a repository method for a one-line query unless it is reused or it hides a meaningful persistence detail.

## Responses

- API controllers should return `ApiResponse::success()` for normal API responses.
- API errors should rely on the global exception mapping in `bootstrap/app.php`.
- Blade views for admin/web should be rendered from module `views/` using the module view namespace.
- New admin data-management views must use AJAX for create, update, delete, publish/unpublish, toggle, and other state-changing operations.
- New admin data-management views should fetch list/detail data with AJAX when the screen is interactive.
- Use jQuery AJAX (`$.ajax`) in admin Blade screens unless plain JavaScript is clearly necessary.
- Admin forms should submit with JSON or `FormData` through AJAX. Use `FormData` for file uploads.
- Admin AJAX success and failure feedback must use the shared popup toast helper `adminToast(message, type)`.
- Do not add inline success/failure alert boxes for new admin data-management operations unless the user explicitly asks for persistent inline messages.
- Admin AJAX endpoints may live under the web/admin route group when they need session auth and CSRF protection; they must still return the standard JSON response shape.
- Full-page form submissions are allowed only for simple auth pages or when the user explicitly chooses that style.

## Providers And Registration

- Module service providers live in `Providers/`.
- Use providers for view namespaces and real bindings only.
- Do not add empty interface bindings.
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

## Before Any Task
1. Understand request.
2. Identify exact impacted files.
3. Explain short plan.
4. Make focused changes only.


### Token Efficiency Rules
- Do NOT scan the whole project unless explicitly asked.
- First identify minimum required files.
- Read only relevant files.
- Reuse existing discovered context.
- Keep responses concise.

### Editing Rules
- Do not modify unrelated files.
- Make smallest safe change possible.
- Match existing naming/style.
- Reuse existing components before creating new ones.

## If Unclear
Ask one short precise question.

## If Large Task
Break into phases and start with step 1.
