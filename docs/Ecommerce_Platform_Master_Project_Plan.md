# Ecommerce Platform Master Project Plan

This document is the execution-oriented master planning file for the full ecommerce project. It complements the existing architecture, WBS, API, and security documents by organizing implementation work into practical delivery streams:

1. Backend Core
2. API Layer
3. Admin Frontend
4. Storefront

The breakdown is intentionally sequenced so foundational backend work lands first, then stable APIs, then admin UI, and finally the storefront implementation as the last major stream.

## 1. Planning Objectives

- Build a modular Laravel ecommerce platform with clear backend and frontend boundaries.
- Keep business logic centralized in services and repositories.
- Expose stable APIs for both admin-facing and customer-facing experiences.
- Implement the admin area as an operational console for platform management.
- Implement the storefront as the customer-facing buying experience.
- Deliver in a way that reduces rework by respecting technical dependencies.

## 2. Delivery Principles

- Backend rules first, UI second.
- Shared business logic should never be duplicated across admin and API.
- API contracts must stabilize before frontend feature completion.
- Admin workflows should prioritize operational reliability and data integrity.
- Storefront work should come after core commerce flows are usable end to end.
- Every module should have clear acceptance criteria and verification steps.

## 3. Scope Summary

### In Scope

- Laravel modular backend
- Versioned APIs
- Admin frontend
- Customer storefront
- Core ecommerce workflows
- Baseline promotions and coupon workflows
- Security, logging, deployment, and reporting baseline

### Out of Scope For Initial Core Delivery

- Advanced marketplace/multi-vendor operations unless Phase 2 is activated
- Native mobile apps
- AI recommendations
- Loyalty ecosystem beyond baseline promotions unless separately approved
- Advanced promotion engine beyond coupon, offer price, flash sale, and free-delivery baseline

## 4. Execution Sequence

| Stage | Workstream | Why It Comes Here |
|---|---|---|
| 1 | Backend Core | All business rules, entities, and internal architecture depend on this |
| 2 | API Layer | Frontends need stable contracts to build against |
| 3 | Admin Frontend | Admin depends on backend modules and validated route/API behavior |
| 4 | Storefront | Storefront should sit on completed customer, cart, promotion/coupon, checkout, and order flows |

## 5. Dependency Map

### Core Dependencies

- Auth depends on users, roles, sessions, and security baseline.
- Catalog depends on categories, products, media, SEO, and inventory rules.
- Promotions depend on catalog, cart pricing, customer identity where available, and order redemption records.
- Checkout depends on cart, pricing, promotions/coupons, addresses, shipping, and payment orchestration.
- Orders depend on checkout, inventory reservation, payment states, and fulfillment rules.
- Reporting depends on stable domain events and queryable operational data.
- Admin frontend depends on backend views/routes or admin-facing endpoints.
- Storefront depends on stable public/customer APIs and published content/catalog data.

### Frontend Dependencies

- Admin dashboard depends on backend metrics aggregation.
- Admin product/order/customer screens depend on complete CRUD and filterable endpoints or server-rendered flows.
- Storefront listing depends on searchable product/category APIs.
- Storefront cart and checkout depend on validated pricing, promotion/coupon, and stock reservation rules.

## 6. Phase Structure

### Phase 1: Foundation And Core Commerce

- Platform foundation
- authentication and RBAC
- store settings
- categories
- products and variants
- inventory
- cart
- promotions and coupons baseline
- checkout
- orders
- payments baseline
- shipping baseline
- customers
- CMS/policy pages
- reports baseline
- security/logging baseline
- deployment baseline

### Phase 2: Optimization And Expansion

- performance improvements
- UX refinement
- reporting expansion
- automation improvements
- advanced promotions enhancement
- optional multi-vendor activation

## 6.1 Engineering Breakdown Standard

The master WBS below should be read at two levels:

1. Business/module level
2. Engineering artifact level

For this project, a module should not be planned only as a feature name. It should also be broken down into the technical artifacts required to deliver it properly.

### Core Engineering Artifacts To Consider

#### Database Layer

- migrations
- foreign keys and indexes
- seeders
- factories
- enums/constants where needed

#### Domain Layer

- models
- model relationships
- casts/accessors/mutators
- repositories
- service classes
- DTOs/value objects if needed
- policies/authorization rules
- observers if domain events are triggered from model changes

#### Application Layer

- controllers
- controller methods
- form requests / validators
- resources / transformers
- route files
- middleware
- events
- listeners
- jobs / queues
- notifications

#### Admin Frontend Layer

- layouts
- Blade views/pages
- partials/components
- form sections
- table/list sections
- filters/search UI
- client-side JS behavior if needed

#### Storefront Layer

- pages/routes
- shared layout shell
- components/sections
- API integration hooks/services
- state handling for cart/auth/checkout
- SEO/meta wiring

#### Quality And Delivery Layer

- feature tests
- unit tests
- API contract tests
- browser/manual QA checklist
- logging hooks
- audit hooks
- documentation updates

## 6.2 Detailed Module Planning Template

Each module in this project should be planned using the following checklist before implementation begins.

### Planning Template

- business goal
- domain entities
- database changes
- CRUD/action requirements
- backend classes required
- API endpoints required
- admin views/screens required
- storefront pages/components required
- authorization rules
- events/jobs/notifications required
- reports/exports impact
- logging/audit impact
- test cases
- acceptance criteria

### Example Implementation Breakdown Format

For each module, capture the following where relevant:

- `Actions Required`
- `Migration`
- `Model`
- `Repository`
- `Service`
- `Controller`
- `Controller Methods`
- `Request`
- `Resource`
- `Policy`
- `Route`
- `View/Page`
- `Partial/Component`
- `Job`
- `Event/Listener`
- `Notification`
- `Seeder/Factory`
- `Test`

### CRUD And Action Matrix Template

Before implementation, define which actions are actually required for the module. Not every module should automatically have full CRUD.

Use this checklist:

- `List`
- `Detail/View`
- `Create`
- `Edit/Update`
- `Delete`
- `Restore`
- `Archive`
- `Publish/Unpublish`
- `Activate/Deactivate`
- `Approve/Reject`
- `Assign/Reassign`
- `Import/Export`
- `Search/Filter/Sort`
- `Bulk Actions`

