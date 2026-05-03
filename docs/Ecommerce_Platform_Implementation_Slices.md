# Ecommerce Platform Implementation Slices

Short coverage log for completed implementation slices.

## B2.1 Admin Authentication Domain

**Covers:**

- Admin login page: `backend/app/Modules/Auth/views/login.blade.php`.
- Admin login with Laravel `web` session guard: `backend/app/Modules/Auth/Controllers/AdminAuthController.php`.
- Admin logout: `backend/app/Modules/Auth/Controllers/AdminAuthController.php`.
- Login validation: `backend/app/Modules/Auth/Requests/AdminLoginRequest.php`.
- Login user lookup and login timestamp update: `backend/app/Modules/Auth/Controllers/AdminAuthController.php`.
- Admin-only route protection for `/admin`: `backend/app/Modules/Auth/Middleware/EnsureUserIsAdmin.php`.
- Active-admin-only access rule using `users.is_admin` and `users.status`: `backend/database/migrations/2026_04_26_000001_add_is_admin_to_users_table.php`.
- Admin auth routes: `backend/app/Modules/Auth/routes/web.php`.
- First admin seed user support: `backend/database/seeders/DatabaseSeeder.php`.
- Auth module registration following module coding standards: `backend/config/modules.php`.
- Root redirect fix for XAMPP subfolder paths: `backend/routes/web.php`.
- Feature tests for guest redirect, admin login, non-admin denial, inactive-admin denial, and logout: `backend/tests/Feature/AdminAuthenticationTest.php`.

**Verification:** `php artisan test` passed.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.2 Customer Authentication Domain

**Covers:**

- Sanctum dependency for storefront API bearer tokens: `backend/composer.json`.
- Sanctum personal access token table: `backend/database/migrations/2026_04_26_000002_create_personal_access_tokens_table.php`.
- Customer registration API: `backend/app/Modules/Auth/Controllers/CustomerAuthController.php`.
- Customer login API with Laravel Sanctum token issue: `backend/app/Modules/Auth/Controllers/CustomerAuthController.php`.
- Customer profile API using `auth:sanctum`: `backend/app/Modules/Auth/Controllers/CustomerAuthController.php`.
- Customer logout API with current token revoke: `backend/app/Modules/Auth/Controllers/CustomerAuthController.php`.
- Customer registration and login validation: `backend/app/Modules/Auth/Requests/CustomerRegisterRequest.php`, `backend/app/Modules/Auth/Requests/CustomerLoginRequest.php`.
- Customer token support on users: `backend/app/Models/User.php`.
- Customer auth routes under `/api/v1/auth/customer/*`: `backend/app/Modules/Auth/routes/api.php`.
- Feature tests for register, login, admin denial, inactive-customer denial, profile token requirement, profile success, and logout revoke: `backend/tests/Feature/CustomerAuthenticationTest.php`.

**Verification:** `php artisan test --filter=CustomerAuthenticationTest` passed.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.3 Password Reset Flow

**Covers:**

- Shared password reset orchestration for admin and customer domains: `backend/app/Modules/Auth/Services/PasswordResetService.php`.
- Customer forgot password API: `POST /api/v1/auth/customer/password/forgot` in `backend/app/Modules/Auth/routes/api.php`.
- Customer reset password API: `POST /api/v1/auth/customer/password/reset` in `backend/app/Modules/Auth/routes/api.php`.
- Admin forgot password form and submit route: `GET|POST /admin/forgot-password` in `backend/app/Modules/Auth/routes/web.php`.
- Admin reset password form and submit route: `GET /admin/reset-password/{token}` and `POST /admin/reset-password` in `backend/app/Modules/Auth/routes/web.php`.
- Password reset link request validation: `backend/app/Modules/Auth/Requests/PasswordResetLinkRequest.php`.
- Password reset submission validation: `backend/app/Modules/Auth/Requests/PasswordResetRequest.php`.
- Admin forgot/reset password views: `backend/app/Modules/Auth/views/forgot-password.blade.php`, `backend/app/Modules/Auth/views/reset-password.blade.php`.
- Admin login page forgot password link: `backend/app/Modules/Auth/views/login.blade.php`.
- Reset notification URL generation for admin reset links and customer reset links: `backend/app/Modules/Auth/Providers/AuthServiceProvider.php`.
- Active account boundary enforcement using `users.is_admin` and `users.status` during reset link and reset submission.
- Account enumeration protection by returning a neutral response for missing or wrong-domain reset link requests.
- Customer token revocation after password reset using Sanctum tokens.
- Feature tests for customer reset link request, admin/customer boundary denial, valid reset, invalid token handling, admin reset link request, admin reset success, and customer denial through admin reset flow: `backend/tests/Feature/CustomerAuthenticationTest.php`, `backend/tests/Feature/AdminAuthenticationTest.php`.

**Verification:** `php artisan test` passed with 29 tests and 152 assertions.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.4 Session/Token Lifecycle Handling

**Covers:**

- Customer token lifecycle defaults for token name, ability, expiry, and maximum active tokens: `backend/config/auth_lifecycle.php`.
- Customer token issue with explicit Sanctum `expires_at`, `customer` ability, and response expiry metadata: `backend/app/Modules/Auth/Services/CustomerAuthService.php`.
- Expired customer token cleanup before issuing a new token: `backend/app/Modules/Auth/Services/CustomerAuthService.php`.
- Configurable active customer token cap with oldest-token revocation: `backend/app/Modules/Auth/Services/CustomerAuthService.php`.
- Current-device logout by deleting the active Sanctum token: `backend/app/Modules/Auth/Services/CustomerAuthService.php`.
- All-device logout by deleting every customer token: `POST /api/v1/auth/customer/logout-all` in `backend/app/Modules/Auth/routes/api.php`.
- Customer API token middleware enforcing active, non-admin, bearer-token-only, `customer` ability access: `backend/app/Modules/Auth/Middleware/EnsureCustomerApiToken.php`.
- Middleware alias registration for `customer.token`: `backend/bootstrap/app.php`.
- Protected customer profile/logout routes now require both `auth:sanctum` and `customer.token`: `backend/app/Modules/Auth/routes/api.php`.
- Admin session lifecycle remains session-guard based with login session regeneration and logout session invalidation: `backend/app/Modules/Auth/Services/AdminAuthService.php`.
- Feature tests for token expiry, inactive customer token denial, admin token denial, wrong ability denial, current-token logout, all-device logout, active token cap, and admin session logout boundary: `backend/tests/Feature/CustomerAuthenticationTest.php`, `backend/tests/Feature/AdminAuthenticationTest.php`.

**Verification:** `php artisan test` passed.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.5 Roles Model

**Covers:**

- Spatie Laravel Permission dependency for RBAC: `backend/composer.json`, `backend/composer.lock`.
- Project role model extending Spatie role model: `backend/app/Models/Role.php`.
- User role support using Spatie `HasRoles`: `backend/app/Models/User.php`.
- Published Spatie RBAC tables for roles, model-role assignments, and role-permission assignments: `backend/database/migrations/2026_04_27_034741_create_permission_tables.php`.
- Central ecommerce RBAC role catalog: `backend/config/rbac.php`.
- Baseline roles: `super_admin`, `admin`, `manager`, `support`, and `customer`: `backend/config/rbac.php`.
- RBAC seeder that creates roles and syncs role permissions safely with Spatie cache resets: `backend/database/seeders/RbacSeeder.php`.
- Main database seeder now seeds RBAC and assigns the first admin user the `super_admin` role: `backend/database/seeders/DatabaseSeeder.php`.
- Customer registration now assigns the `customer` role when a customer account is created: `backend/app/Modules/Auth/Repositories/CustomerAuthRepository.php`.
- Feature tests for role tables, baseline roles, user role assignment, seeded admin role assignment, and customer registration role assignment: `backend/tests/Feature/RbacModelTest.php`, `backend/tests/Feature/CustomerAuthenticationTest.php`.

**Verification:** `php artisan test tests\Feature\RbacModelTest.php tests\Feature\CustomerAuthenticationTest.php tests\Feature\AdminAuthenticationTest.php` passed.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.6 Permissions Model

**Covers:**

- Project permission model extending Spatie permission model: `backend/app/Models/Permission.php`.
- Spatie permission config now points to project-owned `Role` and `Permission` models: `backend/config/permission.php`.
- Published Spatie permissions table and polymorphic model-permission table: `backend/database/migrations/2026_04_27_034741_create_permission_tables.php`.
- Central ecommerce permission catalog: `backend/config/rbac.php`.
- Baseline permissions for admin dashboard, user management, role management, permission management, and customer account access: `backend/config/rbac.php`.
- Role-permission synchronization through `RbacSeeder`: `backend/database/seeders/RbacSeeder.php`.
- Direct permission assignment support through Spatie `HasRoles` on the user model: `backend/app/Models/User.php`.
- Feature tests for permission table creation, permission seeding, role-permission checks, and direct user permission assignment: `backend/tests/Feature/RbacModelTest.php`.

**Verification:** `php artisan test tests\Feature\RbacModelTest.php` passed.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.7 Policy/Gate Enforcement Strategy

**Covers:**

- Laravel `Gate::before` strategy that grants `super_admin` a global authorization bypass: `backend/app/Providers/AppServiceProvider.php`.
- User model policy registration: `backend/app/Providers/AppServiceProvider.php`.
- User action policy mapping to RBAC permissions: `backend/app/Policies/UserPolicy.php`.
- Admin dashboard route protected by `can:admin.dashboard.view`: `backend/routes/admin.php`.
- Admin user-management web route protected by `can:users.view`: `backend/app/Modules/User/routes/web.php`.
- Admin user-management create/edit web routes protected by `can:users.create` and `can:users.update`: `backend/app/Modules/User/routes/web.php`.
- User-management API routes now require Sanctum auth, active admin boundary, and action-specific permissions: `backend/app/Modules/User/routes/api.php`.
- API user-management permission map:
  - `GET /api/v1/users` and `GET /api/v1/users/{id}` require `users.view`.
  - `POST /api/v1/users` requires `users.create`.
  - `PUT /api/v1/users/{id}` requires `users.update`.
  - `DELETE /api/v1/users/{id}` requires `users.delete`.
- Admin boundary middleware now supports both web sessions and Sanctum-authenticated admin API tokens: `backend/app/Modules/Auth/Middleware/EnsureUserIsAdmin.php`.
- Customer token middleware now resolves Sanctum users explicitly before fallback session users: `backend/app/Modules/Auth/Middleware/EnsureCustomerApiToken.php`.
- User RBAC guard pinned to the `web` guard so Spatie permissions remain consistent across admin sessions and admin API tokens: `backend/app/Models/User.php`.
- Feature tests for dashboard permission, user-management web permission, super-admin bypass, policy action mapping, API authentication, API admin boundary, API permission denial, and API permission success: `backend/tests/Feature/AuthorizationEnforcementTest.php`.
- Admin web feature tests for user create/edit pages, create/update submissions, and create/update permission denial: `backend/tests/Feature/UserManagementWebTest.php`.
- Admin user create/edit UI:
  - `GET /admin/users/create`
  - `POST /admin/users`
  - `GET /admin/users/{id}/edit`
  - `PUT /admin/users/{id}`
  - Source: `backend/app/Modules/User/Controllers/UserManagementController.php`, `backend/app/Modules/User/routes/web.php`.
- Admin user form partial and screens: `backend/app/Modules/User/views/_form.blade.php`, `backend/app/Modules/User/views/create.blade.php`, `backend/app/Modules/User/views/edit.blade.php`.
- Admin user listing now shows role badges and create/edit actions: `backend/app/Modules/User/views/index.blade.php`.
- User create/update supports Spatie role assignment through web and API payloads using `roles[]`: `backend/app/Modules/User/Requests/StoreUserRequest.php`, `backend/app/Modules/User/Requests/UpdateUserRequest.php`, `backend/app/Modules/User/Services/UserService.php`.
- User API resource exposes `is_admin` and assigned role names: `backend/app/Modules/User/Resources/UserResource.php`.
- Non-super-admin users cannot assign the `super_admin` role.

**Verification:** `php artisan test tests\Feature\AuthorizationEnforcementTest.php` passed.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.8 Audit Events For Auth-Sensitive Actions

**Covers:**

- Append-only audit events table with actor, auditable target, event name, outcome, request metadata, and JSON metadata: `backend/database/migrations/2026_04_27_040000_create_audit_events_table.php`.
- Audit event model with actor/auditable morph relations and JSON metadata casting: `backend/app/Models/AuditEvent.php`.
- Resilient audit logger service that records request IP, user agent, request ID, guard, actor, target, outcome, and redacts secret metadata: `backend/app/Core/Services/AuditLogger.php`.
- Admin auth audit events:
  - `admin.login.succeeded`
  - `admin.login.failed`
  - `admin.logout`
  - Source: `backend/app/Modules/Auth/Services/AdminAuthService.php`.
- Customer auth audit events:
  - `customer.registered`
  - `customer.login.succeeded`
  - `customer.login.failed`
  - `customer.logout`
  - `customer.logout_all`
  - Source: `backend/app/Modules/Auth/Services/CustomerAuthService.php`.
- Password reset audit events:
  - `admin.password_reset.requested`
  - `admin.password_reset.completed`
  - `admin.password_reset.failed`
  - `customer.password_reset.requested`
  - `customer.password_reset.completed`
  - `customer.password_reset.failed`
  - Source: `backend/app/Modules/Auth/Services/PasswordResetService.php`.
- RBAC assignment audit event for customer registration and seeded super-admin assignment:
  - `rbac.role.assigned`
  - Sources: `backend/app/Modules/Auth/Repositories/CustomerAuthRepository.php`, `backend/database/seeders/DatabaseSeeder.php`.
- Access-denied audit events for admin/customer boundary middleware and authorization exceptions:
  - `auth.access_denied`
  - Sources: `backend/app/Modules/Auth/Middleware/EnsureUserIsAdmin.php`, `backend/app/Modules/Auth/Middleware/EnsureCustomerApiToken.php`, `backend/bootstrap/app.php`.
