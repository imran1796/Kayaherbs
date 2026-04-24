# Software Requirements Specification (SRS)

Project: Modular API-Based Ecommerce Platform  
Prepared For: TransLink Solution Ltd. / Ecommerce Product Team  
Date: 12 April 2026  
Version: 1.0

## 1. Purpose

This SRS defines the software requirements for a reusable ecommerce platform built around two main applications: a Backend Core app containing the admin panel and REST API, and a separate storefront frontend. The system must be modular so features can be enabled or disabled per client package.

The platform is not a CMS-style shared website builder. The intended version 1 model is a single product codebase deployed separately for each client, with isolated server/runtime configuration and separate database per client. The default commerce mode is single-vendor ecommerce, with optional multi-vendor marketplace mode for clients that need multiple sellers/vendors.

## 2. System Overview

The platform will support:

- Customer storefront web app.
- Backend Core web app containing admin panel and business operations UI.
- Backend Core REST API and business logic.
- Database, cache, queue, search, file storage, and integration layer.
- Optional future apps such as mobile app, POS, warehouse app, vendor portal, or delivery app.

Version 1 should support separate deployment per client while keeping the architecture clean enough for future multi-tenant SaaS if the business later chooses that model.

## 3. Architecture Requirements

### 3.1 Logical Components

- Storefront App: customer website consuming public APIs from the Backend Core.
- Backend Core App: Laravel application containing admin panel pages, protected admin flows, authentication, and REST API endpoints.
- Backend Core API: authentication, products, cart, checkout, orders, inventory, payments, shipping, reports, settings, and integrations.
- Database: transactional store data.
- Cache/Queue: caching, background jobs, notifications, exports, imports, webhook processing.
- Search: database search initially, dedicated search service for larger packages.
- File Storage: product images, banners, documents, and imports/exports.
- Integration Layer: payment, courier, SMS, WhatsApp, email, analytics, ERP/POS.

### 3.2 Recommended Stack

- Backend Core: Laravel with admin panel, REST API, Sanctum authentication, and Spatie roles/permissions.
- Frontend Storefront: separate HTML/JS/jQuery/AJAX frontend or equivalent web frontend consuming the Backend Core API.
- Database: MySQL or PostgreSQL.
- Cache/Queue: Redis.
- Search: database search for Starter; Meilisearch/OpenSearch/Elasticsearch for larger scale.
- DevOps: Docker, GitHub Actions or equivalent CI/CD, environment-based configuration.

### 3.3 Deployment

- Use environment-specific configuration.
- Keep secrets outside source code.
- Use migrations for database changes.
- Use queue workers for heavy jobs.
- Use scheduled jobs for cleanup, reports, and backups.
- Support managed hosting and future cloud scaling.
- Deploy each client on a separate server/VPS/cloud environment by default.
- Use a separate database per client to keep business data isolated.
- Keep client-specific business rules in settings/modules instead of hard-coded forks.

## 4. Users And Roles

- Customer: browses, carts, checks out, manages own account if enabled.
- Guest Customer: browses and places guest orders if enabled.
- Store Owner: owns all store settings and data.
- Store Manager: manages daily store operations.
- Product Manager: manages catalog and content.
- Order Manager: handles orders, delivery, and invoices.
- Inventory Manager: handles stock.
- Customer Support: handles customer/order issues.
- Marketing Manager: handles coupons, SEO, campaigns, tracking.
- Accountant: views sales, payments, refunds, COD, and exports.
- Super Admin: platform owner/technical role.

## 5. Functional Requirements

### 5.1 Authentication And Authorization

AUTH-001: The system shall support admin login by email/phone and password.  
AUTH-002: The system shall support customer registration/login where enabled.  
AUTH-003: The system shall support password reset.  
AUTH-004: The system shall support admin 2FA for higher packages.  
AUTH-005: The system shall enforce role-based access control.  
AUTH-006: The system shall throttle failed login attempts.  
AUTH-007: The system shall support secure logout and token/session expiration.

### 5.2 Store Settings