For each module, explicitly mark:

- action needed
- action not needed
- action restricted to admin only
- action restricted to system only
- action blocked for business/safety reasons

### CRUD Planning Rule

If a module supports create, edit, or delete, the planning should also define:

- who can perform the action
- which controller method handles it
- which request validates it
- which service method owns business logic
- which repository operations are needed
- whether soft delete or hard delete is allowed
- whether audit logging is mandatory
- whether confirmation is required in admin UI
- whether dependent records block deletion
- which tests cover happy path and failure path

## 7. Master WBS

## 7.1 Backend Core Workstream

This stream creates the domain model, module structure, internal contracts, and operational backbone of the system.

### Backend Artifact Breakdown By Module

Use this as the engineering-level checklist while implementing backend modules.

#### Backend Foundation Artifact Checklist

- `Config`: app/module config, environment keys, feature flags
- `Middleware`: request logging, exception wrapping, auth guards, throttling
- `Core`: shared reusable services, repositories, and support helpers in `app/Core`
- `Base Classes`: base service, base repository, shared response/logging helpers, and shared traits when needed
- `Providers`: module service providers and bindings in `app/Modules/<ModuleName>/Providers`
- `Routes`: module web/API routes in `app/Modules/<ModuleName>/routes`
- `Views`: module-owned admin views in `app/Modules/<ModuleName>/views` when the UI belongs to that module
- `Tests`: foundation smoke tests and shared testing utilities

#### Auth And RBAC Artifact Checklist

- `Migration`: users, roles, permissions, role_user, permission_role, password reset/session tables if needed
- `Model`: User, Role, Permission, AuthSession or token-related entities if used
- `Repository`: UserRepository, RoleRepository, PermissionRepository
- `Service`: AuthService, PasswordResetService, RolePermissionService
- `Controller`: AdminAuthController, CustomerAuthController, PasswordResetController
- `Controller Methods`: login, logout, register, forgotPassword, resetPassword, me, assignRole, syncPermissions
- `Request`: LoginRequest, RegisterRequest, ForgotPasswordRequest, ResetPasswordRequest, RoleSyncRequest
- `Policy`: UserPolicy, RolePolicy, PermissionPolicy
- `Route`: admin auth routes, API auth routes, protected profile routes
- `Event/Listener`: PasswordResetRequested, PasswordResetCompleted, UserLoggedIn
- `Notification`: password reset mail/OTP notification
- `Seeder/Factory`: admin seed user, role seeders, permission seeders, user factory
- `Test`: auth feature tests, authorization boundary tests, password reset tests

#### Settings And Platform Control Artifact Checklist

- `Migration`: store_settings, modules, content_settings or equivalent configuration storage
- `Model`: StoreSetting, ModuleSetting, SeoSetting, PolicyPage
- `Repository`: SettingsRepository, ModuleRepository
- `Service`: StoreSettingsService, ModuleToggleService, BrandingService
- `Controller`: StoreSettingsController, ModuleController, SeoSettingsController
- `Controller Methods`: show, update, createModuleIfNeeded, editModuleIfNeeded, deleteModuleIfNeeded, enableModule, disableModule, publishPolicy
- `Request`: UpdateStoreSettingsRequest, UpdateSeoSettingsRequest, ToggleModuleRequest
- `Policy`: SettingsPolicy, ModulePolicy
- `Route`: admin settings routes and optional API settings routes
- `Seeder`: default settings seeder
- `Test`: settings update tests, module toggle tests, audit logging tests

#### Catalog Artifact Checklist

- `Migration`: categories, products, product_variants, product_images, product_category pivot if needed
- `Model`: Category, Product, ProductVariant, ProductImage
- `Repository`: CategoryRepository, ProductRepository, ProductVariantRepository
- `Service`: CategoryService, ProductService, ProductMediaService, ProductPublishService
- `Controller`: CategoryController, ProductController, ProductVariantController, ProductMediaController
- `Controller Methods`: index, show, store, update, destroy, reorder, publish, unpublish, archive, restoreIfNeeded, uploadImage, removeImage
- `Request`: StoreCategoryRequest, UpdateCategoryRequest, StoreProductRequest, UpdateProductRequest, UploadProductImageRequest
- `Resource`: CategoryResource, ProductResource, ProductListResource, ProductDetailResource
- `Policy`: CategoryPolicy, ProductPolicy
- `Job`: image optimization job if async media processing is used
- `Event/Listener`: ProductPublished, ProductUnpublished, ProductUpdated
- `Seeder/Factory`: category and product factories/seeders
- `Test`: CRUD tests, slug/SKU uniqueness tests, publish visibility tests, media tests

#### Inventory Artifact Checklist

- `Migration`: inventories, inventory_movements, stock_reservations
- `Model`: Inventory, InventoryMovement, StockReservation
- `Repository`: InventoryRepository, InventoryMovementRepository
- `Service`: InventoryService, StockReservationService, StockAdjustmentService
- `Controller`: InventoryController, StockAdjustmentController
- `Controller Methods`: index, show, adjust, reserve, release, history, lowStock, createAdjustmentIfNeeded, editAdjustmentIfAllowed, deleteAdjustmentIfAllowed
- `Request`: StockAdjustmentRequest, InventoryFilterRequest
- `Resource`: InventoryResource, InventoryHistoryResource
- `Policy`: InventoryPolicy
- `Event/Listener`: StockAdjusted, StockReserved, StockReleased, LowStockDetected
- `Job`: low-stock alert job if asynchronous notifications are used
- `Test`: stock concurrency tests, reservation/release tests, adjustment audit tests

#### Customer Artifact Checklist

- `Migration`: customers or customer-related profile tables, addresses, notes, tags pivots if needed
- `Model`: CustomerProfile or User-as-customer extension, CustomerAddress, CustomerNote, CustomerTag
- `Repository`: CustomerRepository, AddressRepository
- `Service`: CustomerService, CustomerAddressService, CustomerSupportService
- `Controller`: CustomerController, CustomerAddressController
- `Controller Methods`: index, show, storeIfAdminAllowed, update, destroyIfAllowed, storeAddress, updateAddress, deleteAddress, addNote, removeNoteIfAllowed
- `Request`: UpdateCustomerRequest, StoreCustomerAddressRequest, UpdateCustomerAddressRequest
- `Resource`: CustomerResource, CustomerAddressResource
- `Policy`: CustomerPolicy
- `Test`: customer CRUD/list tests, address tests, note/tag tests