- Feature tests for audit table creation, admin login success/failure audit, customer register/login/logout audit, password reset request/failure/completion audit, access-denied audit, and secret metadata redaction: `backend/tests/Feature/AuditEventTest.php`.

**Verification:** `php artisan test` passed with 54 tests and 238 assertions.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.9 Rate Limiting Around Auth Endpoints

**Covers:**

- Configurable auth rate limit settings for login, registration, password reset, and authenticated session actions: `backend/config/auth_rate_limits.php`.
- Named Laravel rate limiters:
  - `auth.login`
  - `auth.register`
  - `auth.password-reset`
  - `auth.session`
  - Source: `backend/app/Providers/AppServiceProvider.php`.
- Admin login throttling by normalized email and IP: `POST /admin/login` in `backend/app/Modules/Auth/routes/web.php`.
- Admin password reset throttling by normalized email and IP: `POST /admin/forgot-password`, `POST /admin/reset-password` in `backend/app/Modules/Auth/routes/web.php`.
- Admin logout/session action throttling by authenticated user and IP: `POST /admin/logout` in `backend/app/Modules/Auth/routes/web.php`.
- Customer registration throttling by IP: `POST /api/v1/auth/customer/register` in `backend/app/Modules/Auth/routes/api.php`.
- Customer login throttling by normalized email and IP: `POST /api/v1/auth/customer/login` in `backend/app/Modules/Auth/routes/api.php`.
- Customer password reset throttling by normalized email and IP: `POST /api/v1/auth/customer/password/forgot`, `POST /api/v1/auth/customer/password/reset` in `backend/app/Modules/Auth/routes/api.php`.
- Customer authenticated session action throttling by authenticated user and IP: `/api/v1/auth/customer/me`, `/logout`, `/logout-all` in `backend/app/Modules/Auth/routes/api.php`.
- Standard API throttle response shape remains `429` with `code: too_many_requests`: `backend/bootstrap/app.php`.
- Auth throttle events are audited as `auth.rate_limited`: `backend/bootstrap/app.php`.
- Feature tests for customer login throttling, admin login throttling, registration throttling, password reset throttling, and authenticated session throttling: `backend/tests/Feature/AuthRateLimitingTest.php`.

**Verification:** `php artisan test tests\Feature\AuthRateLimitingTest.php` passed.

**Not covered yet:** none for B2 auth foundation; continue with deeper module-specific authorization tests as each new module is added.

## B2.10 Authorization Boundary Verification Across Modules

**Covers:**

- Route-level verification that public customer auth API endpoints remain public but throttled: `backend/tests/Feature/AuthorizationBoundaryTest.php`.
- Route-level verification that protected customer auth endpoints require `auth:sanctum`, `customer.token`, and auth-session throttling.
- Route-level verification that admin dashboard and admin user management require web auth, active admin boundary, and action permissions.
- Route-level verification that admin auth/password forms remain guest-accessible and do not require admin middleware.
- Route-level verification that user API routes require Sanctum auth, active admin boundary, and action-specific permissions:
  - `users.view`
  - `users.create`
  - `users.update`
  - `users.delete`
- Behavioral verification that a customer token cannot access admin/user module routes.
- Behavioral verification that an admin token cannot access customer-token-only routes.
- Behavioral verification that a manager can read user API data but cannot create users.
- Behavioral verification that `super_admin` can cross user API action boundaries through the configured gate bypass.
- Boundary tests are centralized in `backend/tests/Feature/AuthorizationBoundaryTest.php` so future modules can add their route/action expectations without weakening existing auth guarantees.

**Verification:** `php artisan test tests\Feature\AuthorizationBoundaryTest.php` passed.

**Not covered yet:** module-specific authorization boundary tests for future modules that do not exist yet.

## B3.1 Store Profile Settings

**Covers:**

- Database-backed store settings table using the existing `store.settings` conventions: `backend/database/migrations/2026_04_27_050000_create_store_settings_table.php`.
- Store setting model for persisted key/value settings: `backend/app/Models/StoreSetting.php`.
- Store setting repository contract and implementation for database access: `backend/app/Modules/Setting/Repositories/Contracts/StoreSettingRepositoryInterface.php`, `backend/app/Modules/Setting/Repositories/StoreSettingRepository.php`.
- Settings module registration through the modular provider and route loader: `backend/config/modules.php`, `backend/app/Modules/Setting/Providers/SettingServiceProvider.php`.
- Cached store profile service with config fallback defaults and cache refresh on update: `backend/app/Modules/Setting/Services/StoreProfileService.php`.
- Store profile fields for name, legal name, support email/phone, address, country, currency, timezone, locale, and website URL.
- Admin web settings screen: `GET /admin/settings/store-profile`.
- Admin web settings update returns JSON and is submitted with jQuery AJAX: `PUT /admin/settings/store-profile`.
- Admin API settings read/update:
  - `GET /api/v1/settings/store-profile`
  - `PUT /api/v1/settings/store-profile`
- Store profile validation with normalized country/currency codes: `backend/app/Modules/Setting/Requests/UpdateStoreProfileRequest.php`.
- Store profile API resource: `backend/app/Modules/Setting/Resources/StoreProfileResource.php`.
- RBAC permissions:
  - `settings.view`
  - `settings.update`
- Admin role can view and update settings; manager role can view but not update settings: `backend/config/rbac.php`.
- Admin sidebar and top navigation link for settings users: `backend/resources/views/admin/layouts/app.blade.php`.
- Audit event for profile updates: `store_profile.updated`.
- Feature tests for table creation, config defaults, admin page access, profile update persistence, audit logging, cache refresh, API boundaries, and route middleware boundaries: `backend/tests/Feature/StoreProfileSettingsTest.php`.

**Verification:** `php artisan test tests\Feature\StoreProfileSettingsTest.php` passed.

**Not covered yet:** other later settings areas such as payment, shipping, tax, and notification settings.

## B3.2 Branding Settings

**Covers:**

- Branding fields added to store settings:
  - `primary_color`
  - `secondary_color`
  - `logo_path`
  - `logo_dark_path`
  - `favicon_path`
  - `social_share_image_path`
- Branding defaults with fallback values: `backend/app/Modules/Setting/Services/StoreProfileService.php`.
- Branding validation rules for hex colors and image uploads: `backend/app/Modules/Setting/Requests/UpdateStoreProfileRequest.php`.
- Admin branding controls inside the store profile settings screen: `backend/app/Modules/Setting/views/store-profile.blade.php`.
- Branding upload handling to the public disk under `branding`: `backend/app/Modules/Setting/Controllers/StoreProfileController.php`.
- API response includes branding values: `backend/app/Modules/Setting/Resources/StoreProfileResource.php`.
- Branding changes are persisted through the shared store settings repository and audited as part of `store_profile.updated`.

**Verification:** covered by `php artisan test tests\Feature\StoreProfileSettingsTest.php`.

**Not covered yet:** a dedicated `BrandingService`, public-safe branding endpoint, old asset cleanup when replacing uploads, and focused upload tests using `Storage::fake('public')`.

## B3.3 SEO Defaults

**Covers:**

- SEO default fields added to store settings:
  - `seo_title_template`
  - `seo_meta_description`
  - `seo_meta_keywords`
  - `seo_robots`
  - `seo_canonical_base_url`
  - `seo_og_image_path`
- SEO defaults with config/store fallback values: `backend/app/Modules/Setting/Services/StoreProfileService.php`.
- SEO validation rules for robots directives, canonical URL, text lengths, and OG image upload: `backend/app/Modules/Setting/Requests/UpdateStoreProfileRequest.php`.
- Admin SEO defaults controls inside the store profile settings screen: `backend/app/Modules/Setting/views/store-profile.blade.php`.
- API response includes SEO defaults: `backend/app/Modules/Setting/Resources/StoreProfileResource.php`.
- SEO updates are persisted through `store_settings` and cache refreshes through `StoreProfileService`.

**Verification:** covered by `php artisan test tests\Feature\StoreProfileSettingsTest.php`.

**Not covered yet:** a dedicated `SeoDefaultsService`, frontend/storefront meta rendering, sitemap/robots output, and per-product/category SEO override support.

## B3.4 Policy Page Settings

**Covers:**

- Policy page setting fields:
  - `privacy_policy_title`
  - `privacy_policy_content`
  - `terms_conditions_title`
  - `terms_conditions_content`
  - `refund_policy_title`
  - `refund_policy_content`
  - `shipping_policy_title`
  - `shipping_policy_content`
- Default policy titles: `backend/app/Modules/Setting/Services/StoreProfileService.php`.
- Policy title/content validation rules: `backend/app/Modules/Setting/Requests/UpdateStoreProfileRequest.php`.
- Admin policy page controls inside the store profile settings screen: `backend/app/Modules/Setting/views/store-profile.blade.php`.
- API response includes policy page settings: `backend/app/Modules/Setting/Resources/StoreProfileResource.php`.
- Policy page settings persist through the shared `store_settings` table and are cached with the store profile settings.

**Verification:** covered by `php artisan test tests\Feature\StoreProfileSettingsTest.php`.

**Not covered yet:** storefront public policy routes/pages, rich text sanitization, and a dedicated `PolicyPageSettingsService`.

## B3.5 Basic Module Toggles

**Covers:**

- Module toggle service with defaults for catalog, inventory, checkout, coupons, reviews, blog, and category modules: `backend/app/Modules/Setting/Services/ModuleToggleService.php`.
- Module toggle persistence using `store.module.{module}_enabled` keys in `store_settings`.
- Separate cache key for module toggles: `store.settings.modules`.
- Admin module toggle screen:
  - `GET /admin/settings/module-toggles`
  - `PUT /admin/settings/module-toggles` through jQuery AJAX with JSON response
  - Sources: `backend/app/Modules/Setting/Controllers/ModuleToggleController.php`, `backend/app/Modules/Setting/routes/web.php`, `backend/app/Modules/Setting/views/module-toggles.blade.php`.
- Admin API module toggle endpoints:
  - `GET /api/v1/settings/module-toggles`
  - `PUT /api/v1/settings/module-toggles`
  - Sources: `backend/app/Modules/Setting/routes/api.php`, `backend/app/Modules/Setting/Resources/ModuleToggleResource.php`.
- RBAC permissions:
  - `modules.view`
  - `modules.update`
  - Source: `backend/config/rbac.php`.
- Admin navigation links for module toggles: `backend/resources/views/admin/layouts/app.blade.php`, `backend/resources/views/admin/layouts/sidebar.blade.php`.
- Audit event for module toggle changes: `module_toggles.updated`.
- Feature tests for admin view/update, manager view-only boundary, API update/cache refresh, and route middleware: `backend/tests/Feature/ModuleToggleSettingsTest.php`.

**Verification:** `php artisan test tests\Feature\ModuleToggleSettingsTest.php` passed.

**Not covered yet:** enforcement middleware that actually disables module routes/features when a toggle is off, and partial-update semantics for API payloads.

## B3.6 Business Configuration Storage

**Covers:**

- Shared database-backed business configuration table: `backend/database/migrations/2026_04_27_050000_create_store_settings_table.php`.
- Store setting model for persisted settings: `backend/app/Models/StoreSetting.php`.
- Key/value storage conventions:
  - unique `key`
  - `group`
  - JSON `value`
  - `type`
  - `is_public`
- Repository contract and implementation for settings reads/upserts: `backend/app/Modules/Setting/Repositories/Contracts/StoreSettingRepositoryInterface.php`, `backend/app/Modules/Setting/Repositories/StoreSettingRepository.php`.
- Repository binding in the settings module provider: `backend/app/Modules/Setting/Providers/SettingServiceProvider.php`.
- Cached settings reads through services with config fallback defaults:
  - `backend/app/Modules/Setting/Services/StoreProfileService.php`
  - `backend/app/Modules/Setting/Services/ModuleToggleService.php`
- Cache invalidation after settings updates.
- Settings table and repository binding tests: `backend/tests/Feature/StoreProfileSettingsTest.php`.

**Verification:** covered by `php artisan test tests\Feature\StoreProfileSettingsTest.php tests\Feature\ModuleToggleSettingsTest.php`.

**Not covered yet:** a generic settings registry/schema layer, setting value encryption for secrets, and separate groups/services for payment, shipping, tax, and notification settings.

## B3.7 Settings Validation Rules

**Covers:**

- Store profile validation rules for identity, support contact, address, country, currency, timezone, locale, and website URL: `backend/app/Modules/Setting/Requests/UpdateStoreProfileRequest.php`.
- Branding validation rules for hex colors, stored asset paths, and uploaded images/files.
- SEO defaults validation rules for title template, meta fields, robots directives, canonical URL, and OG image upload.
- Policy page validation rules for title/content fields.
- Module toggle validation rules generated from the supported module list: `backend/app/Modules/Setting/Requests/UpdateModuleToggleRequest.php`.
- Normalization for country/currency/color values before validation.
- Validation is exercised by settings feature tests through successful web/API updates: `backend/tests/Feature/StoreProfileSettingsTest.php`, `backend/tests/Feature/ModuleToggleSettingsTest.php`.

**Verification:** covered by `php artisan test tests\Feature\StoreProfileSettingsTest.php tests\Feature\ModuleToggleSettingsTest.php`.

**Not covered yet:** explicit negative validation tests for invalid email, URL, timezone, color, robots value, upload type/size, and invalid module toggle payloads.

## B3.8 Audit Trail For Settings Changes

**Covers:**

- Store profile/settings updates are audited as `store_profile.updated`: `backend/app/Modules/Setting/Services/StoreProfileService.php`.
- Module toggle updates are audited as `module_toggles.updated`: `backend/app/Modules/Setting/Services/ModuleToggleService.php`.
- Audit events record the actor and changed keys through the shared audit logger.
- Settings audit assertions for profile updates: `backend/tests/Feature/StoreProfileSettingsTest.php`.
- Module toggle audit assertions: `backend/tests/Feature/ModuleToggleSettingsTest.php`.

