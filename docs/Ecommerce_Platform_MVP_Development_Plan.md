# Ecommerce Platform MVP Development Plan

This is the practical development checklist for the MVP build. The broader `Ecommerce_Platform_Master_Project_Plan.md` remains the full roadmap; this file tracks what should be built for the first usable ecommerce release and marks what is already completed.

## Status Legend

- `[Complete]` Implemented and covered by tests or documented verification.
- `[Partial]` Useful foundation exists, but the full MVP workflow is not done.
- `[Pending]` Not implemented yet.
- `[Later]` Not required for MVP.

## MVP Goal

Deliver a working ecommerce flow:

1. Admin can configure store basics.
2. Admin can manage users, categories, products, variants, images, inventory, customers, orders, and settings.
3. Customers can register/login, manage profile and addresses, browse products, add to cart, checkout, and view orders.
4. Inventory is reserved/released safely during checkout/order lifecycle.
5. Storefront can display catalog, stock availability, policy pages, cart, checkout, and account screens.

## Current Completed Foundation

### B1 Platform Foundation

| Slice | Status | Notes |
|---|---|---|
| B1.1 modular Laravel structure | Complete | Module structure and route/provider loading are in place. |
| B1.2 module registration pattern | Complete | `config/modules.php`, module routes, and provider loading are active. |
| B1.3 shared base patterns | Complete | Base service/repository/support patterns exist. |
| B1.4 global exception handling | Complete | Standard API error shape is active. |
| B1.5 validation error response pattern | Complete | API validation returns standard JSON shape. |
| B1.6 logging and trace correlation | Complete | Request trace handling exists. |
| B1.7 environment configuration structure | Complete | Config/env conventions are documented and used. |
| B1.8 seed/bootstrap strategy | Partial | Admin seed and RBAC seed exist; more business seeders can be added later. |
| B1.9 feature/module enable-disable strategy | Partial | Module toggles exist; route-level enforcement is still pending. |
| B1.10 internal coding standards | Complete | `backend/MODULE_CODING_STANDARDS.md` is active. |

### B2 Authentication And Authorization

| Slice | Status | Notes |
|---|---|---|
| B2.1 admin authentication | Complete | Admin session auth implemented. |
| B2.2 customer authentication | Complete | Customer Sanctum auth implemented. |
| B2.3 password reset flow | Complete | Admin and customer password reset implemented. |
| B2.4 session/token lifecycle | Complete | Customer token expiry, logout, logout-all, and token caps implemented. |
| B2.5 roles model | Complete | Spatie roles implemented. |
| B2.6 permissions model | Complete | Spatie permissions and config catalog implemented. |
| B2.7 policy/gate enforcement | Complete | Permission middleware and super-admin bypass are active. |
| B2.8 auth audit events | Complete | Auth-sensitive audit events implemented. |
| B2.9 auth rate limiting | Complete | Auth endpoints are throttled. |
| B2.10 authorization boundary verification | Complete | Cross-module boundary tests exist. |

### B3 Store Configuration And Platform Controls

| Slice | Status | Notes |
|---|---|---|
| B3.1 store profile settings | Complete | Database-backed store profile settings exist. |
| B3.2 branding settings | Complete | Branding fields and uploads are supported in settings. |
| B3.3 SEO defaults | Complete | SEO default settings exist. |
| B3.4 policy page settings | Complete | Policy content settings exist. |
| B3.5 basic module toggles | Complete | Module toggle settings exist. |
| B3.6 business configuration storage | Complete | Shared settings storage exists. |
| B3.7 settings validation rules | Complete | Settings request validation exists. |
| B3.8 audit trail for settings changes | Complete | Settings updates are audited. |

### B4 Catalog Domain

| Slice | Status | Notes |
|---|---|---|
| B4.1 category entity and relationships | Complete | Category model, parent/child relationship, admin AJAX/API CRUD exist. |
| B4.2 category hierarchy and ordering | Partial | Parent hierarchy and `sort_order` exist; drag/drop ordering UI is not built. |
| B4.3 product entity | Complete | Product model, admin AJAX/API CRUD exist. |
| B4.4 product variants | Complete | Variant model and product variant management exist. |
| B4.5 product images/media | Complete | Image path and primary upload support exist. |
| B4.6 product publish/unpublish rules | Complete | Publish/unpublish actions and rules exist. |
| B4.7 slug generation and uniqueness | Complete | Product slug generation and uniqueness exist. |
| B4.8 SKU uniqueness and validation | Complete | SKU normalization and uniqueness checks exist. |
| B4.9 SEO metadata support | Pending | Product/category SEO overrides still need implementation. |
| B4.10 catalog status and visibility rules | Complete | Public catalog visibility rules exist. |

### B5 Inventory Domain