#### Cart Artifact Checklist

- `Migration`: carts, cart_items
- `Model`: Cart, CartItem
- `Repository`: CartRepository, CartItemRepository
- `Service`: CartService, CartMergeService, CartPricingService
- `Controller`: CartController
- `Controller Methods`: show, createIfMissing, addItem, updateItem, removeItem, clear, merge
- `Request`: AddCartItemRequest, UpdateCartItemRequest
- `Resource`: CartResource, CartItemResource
- `Policy`: cart ownership rules or guard middleware
- `Event/Listener`: CartItemAdded, CartItemUpdated, CartMerged
- `Test`: guest cart tests, logged-in cart tests, merge tests, stale item handling tests

#### Promotions And Coupons Artifact Checklist

- `Migration`: coupons, coupon_redemptions, optional promotion_campaigns or promotion_rules if needed for baseline offers
- `Model`: Coupon, CouponRedemption, PromotionCampaign if campaign scheduling is implemented
- `Repository`: CouponRepository, CouponRedemptionRepository, PromotionCampaignRepository if used
- `Service`: CouponService, CouponValidationService, DiscountCalculationService, CouponRedemptionService, PromotionSchedulingService if used
- `Controller`: CouponController, CouponApplicationController, PromotionCampaignController if used
- `Controller Methods`: index, show, store, update, destroy, activate, deactivate, validateCoupon, applyToCart, removeFromCart, recordRedemption, usageReport
- `Request`: StoreCouponRequest, UpdateCouponRequest, ValidateCouponRequest, ApplyCouponRequest, CouponFilterRequest
- `Resource`: CouponResource, CouponListResource, CouponValidationResultResource, CouponRedemptionResource
- `Policy`: CouponPolicy with `coupon.manage`, `coupon.view`, and reporting permissions
- `Route`: admin coupon CRUD routes, customer/guest cart coupon apply/remove routes, checkout validation hooks
- `Event/Listener`: CouponCreated, CouponUpdated, CouponApplied, CouponRemoved, CouponRedeemed, CouponExpired
- `Job/Command`: scheduled coupon activation/expiration and optional campaign status updates
- `Seeder/Factory`: coupon factory, redemption factory, baseline sample coupon seeder if needed
- `Test`: coupon CRUD tests, active date tests, minimum order tests, usage limit tests, customer limit tests, product/category eligibility tests, free-delivery tests, checkout concurrency tests

#### Checkout And Order Creation Artifact Checklist

- `Migration`: orders, order_items, order_addresses, checkout_sessions if used
- `Model`: Order, OrderItem, OrderAddress, CheckoutSession if needed
- `Repository`: OrderRepository, CheckoutRepository
- `Service`: CheckoutService, OrderCreationService, PricingSnapshotService
- `Controller`: CheckoutController
- `Controller Methods`: validateCheckout, shippingOptions, createCheckoutSessionIfNeeded, updateCheckoutSessionIfNeeded, placeOrder, confirmation
- `Request`: CheckoutValidationRequest, PlaceOrderRequest
- `Resource`: CheckoutSummaryResource, OrderConfirmationResource
- `Policy`: checkout/customer ownership and creation permissions
- `Event/Listener`: OrderPlaced, CheckoutFailed, OrderConfirmed
- `Job`: post-order email/notification job, post-order inventory sync jobs
- `Notification`: order confirmation notifications
- `Test`: checkout happy path, rollback path, idempotency tests, price snapshot tests

#### Orders And Fulfillment Artifact Checklist

- `Migration`: order_status_histories, returns, shipments, shipment_items if needed
- `Model`: OrderStatusHistory, ReturnRequest, Shipment
- `Repository`: OrderRepository, ShipmentRepository, ReturnRepository
- `Service`: OrderService, OrderStatusService, CancellationService, ReturnService, ShipmentService
- `Controller`: OrderController, ShipmentController, ReturnController
- `Controller Methods`: index, show, updateStatus, cancel, createShipment, updateShipmentIfAllowed, deleteShipmentIfAllowed, trackShipment, requestReturn, approveReturn, rejectReturn
- `Request`: UpdateOrderStatusRequest, CancelOrderRequest, CreateShipmentRequest, ReturnRequestStoreRequest
- `Resource`: OrderResource, OrderTimelineResource, ShipmentResource
- `Policy`: OrderPolicy, ShipmentPolicy, ReturnPolicy
- `Event/Listener`: OrderStatusChanged, OrderCancelled, ShipmentCreated, ReturnRequested
- `Notification`: status update notifications
- `Test`: status transition tests, cancel flow tests, shipment tests, return flow tests

#### Payments Artifact Checklist

- `Migration`: payments, payment_transactions, webhook_logs
- `Model`: Payment, PaymentTransaction, WebhookLog
- `Repository`: PaymentRepository, PaymentTransactionRepository
- `Service`: PaymentService, GatewayResolverService, WebhookProcessingService
- `Controller`: PaymentController, PaymentWebhookController
- `Controller Methods`: indexIfAdminNeeded, showStatus, createManualPaymentIfNeeded, updateManualPaymentIfNeeded, deleteManualPaymentIfAllowed, markPaid, markFailed, handleWebhook
- `Request`: ManualPaymentUpdateRequest, WebhookVerificationRequest if wrapped
- `Resource`: PaymentResource
- `Policy`: PaymentPolicy
- `Event/Listener`: PaymentReceived, PaymentFailed, WebhookProcessed
- `Job`: async webhook processing/retry job if needed
- `Test`: COD tests, manual update tests, webhook verification/idempotency tests

#### Shipping Artifact Checklist

- `Migration`: delivery_zones, delivery_rates, shipments, courier_webhooks
- `Model`: DeliveryZone, DeliveryRate, Shipment, CourierWebhookLog
- `Repository`: DeliveryZoneRepository, DeliveryRateRepository, ShipmentRepository
- `Service`: ShippingService, DeliveryRateResolverService, CourierWebhookService
- `Controller`: DeliveryZoneController, DeliveryRateController, ShipmentController, CourierWebhookController
- `Controller Methods`: index, show, store, update, destroy, resolveRates, createShipmentIfNeeded, updateTracking, deleteShipmentIfAllowed, handleWebhook
- `Request`: StoreDeliveryZoneRequest, UpdateDeliveryRateRequest, UpdateTrackingRequest
- `Resource`: DeliveryZoneResource, DeliveryRateResource, ShipmentResource
- `Policy`: ShippingPolicy, ShipmentPolicy
- `Test`: zone/rate CRUD tests, checkout shipping resolution tests, tracking update tests