**Verification:** covered by `php artisan test tests\Feature\StoreProfileSettingsTest.php tests\Feature\ModuleToggleSettingsTest.php`.

**Not covered yet:** old/new value snapshots for every settings change and a dedicated admin audit viewer.

## Admin Layout Refactor

**Covers:**

- Admin sidebar extracted from the main layout into `backend/resources/views/admin/layouts/sidebar.blade.php`.
- Main admin shell now includes the sidebar partial from `backend/resources/views/admin/layouts/app.blade.php`.
- Existing permission checks and active navigation states were preserved.

**Verification:** manual verification recommended after Blade view cache rebuild on Windows.

## B4.1 Category Entity And Relationships

**Covers:**

- Catalog module registration through the modular provider and route loader: `backend/config/modules.php`, `backend/app/Modules/Catalog/Providers/CatalogServiceProvider.php`.
- Category database entity with self-referencing parent relationship: `backend/database/migrations/2026_04_27_060000_create_categories_table.php`.
- Category model with parent and children relationships: `backend/app/Models/Category.php`.
- Category factory for tests: `backend/database/factories/CategoryFactory.php`.
- Category repository contract and implementation: `backend/app/Modules/Catalog/Repositories/Contracts/CategoryRepositoryInterface.php`, `backend/app/Modules/Catalog/Repositories/CategoryRepository.php`.
- Category service for create, update, delete, relationship validation, and duplicate slug guard: `backend/app/Modules/Catalog/Services/CategoryService.php`.
- Category request validation:
  - `backend/app/Modules/Catalog/Requests/StoreCategoryRequest.php`
  - `backend/app/Modules/Catalog/Requests/UpdateCategoryRequest.php`
- Category API resource with parent and children relationship output: `backend/app/Modules/Catalog/Resources/CategoryResource.php`.
- Admin category management routes:
  - `GET /admin/categories`
  - `GET /admin/categories/data`
  - `POST /admin/categories`
  - `GET /admin/categories/{id}`
  - `PUT /admin/categories/{id}`
  - `DELETE /admin/categories/{id}`
  - Source: `backend/app/Modules/Catalog/routes/web.php`.
- Admin category API routes:
  - `GET /api/v1/categories`
  - `POST /api/v1/categories`
  - `GET /api/v1/categories/{id}`
  - `PUT /api/v1/categories/{id}`
  - `DELETE /api/v1/categories/{id}`
  - Source: `backend/app/Modules/Catalog/routes/api.php`.
- Admin category management view uses jQuery AJAX for list, create, edit, and delete operations:
  - `backend/app/Modules/Catalog/views/categories/manage.blade.php`
- RBAC permissions:
  - `categories.view`
  - `categories.create`
  - `categories.update`
  - `categories.delete`
  - Source: `backend/config/rbac.php`.
- Admin role can manage categories; manager role can view categories only.
- Admin sidebar category navigation: `backend/resources/views/admin/layouts/sidebar.blade.php`.
- Feature tests for schema, parent/child relationships, admin AJAX CRUD endpoints, API relationship output, manager mutation denial, and route permission boundaries: `backend/tests/Feature/CategoryDomainTest.php`.

**Verification:** `php artisan test tests\Feature\CategoryDomainTest.php` passed.

**Not covered yet:** advanced hierarchy depth rules, drag/drop ordering, product-category relationships, automated slug generation strategy beyond request normalization, SEO metadata, and storefront catalog visibility rules.

## B4.3 Product Entity

**Covers:**

- Product database entity: `backend/database/migrations/2026_04_27_061000_create_products_table.php`.
- Product model with categories, variants, and images relationships: `backend/app/Models/Product.php`.
- Product factory for tests: `backend/database/factories/ProductFactory.php`.
- Product repository contract and implementation: `backend/app/Modules/Catalog/Repositories/Contracts/ProductRepositoryInterface.php`, `backend/app/Modules/Catalog/Repositories/ProductRepository.php`.
- Product service for create, update, delete, relation syncing, and duplicate slug guard: `backend/app/Modules/Catalog/Services/ProductService.php`.
- Product validation requests: `backend/app/Modules/Catalog/Requests/StoreProductRequest.php`, `backend/app/Modules/Catalog/Requests/UpdateProductRequest.php`.
- Product API resource: `backend/app/Modules/Catalog/Resources/ProductResource.php`.
- Product admin jQuery AJAX page: `backend/app/Modules/Catalog/views/products/manage.blade.php`.
- Admin product JSON endpoints under `/admin/products/*` for session-authenticated AJAX usage: `backend/app/Modules/Catalog/routes/web.php`.
- Admin API product endpoints under `/api/v1/products/*`: `backend/app/Modules/Catalog/routes/api.php`.
- RBAC permissions:
  - `products.view`
  - `products.create`
  - `products.update`
  - `products.delete`
  - Source: `backend/config/rbac.php`.
- Product navigation in admin sidebar: `backend/resources/views/admin/layouts/sidebar.blade.php`.
- Feature tests for product schema, relationships, admin AJAX endpoints, API endpoints, authorization boundaries, and manager view-only access: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** `php artisan test tests\Feature\ProductDomainTest.php` passed.

**Not covered yet:** product SEO metadata, stock/inventory integration, pricing rules beyond base variant price, and storefront product listing/detail pages.

## B4.4 Product Variants

**Covers:**

- Product variant database entity: `backend/database/migrations/2026_04_27_061100_create_product_variants_table.php`.
- Product variant model: `backend/app/Models/ProductVariant.php`.
- Product-to-variant relationship on `Product`.
- Variant fields:
  - `name`
  - `sku`
  - `price`
  - `compare_at_price`
  - `sort_order`
  - `is_default`
  - `status`
- Variant validation inside product create/update requests.
- Variant syncing in `ProductService`.
- SKU uniqueness guard across products: `backend/app/Modules/Catalog/Services/ProductService.php`, `backend/app/Modules/Catalog/Repositories/ProductRepository.php`.
- Product API output includes variants through `backend/app/Modules/Catalog/Resources/ProductVariantResource.php`.
- Admin AJAX product form supports one default variant in the initial UI.
- Feature tests prove variant creation, update, response output, and duplicate SKU rejection: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** `php artisan test tests\Feature\ProductDomainTest.php` passed.

**Not covered yet:** multi-option variant matrix generation, variant option labels such as size/color, inventory per variant, and richer admin UI for adding/removing multiple variants dynamically.

## B4.5 Product Images/Media

**Covers:**

- Product image database entity: `backend/database/migrations/2026_04_27_061200_create_product_images_table.php`.
- Product image model: `backend/app/Models/ProductImage.php`.
- Product-to-image relationship on `Product`.
- Image fields:
  - `path`
  - `alt_text`
  - `sort_order`
  - `is_primary`
- Image validation inside product create/update requests.
- Image syncing in `ProductService`.
- Product API output includes images through `backend/app/Modules/Catalog/Resources/ProductImageResource.php`.
- Admin AJAX product form supports one primary image path in the initial UI.
- Feature tests prove image creation, update, response output, and publish dependency: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** `php artisan test tests\Feature\ProductDomainTest.php` passed.

**Not covered yet:** actual file upload handling, media library integration, image deletion/cleanup, gallery reordering UI, and image transformations/thumbnails.

## B4.6 Product Publish/Unpublish Rules

**Covers:**

- Product status field supports draft, unpublished, and published states: `backend/database/migrations/2026_04_27_061000_create_products_table.php`.
- Product `published_at` timestamp.
- Create/update requests only allow draft/unpublished; publishing is handled through explicit publish action.
- Publish rule: product must have at least one variant.
- Publish rule: product must have at least one image.
- Publish action:
  - `POST /admin/products/{id}/publish`
  - `POST /api/v1/products/{id}/publish`
- Unpublish action:
  - `POST /admin/products/{id}/unpublish`
  - `POST /api/v1/products/{id}/unpublish`
- RBAC permission: `products.publish`.
- Admin AJAX product list has publish/unpublish buttons.
- Feature tests prove publish failure without images and successful publish/unpublish flow: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** `php artisan test tests\Feature\ProductDomainTest.php` passed.

**Not covered yet:** scheduled publishing, approval workflow, product visibility windows, and customer-facing visibility enforcement.

## B4.7 Slug Generation And Uniqueness

**Covers:**

- Product create/update requests normalize product slugs from the supplied slug or product name: `backend/app/Modules/Catalog/Requests/StoreProductRequest.php`.
- Empty/generated-invalid slugs fall back to `product`.
- Product service assigns unique slugs during create/update: `backend/app/Modules/Catalog/Services/ProductService.php`.
- Duplicate product slugs receive a numeric suffix such as `herbal-face-wash-2`.
- Updating a product can keep its existing slug without colliding with itself.
- Database-level product slug uniqueness remains enforced by the `products.slug` unique index: `backend/database/migrations/2026_04_27_061000_create_products_table.php`.
- Feature tests prove generated slugs and duplicate suffixing: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** `php artisan test tests\Feature\ProductDomainTest.php` passed.

**Not covered yet:** category slug auto-suffixing and storefront canonical URL generation.

## B4.8 SKU Uniqueness And Validation

**Covers:**

- Product variant SKU validation requires every variant to include a SKU: `backend/app/Modules/Catalog/Requests/StoreProductRequest.php`.
- SKU values are trimmed and normalized to uppercase before persistence.
- Product variant database SKU uniqueness is enforced by the `product_variants.sku` unique index: `backend/database/migrations/2026_04_27_061100_create_product_variants_table.php`.
- Product service rejects duplicate SKUs in the same product payload: `backend/app/Modules/Catalog/Services/ProductService.php`.
- Product service rejects duplicate SKUs already used by another product, including case-insensitive matches through repository lookup: `backend/app/Modules/Catalog/Repositories/ProductRepository.php`.
- Product update ignores the product's own existing SKUs while still preventing collisions with other products.
- Variant normalization ensures only one variant is marked as default.
- Feature tests prove SKU normalization, duplicate rejection, and default variant normalization: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** `php artisan test tests\Feature\ProductDomainTest.php` passed.

**Not covered yet:** merchant-configurable SKU format patterns, SKU auto-generation, and inventory-reservation rules around SKU changes.

## B4.10 Catalog Status And Visibility Rules

**Covers:**

- Product storefront visibility rule through `Product::visibleToStorefront()`: `backend/app/Models/Product.php`.
- Public catalog only shows products that are:
  - `published`
  - have `published_at`
  - have at least one active variant
  - have at least one image
  - have no category or at least one active category
- Category active scope: `backend/app/Models/Category.php`.
- Variant active scope: `backend/app/Models/ProductVariant.php`.
- Public storefront catalog endpoints:
  - `GET /api/v1/catalog/products`
  - `GET /api/v1/catalog/products/{slug}`
  - Source: `backend/app/Modules/Catalog/Controllers/PublicCatalogController.php`, `backend/app/Modules/Catalog/routes/api.php`.
- Public catalog responses only load active categories and active variants.
- Admin product endpoints remain unchanged and can still see draft, unpublished, and hidden products.
- Feature tests prove public catalog hides draft products, products without images, products without active variants, and products without an active category: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** `php artisan test tests\Feature\ProductDomainTest.php` passed.

**Not covered yet:** product visibility windows, scheduled publishing, inventory-aware visibility, customer group/channel visibility, and storefront category listing endpoints.

## B5.1 Stock Model Design

**Covers:**

- Inventory stock table with one stock row per product variant: `backend/database/migrations/2026_04_28_070000_create_inventory_stocks_table.php`.
- Inventory stock model: `backend/app/Models/InventoryStock.php`.
- Product variant stock relationship: `$variant->stock` in `backend/app/Models/ProductVariant.php`.
- Stock fields:
  - `product_variant_id`
  - `quantity_on_hand`
  - `quantity_reserved`
  - `low_stock_threshold`
  - `track_inventory`
  - `allow_backorder`
- Computed model attributes:
  - `available_quantity`
  - `is_low_stock`
- Database uniqueness guarantees one stock row per variant.
- Feature tests for schema, relationships, available quantity, low-stock detection, and uniqueness: `backend/tests/Feature/InventoryStockModelTest.php`.

**Verification:** covered by `php artisan test tests\Feature\InventoryStockModelTest.php`.

**Not covered yet:** admin inventory screen, multi-warehouse stock, and order-driven stock reduction.

## B5.2 Inventory Transaction Log

**Covers:**

- Inventory transaction table: `backend/database/migrations/2026_04_28_071000_create_inventory_transactions_table.php`.
- Inventory transaction model: `backend/app/Models/InventoryTransaction.php`.
- Transaction relationships:
  - `$stock->transactions`
  - `$variant->inventoryTransactions`
  - `$transaction->stock`
  - `$transaction->variant`
  - `$transaction->actor`
  - `$transaction->reference`
- Transaction fields:
  - `inventory_stock_id`
  - `product_variant_id`
  - `actor_id`
  - `type`
  - `quantity_delta`
  - `quantity_on_hand_after`
  - `quantity_reserved_after`
  - `reference_type`
  - `reference_id`
  - `note`
  - `metadata`
- Feature tests for schema, relationships, metadata casting, actor relation, and negative movement logging: `backend/tests/Feature/InventoryTransactionLogTest.php`.

**Verification:** covered by `php artisan test tests\Feature\InventoryTransactionLogTest.php`.

**Not covered yet:** admin transaction history viewer and order/reference integration.

## B5.3 Stock Adjustment Workflow

**Covers:**

- Inventory module routes registered through `backend/config/modules.php`.
- Stock adjustment service operation: `backend/app/Modules/Inventory/Services/InventoryStockService.php`.
- Admin/API controller action: `backend/app/Modules/Inventory/Controllers/InventoryStockController.php`.
- Adjustment request validation: `backend/app/Modules/Inventory/Requests/AdjustStockRequest.php`.
- Stock response resource: `backend/app/Modules/Inventory/Resources/InventoryStockResource.php`.
- Admin web endpoint:
  - `POST /admin/inventory/variants/{variantId}/adjust`