| Slice | Status | Notes |
|---|---|---|
| B5.1 stock model design | Complete | `inventory_stocks` table and model exist. |
| B5.2 inventory transaction log | Complete | `inventory_transactions` table and model exist. |
| B5.3 stock adjustment workflow | Complete | Backend/API adjustment workflow exists. |
| B5.4 stock reservation workflow | Complete | Backend/API reservation workflow exists. |
| B5.5 stock release workflow | Complete | Backend/API release workflow exists. |
| B5.6 low stock rule support | Complete | Low-stock threshold and computed state exist. |
| B5.7 concurrency protection | Complete | Transactions, row locks, and retry attempts are implemented. |
| B5.8 inventory visibility rules | Complete | Public catalog respects variant stock visibility. |

Current gap: inventory is backend/API only. Admin inventory UI is still needed for MVP operations.

### B6 Customer Domain

| Slice | Status | Notes |
|---|---|---|
| B6.1 customer profile domain | Complete | Customer profile API exists. |
| B6.2 customer address domain | Complete | Customer address CRUD API exists. |
| B6.3 customer order linkage | Partial | Customer/address foundation exists; order table does not exist yet. |
| B6.4 customer tags/notes support | Later | Useful for admin CRM, not MVP-critical. |
| B6.5 customer status management | Pending | Admin customer status controls still needed. |
| B6.6 customer support-oriented data structure | Later | Can wait until after order/customer support workflows mature. |

## MVP Backend Work Remaining

### B7 Cart Domain

| Slice | Status | MVP Decision |
|---|---|---|
| B7.1 guest cart lifecycle | Complete | Guest token cart API exists with add/update/remove/clear and stock-aware validation. |
| B7.2 customer cart lifecycle | Complete | Authenticated customer cart API exists with ownership boundaries. |
| B7.3 cart line item rules | Complete | One line per variant, quantity/line limits, sellable variant and positive price checks. |
| B7.4 cart quantity update rules | Complete | Exact quantity replacement with min/max and stock checks. |
| B7.5 cart price recalculation pipeline | Complete | Cart load/mutation refreshes line snapshots and subtotal from current variant price. |
| B7.6 cart merge after login | Pending | Required for good UX. |
| B7.7 unavailable item handling | Complete | Cart keeps unavailable lines with reason and excludes them from totals. |
| B7.8 cart persistence and recovery | Complete | Guest token recovery, expiry extension, and customer active-cart recovery exist. |

### B8 Checkout And Order Creation

| Slice | Status | MVP Decision |
|---|---|---|
| B8.1 checkout validation pipeline | Complete | Customer cart and address readiness validation endpoint exists. |
| B8.2 address selection and creation flow | Complete | Existing address selection and inline address creation are supported. |
| B8.3 shipping resolution flow | Complete | Config-based standard/express shipping resolution exists. |
| B8.4 payment method resolution flow | Complete | Config-based COD/manual bank payment resolution exists. |
| B8.5 order creation transaction | Complete | Checkout submit creates order, reserves stock, and completes cart transactionally. |
| B8.6 order item snapshot capture | Complete | Order items snapshot product/variant/price/quantity from cart lines. |
| B8.7 idempotent checkout submission | Complete | Customer/idempotency key prevents duplicate orders and reservations. |
| B8.8 rollback and compensation rules | Complete | Validation failures roll back order, item, cart, and reservation changes. |
| B8.9 order confirmation event | Complete | Order confirmation event and audit record are generated once per new order. |

### B9 Orders And Fulfillment

| Slice | Status | MVP Decision |
|---|---|---|
| B9.1 order entity lifecycle | Pending | Required. |
| B9.2 order status machine | Pending | Required. |
| B9.3 order history tracking | Pending | Required. |
| B9.4 internal order notes | Pending | Useful for admin MVP. |
| B9.5 cancellation workflow | Pending | Required because inventory release depends on it. |
| B9.6 return request baseline | Later | Can wait unless business needs returns on day one. |
| B9.7 invoice generation baseline | Later | Can be simple printable page later. |
| B9.8 packing slip baseline | Later | Can be later. |
| B9.9 shipment linkage | Partial/Pending | Basic courier/status field needed; advanced shipment tracking later. |

### B10 Payments

| Slice | Status | MVP Decision |
|---|---|---|
| Payment method configuration | Pending | Required. |
| COD/manual payment baseline | Pending | Required for MVP if online gateway is not ready. |
| Online payment gateway abstraction | Pending | Optional for MVP depending on launch requirement. |
| Payment status tracking | Pending | Required. |
| Payment audit/history | Pending | Required. |
| Refund workflow | Later | Can be phase 2 unless required. |

### B11 Shipping And Courier

| Slice | Status | MVP Decision |
|---|---|---|
| Shipping zone/rate baseline | Pending | Required. |
| Courier method configuration | Pending | Required if delivery is managed externally. |
| Delivery charge calculation | Pending | Required. |
| Shipment status fields | Pending | Required at basic order level. |
| Courier API integration | Later | Can be manual for MVP. |

### B12 Content And CMS

| Slice | Status | MVP Decision |
|---|---|---|
| Policy settings storage | Complete | Existing B3.4. |
| Public policy pages/API | Pending | Required for storefront. |
| Basic static pages | Pending | Useful for MVP. |
| Blog/content module | Later | Not required for MVP. |