#### CMS And Content Artifact Checklist

- `Migration`: pages, banners, content_blocks
- `Model`: Page, Banner, ContentBlock
- `Repository`: PageRepository, BannerRepository
- `Service`: PageService, HomepageContentService
- `Controller`: PageController, BannerController
- `Controller Methods`: index, show, store, update, destroy, publish, unpublish, archiveIfNeeded
- `Request`: StorePageRequest, UpdatePageRequest, StoreBannerRequest
- `Resource`: PageResource, BannerResource
- `Policy`: PagePolicy, BannerPolicy
- `Test`: page CRUD tests, publish tests, storefront visibility tests

#### Reporting Artifact Checklist

- `Migration`: export_jobs or reporting cache tables if needed
- `Model`: ExportJob, ReportSnapshot if used
- `Repository`: ReportRepository, ExportRepository
- `Service`: DashboardMetricsService, SalesReportService, InventoryReportService, ExportService
- `Controller`: DashboardController, ReportController, ExportController
- `Controller Methods`: kpis, sales, orders, inventory, customers, exportCsv, exportXlsx
- `Request`: ReportFilterRequest, ExportRequest
- `Resource`: DashboardKpiResource, ReportRowResource
- `Job`: export generation jobs
- `Test`: report query tests, export tests, permissions tests

### Recommended Additional Backend Artifacts Often Missed

- `Enum`: order status, payment status, shipment status, module status
- `Observer`: stock-related side effects, audit hooks
- `Trait`: reusable filter/sort/search behaviors
- `Scope`: active/published/visible query scopes
- `Command`: maintenance/import/export/cleanup commands
- `Policy Matrix`: module-action-role mapping document
- `Audit Mapping`: list of actions that must be logged
- `Index Review`: DB indexing checklist for filters/searches
- `Transaction Map`: list of write flows that must be wrapped in DB transactions

### B1. Platform Foundation

- [DONE] `B1.1` Create and finalize simple modular Laravel folder structure - finalized in `backend/PROJECT_STRUCTURE.md`
- [DONE] `B1.2` define module registration pattern using `config/modules.php`, `bootstrap/providers.php`, and module providers - finalized in `backend/PROJECT_STRUCTURE.md`
- [DONE] `B1.3` create shared base controller, service, repository, and request patterns
- [DONE] `B1.4` define global exception handling approach - finalized in `backend/PROJECT_STRUCTURE.md`
- [DONE] `B1.5` define global validation error response pattern
- [DONE] `B1.6` define logging middleware and trace correlation strategy - finalized in `backend/PROJECT_STRUCTURE.md`
- [DONE] `B1.7` set up environment configuration structure - finalized in `backend/PROJECT_STRUCTURE.md`
- [PARTIAL] `B1.8` create seed and bootstrap strategy for baseline data
- [PARTIAL] `B1.9` define feature/module enable-disable strategy
- [DONE] `B1.10` define internal coding standards for modules - finalized in `backend/MODULE_CODING_STANDARDS.md`

### B2. Authentication And Authorization

- [DONE] `B2.1` implement admin authentication domain
- [DONE] `B2.2` implement customer authentication domain
- [DONE] `B2.3` implement password reset flow
- [DONE] `B2.4` implement session/token lifecycle handling
- [DONE] `B2.5` implement roles model -front end needed later
- [DONE] `B2.6` implement permissions model-front end needed later
- [DONE] `B2.7` implement policy/gate enforcement strategy
- [DONE] `B2.8` implement audit events for auth-sensitive actions
- [DONE] `B2.9` add rate limiting around auth endpoints
- [DONE] `B2.10` verify authorization boundaries across modules

### B3. Store Configuration And Platform Controls

- [DONE] `B3.1` store profile settings
- [DONE] `B3.2` branding settings
- [DONE] `B3.3` SEO defaults
- [DONE] `B3.4` policy page settings
- [DONE] `B3.5` basic module toggles
- [DONE] `B3.6` business configuration storage
- [DONE] `B3.7` settings validation rules
- [DONE] `B3.8` audit trail for settings changes

### B4. Catalog Domain

- [DONE] `B4.1` category entity and relationships
- [PARTIAL] `B4.2` category hierarchy and ordering
- [DONE] `B4.3` product entity
- [DONE] `B4.4` product variants
- [DONE] `B4.5` product images/media
- [DONE] `B4.6` product publish/unpublish rules
- [DONE] `B4.7` slug generation and uniqueness
- [DONE] `B4.8` SKU uniqueness and validation
- [PENDING] `B4.9` SEO metadata support
- [DONE] `B4.10` catalog status and visibility rules

### B5. Inventory Domain

- [DONE] `B5.1` stock model design
- [DONE] `B5.2` inventory transaction log
- [DONE] `B5.3` stock adjustment workflow
- [DONE] `B5.4` stock reservation workflow
- [DONE] `B5.5` stock release workflow
- [DONE] `B5.6` low stock rule support
- [DONE] `B5.7` concurrency protection for stock mutation
- [DONE] `B5.8` inventory visibility rules by product and variant

### B6. Customer Domain

- [DONE] `B6.1` customer profile domain
- [DONE] `B6.2` customer address domain
- [DONE] `B6.3` customer order linkage
- [DONE] `B6.4` customer tags/notes support
- [DONE] `B6.5` customer status management
- [DONE] `B6.6` customer support-oriented data structure

### B7. Cart Domain

- [DONE] `B7.1` guest cart lifecycle
- [DONE] `B7.2` customer cart lifecycle
- [DONE] `B7.3` cart line item rules
- [DONE] `B7.4` cart quantity update rules
- [DONE] `B7.5` cart price recalculation pipeline
- [PENDING] `B7.6` cart merge logic after login
- [DONE] `B7.7` unavailable item handling
- [DONE] `B7.8` cart persistence and recovery behavior