- Admin API endpoint:
  - `POST /api/v1/inventory/variants/{variantId}/adjust`
- Adjustment behavior:
  - Positive `quantity_delta` increases `quantity_on_hand`.
  - Negative `quantity_delta` decreases `quantity_on_hand`.
  - Stock cannot go below zero.
  - Stock cannot go below reserved quantity unless backorder is allowed.
  - Every adjustment creates an `inventory_transactions` row with type `adjustment`.
- RBAC permission: `inventory.adjust`.
- Feature tests for successful adjustment, transaction logging, API adjustment, validation failure, and manager mutation denial: `backend/tests/Feature/InventoryWorkflowTest.php`.

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** admin AJAX inventory page and stock adjustment UI.

## B5.4 Stock Reservation Workflow

**Covers:**

- Stock reservation service operation: `backend/app/Modules/Inventory/Services/InventoryStockService.php`.
- Admin/API controller action: `backend/app/Modules/Inventory/Controllers/InventoryStockController.php`.
- Reservation request validation: `backend/app/Modules/Inventory/Requests/ReserveStockRequest.php`.
- Admin web endpoint:
  - `POST /admin/inventory/variants/{variantId}/reserve`
- Admin API endpoint:
  - `POST /api/v1/inventory/variants/{variantId}/reserve`
- Reservation behavior:
  - Increases `quantity_reserved`.
  - Checks `available_quantity` before reserving when inventory tracking is on and backorder is off.
  - Allows reservation beyond stock only when `allow_backorder` is true or tracking is disabled.
  - Every reservation creates an `inventory_transactions` row with type `reservation`.
- RBAC permission: `inventory.reserve`.
- Feature tests for successful reservation, transaction logging, insufficient-stock validation, and manager mutation denial: `backend/tests/Feature/InventoryWorkflowTest.php`.

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** cart/checkout integration and reservation expiry.

## B5.5 Stock Release Workflow

**Covers:**

- Stock release service operation: `backend/app/Modules/Inventory/Services/InventoryStockService.php`.
- Admin/API controller action: `backend/app/Modules/Inventory/Controllers/InventoryStockController.php`.
- Release request validation: `backend/app/Modules/Inventory/Requests/ReleaseStockRequest.php`.
- Admin web endpoint:
  - `POST /admin/inventory/variants/{variantId}/release`
- Admin API endpoint:
  - `POST /api/v1/inventory/variants/{variantId}/release`
- Release behavior:
  - Reduces `quantity_reserved`.
  - Blocks releasing more than currently reserved.
  - Every release creates an `inventory_transactions` row with type `release`.
- RBAC permission: `inventory.release`.
- Feature tests for successful release, transaction logging, validation failure, API release, and manager mutation denial: `backend/tests/Feature/InventoryWorkflowTest.php`.

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** automatic release from cancelled/expired checkout or order flows.

## B5.6 Low Stock Rule Support

**Covers:**

- Low-stock threshold field on inventory stock: `low_stock_threshold`.
- Low-stock computed model attribute: `InventoryStock::is_low_stock`.
- Available quantity is used for low-stock detection: `quantity_on_hand - quantity_reserved`.
- Low-stock values are returned in inventory stock API resources:
  - `low_stock_threshold`
  - `is_low_stock`
- Public catalog variant stock output includes low-stock status when stock is loaded.
- Feature tests for low-stock detection: `backend/tests/Feature/InventoryStockModelTest.php`.

**Verification:** covered by `php artisan test tests\Feature\InventoryStockModelTest.php`.

**Not covered yet:** low-stock admin report, dashboard panel, alert emails, and scheduled notifications.

## B5.7 Concurrency Protection For Stock Mutation

**Covers:**

- Shared transaction helper supports retry attempts: `backend/app/Core/Services/BaseService.php`.
- Inventory adjustment, reservation, and release run inside database transactions with `attempts: 3`: `backend/app/Modules/Inventory/Services/InventoryStockService.php`.
- Product variant row is locked before stock read/create to protect the missing-stock-row case.
- Inventory stock row is locked with `lockForUpdate()` before mutation.
- Stock row creation during mutation remains inside the locked transaction.
- Feature test proves mutation can create a missing stock row safely and only one row exists afterward: `backend/tests/Feature/InventoryWorkflowTest.php`.

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** true parallel process stress tests outside Laravel feature tests.

## B5.8 Inventory Visibility Rules By Product And Variant

**Covers:**

- Variant storefront visibility scope: `ProductVariant::visibleToStorefront()` in `backend/app/Models/ProductVariant.php`.
- Product storefront visibility now requires at least one inventory-visible variant: `Product::visibleToStorefront()` in `backend/app/Models/Product.php`.
- Variant is storefront-visible when:
  - variant status is `active`, and
  - no stock row exists yet, or
  - inventory tracking is disabled, or
  - backorder is allowed, or
  - `quantity_on_hand > quantity_reserved`.
- Public catalog endpoints load only storefront-visible variants and their stock:
  - `GET /api/v1/catalog/products`
  - `GET /api/v1/catalog/products/{slug}`
  - Source: `backend/app/Modules/Catalog/Controllers/PublicCatalogController.php`.
- Public variant resource includes stock visibility data when stock is loaded:
  - `quantity_on_hand`
  - `quantity_reserved`
  - `available_quantity`
  - `low_stock_threshold`
  - `is_low_stock`
  - `track_inventory`
  - `allow_backorder`
- Feature tests prove out-of-stock products are hidden, backorder/untracked products remain visible, and stock data appears in public catalog responses: `backend/tests/Feature/ProductDomainTest.php`.

**Verification:** covered by `php artisan test tests\Feature\ProductDomainTest.php`.

**Not covered yet:** admin inventory UI, storefront UI components, cart stock validation, checkout reservation integration, and configurable out-of-stock display modes.

## B6.1 Customer Profile Domain

**Covers:**

- Customer API module registration through `backend/config/modules.php`.
- Customer profile controller: `backend/app/Modules/Customer/Controllers/CustomerProfileController.php`.
- Customer profile update validation: `backend/app/Modules/Customer/Requests/UpdateCustomerProfileRequest.php`.
- Customer profile resource: `backend/app/Modules/Customer/Resources/CustomerProfileResource.php`.
- Customer profile endpoints:
  - `GET /api/v1/customer/profile`
  - `PUT /api/v1/customer/profile`
  - Source: `backend/app/Modules/Customer/routes/api.php`.
- Profile fields currently use the customer user record:
  - `name`
  - `email`
  - `phone`
  - `status`
- Email uniqueness validation ignores the current customer.
- Routes require `auth:sanctum`, `customer.token`, and `throttle:auth.session`.
- Feature tests for profile view, update, duplicate email rejection, and route middleware boundaries: `backend/tests/Feature/CustomerProfileAddressTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CustomerProfileAddressTest.php`.

**Not covered yet:** separate customer profile table, avatar upload, date of birth, gender, marketing preferences, and storefront UI.

## B6.2 Customer Address Domain

**Covers:**

- Customer address table: `backend/database/migrations/2026_04_28_080000_create_customer_addresses_table.php`.
- Customer address model: `backend/app/Models/CustomerAddress.php`.
- Customer-to-address relationship: `$user->customerAddresses()` in `backend/app/Models/User.php`.
- Customer address controller: `backend/app/Modules/Customer/Controllers/CustomerAddressController.php`.
- Customer address service for create/update and default-address handling: `backend/app/Modules/Customer/Services/CustomerAddressService.php`.
- Customer address validation:
  - `backend/app/Modules/Customer/Requests/StoreCustomerAddressRequest.php`
  - `backend/app/Modules/Customer/Requests/UpdateCustomerAddressRequest.php`
- Customer address resource: `backend/app/Modules/Customer/Resources/CustomerAddressResource.php`.
- Customer address fields:
  - `label`
  - `recipient_name`
  - `phone`
  - `address_line_1`
  - `address_line_2`
  - `city`
  - `state`
  - `postal_code`
  - `country`
  - `is_default_shipping`
  - `is_default_billing`
- Customer address endpoints:
  - `GET /api/v1/customer/addresses`
  - `POST /api/v1/customer/addresses`
  - `GET /api/v1/customer/addresses/{id}`
  - `PUT /api/v1/customer/addresses/{id}`
  - `DELETE /api/v1/customer/addresses/{id}`
  - Source: `backend/app/Modules/Customer/routes/api.php`.
- Ownership boundary: customers can only read/update/delete their own addresses.
- Default shipping and billing flags are unique per customer through service-level clearing.
- Routes require `auth:sanctum`, `customer.token`, and `throttle:auth.session`.
- Feature tests for schema, relationship, CRUD, default uniqueness, ownership denial, and route middleware boundaries: `backend/tests/Feature/CustomerProfileAddressTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CustomerProfileAddressTest.php`.

**Not covered yet:** admin customer address management, address verification, delivery zone validation, and checkout address selection UI.

## B6.3 Customer Order Linkage

**Covers:**

- Order-to-customer linkage through `orders.user_id`.
- Customer-to-order relationship: `$user->orders()` in `backend/app/Models/User.php`.
- Customer self-service order history controller: `backend/app/Modules/Customer/Controllers/CustomerOrderController.php`.
- Customer order history endpoints:
  - `GET /api/v1/customer/orders`
  - `GET /api/v1/customer/orders/{id}`
  - Source: `backend/app/Modules/Customer/routes/api.php`.
- Customer order responses reuse the checkout order resource so items, payments, totals, addresses, and lifecycle fields stay consistent.
- Ownership boundary: customers can only see their own orders.
- Admin support customer profile includes recent linked orders and `orders_count`.
- Feature tests for customer order history ownership and support order linkage: `backend/tests/Feature/CustomerSupportTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** storefront order tracking screen and advanced customer-facing shipment timeline.

## B6.4 Customer Tags/Notes Support

**Covers:**

- Customer note table: `backend/database/migrations/2026_04_29_120000_create_customer_notes_table.php`.
- Customer tag table: `backend/database/migrations/2026_04_29_121000_create_customer_tags_table.php`.
- Customer note model: `backend/app/Models/CustomerNote.php`.
- Customer tag model: `backend/app/Models/CustomerTag.php`.
- User relationships:
  - `$user->customerNotes()`
  - `$user->customerTags()`
- Admin customer support controller: `backend/app/Modules/Customer/Controllers/CustomerSupportController.php`.
- Simple repository/service flow:
  - `backend/app/Modules/Customer/Repositories/CustomerSupportRepository.php`
  - `backend/app/Modules/Customer/Services/CustomerSupportService.php`
- Validation requests:
  - `backend/app/Modules/Customer/Requests/StoreCustomerNoteRequest.php`
  - `backend/app/Modules/Customer/Requests/SyncCustomerTagsRequest.php`
- Resources:
  - `backend/app/Modules/Customer/Resources/CustomerNoteResource.php`
  - `backend/app/Modules/Customer/Resources/CustomerTagResource.php`
- Admin endpoints:
  - `POST /api/v1/customers/{id}/notes`
  - `PUT /api/v1/customers/{id}/tags`
- Permissions:
  - `customers.notes.create`
  - `customers.tags.update`
- Audit events:
  - `customer.note.created`
  - `customer.tags.synced`
- Feature tests for note creation, tag sync, replacement behavior, permissions, and audit events: `backend/tests/Feature/CustomerSupportTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** admin UI for notes/tags, tag suggestions, tag color metadata, and bulk tagging.

## B6.5 Customer Status Management

**Covers:**

- Customer status update validation: `backend/app/Modules/Customer/Requests/UpdateCustomerStatusRequest.php`.
- Allowed customer statuses:
  - `active`
  - `inactive`
  - `suspended`
- Admin endpoint:
  - `PATCH /api/v1/customers/{id}/status`
- Permission:
  - `customers.update`
- Status change audit event: `customer.status.updated`.
- Feature tests for status update and permission enforcement: `backend/tests/Feature/CustomerSupportTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** automatic login blocking rules for suspended customers and admin UI status controls.

## B6.6 Customer Support-Oriented Data Structure

**Covers:**

- Admin customer list endpoint:
  - `GET /api/v1/customers`
- Admin customer detail endpoint:
  - `GET /api/v1/customers/{id}`
- Support resource: `backend/app/Modules/Customer/Resources/CustomerSupportResource.php`.
- Support profile includes:
  - customer identity fields
  - status
  - `orders_count`
  - addresses
  - recent orders
  - internal notes
  - tags
- Admin/support permissions added to `backend/config/rbac.php`:
  - `customers.view`
  - `customers.update`
  - `customers.notes.create`
  - `customers.tags.update`
- Routes require `auth:sanctum`, `admin`, and per-action `can:*` permission checks.
- Feature tests for support profile payload and forbidden access without permission: `backend/tests/Feature/CustomerSupportTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** full admin customer management UI, support ticketing, customer segmentation reports, and advanced search/filtering.

## B7.1 Guest Cart Lifecycle

**Covers:**

- Cart module registration through `backend/config/modules.php`.
- Guest cart tables:
  - `backend/database/migrations/2026_04_28_090000_create_carts_table.php`
  - `backend/database/migrations/2026_04_28_091000_create_cart_items_table.php`
- Cart models:
  - `backend/app/Models/Cart.php`
  - `backend/app/Models/CartItem.php`
- Cart service: `backend/app/Modules/Cart/Services/CartService.php`.
- Cart repository: `backend/app/Modules/Cart/Repositories/CartRepository.php`.
- Guest cart controller: `backend/app/Modules/Cart/Controllers/GuestCartController.php`.
- Guest cart request validation:
  - `backend/app/Modules/Cart/Requests/StoreGuestCartItemRequest.php`
  - `backend/app/Modules/Cart/Requests/UpdateGuestCartItemRequest.php`