STORE-001: Admin shall manage store name, logo, favicon, address, contact, currency, timezone, and social links.  
STORE-002: Admin shall manage privacy, refund, return, shipping, terms, and payment policy pages.  
STORE-003: Admin shall configure active modules by package.  
STORE-004: Admin shall configure SEO defaults and tracking IDs.
STORE-005: The system shall support single-vendor mode as the default commerce mode.
STORE-006: The system shall support multi-vendor mode as an optional advanced module.

### 5.3 Product Catalog

PROD-001: Admin shall create, edit, publish, unpublish, archive, and delete products.  
PROD-002: Product shall support name, slug, SKU, short description, long description, price, offer price, cost price where allowed, category, brand, tags, images, status, and visibility.  
PROD-003: Product shall support variants such as size, color, weight, pack size, flavor, or custom attributes.  
PROD-004: Each variant shall support SKU, price, offer price, stock, image, and status.  
PROD-005: Product shall support multiple images and image ordering.  
PROD-006: Product shall support SEO title, meta description, canonical URL, and image alt text.  
PROD-007: Product shall support related products, upsells, and cross-sells where enabled.  
PROD-008: Product shall support Bengali and English content where enabled.  
PROD-009: Compliance-sensitive products shall support ingredients, usage instruction, warning, expiry date, batch number, and certification/license fields where enabled.

### 5.4 Category, Brand, Attribute, Tag

CAT-001: Admin shall create nested categories.  
CAT-002: Category shall support name, slug, image, banner, parent category, sort order, status, and SEO fields.  
CAT-003: Admin shall manage brands.  
CAT-004: Admin shall manage product attributes and attribute values.  
CAT-005: Admin shall manage product tags.

### 5.5 Inventory

INV-001: The system shall track stock per product or variant.  
INV-002: The system shall reserve or validate stock during checkout according to configuration.  
INV-003: The system shall reduce stock when order reaches the configured status.  
INV-004: The system shall restore stock on cancellation/return according to rules.  
INV-005: The system shall record stock adjustment history with reason and staff user.  
INV-006: The system shall provide low-stock alerts and reports.  
INV-007: The system shall support out-of-stock visibility rules.  
INV-008: Multi-warehouse shall be supported as an advanced module.

### 5.6 Storefront Browsing

BROWSE-001: Customer shall browse home, category, product listing, search results, and product detail pages.  
BROWSE-002: Customer shall filter by category, price, brand, attributes, availability, rating, and offer where configured.  
BROWSE-003: Customer shall sort by latest, price, popularity, rating, and relevance.  
BROWSE-004: Customer shall search products by keyword.  
BROWSE-005: Product detail page shall show images, price, offer price, variants, description, stock status, reviews, related products, and call-to-action buttons.  
BROWSE-006: Storefront shall be mobile responsive.

### 5.7 Cart And Checkout

CART-001: Customer shall add products/variants to cart.  
CART-002: Customer shall update quantity and remove cart items.  
CART-003: Cart shall validate stock, price, product status, and variant availability.  
CART-004: Cart shall calculate subtotal, discount, delivery charge, and total.  
CHK-001: The system shall support guest checkout where enabled.  
CHK-002: The system shall support registered customer checkout with saved addresses.  
CHK-003: Checkout shall collect name, phone, email if required, delivery address, delivery zone, payment method, and order note.  
CHK-004: Checkout shall apply coupon and delivery rules.  
CHK-005: Checkout shall create an order with unique order number.  
CHK-006: Checkout shall trigger customer and admin notifications.

### 5.8 Order Management

ORD-001: Admin shall view order list with filters by status, date, customer, payment method, courier, delivery zone, and amount.  
ORD-002: Admin shall view full order detail.  
ORD-003: Admin shall update order status according to allowed workflow.  
ORD-004: Admin shall add internal order notes.  
ORD-005: Admin shall assign courier and tracking ID.  
ORD-006: Admin shall print invoice and packing slip.  
ORD-007: Admin shall cancel orders based on permission.  
ORD-008: Admin shall process return, refund, and exchange where enabled.  
ORD-009: The system shall record order status history with timestamp and actor.  
ORD-010: The system shall export orders.

Recommended order statuses:

- Pending.
- Awaiting confirmation.
- Confirmed.
- Processing.
- Packed.
- Shipped.
- Delivered.
- Cancelled.
- Return requested.
- Returned.
- Refunded.
- Failed delivery.

### 5.9 Payment

PAY-001: The system shall support COD.  
PAY-002: The system shall support configurable online payment adapters.  
PAY-003: Payment status shall include unpaid, pending, paid, failed, refunded, and partially refunded.  
PAY-004: The system shall store transaction ID and gateway response metadata securely.  
PAY-005: Payment callback/webhook shall be verified where supported.  
PAY-006: Payment webhook processing shall be idempotent to prevent duplicate processing.

Target adapters:

- bKash.
- Nagad.
- Rocket.
- SSLCommerz.
- ShurjoPay.
- Manual bank transfer if needed.

### 5.10 Shipping And Courier

SHIP-001: Admin shall manage delivery zones.  
SHIP-002: Admin shall configure delivery charge by zone, amount, weight, or custom rule where enabled.  
SHIP-003: Admin shall configure free delivery rules.  
SHIP-004: Admin shall assign courier to an order.  
SHIP-005: The system shall store tracking ID and tracking URL.  
SHIP-006: Courier integrations shall use adapter interfaces.  
SHIP-007: COD reconciliation report should be available where courier data exists.

Target adapters:

- Pathao.
- Steadfast.
- RedX.
- eCourier.
- Manual courier.

### 5.11 Customer Management

CUST-001: Admin shall view customers and guest customers.  
CUST-002: Customer profile shall show name, phone, email, addresses, order history, total spend, last order, and status.  
CUST-003: Admin shall add internal customer notes where permitted.  
CUST-004: Customer shall manage profile and addresses where account module is enabled.  
CUST-005: Customer shall view order history where account module is enabled.

### 5.12 Reviews, Wishlist, Promotions

WISH-001: Logged-in customer shall add/remove wishlist items where enabled.  
REV-001: Customer shall submit product rating and review where enabled.  
REV-002: Admin shall moderate reviews where configured.  
PROMO-001: Admin shall create coupon codes.  
PROMO-002: Coupon shall support fixed and percentage discounts.  
PROMO-003: Coupon shall support start/end date, usage limit, minimum order value, customer limit, and product/category rules where enabled.  
PROMO-004: The system shall support product offer price, flash sale, and free delivery promotions.

### 5.13 Content Management, SEO, Analytics

CONTENT-001: Admin shall manage homepage banners and product sections.  
CONTENT-002: Admin shall manage static pages, FAQ, and optional blog.  
SEO-001: Admin shall manage meta title, meta description, slug, canonical URL, and image alt text.  
SEO-002: The system shall generate sitemap.xml and support robots.txt.  
SEO-003: Storefront shall include product structured data where applicable.  
ANA-001: Admin shall configure Google Analytics, Google Tag Manager, and Meta Pixel.  
ANA-002: Storefront shall emit ecommerce events such as view product, add to cart, begin checkout, purchase, and search where enabled.

### 5.14 Multi-Vendor Marketplace Module

VENDOR-001: Multi-vendor mode shall be disabled by default.
VENDOR-002: When enabled, the system shall support vendor/seller accounts.
VENDOR-003: Vendor users shall only manage their own products, orders, and reports unless elevated permission is granted.
VENDOR-004: Store owner/admin shall approve, suspend, or reject vendor accounts.
VENDOR-005: Store owner/admin shall approve or reject vendor products where configured.
VENDOR-006: Orders shall support vendor-level order item grouping.
VENDOR-007: The system shall support vendor commission rules where enabled.
VENDOR-008: The system shall support vendor payout reporting where enabled.
VENDOR-009: The platform admin/store owner shall retain marketplace-wide control over categories, settings, payment, delivery, and policies.
VENDOR-010: The API shall enforce vendor data boundaries so one vendor cannot access another vendor's data.

### 5.15 Reports

REP-001: Dashboard shall show sales, orders, pending orders, cancelled orders, delivered orders, top products, low-stock products, recent orders, and COD pending amount.  
REP-002: The system shall provide sales report by date range.  
REP-003: The system shall provide product performance report.  
REP-004: The system shall provide customer report.  
REP-005: The system shall provide inventory report.  
REP-006: The system shall provide payment and refund report.  
REP-007: The system shall provide delivery/courier report where data exists.  
REP-008: The system shall export reports in CSV/XLSX where enabled.