### B8. Promotions And Coupons Domain

#### Group 1: Coupon Foundation

- [DONE] `B8.1` coupon entity, code uniqueness, and status lifecycle
- [DONE] `B8.2` fixed, percentage, and free-delivery discount types
- [DONE] `B8.3` coupon start/end date and scheduled activation/expiration behavior

#### Group 2: Coupon Eligibility Rules

- [DONE] `B8.4` minimum order value and cart eligibility validation
- [DONE] `B8.5` product/category eligibility rules where enabled
- [DONE] `B8.6` total usage limit and per-customer usage limit enforcement

#### Group 3: Cart And Checkout Discount Integration

- [DONE] `B8.7` cart coupon apply/remove flow for guest and customer carts
- [DONE] `B8.8` discount calculation integration with cart totals and checkout totals

#### Group 4: Redemption And Usage Consistency

- [DONE] `B8.9` coupon redemption recording during order creation
- [DONE] `B8.10` atomic usage count updates inside checkout/order transaction

#### Group 5: Audit, Reporting, And Campaign Baseline

- [DONE] `B8.11` coupon audit events for create/update/delete/activate/deactivate
- [DONE] `B8.12` basic coupon performance data for reporting
- [PARTIAL] `B8.13` product offer price, flash sale, and campaign banner baseline alignment

### B9. Checkout And Order Creation

- [DONE] `B9.1` checkout validation pipeline
- [DONE] `B9.2` address selection and creation flow
- [DONE] `B9.3` shipping resolution flow
- [DONE] `B9.4` payment method resolution flow
- [DONE] `B9.5` coupon and discount revalidation before order creation
- [DONE] `B9.6` order draft to order creation transaction
- [DONE] `B9.7` order item and discount snapshot capture
- [DONE] `B9.8` idempotent checkout submission handling
- [DONE] `B9.9` rollback and compensation rules
- [DONE] `B9.10` order confirmation event generation
Group 1: B9.1 + B9.2
Group 2: B9.3 + B9.4 + B9.5
Group 3: B9.6 + B9.7 + B9.8 + B9.9
Group 4: B9.10



### B10. Orders And Fulfillment

- [DONE] `B10.1` order entity lifecycle
- [DONE] `B10.2` order status machine
- [DONE] `B10.3` order history tracking
- [DONE] `B10.4` internal order notes
- [DONE] `B10.5` cancellation workflow
- [DONE] `B10.6` return request baseline
- [DONE] `B10.7` invoice generation baseline
- [DONE] `B10.8` packing slip baseline
- [DONE] `B10.9` shipment linkage

B10.1 + B10.2 + B10.3
B10.4 + B10.5
B10.7 + B10.8
B10.6 + B10.9

### B11. Payments

- [DONE] `B11.1` payment entity and states
- [DONE] `B11.2` COD workflow
- [DONE] `B11.3` manual admin payment update flow
- [DONE] `B11.4` payment gateway adapter contract
- [DONE] `B11.5` webhook verification
- [DONE] `B11.6` webhook idempotency
- [DONE] `B11.7` order-payment synchronization rules

B11.1 + B11.7
B11.2 + B11.3
B11.4
B11.5 + B11.6

### B12. Shipping And Courier //skipped

- [DONE] `B12.1` delivery zones
- [DONE] `B12.2` delivery rules and rates
- [DONE] `B12.3` shipment creation rules
- [DONE] `B12.4` tracking information support
- [PENDING] `B12.5` courier adapter baseline
- [PENDING] `B12.6` courier webhook normalization
- [PARTIAL] `B12.7` shipment state synchronization

### B13. Content And CMS Support //skip

- [PARTIAL] `B13.1` policy pages
- [PENDING] `B13.2` content pages
- [PENDING] `B13.3` homepage content blocks
- [PENDING] `B13.4` footer content configuration
- [PENDING] `B13.5` banner/hero content support

### B14. Reporting And Dashboard Data

- [DONE] `B14.1` KPI aggregation services
- [DONE] `B14.2` orders reporting queries
- [DONE] `B14.3` sales reporting queries
- [DONE] `B14.4` inventory reporting queries
- [DONE] `B14.5` customer reporting queries
- [DONE] `B14.6` coupon performance reporting queries
- [PARTIAL] `B14.7` export job infrastructure //skip

B14.1 + B14.2 + B14.3
B14.4 + B14.5 + B14.6
B14.7

### B15. Security, Audit, And Observability

- [DONE] `B15.1` audit log storage model
- [DONE] `B15.2` security event catalog
- [DONE] `B15.3` request/response logging baseline
- [DONE] `B15.4` permission hardening
- [DONE] `B15.5` input validation hardening
- [PARTIAL] `B15.6` throttling and abuse protection
- [DONE] `B15.7` exception traceability
- [PARTIAL] `B15.8` operational monitoring hooks

### B16. Testing And Delivery Infrastructure

- [PARTIAL] `B16.1` backend unit testing baseline
- [DONE] `B16.2` feature testing baseline
- [PARTIAL] `B16.3` test factories and fixtures
- [PENDING] `B16.4` CI lint/test pipeline
- [PARTIAL] `B16.5` build automation
- [PENDING] `B16.6` deployment templates
- [PENDING] `B16.7` backup and restore procedures
- [PENDING] `B16.8` rollback checklist

### Backend Deliverables

- modular Laravel backend
- stable domain models
- promotion/coupon rules integrated with cart, checkout, orders, and reporting
- shared service/repository patterns
- validated business rules
- logging and audit baseline
- deployable operational backend

## 7.2 API Workstream

This stream turns backend business logic into stable versioned contracts for admin and customer-facing use.

### API Artifact Breakdown

Plan each API module with the following engineering artifacts.

- `Route File`: public, customer-authenticated, admin-authenticated, webhook routes
- `Controller`: grouped by module and audience
- `Controller Methods`: index, show, store, update, delete, custom actions
- `Request`: filter requests, create/update requests, action requests
- `Resource`: list resource, detail resource, minimal lookup resource
- `Policy/Middleware`: guard, role, throttling, ownership checks
- `Service`: API should call shared domain services, not embed business rules
- `OpenAPI`: request/response examples, auth requirements, error cases
- `Test`: contract tests, validation tests, auth tests, permission tests, pagination/filter tests