- Guest cart resources:
  - `backend/app/Modules/Cart/Resources/CartResource.php`
  - `backend/app/Modules/Cart/Resources/CartItemResource.php`
- Guest cart endpoints:
  - `POST /api/v1/cart/guest`
  - `GET /api/v1/cart/guest/{cartToken}`
  - `POST /api/v1/cart/guest/{cartToken}/items`
  - `PUT /api/v1/cart/guest/{cartToken}/items/{itemId}`
  - `DELETE /api/v1/cart/guest/{cartToken}/items/{itemId}`
  - `DELETE /api/v1/cart/guest/{cartToken}`
  - Source: `backend/app/Modules/Cart/routes/api.php`.
- Guest cart tokens expire after 30 days.
- Add/update/remove/clear item lifecycle.
- Published-catalog and inventory-aware validation before adding or updating an item.
- Route boundaries stay public, token-based, and rate limited.
- Feature tests for schema, create/load, add/update/remove/clear, hidden product rejection, unavailable stock rejection, expired token rejection, and route boundaries: `backend/tests/Feature/GuestCartLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\GuestCartLifecycleTest.php`.

**Not covered yet:** guest-to-customer merge after login, checkout stock reservation, tax/shipping/discount totals, unavailable item recovery UI, and storefront cart screens.

## B7.2 Customer Cart Lifecycle

**Covers:**

- Customer-to-cart relationship: `$user->carts()` in `backend/app/Models/User.php`.
- Cart service: `backend/app/Modules/Cart/Services/CartService.php`.
- Cart repository: `backend/app/Modules/Cart/Repositories/CartRepository.php`.
- Customer cart controller: `backend/app/Modules/Cart/Controllers/CustomerCartController.php`.
- Customer cart endpoints:
  - `GET /api/v1/customer/cart`
  - `POST /api/v1/customer/cart/items`
  - `PUT /api/v1/customer/cart/items/{itemId}`
  - `DELETE /api/v1/customer/cart/items/{itemId}`
  - `DELETE /api/v1/customer/cart`
  - Source: `backend/app/Modules/Cart/routes/api.php`.
- Customer cart routes require `auth:sanctum`, `customer.token`, and `throttle:auth.session`.
- Customer cart auto-creates on cart view or first add.
- Add/update/remove/clear item lifecycle for the authenticated customer's active cart.
- Published-catalog and inventory-aware validation before adding or updating an item.
- Ownership boundary: customers cannot update/remove another customer's cart item.
- Feature tests for create/load, item lifecycle, ownership boundary, unavailable stock rejection, token boundary, and route middleware: `backend/tests/Feature/CustomerCartLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CustomerCartLifecycleTest.php`.

**Not covered yet:** guest cart merge after login, multiple saved carts, abandoned cart recovery, checkout stock reservation, tax/shipping/discount totals, and storefront cart screens.

## B7.3 Cart Line Item Rules

**Covers:**

- One cart line per product variant.
- Adding the same variant increments the existing cart line instead of creating a duplicate row.
- Maximum quantity per cart line: `99`.
- Maximum distinct cart lines per cart: `50`.
- Product variant must still be storefront-sellable before add/update.
- Product variant price must be greater than zero before add/update.
- Inventory availability still applies before add/update.
- Cart item ownership remains enforced by querying items through the active guest/customer cart.
- Rules live in `backend/app/Modules/Cart/Services/CartService.php`.
- Cart line persistence lives in `backend/app/Modules/Cart/Repositories/CartRepository.php`.
- Request validation uses the same max line quantity constant:
  - `backend/app/Modules/Cart/Requests/StoreGuestCartItemRequest.php`
  - `backend/app/Modules/Cart/Requests/UpdateGuestCartItemRequest.php`
- Feature tests: `backend/tests/Feature/CartLineItemRulesTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CartLineItemRulesTest.php`.

**Not covered yet:** cart quantity update UX, price recalculation pipeline, discounts, tax, shipping, unavailable item recovery, and guest-to-customer cart merge.

## B7.4 Cart Quantity Update Rules

**Covers:**

- Cart item update uses exact quantity replacement, not increment behavior.
- Add item increments an existing cart line when the same variant is added again.
- Update item keeps one cart line per variant.
- Quantity must be at least `1`.
- Quantity must not exceed `CartService::MAX_LINE_QUANTITY` (`99`).
- Quantity must not exceed available stock when inventory is tracked and backorder is disabled.
- Quantity update is scoped to the active guest/customer cart, so another customer's item cannot be updated.
- Delete item remains the supported way to remove a line.
- Rules live in `backend/app/Modules/Cart/Services/CartService.php`.
- Validation lives in:
  - `backend/app/Modules/Cart/Requests/StoreGuestCartItemRequest.php`
  - `backend/app/Modules/Cart/Requests/UpdateGuestCartItemRequest.php`
- Feature tests: `backend/tests/Feature/CartQuantityAndPricingTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CartQuantityAndPricingTest.php`.

**Not covered yet:** storefront quantity stepper UI, bulk quantity update, and unavailable item recovery messaging.

## B7.5 Cart Price Recalculation Pipeline

**Covers:**

- Cart line `unit_price` and `line_total` are recalculated from the current product variant price.
- Cart item snapshots refresh current:
  - product name
  - variant name
  - SKU
  - unit price
  - line total
- Recalculation runs when:
  - guest cart is loaded
  - customer cart is loaded
  - item is added
  - item quantity is updated
- Recalculation is intentionally simple and currently covers subtotal only.
- Price refresh query lives in `backend/app/Modules/Cart/Repositories/CartRepository.php`.
- Recalculation workflow lives in `backend/app/Modules/Cart/Services/CartService.php`.
- Feature tests: `backend/tests/Feature/CartQuantityAndPricingTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CartQuantityAndPricingTest.php`.

**Not covered yet:** discounts, coupons, taxes, shipping charges, currency conversion, and price change warnings.

## B7.7 Unavailable Item Handling

**Covers:**

- Cart items keep availability status instead of being silently deleted.
- Cart item fields:
  - `is_available`
  - `unavailable_reason`
  - Migration: `backend/database/migrations/2026_04_28_092000_add_availability_fields_to_cart_items_table.php`.
- Cart load marks an item unavailable when:
  - variant was removed
  - variant is inactive
  - product is unpublished
  - price is not purchasable
  - stock is insufficient
- Unavailable lines remain visible in the API response.
- Cart totals only count available lines.
- API includes:
  - `unavailable_items_count`
  - item-level `is_available`
  - item-level `unavailable_reason`
- Item can recover automatically when the product/variant/stock becomes available again.
- Rules live in `backend/app/Modules/Cart/Services/CartService.php`.
- Persistence lives in `backend/app/Modules/Cart/Repositories/CartRepository.php`.
- Feature tests: `backend/tests/Feature/CartUnavailableAndRecoveryTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CartUnavailableAndRecoveryTest.php`.

**Not covered yet:** storefront warning UI, automatic quantity reduction, checkout blocking summary, and admin recovery tools.

## B7.8 Cart Persistence And Recovery Behavior

**Covers:**

- Guest carts persist by `cart_token`.
- Guest cart activity extends the recovery window to 30 days.
- Expired guest carts remain inaccessible.
- Customer carts persist by authenticated customer account.
- Customer cart load recovers the existing active cart instead of creating a duplicate.
- Cart recovery recalculates item availability and prices during load.
- Feature tests: `backend/tests/Feature/CartUnavailableAndRecoveryTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CartUnavailableAndRecoveryTest.php`.

**Not covered yet:** guest cart merge after login, abandoned cart reminders, cart restore emails, multi-device conflict resolution, and manual cart archive/restore.

## B8.1 Checkout Validation Pipeline

**Covers:**

- Checkout module registration through `backend/config/modules.php`.
- Checkout validation endpoint:
  - `POST /api/v1/checkout/validate`
  - Source: `backend/app/Modules/Checkout/routes/api.php`.
- Route requires `auth:sanctum`, `customer.token`, and `throttle:auth.session`.
- Checkout controller: `backend/app/Modules/Checkout/Controllers/CheckoutController.php`.
- Checkout service: `backend/app/Modules/Checkout/Services/CheckoutService.php`.
- Checkout repository: `backend/app/Modules/Checkout/Repositories/CheckoutRepository.php`.
- Checkout validation request: `backend/app/Modules/Checkout/Requests/CheckoutValidationRequest.php`.
- Checkout validation resource: `backend/app/Modules/Checkout/Resources/CheckoutValidationResource.php`.
- Validation checks:
  - customer must be authenticated with customer token
  - customer cart must contain at least one available item
  - cart must not contain unavailable items
  - selected addresses must belong to the current customer
  - inline address data must pass required address fields
- Returns checkout readiness, validation step statuses, cart summary, shipping address, and billing address.
- Feature tests: `backend/tests/Feature/CheckoutValidationAddressTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutValidationAddressTest.php`.

**Not covered yet:** shipping methods, payment methods, order creation, stock reservation, idempotency, rollback, and confirmation events.

## B8.2 Address Selection And Creation Flow

**Covers:**

- Existing customer shipping address selection using `shipping_address_id`.
- Existing customer billing address selection using `billing_address_id`.
- Billing address can default to shipping address with `billing_same_as_shipping`.
- Inline shipping address creation through `shipping_address`.
- Inline billing address creation through `billing_address`.
- Address creation reuses `backend/app/Modules/Customer/Services/CustomerAddressService.php`.
- Address ownership boundary: another customer's address cannot be used.
- New checkout-created addresses are saved to the customer's address book.
- Feature tests: `backend/tests/Feature/CheckoutValidationAddressTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutValidationAddressTest.php`.

**Not covered yet:** checkout-only unsaved address snapshots, delivery-zone validation, shipping rate lookup, and order address snapshot capture.

## B8.3 Shipping Resolution Flow

**Covers:**

- Checkout validation resolves a shipping method before order creation.
- Shipping configuration: `backend/config/checkout.php`.
- Supported MVP shipping methods:
  - `standard` delivery: `80.00`
  - `express` delivery: `150.00`
- Shipping methods can be enabled/disabled through config.
- Shipping methods can be limited by address country.
- Default shipping method is `standard`.
- Checkout response includes:
  - `shipping_method`
  - `totals.shipping_total`
  - `totals.grand_total`
- Validation rejects unknown, inactive, or unsupported-country shipping methods.
- Feature tests: `backend/tests/Feature/CheckoutShippingPaymentTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutShippingPaymentTest.php`.

**Not covered yet:** database-managed shipping methods, zones, weight rules, free-shipping rules, courier integration, and admin shipping UI.

## B8.4 Payment Method Resolution Flow

**Covers:**

- Checkout validation resolves a payment method before order creation.
- Payment configuration: `backend/config/checkout.php`.
- Supported MVP payment methods:
  - `cod`
  - `manual_bank`
- Payment methods can be enabled/disabled through config.
- Default payment method is `cod`.
- Checkout response includes `payment_method`.
- Validation rejects unknown or inactive payment methods.
- Feature tests: `backend/tests/Feature/CheckoutShippingPaymentTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutShippingPaymentTest.php`.

**Not covered yet:** payment provider integration, online payment authorization, payment fees, payment status records, and admin payment method UI.

## B8.5 Order Draft To Order Creation Transaction

**Covers:**

- Order tables:
  - `backend/database/migrations/2026_04_28_100000_create_orders_table.php`
  - `backend/database/migrations/2026_04_28_101000_create_order_items_table.php`
- Order models:
  - `backend/app/Models/Order.php`
  - `backend/app/Models/OrderItem.php`
- Customer-to-order relationship: `$user->orders()` in `backend/app/Models/User.php`.
- Checkout submit endpoint:
  - `POST /api/v1/checkout/submit`
  - Source: `backend/app/Modules/Checkout/routes/api.php`.
- Submit request validation: `backend/app/Modules/Checkout/Requests/CheckoutSubmitRequest.php`.
- Order resources:
  - `backend/app/Modules/Checkout/Resources/OrderResource.php`
  - `backend/app/Modules/Checkout/Resources/OrderItemResource.php`.
- Submit flow reuses checkout validation, address resolution, shipping resolution, and payment resolution.
- Order creation stores:
  - customer
  - cart
  - idempotency key
  - status
  - payment status
  - fulfillment status
  - shipping method
  - payment method
  - totals
  - address snapshots
  - placed timestamp
- Cart is marked `completed` after successful order creation.
- Inventory is reserved during checkout submit.
- Feature tests: `backend/tests/Feature/CheckoutOrderCreationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutOrderCreationTest.php`.

**Not covered yet:** payment authorization, admin order UI, and customer order history endpoint.

## B8.6 Order Item Snapshot Capture

**Covers:**

- Order items are copied from available cart lines at order creation.
- Snapshot fields include:
  - product ID
  - product variant ID
  - product name
  - variant name
  - SKU
  - quantity
  - unit price
  - line total
  - source cart item ID
- Product changes after checkout do not change existing order item snapshots.
- Feature tests: `backend/tests/Feature/CheckoutOrderCreationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutOrderCreationTest.php`.

**Not covered yet:** tax line snapshots, discount snapshots, shipment item snapshots, and product option snapshots.

## B8.7 Idempotent Checkout Submission Handling

**Covers:**

- Checkout submit requires `idempotency_key`.
- Orders enforce uniqueness by customer and idempotency key.
- Retrying checkout submit with the same customer/key returns the existing order.
- Retry does not create duplicate order items.
- Retry does not reserve inventory again.
- Feature tests: `backend/tests/Feature/CheckoutOrderCreationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutOrderCreationTest.php`.

**Not covered yet:** idempotency expiry policy, request payload hash comparison, and cross-device idempotency reporting.

## B8.8 Rollback And Compensation Rules

**Covers:**

- Checkout submit runs inside one database transaction.
- Validation failure creates no order.
- Validation failure creates no order items.
- Validation failure does not complete the cart.
- Validation failure does not reserve inventory.
- Inventory reservation occurs after order and order item creation inside the same transaction.
- If a later exception occurs, database changes roll back together.
- Feature tests: `backend/tests/Feature/CheckoutOrderCreationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutOrderCreationTest.php`.

