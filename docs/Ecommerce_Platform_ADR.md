# Architecture Decision Record (ADR)

Project: Modular API-Based Ecommerce Platform  
Date: 12 April 2026  
Version: 1.0

## 1. Purpose

This document records the major architecture decisions for the ecommerce platform. It should be updated whenever a major technology, deployment, integration, data, or module strategy changes.

Status values:

- Accepted: approved direction for current architecture.
- Proposed: recommended but still needs final approval.
- Deferred: intentionally postponed.
- Rejected: considered but not selected.

## ADR-001: Use API-First Headless Ecommerce Architecture

Status: Accepted

Context:

The platform must support a separate storefront app, a Backend Core app containing the admin panel and REST API, and future apps such as mobile, POS, warehouse, delivery, or vendor portal.

Decision:

Use an API-first architecture where the Backend Core owns ecommerce business logic, admin panel, and REST API, while the separate storefront consumes the API.

Consequences:

- Storefront and Backend Core can be developed and deployed separately.
- Future mobile/POS/vendor apps can use the same backend.
- API security, versioning, and documentation become important from the beginning.

## ADR-002: Deploy Separately Per Client

Status: Accepted

Context:

The business does not want a CMS-style shared website builder. Each client may need separate hosting, data isolation, custom configuration, and controlled support.

Decision:

Use one maintained product codebase, but deploy it separately per client with separate runtime, database, storage, domain, and environment configuration.

Consequences:

- Stronger client data isolation.
- Easier custom hosting and support agreements.
- Updates must be managed across multiple deployments.
- Future SaaS remains possible, but it is not the version 1 default.

## ADR-003: Single-Vendor Default, Multi-Vendor Optional

Status: Accepted

Context:

Most ecommerce clients will sell their own products only. Some future clients may need a marketplace where multiple vendors sell under one store.

Decision:

Make single-vendor ecommerce the default operating mode. Build multi-vendor marketplace capability as an optional advanced module.

Consequences:

- Normal clients get a simpler admin and cleaner workflow.
- Marketplace clients can use vendor accounts, vendor product ownership, vendor order grouping, commission, and payout reports.
- Vendor data boundaries must be enforced at API and database query level.

## ADR-004: Use Modular Monolith For Version 1

Status: Accepted

Context:

The product needs many ecommerce modules, but early microservices would add operational complexity before the product is mature.

Decision:

Build the backend as a modular monolith with clear domain modules such as catalog, inventory, checkout, order, payment, shipping, promotion, customer, reporting, and vendor marketplace.

Consequences:

- Faster initial development.
- Easier local development and deployment.
- Lower operational cost.
- Modules can be extracted later if scale or team size requires it.

## ADR-005: Separate Storefront And Backend Core, With Optional Vendor Portal Later

Status: Accepted

Context:

Customers, business staff, and vendors have different user experiences and security needs.

Decision:

Keep the storefront as a separate application. Keep the admin panel and REST API inside the Backend Core application. Treat the vendor portal as an optional future module or later-separate app if needed.

Consequences:

- Cleaner separation between customer commerce UI and business operations.
- Admin functionality remains inside the Backend Core where business rules and permissions are enforced.
- Vendor portal can remain disabled for single-vendor clients.

## ADR-006: Use Package/Module Feature Control

Status: Accepted

Context:

The same product must support Starter, Growth, Professional, and Enterprise packages without creating code forks for each client.

Decision:

Use package/module configuration to enable or disable features per client deployment.

Consequences:

- Sales packages can be mapped to technical modules.
- Disabled features must be hidden from UI and blocked at API level.
- Module dependency validation is required.

## ADR-007: Use Adapter-Based Third-Party Integrations

Status: Accepted

Context:

Payment, courier, SMS, WhatsApp, and analytics providers may change per client or over time.

Decision:

Use adapter interfaces for integrations such as bKash, Nagad, SSLCommerz, ShurjoPay, Pathao, Steadfast, RedX, eCourier, SMS, and WhatsApp.

Consequences:

- Providers can be added or replaced without changing core order/payment logic.
- Provider-specific errors can be normalized for admin users.
- Integration credentials must be stored securely.

## ADR-008: Use Relational Database As Primary Data Store

Status: Accepted

Context:

Ecommerce requires transactional consistency for orders, stock, payments, customers, and refunds.

Decision:

Use a relational database such as MySQL or PostgreSQL as the primary data store.

Consequences:

- Strong fit for transactional ecommerce data.
- Requires careful indexing for reporting and admin filters.
- Future reporting replicas can be added if needed.

## ADR-009: Use Redis For Cache, Queue, And Runtime Support

Status: Accepted

Context:

The system needs caching, background jobs, rate limiting, locks, notification dispatch, imports, exports, and webhook processing.

Decision:

Use Redis or equivalent infrastructure for cache, queue, locks, rate limiting, and background job support.

Consequences:

- Better performance for common reads.
- Heavy work can run outside request lifecycle.
- Queue workers and failed jobs must be monitored.

## ADR-010: Use Search Service As Scalable Module

Status: Accepted

Context:

Small stores may not need dedicated search infrastructure, but larger stores need faster and smarter product discovery.

Decision:

Start with database search where sufficient, but design search behind a replaceable search layer so Meilisearch, OpenSearch, or Elasticsearch can be used for larger packages.

Consequences:

- Starter deployments stay simpler.
- Growth/Professional clients can get better search.
- Product publishing must update the search index when search service is enabled.

## ADR-011: Treat Content Management As Ecommerce Content, Not CMS Platform

Status: Accepted

Context:

The platform needs banners, policy pages, homepage sections, FAQ, and optional blog, but it should not become a generic CMS or shared website builder.

Decision:

Include ecommerce content management for store operations only. Do not design the product as a general-purpose CMS platform.

Consequences:

- Keeps scope focused on ecommerce.
- Avoids unnecessary CMS complexity.
- Content features remain tied to selling, trust, policies, SEO, and campaigns.

## ADR-012: Use Queue-Based Processing For Asynchronous Work

Status: Accepted

Context:

Notifications, imports, exports, payment/courier webhook handling, reports, and image processing can slow user-facing requests.

Decision:

Move slow or retryable work to background queues.

Consequences:

- Faster checkout/admin actions.
- Failed jobs need retry policy and monitoring.
- Some operations become eventually consistent.

## ADR-013: Enforce Data Ownership And Platform IP Boundary

Status: Accepted

Context:

The software owner wants to reuse the platform across clients, while clients need ownership and exportability of their business data.

Decision:

Treat platform source code as product IP unless a separate source-code transfer agreement exists. Treat client business data as client-owned and exportable.

Consequences:

- Contracts must clearly separate source code ownership from data ownership.
- Data export tools are required.
- Handover/support terms must be clear in client proposals.

## ADR-014: Defer Multi-Tenant SaaS

Status: Deferred

Context:

Multi-tenant SaaS could support many clients on one shared platform, but the current business model favors separate deployments.

Decision:

Do not build multi-tenant SaaS as the version 1 default. Keep the architecture clean enough that SaaS can be considered later.

Consequences:

- Version 1 remains simpler operationally.
- Separate deployments require update management.
- If SaaS is later selected, store/tenant isolation, billing, and central platform admin will need deeper design.

## ADR-015: Recommended Framework Stack

Status: Proposed

Context:

The final technology stack still needs owner/team approval.

Decision:

Recommended stack:

- Backend Core: Laravel.
- Storefront: separate HTML/JS/jQuery/AJAX frontend.
- Admin Panel: Laravel-served admin pages inside Backend Core.
- Database: MySQL or PostgreSQL.
- Cache/Queue: Redis.
- Search: database search first, Meilisearch/OpenSearch/Elasticsearch for larger packages.
- Deployment: Docker-assisted deployment with environment-based configuration.

Consequences:

- Laravel gives strong ecommerce/admin/backend productivity.
- A separate storefront application gives frontend flexibility while keeping the admin panel inside Backend Core.
- Final decision should consider team skill, hosting cost, long-term maintenance, and available developer resources.

## ADR-016: Use Staging/UAT Before Production

Status: Accepted

Context:

Ecommerce changes can affect orders, payments, stock, and revenue.

Decision:

Use at least staging/UAT and production environments for client deployments. Production releases must use migration, backup, and smoke-test steps.

Consequences:

- Lower risk of broken checkout or admin workflows.
- Requires deployment discipline.
- Client UAT sign-off can be tied to milestone acceptance.

## ADR-017: Use Audit Logging For Sensitive Admin Actions

Status: Accepted

Context:

Order, stock, payment, coupon, permission, and settings changes affect business trust and accountability.

Decision:

Record audit logs for sensitive admin and vendor actions.

Consequences:

- Easier support and dispute investigation.
- More database/log storage required.
- Audit logs must be permission-protected.

## ADR-018: Use Idempotent Payment And Courier Webhooks

Status: Accepted

Context:

External providers can send duplicate callbacks, delayed callbacks, or failed retry callbacks.

Decision:

Payment and courier webhook processing must be idempotent and must avoid duplicate order confirmation, duplicate refund, or repeated delivery state changes.

Consequences:

- Safer payment/order processing.
- Requires transaction IDs, webhook logs, and duplicate detection.
- Webhook failure handling must be monitored.

## ADR-019: Keep Marketplace Vendor Boundaries Strict

Status: Accepted

Context:

When multi-vendor mode is enabled, vendors must not access other vendors' products, orders, stock, or payout data.

Decision:

Vendor ownership must be enforced in API policies, database queries, reports, and admin/vendor UI.

Consequences:

- Better marketplace security.
- More testing is required for vendor access control.
- Store owner/admin must retain marketplace-wide override permissions.

## ADR-020: Use Backup And Restore As Part Of Managed Hosting

Status: Accepted

Context:

Ecommerce clients depend on order, customer, product, and payment history. Data loss would be commercially damaging.

Decision:

Managed hosting must include automated backup and documented restore procedures. Backup retention may vary by package.

Consequences:

- Stronger client trust.
- Hosting packages need backup cost planning.
- Restore should be tested for higher-value packages.