### API Method-Level Checklist

For every endpoint, define:

- route name and URI
- HTTP method
- action type such as list/detail/create/update/delete/custom
- authentication requirement
- permission requirement
- request payload
- validation rules
- service method called
- response resource used
- error responses
- audit/logging requirements
- tests required

### A1. API Foundation

- [DONE] `A1.1` define API versioning strategy
- [DONE] `A1.2` define response envelope standard
- [DONE] `A1.3` define error response contract
- [DONE] `A1.4` define pagination contract
- [PARTIAL] `A1.5` define filtering and sorting conventions
- [DONE] `A1.6` define authentication guard strategy for APIs
- [DONE] `A1.7` define rate limiting profile by endpoint category
- [PARTIAL] `A1.8` define API documentation workflow

### A2. Auth APIs

- [PENDING] `A2.1` admin login API
- [DONE] `A2.2` customer register API
- [DONE] `A2.3` customer login API
- [DONE] `A2.4` logout API
- [DONE] `A2.5` password reset APIs
- [PENDING] `A2.6` token refresh API if applicable
- [DONE] `A2.7` current user/profile API

### A3. Settings And Platform APIs

- [DONE] `A3.1` store settings retrieval API
- [DONE] `A3.2` settings update API
- [DONE] `A3.3` module control APIs if exposed
- [PENDING] `A3.4` public store configuration API for storefront

### A4. Catalog APIs

- [DONE] `A4.1` category list API
- [PENDING] `A4.2` category tree API
- [DONE] `A4.3` product list API
- [DONE] `A4.4` product details API
- [PARTIAL] `A4.5` product filters API behavior
- [PENDING] `A4.6` search API behavior
- [DONE] `A4.7` admin catalog CRUD APIs if API-driven screens are used

### A5. Inventory APIs

- [PENDING] `A5.1` stock visibility API for admin
- [DONE] `A5.2` inventory adjustment API
- [PENDING] `A5.3` inventory history API
- [DONE] `A5.4` low-stock data API

### A6. Customer APIs

- [DONE] `A6.1` customer profile API
- [DONE] `A6.2` customer address CRUD APIs
- [DONE] `A6.3` customer order history API
- [DONE] `A6.4` admin customer management APIs

### A7. Cart APIs

- [DONE] `A7.1` create/recover cart API
- [DONE] `A7.2` add item API
- [DONE] `A7.3` update quantity API
- [DONE] `A7.4` remove item API
- [DONE] `A7.5` cart summary API
- [PENDING] `A7.6` merge cart API

### A8. Promotions And Coupons APIs

- [DONE] `A8.1` admin coupon list/detail APIs
- [DONE] `A8.2` admin coupon create/update/delete APIs
- [DONE] `A8.3` admin coupon activate/deactivate APIs
- [DONE] `A8.4` guest cart apply/remove coupon APIs
- [DONE] `A8.5` customer cart apply/remove coupon APIs
- [DONE] `A8.6` coupon validation API used by cart and checkout
- [DONE] `A8.7` coupon redemption/usage reporting API
- [PARTIAL] `A8.8` OpenAPI and Postman examples for coupon success and rejection cases

### A9. Checkout APIs

- [DONE] `A9.1` checkout validation API
- [DONE] `A9.2` shipping options resolution API
- [DONE] `A9.3` checkout submit API
- [PARTIAL] `A9.4` checkout confirmation/status API
- [DONE] `A9.5` checkout coupon revalidation and discount response fields

### A10. Order APIs

- [DONE] `A10.1` order list API for admin
- [DONE] `A10.2` order detail API
- [DONE] `A10.3` order status update API
- [DONE] `A10.4` cancellation API
- [DONE] `A10.5` return request APIs
- [PENDING] `A10.6` customer order tracking API
- [DONE] `A10.7` order discount/coupon snapshot fields in admin and customer responses

### A11. Payment APIs

- [DONE] `A11.1` payment initiation baseline
- [PARTIAL] `A11.2` payment status API
- [DONE] `A11.3` manual payment update API for admin
- [DONE] `A11.4` webhook endpoints

### A12. Shipping APIs

- [DONE] `A12.1` delivery zone/rate admin APIs
- [PARTIAL] `A12.2` shipment creation/update APIs
- [PARTIAL] `A12.3` tracking APIs
- [PENDING] `A12.4` courier webhook APIs

### A13. Reporting APIs

- [DONE] `A13.1` dashboard KPI API
- [DONE] `A13.2` sales report API
- [DONE] `A13.3` orders report API
- [DONE] `A13.4` inventory report API
- [DONE] `A13.5` customer report API
- [DONE] `A13.6` coupon report API
- [PENDING] `A13.7` export trigger/status APIs

### A14. API Quality And Governance

- `A14.1` request validation coverage
- `A14.2` resource transformer coverage
- [DONE] `A14.3` OpenAPI documentation updates
- `A14.4` contract testing
- `A14.5` API permission matrix validation
- `A14.6` API performance review for list/search endpoints

### API Deliverables

- versioned API surface
- documented request/response contracts
- admin-facing and customer-facing endpoints
- coupon validation, apply/remove, redemption, and discount snapshot contracts
- secured and validated API layer

## 7.3 Admin Frontend Workstream

This stream creates the operational interface used by administrators to manage the platform.

### Admin Frontend Artifact Breakdown

Each admin module should be broken down beyond just a page name.

- `Route`: web route or admin API route dependency
- `Controller`: page controller or action controller
- `Controller Methods`: index, create, store, edit, update, destroy, detail, export
- `View`: main Blade page
- `Layout`: shared admin layout dependency
- `Partial`: form partials, filters partials, table partials, modal partials
- `Component`: reusable badges, stats cards, pagination wrappers, breadcrumbs
- `Request`: form validation request classes
- `Service`: page data aggregation services where multiple datasets are needed
- `Table Columns`: list page field mapping
- `Filters`: query params, search fields, status/date filters
- `Actions`: create, edit, publish, archive, delete, export, bulk actions
- `States`: empty, loading, validation error, permission denied, no results
- `Test`: controller/view tests, permission tests, critical action tests

### Recommended Admin Screen Checklist

For every admin screen, define:

- page purpose
- route name
- supported actions such as create, edit, delete, view, publish, archive, export
- data source
- columns/widgets shown
- filters/search options
- create/edit form fields
- validation rules
- success/error feedback
- role permissions
- audit log behavior
- responsive behavior
- acceptance criteria

### F1. Admin Frontend Foundation

- [DONE] `F1.1` choose admin rendering strategy
- [DONE] `F1.2` create shared admin layout
- [PARTIAL] `F1.3` configure local asset strategy
- [DONE] `F1.4` create navigation structure
- [DONE] `F1.5` create breadcrumb/title pattern
- [DONE] `F1.6` create reusable admin page shell
- [PARTIAL] `F1.7` define shared UI helpers for cards, tables, badges, forms

### F2. Admin Authentication Screens

- [DONE] `F2.1` login screen
- [DONE] `F2.2` forgot password screen
- [DONE] `F2.3` reset password screen
- [DONE] `F2.4` session expiration handling
- [DONE] `F2.5` unauthorized access screen

### F3. Dashboard

- [DONE] `F3.1` dashboard layout
- [DONE] `F3.2` KPI cards
- [DONE] `F3.3` recent activity widgets
- [DONE] `F3.4` quick navigation actions
- [DONE] `F3.5` low-stock/order/customer summary panels
- [DONE] `F3.6` dashboard data loading states

### F4. Settings And Platform Controls

- [DONE] `F4.1` store settings screens
- [DONE] `F4.2` branding screens
- [DONE] `F4.3` SEO settings screens
- [DONE] `F4.4` policy/content management screens
- [DONE] `F4.5` module control screens

### F5. Category Management

- [DONE] `F5.1` category list screen
- [DONE] `F5.2` category create screen
- [DONE] `F5.3` category edit screen
- [PARTIAL] `F5.4` hierarchy/reorder UI
- [DONE] `F5.5` validation and error presentation

### F6. Product Management

- [DONE] `F6.1` product list screen
- [DONE] `F6.2` product filter/search UI
- [DONE] `F6.3` product create screen
- [DONE] `F6.4` product edit screen
- [DONE] `F6.5` variant management UI
- [PARTIAL] `F6.6` media upload/ordering UI
- [DONE] `F6.7` publish status controls
- [PARTIAL] `F6.8` SEO data fields

### F7. Inventory Management

- [DONE] `F7.1` stock list screen
- [DONE] `F7.2` stock adjustment screen
- [DONE] `F7.3` inventory history screen
- [DONE] `F7.4` low-stock visibility panel

### F8. Customer Management

- [DONE] `F8.1` customer list screen
- [DONE] `F8.2` customer filter/search UI
- [DONE] `F8.3` customer detail screen
- [DONE] `F8.4` address management UI
- [DONE] `F8.5` tags/notes UI
- [DONE] `F8.6` customer order history screen

### F9. Order Management

- [DONE] `F9.1` order list screen
- [DONE] `F9.2` order filter/search UI
- [DONE] `F9.3` order detail timeline
- [DONE] `F9.4` status change controls
- [DONE] `F9.5` cancellation/return handling screens
- [DONE] `F9.6` shipment and tracking UI
- [DONE] `F9.7` invoice/packing slip access points

### F10. Promotions And Coupons Management

- [DONE] `F10.1` coupon list screen
- [DONE] `F10.2` coupon create/edit form
- [DONE] `F10.3` coupon status controls for activate/deactivate/archive/delete
- [DONE] `F10.4` discount type, value, minimum order, date window, and usage limit fields
- [DONE] `F10.5` product/category eligibility selector where enabled
- [DONE] `F10.6` coupon redemption/usage detail screen
- [DONE] `F10.7` coupon performance summary widgets
- [DONE] `F10.8` coupon validation, permission, audit, empty, and error states

### F11. Payment And Shipping Management

- [DONE] `F11.1` payment status/admin payment controls
- [DONE] `F11.2` delivery zones UI
- [DONE] `F11.3` shipping rates UI
- [DONE] `F11.4` shipment management UI

### F12. Reports And Exports

- [DONE] `F12.1` sales report screen
- [DONE] `F12.2` orders report screen
- [DONE] `F12.3` inventory report screen
- [DONE] `F12.4` customer report screen
- [PENDING] `F12.5` coupon performance report screen
- [DONE] `F12.6` export trigger/download UI

### F13. Admin UX Consistency And Quality

- [PARTIAL] `F13.1` shared table patterns
- [PARTIAL] `F13.2` shared form validation patterns
- [PARTIAL] `F13.3` shared confirmation modal/prompt patterns
- [PARTIAL] `F13.4` shared status badge conventions
- [PARTIAL] `F13.5` responsive admin layout review
- [PARTIAL] `F13.6` accessibility baseline review

### Admin Frontend Deliverables

- reusable admin layout
- module-based management screens
- coupon management and coupon performance visibility
- operational dashboard
- consistent admin UX for core commerce flows

## 7.4 Storefront Workstream

This stream is deliberately placed last in the document and later in implementation order, because it depends heavily on completed backend and API contracts.

### Storefront Artifact Breakdown

Each storefront feature should be planned with implementation artifacts, not only page names.

- `Route/Page`: storefront route and page container
- `Layout`: main shell, auth shell, checkout shell if separate
- `Component`: reusable sections, product cards, filters, price blocks, form blocks
- `Composable/Hook/Service`: API calling layer and local state helpers
- `State`: cart state, auth state, checkout state, filters state
- `API Dependency`: endpoint contract consumed by the page
- `SEO`: title, description, canonical, structured metadata if needed
- `Loading/Error/Empty States`: UX states for every data-driven page
- `Analytics Hook`: optional event points for product views, add-to-cart, checkout
- `Test`: page rendering tests, integration tests, checkout/cart flow tests, manual QA checklist

### Recommended Storefront Screen Checklist

For every storefront page or flow, define:

- business purpose
- route/path
- API dependencies
- whether create/edit/delete actions exist for customer-owned data
- sections/components required
- user interactions
- validation/error states
- loading/empty states
- SEO/meta requirements
- responsive/mobile behavior
- accessibility considerations
- acceptance criteria

### S1. Storefront Foundation

- `S1.1` choose storefront rendering strategy
- `S1.2` define storefront routing structure
- `S1.3` define layout structure
- `S1.4` define design system tokens
- `S1.5` define SEO/meta rendering approach
- `S1.6` define public API integration layer
- `S1.7` define auth/cart persistence approach