**Not covered yet:** external payment compensation, shipment cancellation, and manual recovery tools.

## B8.9 Order Confirmation Event Generation

**Covers:**

- Laravel event: `backend/app/Events/OrderConfirmationGenerated.php`.
- Event is dispatched after a new checkout order is created successfully.
- Event is not dispatched again for idempotent checkout retries.
- Confirmation event carries the created `Order` model.
- Audit event is recorded:
  - `order.confirmation.generated`
  - actor: customer
  - auditable: order
  - metadata: order number, grand total, currency
- Checkout submit response still returns the created order.
- Feature tests: `backend/tests/Feature/CheckoutOrderCreationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\CheckoutOrderCreationTest.php`.

**Not covered yet:** confirmation email, SMS, queue worker listener, notification templates, and customer-facing order confirmation page.

## B9.1 Order Entity Lifecycle

**Covers:**

- Lifecycle timestamp columns on `orders`:
  - `confirmed_at`
  - `processing_at`
  - `packed_at`
  - `shipped_at`
  - `delivered_at`
  - `failed_delivery_at`
  - `return_requested_at`
  - `returned_at`
  - `refunded_at`
  - `cancelled_at`
- Migration:
  - `backend/database/migrations/2026_04_29_091000_add_lifecycle_timestamps_to_orders_table.php`
- Order model casts and relationships:
  - `backend/app/Models/Order.php`
- Admin order detail resource includes lifecycle timestamps.
- Feature tests: `backend/tests/Feature/OrderLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** customer-facing order history endpoint and admin Blade order screens.

## B9.2 Order Status Machine

**Covers:**

- Status constants and allowed transitions:
  - `backend/app/Modules/Order/Support/OrderStatus.php`
- Admin status update endpoint:
  - `PATCH /api/v1/orders/{id}/status`
  - Source: `backend/app/Modules/Order/routes/api.php`
- Request validation:
  - `backend/app/Modules/Order/Requests/UpdateOrderStatusRequest.php`
- Service/repository flow:
  - `backend/app/Modules/Order/Services/OrderLifecycleService.php`
  - `backend/app/Modules/Order/Repositories/OrderRepository.php`
- Permission:
  - `orders.update_status`
- Audit event:
  - `order.status.changed`
- Feature tests: `backend/tests/Feature/OrderLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** webhook-driven status updates, shipment-triggered transitions, and payment-triggered transitions.

## B9.3 Order History Tracking

**Covers:**

- Status history table:
  - `backend/database/migrations/2026_04_29_090000_create_order_status_histories_table.php`
- Status history model:
  - `backend/app/Models/OrderStatusHistory.php`
- Status history resource:
  - `backend/app/Modules/Order/Resources/OrderStatusHistoryResource.php`
- Checkout creates the initial `pending` status history.
- Admin status changes create history rows with actor, previous status, next status, note, and metadata.
- Admin order detail response includes status history.
- Feature tests:
  - `backend/tests/Feature/OrderLifecycleTest.php`
  - `backend/tests/Feature/CheckoutOrderCreationTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php tests\Feature\CheckoutOrderCreationTest.php`.

**Not covered yet:** status history filtering/export and customer-visible status timeline formatting.

## B9.4 Internal Order Notes

**Covers:**

- Internal order notes table:
  - `backend/database/migrations/2026_04_29_092000_create_order_notes_table.php`
- Order note model:
  - `backend/app/Models/OrderNote.php`
- Order note relationship:
  - `$order->notes()`
- Admin note endpoint:
  - `POST /api/v1/orders/{id}/notes`
- Request/resource classes:
  - `backend/app/Modules/Order/Requests/StoreOrderNoteRequest.php`
  - `backend/app/Modules/Order/Resources/OrderNoteResource.php`
- Permission:
  - `orders.notes.create`
- Audit event:
  - `order.note.created`
- Feature tests: `backend/tests/Feature/OrderLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** note update/delete, pinned notes, note attachments, and admin Blade note UI.

## B9.5 Cancellation Workflow

**Covers:**

- Admin cancellation endpoint:
  - `POST /api/v1/orders/{id}/cancel`
- Request validation:
  - `backend/app/Modules/Order/Requests/CancelOrderRequest.php`
- Cancellation follows the order status machine and is blocked once the order reaches `shipped`.
- Cancellation updates:
  - `orders.status = cancelled`
  - `orders.fulfillment_status = cancelled`
  - `orders.cancelled_at`
- Cancellation creates order status history.
- Cancellation releases reserved inventory through `InventoryStockService::release()`.
- Permission:
  - `orders.cancel`
- Audit event:
  - `order.cancelled`
- Feature tests:
  - `backend/tests/Feature/OrderLifecycleTest.php`
  - `backend/tests/Feature/InventoryWorkflowTest.php`

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** customer cancellation requests, refund/payment compensation, shipment cancellation, cancellation reason catalog, and override cancellation after shipment.

## B9.6 Return Request Baseline

**Covers:**

- Return request table:
  - `backend/database/migrations/2026_04_29_093000_create_order_return_requests_table.php`
- Return request model:
  - `backend/app/Models/OrderReturnRequest.php`
- Order relationship:
  - `$order->returnRequests()`
- Admin return request endpoint:
  - `POST /api/v1/orders/{id}/return-requests`
- Request/resource classes:
  - `backend/app/Modules/Order/Requests/StoreReturnRequest.php`
  - `backend/app/Modules/Order/Resources/OrderReturnRequestResource.php`
- Return request baseline rule:
  - only `delivered` orders can receive a return request
  - accepted request changes order status to `return_requested`
  - `return_requested_at` is set on the order
- Status history is recorded for `delivered -> return_requested`.
- Permission:
  - `orders.returns.create`
- Audit event:
  - `order.return.requested`
- Feature tests: `backend/tests/Feature/OrderLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** customer self-service return requests, approval/rejection, received-item processing, refund/exchange decisions, return item quantities, and return window policy.

## B9.7 Invoice Generation Baseline

**Covers:**

- Invoice table:
  - `backend/database/migrations/2026_04_29_094000_create_order_invoices_table.php`
- Invoice model:
  - `backend/app/Models/OrderInvoice.php`
- Order relationship:
  - `$order->invoice()`
- Admin invoice endpoint:
  - `POST /api/v1/orders/{id}/invoice`
- Request/resource classes:
  - `backend/app/Modules/Order/Requests/GenerateInvoiceRequest.php`
  - `backend/app/Modules/Order/Resources/OrderInvoiceResource.php`
- One invoice is generated per order.
- Invoice generation is idempotent and returns the existing invoice on retry.
- Invoice snapshot stores:
  - invoice number
  - status
  - totals
  - currency
  - billing address
  - shipping address
  - issued timestamp
  - issuing admin
- Cancelled orders cannot receive invoices.
- Permission:
  - `orders.invoices.generate`
- Audit event:
  - `order.invoice.generated`
- Feature tests: `backend/tests/Feature/OrderLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** printable invoice Blade/PDF output, tax lines, discount lines, invoice voiding, invoice email delivery, and custom invoice numbering settings.

## B9.8 Packing Slip Baseline

**Covers:**

- Packing slip table:
  - `backend/database/migrations/2026_04_29_095000_create_order_packing_slips_table.php`
- Packing slip model:
  - `backend/app/Models/OrderPackingSlip.php`
- Order relationship:
  - `$order->packingSlip()`
- Admin packing slip endpoint:
  - `POST /api/v1/orders/{id}/packing-slip`
- Request/resource classes:
  - `backend/app/Modules/Order/Requests/GeneratePackingSlipRequest.php`
  - `backend/app/Modules/Order/Resources/OrderPackingSlipResource.php`
- One packing slip is generated per order.
- Packing slip generation is idempotent and returns the existing packing slip on retry.
- Packing slip snapshot stores:
  - packing slip number
  - status
  - shipping address
  - order item IDs
  - product/variant/SKU names
  - quantities
  - generated timestamp
  - generating admin
- Cancelled orders cannot receive packing slips.
- Permission:
  - `orders.packing_slips.generate`
- Audit event:
  - `order.packing_slip.generated`
- Feature tests: `backend/tests/Feature/OrderLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** printable packing slip Blade/PDF output, barcode/QR labels, shipment item snapshots, warehouse pick/pack workflow, and packing slip voiding.

## B9.9 Shipment Linkage

**Covers:**

- Shipment table:
  - `backend/database/migrations/2026_04_29_100000_create_order_shipments_table.php`
- Shipment model:
  - `backend/app/Models/OrderShipment.php`
- Order relationship:
  - `$order->shipments()`
- Admin shipment endpoint:
  - `POST /api/v1/orders/{id}/shipments`
- Request/resource classes:
  - `backend/app/Modules/Order/Requests/StoreShipmentRequest.php`
  - `backend/app/Modules/Order/Resources/OrderShipmentResource.php`
- Shipment linkage stores:
  - carrier name
  - tracking number
  - tracking URL
  - shipment status
  - shipped timestamp
  - creating admin
- `pending` shipment links do not change the order status.
- `shipped` shipment links move a `packed` order to `shipped`, set `fulfillment_status = shipped`, set `shipped_at`, and create status history.
- Cancelled, returned, and refunded orders cannot receive shipments.
- Permission:
  - `orders.shipments.create`
- Audit event:
  - `order.shipment.linked`
- Feature tests: `backend/tests/Feature/OrderLifecycleTest.php`.

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** courier booking APIs, webhook tracking updates, shipment cancellation, shipment item quantities, multi-package shipment rules, delivery confirmation, and failed-delivery handling.

## B10.1 Payment Entity And States

**Covers:**

- Payment table:
  - `backend/database/migrations/2026_04_29_110000_create_payments_table.php`
- Payment model:
  - `backend/app/Models/Payment.php`
- Order relationship:
  - `$order->payments()`
- Payment status constants and allowed transitions:
  - `backend/app/Modules/Payment/Support/PaymentStatus.php`
- Payment module service/repository:
  - `backend/app/Modules/Payment/Services/PaymentService.php`
  - `backend/app/Modules/Payment/Repositories/PaymentRepository.php`
- Payment resource:
  - `backend/app/Modules/Payment/Resources/PaymentResource.php`
- Supported baseline payment statuses:
  - `unpaid`
  - `pending`
  - `paid`
  - `failed`
  - `refunded`
  - `partially_refunded`
- Checkout submit creates one initial payment record for the order.
- Initial payment record stores:
  - order
  - provider/payment method code
  - payment method name
  - status
  - amount
  - currency
  - transaction ID/reference fields for later gateway/manual updates
  - metadata
- Audit event:
  - `payment.created`
- Feature tests:
  - `backend/tests/Feature/PaymentFoundationTest.php`
  - `backend/tests/Feature/CheckoutOrderCreationTest.php`

**Verification:** covered by `php artisan test tests\Feature\PaymentFoundationTest.php tests\Feature\CheckoutOrderCreationTest.php`.

**Not covered yet:** admin payment UI, concrete gateway adapters, and refunds.

## B10.2 COD Workflow

**Covers:**

- COD fields on payments:
  - `cod_status`
  - `collected_at`
- Migration:
  - `backend/database/migrations/2026_04_29_111000_add_cod_fields_to_payments_table.php`
- COD status constants:
  - `backend/app/Modules/Payment/Support/CodStatus.php`
- COD initial checkout payment state:
  - `provider = cod`
  - `status = pending`
  - `cod_status = pending`
- Admin COD collection endpoint:
  - `POST /api/v1/payments/{id}/cod/collect`
- COD collection updates:
  - `payments.status = paid`
  - `payments.cod_status = collected`
  - `payments.paid_at`
  - `payments.collected_at`
  - `orders.payment_status = paid`
- COD collection rejects non-COD payments.
- Permission:
  - `payments.cod.collect`
- Audit event:
  - `payment.cod.collected`
- Feature tests: `backend/tests/Feature/PaymentFoundationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\PaymentFoundationTest.php`.

**Not covered yet:** courier COD remittance, COD disputes, failed COD collection, reconciliation uploads, and COD reports.

## B10.3 Manual Admin Payment Update Flow

**Covers:**

- Admin payment status update endpoint:
  - `PATCH /api/v1/payments/{id}/status`
- Request/resource classes:
  - `backend/app/Modules/Payment/Requests/UpdatePaymentStatusRequest.php`
  - `backend/app/Modules/Payment/Resources/PaymentResource.php`
- Manual update supports:
  - payment status
  - transaction ID
  - provider reference
  - metadata
- Payment status changes validate against the payment state machine.
- Manual `paid` update sets `payments.paid_at`.
- Manual status changes synchronize `orders.payment_status`.
- Permission:
  - `payments.update`
- Audit event:
  - `payment.status.changed`
- Feature tests: `backend/tests/Feature/PaymentFoundationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\PaymentFoundationTest.php`.

**Not covered yet:** admin Blade payment UI, payment attachments, partial payment amount handling, refund amount entry, and accountant-only reporting screens.

## B10.4 Payment Gateway Adapter Contract

**Covers:**

- Payment gateway adapter interface:
  - `backend/app/Modules/Payment/Contracts/PaymentGatewayAdapter.php`
- Contract methods:
  - `provider()`
  - `createPayment(Order $order, Payment $payment)`
  - `verifyWebhook(array $headers, string $payload)`
  - `parseWebhook(array $headers, string $payload)`
  - `refund(Payment $payment, string|float|int $amount, array $metadata = [])`
- Contract keeps gateway integrations replaceable without coupling payment workflow code to a specific provider.
- The interface uses plain arrays for gateway intent/event/refund results to avoid premature DTO infrastructure.
- Feature test:
  - `backend/tests/Feature/PaymentGatewayAdapterContractTest.php`

**Verification:** covered by `php artisan test tests\Feature\PaymentGatewayAdapterContractTest.php`.