### 5.16 Notifications

NOTIF-001: The system shall notify admin on new order through configured channels.  
NOTIF-002: The system shall notify customer on order placement.  
NOTIF-003: The system shall notify customer on major order status changes where enabled.  
NOTIF-004: The system shall support email templates.  
NOTIF-005: The system shall support SMS and WhatsApp templates where providers are integrated.  
NOTIF-006: Failed notifications shall be logged.

### 5.17 Audit Log And Import/Export

AUD-001: The system shall log important admin actions.  
AUD-002: Audit log shall include actor, action, entity, timestamp, IP address where available, and before/after values where feasible.  
AUD-003: Audit log shall not be deletable by normal staff roles.  
IMP-001: Admin shall import products in CSV/XLSX where enabled.  
IMP-002: Admin shall export products, orders, customers, and reports where enabled.  
IMP-003: Import shall validate data and show row-level errors.

### 5.18 Module Control

MOD-001: The system shall allow modules to be enabled/disabled per client/store/package.  
MOD-002: Disabled modules shall not show admin menus or storefront features.  
MOD-003: API endpoints for disabled modules shall return proper authorization or feature-unavailable responses.  
MOD-004: Module dependencies shall be validated before activation.

## 6. Data Requirements

Core entities:

- Store.
- Vendor.
- VendorUser.
- VendorCommissionRule.
- VendorPayout.
- User.
- Role.
- Permission.
- Customer.
- CustomerAddress.
- Category.
- Brand.
- Attribute.
- AttributeValue.
- Product.
- ProductVariant.
- ProductImage.
- InventoryStock.
- InventoryMovement.
- Cart.
- CartItem.
- Order.
- OrderItem.
- OrderStatusHistory.
- Payment.
- PaymentTransaction.
- Shipment.
- Courier.
- DeliveryZone.
- Coupon.
- Promotion.
- Review.
- Wishlist.
- ContentPage.
- Banner.
- Menu.
- Setting.
- NotificationLog.
- AuditLog.
- IntegrationCredential.
- ReportExport.

Data rules:

- Client business data belongs to the client.
- Platform source code ownership is controlled by license/contract.
- Orders and payment records should be retained for accounting needs.
- Backups should follow package-level retention.
- Customer data export should be supported.
- Each client deployment should use separate database/storage configuration unless a future SaaS model is intentionally approved.
- In multi-vendor mode, vendor-owned data must include vendor ownership boundaries.

## 7. API Requirements

- API paths shall be versioned, for example `/api/v1`.
- API shall use JSON request/response.
- API shall use consistent error response format.
- List endpoints shall support pagination.
- Admin endpoints shall require authentication and permission checks.
- Public and sensitive endpoints shall use rate limiting.
- Payment and courier webhook endpoints shall be idempotent.
- API documentation shall be maintained with OpenAPI/Swagger or equivalent.

Example endpoint groups:

- `/auth`
- `/store`
- `/products`
- `/categories`
- `/cart`
- `/checkout`
- `/orders`
- `/customers`
- `/admin/products`
- `/admin/orders`
- `/admin/inventory`
- `/admin/reports`
- `/admin/settings`
- `/admin/modules`
- `/webhooks/payment`
- `/webhooks/courier`

## 8. Non-Functional Requirements

### Performance

- Common API read responses should target under 500ms server response under normal load.
- Use caching for settings, categories, menus, and high-traffic content.
- Use queues for imports, exports, emails, SMS, WhatsApp, reports, and webhook processing.
- Optimize storefront images and use responsive image sizes where possible.

### Scalability

- API and frontend should be independently deployable.
- Search service should be replaceable.
- File storage should support migration to S3-compatible storage.
- Client-specific logic should not be hard-coded in the core.

### Security

- Passwords must be securely hashed.
- Admin routes must enforce permissions.
- Inputs must be validated and sanitized.
- The system must protect against SQL injection, XSS, CSRF where applicable, IDOR, and broken access control.
- Payment credentials/API keys must be stored securely.
- Rate limiting must apply to login, checkout, and sensitive endpoints.
- Admin 2FA should be available for higher packages.
- Audit logs must be protected.