### S2. Global Storefront Shell

- `S2.1` header/navigation
- `S2.2` mobile navigation
- `S2.3` footer
- `S2.4` search entry point
- `S2.5` announcement/banner support
- `S2.6` account/cart quick access

### S3. Homepage

- `S3.1` homepage content structure
- `S3.2` hero/banner section
- `S3.3` featured categories section
- `S3.4` featured products section
- `S3.5` promotional blocks
- `S3.6` CMS-driven sections
- `S3.7` homepage SEO/meta wiring

### S4. Category Browsing

- `S4.1` category landing page
- `S4.2` category tree navigation
- `S4.3` listing grid/list toggles
- `S4.4` sorting controls
- `S4.5` filter panels
- `S4.6` pagination or infinite loading pattern
- `S4.7` empty-state handling

### S5. Product Discovery

- `S5.1` search results page
- `S5.2` autocomplete or suggested search baseline
- `S5.3` filter persistence behavior
- `S5.4` no-results handling

### S6. Product Detail Experience

- `S6.1` product gallery
- `S6.2` product summary block
- `S6.3` variant selection UI
- `S6.4` stock/availability presentation
- `S6.5` quantity selector
- `S6.6` add-to-cart interaction
- `S6.7` product attributes/specification section
- `S6.8` related product section
- `S6.9` SEO/meta and share metadata

### S7. Cart Experience

- `S7.1` mini cart
- `S7.2` full cart page
- `S7.3` update quantity interaction
- `S7.4` remove item interaction
- `S7.5` cart totals summary
- `S7.6` invalid item/stock change messaging
- `S7.7` guest cart persistence
- `S7.8` coupon apply/remove interaction
- `S7.9` discount, free-delivery, invalid coupon, and expired coupon messaging

### S8. Customer Authentication And Account

- `S8.1` customer registration screen
- `S8.2` customer login screen
- `S8.3` forgot/reset password screens
- `S8.4` account dashboard
- `S8.5` profile management
- `S8.6` address book
- `S8.7` order history
- `S8.8` order detail/tracking view

### S9. Checkout

- `S9.1` checkout shell
- `S9.2` contact/address step
- `S9.3` shipping option step
- `S9.4` payment option step
- `S9.5` order summary step
- `S9.6` coupon and discount revalidation feedback
- `S9.7` validation/error messaging
- `S9.8` duplicate submit protection
- `S9.9` success/confirmation page with discount snapshot

### S10. Content And Informational Pages

- `S10.1` about page
- `S10.2` contact page
- `S10.3` policy pages
- `S10.4` FAQ/help content baseline

### S11. Storefront UX Quality

- `S11.1` responsive behavior across devices
- `S11.2` performance optimization for listing/detail pages
- `S11.3` image optimization strategy
- `S11.4` accessibility baseline review
- `S11.5` SEO baseline review
- `S11.6` analytics hook points if required

### Storefront Deliverables

- customer-facing shopping experience
- category/product discovery flows
- cart, coupon, and checkout flows
- customer account area
- content and trust pages

## 8. Milestones

### M1. Platform Foundation Ready

- modular backend structure exists
- auth baseline exists
- logging and exception handling exist

### M2. Core Commerce Backend Ready

- catalog, inventory, customer, cart, baseline promotions/coupons, checkout, orders, payments, and shipping rules are implemented

### M3. API Contract Ready

- major API contracts are versioned and documented
- request/response patterns are stable
- coupon apply/remove, checkout discount, and order discount snapshot contracts are stable

### M4. Admin Operations Ready

- admin users can manage store, catalog, customers, orders, inventory, coupons, and reports

### M5. Storefront MVP Ready

- customer can browse, search, view product, add to cart, apply a valid coupon where available, checkout, and track order

### M6. Go-Live Readiness

- security checks completed
- deployment path validated
- rollback and backup procedures documented

## 9. Cross-Workstream Verification

### Functional Verification

- every core module has happy-path validation
- failure paths are handled gracefully
- business rules are enforced consistently across UI and API
- coupon expiry, eligibility, usage limit, and redemption rules are verified across cart, checkout, and order creation

### Technical Verification

- route integrity
- API contract integrity
- permission enforcement
- database transaction safety
- logging and traceability
- asset loading and UI rendering

### Release Verification

- environment setup verified
- migrations and seeders verified
- build and deployment verified
- rollback procedure verified

## 10. Recommended Delivery Order By Module

1. foundation
2. auth and RBAC
3. settings/platform control
4. categories
5. products and variants
6. inventory
7. customers
8. cart
9. promotions/coupons
10. checkout
11. orders
12. payments
13. shipping
14. content/CMS
15. reports/dashboard
16. admin frontend completion
17. storefront completion
18. optimization and release hardening

## 11. Definition Of Done

A module is considered done only when:

- backend business rules are implemented
- routes/endpoints/views are wired
- validation is present
- error handling is present
- permissions are enforced where needed
- happy path has been tested
- major failure paths have been tested
- documentation is updated if contracts changed

## 12. Related Documents

- [Ecommerce_Platform_WBS.md](./Ecommerce_Platform_WBS.md)
- [Ecommerce_Platform_HLD.md](./Ecommerce_Platform_HLD.md)
- [Ecommerce_Platform_LLD.md](./Ecommerce_Platform_LLD.md)
- [Ecommerce_Platform_SRS.md](./Ecommerce_Platform_SRS.md)
- [Ecommerce_Platform_OpenAPI.yaml](./Ecommerce_Platform_OpenAPI.yaml)
- [Ecommerce_Platform_Testing_Strategy.md](./Ecommerce_Platform_Testing_Strategy.md)
- [Ecommerce_Platform_Security_Design.md](./Ecommerce_Platform_Security_Design.md)

## 13. Notes

- Use this file as the master implementation tracker and sequencing guide.
- Use the existing WBS file when development-hour estimates are needed.
- Add status markers beside each task if this document becomes the active execution tracker.
- If Phase 2 multi-vendor work is approved, append a dedicated marketplace workstream after the core storefront delivery.
- Before starting any module, expand it into engineering artifacts using Sections 6.1 and 6.2 of this document.