**Not covered yet:** gateway resolver, concrete bKash/Nagad/SSLCommerz adapters, gateway configuration UI, real payment initiation route, real refund workflow, and adapter-specific webhook parsing.

## B10.5 Webhook Verification

**Covers:**

- Payment webhook log table:
  - `backend/database/migrations/2026_04_29_112000_create_payment_webhook_logs_table.php`
- Payment webhook log model:
  - `backend/app/Models/PaymentWebhookLog.php`
- Payment webhook repository/service:
  - `backend/app/Modules/Payment/Repositories/PaymentWebhookRepository.php`
  - `backend/app/Modules/Payment/Services/PaymentWebhookService.php`
- Public payment webhook endpoint:
  - `POST /api/v1/payments/webhooks/{provider}`
- Generic HMAC-SHA256 signature verification.
- Signature header and provider secrets are read from:
  - `config/services.php`
  - `services.payment_webhooks.signature_header`
  - `services.payment_webhooks.providers.{provider}.secret`
- Invalid signatures are rejected with `401 invalid_webhook_signature`.
- Rejected webhook attempts are logged with:
  - provider
  - event ID
  - transaction ID
  - payload hash
  - payload
  - status `rejected`
  - failure reason `invalid_signature`
- Feature tests: `backend/tests/Feature/PaymentWebhookTest.php`.

**Verification:** covered by `php artisan test tests\Feature\PaymentWebhookTest.php`.

**Not covered yet:** provider-specific signature formats, gateway adapter resolver integration, IP allowlists, timestamp replay windows, and encrypted gateway credential storage.

## B10.6 Webhook Idempotency

**Covers:**

- Webhook logs enforce uniqueness by:
  - `provider + event_id`
  - `provider + payload_hash`
- Duplicate webhook events return success with `meta.duplicate = true`.
- Duplicate webhook events do not re-apply payment status changes.
- Valid webhook processing can update matching payments by:
  - provider
  - transaction ID
- Supported baseline webhook statuses:
  - `paid`
  - `failed`
- Processed webhook logs store:
  - status `processed`
  - `processed_at`
- Unknown payment or unsupported status is logged as `ignored`.
- Feature tests: `backend/tests/Feature/PaymentWebhookTest.php`.

**Verification:** covered by `php artisan test tests\Feature\PaymentWebhookTest.php`.

**Not covered yet:** adapter-specific event normalization, retry queues, webhook failure alerting, refund webhook idempotency, event replay admin tools, and payload-hash conflict review.

## B10.7 Order-Payment Synchronization Rules

**Covers:**

- Payment status changes synchronize `orders.payment_status`.
- Sync priority:
  - any `refunded` payment makes the order `refunded`
  - any `partially_refunded` payment makes the order `partially_refunded`
  - any `paid` payment makes the order `paid`
  - any `pending` payment makes the order `pending`
  - any `failed` payment makes the order `failed`
  - no matching payment state leaves the order `unpaid`
- Payment status transitions validate against the payment state machine.
- `paid` transition sets `payments.paid_at`.
- Audit event:
  - `payment.status.changed`
- Admin and checkout order resources include loaded payment records.
- Feature tests: `backend/tests/Feature/PaymentFoundationTest.php`.

**Verification:** covered by `php artisan test tests\Feature\PaymentFoundationTest.php`.

**Not covered yet:** automatic order lifecycle confirmation after online payment, COD reconciliation rules, refund amount aggregation, multi-payment split tender rules, and webhook-driven synchronization.

## B13.1 KPI Aggregation Services

**Covers:**

- Reporting module:
  - `backend/app/Modules/Reporting`
- KPI service/repository:
  - `backend/app/Modules/Reporting/Services/ReportingService.php`
  - `backend/app/Modules/Reporting/Repositories/ReportingRepository.php`
- Dashboard KPI endpoint:
  - `GET /api/v1/reports/dashboard`
- Request/resource classes:
  - `backend/app/Modules/Reporting/Requests/ReportDateRangeRequest.php`
  - `backend/app/Modules/Reporting/Resources/DashboardKpiResource.php`
- KPI response includes:
  - total orders
  - pending orders
  - cancelled orders
  - delivered orders
  - gross sales
  - paid sales
  - average order value
- Optional date filters:
  - `from`
  - `to`
- Permission:
  - `reports.view`
- Feature tests: `backend/tests/Feature/ReportingDashboardTest.php`.

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** dashboard caching, low-stock/top-product/customer KPI cards, payment/COD KPI cards, and admin dashboard UI replacement.

## B13.2 Orders Reporting Queries

**Covers:**

- Orders report endpoint:
  - `GET /api/v1/reports/orders`
- Orders report returns:
  - total orders
  - order counts grouped by status
- Optional date filters:
  - `from`
  - `to`
- Report queries are read-only and use aggregate SQL through `ReportingRepository`.
- Permission:
  - `reports.view`
- Feature tests: `backend/tests/Feature/ReportingDashboardTest.php`.

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** paginated order report rows, channel/source filters, payment-method filters, customer filters, and export support.

## B13.3 Sales Reporting Queries

**Covers:**

- Sales report endpoint:
  - `GET /api/v1/reports/sales`
- Sales report returns:
  - gross sales excluding cancelled orders
  - paid sales from paid orders
  - daily sales rows
  - daily order counts
- Optional date filters:
  - `from`
  - `to`
- Report queries are read-only and use aggregate SQL through `ReportingRepository`.
- Permission:
  - `reports.view`
- Feature tests: `backend/tests/Feature/ReportingDashboardTest.php`.

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** refunds/net sales, taxes, discounts, payment-method breakdowns, product/category sales breakdowns, and export support.

## B13.4 Inventory Reporting Queries

**Covers:**

- Inventory report endpoint:
  - `GET /api/v1/reports/inventory`
- Inventory summary returns:
  - tracked variants count
  - total on hand quantity
  - total reserved quantity
  - total available quantity
  - low stock count
- Low-stock rows return:
  - product variant ID
  - product name
  - variant name
  - SKU
  - on-hand quantity
  - reserved quantity
  - available quantity
  - low stock threshold
- Report queries are read-only and use `inventory_stocks`, variants, and products through `ReportingRepository`.
- Permission:
  - `reports.view`
- Feature tests: `backend/tests/Feature/ReportingDashboardTest.php`.

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** inventory movement reports, stock aging, warehouse/location filters, product category filters, and export support.

## B13.5 Customer Reporting Queries

**Covers:**

- Customer report endpoint:
  - `GET /api/v1/reports/customers`
- Customer summary returns:
  - total customers
  - active customers
  - customers with orders
  - repeat customers
- Top customer rows return:
  - customer ID
  - name
  - email
  - order count
  - total spent
- Optional date filters:
  - `from`
  - `to`
- Cancelled orders are excluded from customer spend ranking.
- Report queries are read-only and use users and orders through `ReportingRepository`.
- Permission:
  - `reports.view`
- Feature tests: `backend/tests/Feature/ReportingDashboardTest.php`.

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** customer cohort reports, retention reports, customer lifetime value rules, customer segment filters, and export support.

## F3.5 Low-Stock/Order/Customer Summary Panels

**Covers:**

- Admin dashboard order summary panel using reporting service order counts.
- Admin dashboard customer summary panel using reporting service customer summary.
- Admin dashboard low-stock summary panel and low-stock item table using reporting service inventory summary.
- Dashboard controller now depends on `ReportingService` instead of duplicating reporting logic in the view.
- Files:
  - `backend/app/Http/Controllers/Admin/DashboardController.php`
  - `backend/resources/views/admin/dashboard/index.blade.php`
  - `backend/tests/Feature/AdminDashboardWebTest.php`

**Verification:** covered by `php artisan test tests\Feature\AdminDashboardWebTest.php`.

**Not covered yet:** date filters, chart visualization, dashboard caching, and dashboard API polling.

## F3.6 Dashboard Data Loading States

**Covers:**

- Dashboard summary panels include loading-state placeholders for order, customer, and low-stock sections.
- Empty states for no orders, no customers, and no low-stock rows.
- Existing recent-users table keeps empty-state handling.
- Files:
  - `backend/resources/views/admin/dashboard/index.blade.php`
  - `backend/tests/Feature/AdminDashboardWebTest.php`

**Verification:** covered by `php artisan test tests\Feature\AdminDashboardWebTest.php`.

**Not covered yet:** client-side async loading, retry UI, and partial refresh states.

## F7.1 Stock List Screen

**Covers:**

- Admin inventory page: `GET /admin/inventory`.
- Inventory data endpoint: `GET /admin/inventory/data`.
- Stock table with product, variant, SKU, on-hand, reserved, available, and status columns.
- Sidebar Inventory navigation item for users with `inventory.view`.
- Inventory module view provider registration: `backend/app/Modules/Inventory/Providers/InventoryServiceProvider.php`.
- Files:
  - `backend/app/Modules/Inventory/Controllers/InventoryManagementController.php`
  - `backend/app/Modules/Inventory/Controllers/InventoryStockController.php`
  - `backend/app/Modules/Inventory/Repositories/InventoryStockRepository.php`
  - `backend/app/Modules/Inventory/Services/InventoryStockService.php`
  - `backend/app/Modules/Inventory/Resources/InventoryStockResource.php`
  - `backend/app/Modules/Inventory/views/stocks/index.blade.php`
  - `backend/app/Modules/Inventory/routes/web.php`

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** advanced stock filters, pagination controls in the Blade UI, and warehouse/location support.

## F7.2 Stock Adjustment Screen

**Covers:**

- Permission-aware stock adjustment panel on `/admin/inventory`.
- Row-level Adjust action for users with `inventory.adjust`.
- Quantity delta and note fields using existing `POST /admin/inventory/variants/{variantId}/adjust` endpoint.
- Stock list and low-stock panel refresh after adjustment.
- Managers can view inventory but do not see mutation controls.
- Files:
  - `backend/app/Modules/Inventory/views/stocks/index.blade.php`
  - `backend/tests/Feature/InventoryWorkflowTest.php`

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** bulk adjustments, approval workflow, attachment support, and adjustment reason catalog.

## F7.3 Inventory History Screen

**Covers:**

- Admin inventory history page: `GET /admin/inventory/history`.
- Inventory history data endpoint: `GET /admin/inventory/history/data`.
- History table with date, product, variant, SKU, movement type, delta, post-movement stock, actor, and note.
- Link between stock list and history screen.
- Inventory transaction resource:
  - `backend/app/Modules/Inventory/Resources/InventoryTransactionResource.php`
- Files:
  - `backend/app/Modules/Inventory/views/stocks/history.blade.php`
  - `backend/app/Modules/Inventory/Controllers/InventoryManagementController.php`
  - `backend/app/Modules/Inventory/Controllers/InventoryStockController.php`
  - `backend/app/Modules/Inventory/Repositories/InventoryStockRepository.php`
  - `backend/app/Modules/Inventory/Services/InventoryStockService.php`
  - `backend/app/Modules/Inventory/routes/web.php`

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** history filters, export, reference links to orders/checkouts, and movement type summaries.

## F7.4 Low-Stock Visibility Panel

**Covers:**

- Low-stock panel on `/admin/inventory`.
- Low-stock data endpoint: `GET /admin/inventory/low-stock/data`.
- Low-stock rows show item, available quantity, and threshold.
- Low-stock panel refreshes after stock adjustments.
- Simple repository filtering using the existing `InventoryStock::is_low_stock` model accessor.
- Files:
  - `backend/app/Modules/Inventory/views/stocks/index.blade.php`
  - `backend/app/Modules/Inventory/Controllers/InventoryStockController.php`
  - `backend/app/Modules/Inventory/Repositories/InventoryStockRepository.php`
  - `backend/app/Modules/Inventory/Services/InventoryStockService.php`
  - `backend/app/Modules/Inventory/routes/web.php`

**Verification:** covered by `php artisan test tests\Feature\InventoryWorkflowTest.php`.

**Not covered yet:** threshold editing UI, low-stock alerts, supplier reorder workflow, and category/product filters.

## F8.1 Customer List Screen

**Covers:**

- Admin customer list page: `GET /admin/customers`.
- Customer list data endpoint: `GET /admin/customers/data`.
- Customer table with name, email, phone, status, order count, joined date, and View action.
- Sidebar Customers navigation item for users with `customers.view`.
- Customer module view provider registration:
  - `backend/app/Modules/Customer/Providers/CustomerServiceProvider.php`
- Files:
  - `backend/app/Modules/Customer/Controllers/CustomerManagementController.php`
  - `backend/app/Modules/Customer/Controllers/CustomerSupportController.php`
  - `backend/app/Modules/Customer/views/customers/index.blade.php`
  - `backend/app/Modules/Customer/routes/web.php`

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** pagination controls in the Blade UI, bulk actions, and CSV export.

## F8.2 Customer Filter/Search UI

**Covers:**

- Customer search by name, email, or phone.
- Customer status filter.
- Repository-level filtering in `CustomerSupportRepository`; no query logic in Blade.
- Filter form and clear button on the customer list screen.
- Files:
  - `backend/app/Modules/Customer/Repositories/CustomerSupportRepository.php`
  - `backend/app/Modules/Customer/Services/CustomerSupportService.php`
  - `backend/app/Modules/Customer/Controllers/CustomerSupportController.php`
  - `backend/app/Modules/Customer/views/customers/index.blade.php`

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** tag filters, order-count filters, date filters, and saved filter presets.

## F8.3 Customer Detail Screen

**Covers:**

- Admin customer detail page: `GET /admin/customers/{id}`.
- Customer detail data endpoint: `GET /admin/customers/{id}/data`.
- Detail sections for profile, tags, addresses, recent orders, and internal notes.
- View action from the customer list screen.
- Files:
  - `backend/app/Modules/Customer/Controllers/CustomerManagementController.php`
  - `backend/app/Modules/Customer/Controllers/CustomerSupportController.php`
  - `backend/app/Modules/Customer/views/customers/show.blade.php`
  - `backend/app/Modules/Customer/views/customers/index.blade.php`
  - `backend/app/Modules/Customer/routes/web.php`

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** full order detail links and customer activity timeline.