### B13 Reports And Dashboard

| Slice | Status | MVP Decision |
|---|---|---|
| Admin dashboard shell | Partial | Exists, but real metrics are pending. |
| Sales/order summary metrics | Pending | Required after orders exist. |
| Low-stock panel | Pending | Required after inventory UI. |
| Customer/order/product counters | Pending | Useful for MVP. |
| Export/reporting tools | Later | Not MVP-critical. |

### B14 Security, Audit, And Observability

| Slice | Status | MVP Decision |
|---|---|---|
| Auth audit | Complete | Existing B2.8. |
| Settings audit | Complete | Existing B3.8. |
| Request trace/logging | Complete | Existing B1.6. |
| Order/payment/inventory audit events | Partial | Inventory transaction log exists; order/payment audit pending. |
| Rate limiting | Complete for auth | Add module-specific throttles later where needed. |

### B15 Testing And Delivery Infrastructure

| Slice | Status | MVP Decision |
|---|---|---|
| Feature test baseline | Complete | Current backend has strong coverage. |
| API contract/Postman updates | Partial | Customer auth docs exist; newer modules need collection updates. |
| Browser/manual QA checklist | Pending | Required before MVP release. |
| Deployment checklist | Pending | Required before MVP release. |
| Seed/demo data | Partial | Basic seed exists; catalog/order demo seed needed. |

## MVP Admin Frontend Work Remaining

Admin pages should use the current project rule: jQuery AJAX for state-changing operations and `adminToast()` for success/failure feedback.

| Admin Area | Status | MVP Need |
|---|---|---|
| Admin login/dashboard shell | Complete | Keep improving dashboard metrics. |
| User management UI | Complete | Create/edit/list with roles exists. |
| Settings UI | Complete | Store profile and module toggles exist. |
| Category UI | Complete | AJAX manage page exists. |
| Product UI | Complete | AJAX manage page exists. |
| Inventory UI | Pending | Need stock list, adjust modal/form, transaction log, low-stock filter. |
| Customer UI | Pending | Need customer list/detail, status update, address viewer. |
| Cart UI | Not needed | Cart is customer-facing. |
| Order UI | Pending | Need order list/detail, status updates, notes, cancellation. |
| Payment UI | Pending | Need payment status and method display/update. |
| Shipping UI | Pending | Need shipping methods/rates and order shipment basics. |
| Reports UI | Pending | Need dashboard cards and simple reports. |

## MVP Storefront Work Remaining

| Storefront Area | Status | MVP Need |
|---|---|---|
| Storefront shell/layout | Pending | Required. |
| Home page | Pending | Required. |
| Product listing | Pending | Required; can use public catalog API. |
| Product detail | Pending | Required; must show variants and stock state. |
| Customer register/login | Pending frontend | Backend complete. |
| Customer account/profile | Pending frontend | Backend complete. |
| Address book | Pending frontend | Backend complete. |
| Cart | Backend partial | Guest/customer cart APIs, line/quantity rules, price recalculation, unavailable handling, and persistence complete; merge and storefront UI still pending. |
| Checkout | Backend partial | Validation, address, shipping, payment, order creation, and confirmation event complete. |
| Order confirmation | Backend partial | Event/audit complete; email/SMS/page still pending. |
| Customer order history | Pending | Required after orders. |
| Policy pages | Pending | Required. |

## Recommended Next Build Order

### Step 1: Admin Inventory UI

Build before cart/checkout so operations can inspect and correct stock.

- Inventory stock list by product/variant.
- Available/on-hand/reserved/low-stock columns.
- AJAX adjust stock action.
- Transaction history panel.
- Toast success/failure feedback.

### Step 2: Cart Domain

- Guest/customer cart tables.
- Add/update/remove cart items.
- Price and stock validation.
- Merge guest cart after login.

### Step 3: Checkout And Order Creation

- Address selection.
- Shipping/payment method selection.
- Order creation transaction.
- Inventory reservation/release integration.
- Idempotency key.

### Step 4: Orders Admin

- Order list/detail.
- Status changes.
- Internal notes.
- Cancellation with stock release.

### Step 5: MVP Storefront

- Catalog pages.
- Product detail.
- Auth/account/address pages.
- Cart/checkout/order confirmation.
- Policy pages.

### Step 6: Dashboard And Release QA

- Dashboard metrics.
- Low-stock list.
- Manual QA checklist.
- Deployment checklist.
- Demo seed data.

## MVP Release Definition

The MVP is releasable when:

- Admin can manage store settings, categories, products, inventory, customers, and orders.
- Customer can register/login, manage profile/address, browse products, checkout, and see orders.
- Checkout creates orders atomically and safely reserves or releases stock.
- Order cancellation restores stock correctly.
- Public catalog hides unavailable products/variants according to inventory rules.
- Basic payment and shipping options work.
- Full automated test suite passes.
- Manual QA checklist passes on a seeded demo store.