### Reliability

- Managed hosting should include daily backup at minimum.
- Restore procedure must be documented.
- Failed jobs must be logged and retryable where safe.
- Payment and courier webhook processing must avoid duplicate state changes.

### Maintainability

- Code should be modular by business domain.
- Integrations should use adapter interfaces.
- Module enable/disable should not require major code changes.
- Environment configuration must not be hard-coded.
- API documentation and release notes should be maintained.

### Usability

- Backend Core admin panel should be usable by non-technical staff.
- Storefront should be mobile-first and responsive.
- Checkout should minimize steps.
- Error messages should be understandable.
- Admin forms should provide clear validation feedback.

### Localization

- BDT should be default currency.
- Store timezone should be configurable.
- Bangla and English product/storefront content should be supported where enabled.

## 9. Integration Requirements

Payment:

- bKash.
- Nagad.
- Rocket.
- SSLCommerz.
- ShurjoPay.

Courier:

- Pathao.
- Steadfast.
- RedX.
- eCourier.
- Manual courier.

Messaging:

- Email SMTP or transactional email provider.
- SMS provider adapter.
- WhatsApp provider adapter.

Analytics:

- Google Analytics.
- Google Tag Manager.
- Meta Pixel.
- Future Facebook Conversion API.

Storage:

- Local storage for small deployments.
- S3-compatible storage for scale.

## 10. Storefront Pages

Required:

- Home.
- Product listing.
- Category.
- Search results.
- Product detail.
- Cart.
- Checkout.
- Order success.
- Login/register.
- Customer account.
- Order history.
- Wishlist where enabled.
- Contact.
- About.
- Privacy policy.
- Terms and conditions.
- Refund/return policy.
- Shipping policy.

Optional:

- Blog.
- Campaign landing page.
- Flash sale.
- Brand page.
- FAQ.

## 11. Testing Requirements

Testing must cover:

- Unit tests for core services.
- API tests for critical flows.
- Checkout flow tests.
- Order lifecycle tests.
- Inventory stock movement tests.
- Payment webhook tests with sandbox where possible.
- Courier adapter tests where sandbox is available.
- Admin permission tests.
- Disabled module access tests.
- Import/export validation tests.
- Responsive UI tests.
- Security tests for authentication and authorization.

Critical scenarios:

- Guest places COD order.
- Registered customer places order.
- Admin confirms, packs, ships, and delivers order.
- Stock reduces and restores correctly.
- Coupon applies and invalid coupon is rejected.
- Payment success updates order once only.
- Payment failure does not mark order paid.
- Unauthorized staff cannot access restricted modules.
- Disabled module is inaccessible.
- Product import shows validation errors.

## 12. Operational Requirements

- Maintain deployment checklist.
- Run migrations during deployment.
- Provision separate runtime/database/storage per client by default.
- Take backup before major release.
- Monitor application errors, failed jobs, disk usage, database size, and queue workers.
- Monitor payment/courier webhook failures.
- Define support channel and SLA by package.
- Define bug vs change request.
- Maintain release notes.
- Maintain backup retention by package.

## 13. Acceptance Criteria

Version 1 is acceptable when:

- Storefront can display products, categories, cart, and checkout.
- Customer can place COD order.
- Admin can manage products, categories, stock, orders, customers, content, and settings.
- Roles and permissions work.
- Reports show core sales, order, product, customer, and stock data.
- SEO fields and sitemap are available.
- Module flags control feature availability.
- API documentation exists.
- Critical test scenarios pass.
- Backup and deployment process are documented.

## 14. Future Enhancements

- Optional multi-tenant SaaS if the business later chooses a shared-platform model.
- Mobile apps.
- POS.
- Multi-vendor marketplace.
- Multi-warehouse and purchase management.
- Loyalty points.
- Wallet.
- Subscription products.
- Affiliate/referral system.
- AI recommendation.
- Advanced customer segmentation.
- Accounting/ERP integration.
- Custom report builder.
- GraphQL storefront API.