## F8.4 Address Management UI

**Covers:**

- Address management form on the customer detail screen.
- Admin address create, update, and delete routes:
  - `POST /admin/customers/{id}/addresses`
  - `PUT /admin/customers/{id}/addresses/{addressId}`
  - `DELETE /admin/customers/{id}/addresses/{addressId}`
- Address table row Edit/Delete actions.
- Default shipping and default billing checkbox support.
- Address logic reuses `CustomerAddressService`; ownership lookup stays in the repository/service layer.
- Files:
  - `backend/app/Modules/Customer/views/customers/show.blade.php`
  - `backend/app/Modules/Customer/Controllers/CustomerAddressController.php`
  - `backend/app/Modules/Customer/Services/CustomerAddressService.php`
  - `backend/app/Modules/Customer/Repositories/CustomerAddressRepository.php`
  - `backend/app/Modules/Customer/routes/web.php`
  - `backend/tests/Feature/CustomerSupportTest.php`

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** address validation previews, map/geocoding support, delivery-zone validation, and address import/export.

## F8.5 Tags/Notes UI

**Covers:**

- Tags form on the customer detail screen.
- Internal note form on the customer detail screen.
- Admin web routes:
  - `POST /admin/customers/{id}/notes`
  - `PUT /admin/customers/{id}/tags`
- Support controller now resolves the actor from either Sanctum API token or web session.
- Tags and notes refresh after successful AJAX actions.
- Files:
  - `backend/app/Modules/Customer/views/customers/show.blade.php`
  - `backend/app/Modules/Customer/Controllers/CustomerSupportController.php`
  - `backend/app/Modules/Customer/routes/web.php`
  - `backend/tests/Feature/CustomerSupportTest.php`

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** note editing/deletion, pinned notes, tag autocomplete, and activity timeline grouping.

## F8.6 Customer Order History Screen

**Covers:**

- Order history section on the admin customer detail screen.
- Customer order count badge and lightweight order summary panel.
- Order table columns for order number, status, payment status, fulfillment status, payment method, total, and placed date.
- Customer detail data now exposes a larger recent history window for admin support review.
- Files:
  - `backend/app/Modules/Customer/views/customers/show.blade.php`
  - `backend/app/Modules/Customer/Repositories/CustomerSupportRepository.php`
  - `backend/tests/Feature/CustomerSupportTest.php`

**Verification:** covered by `php artisan test tests\Feature\CustomerSupportTest.php`.

**Not covered yet:** separate full order detail screen, customer activity timeline grouping, advanced order filters, and export.

## F9.1 Order List Screen

**Covers:**

- Admin order list page: `GET /admin/orders`.
- Order list data endpoint for the admin screen: `GET /admin/orders/data`.
- Sidebar navigation entry for users with `orders.view`.
- Order table columns for order number, customer, status, payment status, fulfillment status, total, and placed date.
- Files:
  - `backend/app/Modules/Order/Controllers/OrderManagementController.php`
  - `backend/app/Modules/Order/Controllers/OrderController.php`
  - `backend/app/Modules/Order/views/orders/index.blade.php`
  - `backend/app/Modules/Order/routes/web.php`
  - `backend/app/Modules/Order/Providers/OrderServiceProvider.php`
  - `backend/resources/views/admin/layouts/sidebar.blade.php`
  - `backend/config/modules.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** order detail Blade screen, row action buttons, export, and bulk actions.

## F9.2 Order Filter/Search UI

**Covers:**

- Search by order number, customer name, or customer email.
- Filters for order status, payment status, and fulfillment status.
- Load-more pagination on the admin order list.
- Query logic stays in `OrderRepository`; controller and service only pass filters through.
- Files:
  - `backend/app/Modules/Order/Repositories/OrderRepository.php`
  - `backend/app/Modules/Order/Services/OrderLifecycleService.php`
  - `backend/app/Modules/Order/Controllers/OrderController.php`
  - `backend/app/Modules/Order/views/orders/index.blade.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** date range filters, saved views, channel filters, payment method filters, and customer segment filters.

## F9.3 Order Detail Timeline

**Covers:**

- Admin order detail page: `GET /admin/orders/{id}`.
- Order detail data endpoint for the admin screen: `GET /admin/orders/{id}/data`.
- Timeline panel backed by existing order status history.
- Summary, items, and customer panels on the order detail screen.
- View action from the admin order list.
- Files:
  - `backend/app/Modules/Order/Controllers/OrderManagementController.php`
  - `backend/app/Modules/Order/Controllers/OrderController.php`
  - `backend/app/Modules/Order/views/orders/show.blade.php`
  - `backend/app/Modules/Order/views/orders/index.blade.php`
  - `backend/app/Modules/Order/routes/web.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** richer activity grouping, payment/shipment timeline events, and printable order detail.

## F9.4 Status Change Controls

**Covers:**

- Permission-gated status change form on the order detail screen.
- Allowed next statuses are rendered from `OrderStatus::transitions()`.
- Admin web status update route: `PATCH /admin/orders/{id}/status`.
- Web session actor fallback for status update audit/history records.
- Timeline refresh after successful status update.
- Files:
  - `backend/app/Modules/Order/Controllers/OrderController.php`
  - `backend/app/Modules/Order/views/orders/show.blade.php`
  - `backend/app/Modules/Order/routes/web.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** inline confirmation prompts, custom transition reason catalogs, and status-change attachments.

## F9.5 Cancellation/Return Handling Screens

**Covers:**

- Cancellation form on the admin order detail screen.
- Return request form on the admin order detail screen.
- Return request list panel on the order detail screen.
- Admin web routes:
  - `POST /admin/orders/{id}/cancel`
  - `POST /admin/orders/{id}/return-requests`
- Web session actor fallback for cancellation and return request audit/history records.
- Files:
  - `backend/app/Modules/Order/Controllers/OrderController.php`
  - `backend/app/Modules/Order/views/orders/show.blade.php`
  - `backend/app/Modules/Order/routes/web.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** return approval/rejection screens, return item quantity handling, cancellation reason catalog, and refund compensation UI.

## F9.6 Shipment And Tracking UI

**Covers:**

- Shipment creation form on the admin order detail screen.
- Shipment/tracking list panel on the order detail screen.
- Carrier, tracking number, tracking URL, and shipment status fields.
- Admin web route: `POST /admin/orders/{id}/shipments`.
- Web session actor fallback for shipment creation audit/history records.
- Files:
  - `backend/app/Modules/Order/Controllers/OrderController.php`
  - `backend/app/Modules/Order/views/orders/show.blade.php`
  - `backend/app/Modules/Order/routes/web.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** shipment edit/cancel screens, courier booking, tracking refresh jobs, delivery confirmation, and failed-delivery handling UI.

## F9.7 Invoice/Packing Slip Access Points

**Covers:**

- Documents panel on the admin order detail screen.
- Invoice and packing slip status display when generated.
- Printable invoice Blade page with browser print/save-PDF support.
- Printable packing slip Blade page with browser print/save-PDF support.
- Generate invoice button for admins with `orders.invoices.generate`.
- Generate packing slip button for admins with `orders.packing_slips.generate`.
- Admin web routes:
  - `POST /admin/orders/{id}/invoice`
  - `POST /admin/orders/{id}/packing-slip`
  - `GET /admin/orders/{id}/invoice/print`
  - `GET /admin/orders/{id}/packing-slip/print`
- Web session actor fallback for invoice and packing-slip audit records.
- Files:
  - `backend/app/Modules/Order/Controllers/OrderController.php`
  - `backend/app/Modules/Order/Controllers/OrderManagementController.php`
  - `backend/app/Modules/Order/Repositories/OrderRepository.php`
  - `backend/app/Modules/Order/Services/OrderLifecycleService.php`
  - `backend/app/Modules/Order/views/orders/show.blade.php`
  - `backend/app/Modules/Order/views/documents/invoice.blade.php`
  - `backend/app/Modules/Order/views/documents/packing-slip.blade.php`
  - `backend/app/Modules/Order/routes/web.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** server-side PDF generation/download, invoice voiding, and email delivery.

## F10.1 Payment Status/Admin Payment Controls

**Covers:**

- Payment controls panel on the admin order detail screen.
- Payment status display for order-linked payments.
- Manual payment status update controls for admins with `payments.update`.
- Transaction ID and provider reference entry during manual status update.
- COD collection button for COD payments when admin has `payments.cod.collect`.
- Admin web routes:
  - `PATCH /admin/payments/{id}/status`
  - `POST /admin/payments/{id}/cod/collect`
- Web session actor fallback for payment status and COD collection audit records.
- Files:
  - `backend/app/Modules/Payment/Controllers/PaymentController.php`
  - `backend/app/Modules/Payment/routes/web.php`
  - `backend/app/Modules/Order/views/orders/show.blade.php`
  - `backend/config/modules.php`
  - `backend/tests/Feature/PaymentFoundationTest.php`
  - `backend/tests/Feature/OrderLifecycleTest.php`

**Verification:** covered by `php artisan test tests\Feature\PaymentFoundationTest.php tests\Feature\OrderLifecycleTest.php`.

**Not covered yet:** standalone payment list screen, payment attachments, refund controls, and payment reconciliation UI.

## F11.1 Sales Report Screen

**Covers:**

- Admin sales report page: `GET /admin/reports/sales`.
- Sales report data endpoint for the admin screen: `GET /admin/reports/sales/data`.
- Date range filters for sales reporting.
- Summary cards for gross sales, paid sales, and daily row count.
- Daily sales table with date, order count, gross sales, and paid sales.
- Reports sidebar entry for admins with `reports.view`.
- Files:
  - `backend/app/Modules/Reporting/Controllers/ReportManagementController.php`
  - `backend/app/Modules/Reporting/Controllers/ReportingController.php`
  - `backend/app/Modules/Reporting/views/reports/sales.blade.php`
  - `backend/app/Modules/Reporting/routes/web.php`
  - `backend/app/Modules/Reporting/Providers/ReportingServiceProvider.php`
  - `backend/resources/views/admin/layouts/sidebar.blade.php`
  - `backend/config/modules.php`
  - `backend/tests/Feature/ReportingDashboardTest.php`

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** charts, export buttons, comparison periods, payment-method breakdowns, and product/category sales breakdowns.

## F11.2 Orders Report Screen

**Covers:**

- Admin orders report page: `GET /admin/reports/orders`.
- Orders report data endpoint for the admin screen: `GET /admin/reports/orders/data`.
- Date range filters for order reporting.
- Total orders summary card.
- Status-count table for order statuses.
- Files:
  - `backend/app/Modules/Reporting/Controllers/ReportManagementController.php`
  - `backend/app/Modules/Reporting/Controllers/ReportingController.php`
  - `backend/app/Modules/Reporting/views/reports/orders.blade.php`
  - `backend/app/Modules/Reporting/routes/web.php`
  - `backend/tests/Feature/ReportingDashboardTest.php`

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** paginated order report rows, channel/source filters, payment-method filters, customer filters, and export support.

## F11.3 Inventory Report Screen

**Covers:**

- Admin inventory report page: `GET /admin/reports/inventory`.
- Inventory report data endpoint for the admin screen: `GET /admin/reports/inventory/data`.
- Summary cards for tracked variants, on-hand quantity, available quantity, and low-stock count.
- Low-stock table with product, variant, SKU, on-hand, reserved, available, and threshold columns.
- Files:
  - `backend/app/Modules/Reporting/Controllers/ReportManagementController.php`
  - `backend/app/Modules/Reporting/Controllers/ReportingController.php`
  - `backend/app/Modules/Reporting/views/reports/inventory.blade.php`
  - `backend/app/Modules/Reporting/routes/web.php`
  - `backend/tests/Feature/ReportingDashboardTest.php`

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** stock aging, movement reports, category filters, warehouse/location filters, and export support.

## F11.4 Customer Report Screen

**Covers:**

- Admin customer report page: `GET /admin/reports/customers`.
- Customer report data endpoint for the admin screen: `GET /admin/reports/customers/data`.
- Date range filters for customer reporting.
- Summary cards for total customers, active customers, customers with orders, and repeat customers.
- Top customers table with name, email, order count, and total spent.
- Files:
  - `backend/app/Modules/Reporting/Controllers/ReportManagementController.php`
  - `backend/app/Modules/Reporting/Controllers/ReportingController.php`
  - `backend/app/Modules/Reporting/views/reports/customers.blade.php`
  - `backend/app/Modules/Reporting/routes/web.php`
  - `backend/tests/Feature/ReportingDashboardTest.php`

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** cohort reports, retention reports, customer lifetime value rules, customer segment filters, and export support.

## F11.5 Export Trigger/Download UI

**Covers:**

- Export CSV buttons on sales, orders, inventory, and customer report screens.
- Direct CSV download endpoint: `GET /admin/reports/{report}/export`.
- Date filter preservation for sales, orders, and customer exports.
- CSV export data for sales daily rows, order status counts, inventory low-stock rows, and top customer rows.
- Files:
  - `backend/app/Modules/Reporting/Controllers/ReportingController.php`
  - `backend/app/Modules/Reporting/Services/ReportingService.php`
  - `backend/app/Modules/Reporting/routes/web.php`
  - `backend/app/Modules/Reporting/views/reports/sales.blade.php`
  - `backend/app/Modules/Reporting/views/reports/orders.blade.php`
  - `backend/app/Modules/Reporting/views/reports/inventory.blade.php`
  - `backend/app/Modules/Reporting/views/reports/customers.blade.php`
  - `backend/tests/Feature/ReportingDashboardTest.php`

**Verification:** covered by `php artisan test tests\Feature\ReportingDashboardTest.php`.

**Not covered yet:** queued export jobs, XLSX export, export history, large-file storage, and separate export permission.
