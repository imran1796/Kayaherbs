# Entity Relationship Diagram (ERD) - Database Design

Project: Modular API-Based Ecommerce Platform  
Date: 12 April 2026  
Version: 1.0

## 1. Purpose

This document defines the database entity relationship design for the modular ecommerce platform.

The platform uses one maintained product codebase deployed separately per client. Each client deployment should have isolated runtime, database, storage, domain, and configuration. Single-vendor ecommerce is the default mode. Multi-vendor marketplace is an optional module.

## 2. Database Design Principles

- Use a relational database as the primary transactional store.
- Keep `store_id` on store-owned tables to preserve future SaaS readiness, even when version 1 uses separate client deployments.
- Use `vendor_id` only where multi-vendor ownership is needed.
- Use snapshot fields for order/customer/address/product data so historical orders do not change when master data changes.
- Store stock changes in movement tables, not only in current stock quantity.
- Keep payment/courier webhook logs for idempotency and investigation.
- Keep audit logs for sensitive admin/vendor actions.
- Use encrypted storage for integration credentials and payout details where possible.

## 3. Full ERD Overview

```mermaid
erDiagram
    stores ||--o{ store_settings : has
    stores ||--o{ store_modules : enables
    modules ||--o{ store_modules : configured_as

    stores ||--o{ users : has
    vendors ||--o{ users : has_vendor_users
    users }o--o{ roles : assigned
    roles }o--o{ permissions : grants

    stores ||--o{ categories : owns
    categories ||--o{ categories : parent_child
    stores ||--o{ brands : owns
    stores ||--o{ products : owns
    vendors ||--o{ products : may_own
    categories ||--o{ products : categorizes
    brands ||--o{ products : brands
    products ||--o{ product_variants : has
    products ||--o{ product_images : has
    product_variants ||--o{ product_images : may_have
    products ||--o| product_compliance_fields : may_have

    products ||--o{ inventory_stocks : tracked_by
    product_variants ||--o{ inventory_stocks : tracked_by
    products ||--o{ inventory_movements : has
    product_variants ||--o{ inventory_movements : has

    stores ||--o{ customers : has
    customers ||--o{ customer_addresses : has
    customers ||--o{ carts : owns
    carts ||--o{ cart_items : contains
    products ||--o{ cart_items : added_as
    product_variants ||--o{ cart_items : added_as

    customers ||--o{ orders : places
    orders ||--o{ order_items : contains
    vendors ||--o{ order_items : may_fulfill
    products ||--o{ order_items : sold_as
    product_variants ||--o{ order_items : sold_as
    orders ||--o{ order_status_histories : records
    orders ||--o{ payments : has
    orders ||--o{ shipments : has

    stores ||--o{ coupons : owns
    coupons ||--o{ coupon_redemptions : used_as
    orders ||--o{ coupon_redemptions : applies_to
    customers ||--o{ coupon_redemptions : used_by

    customers ||--o{ reviews : writes
    products ||--o{ reviews : receives
    customers ||--o{ wishlists : owns
    products ||--o{ wishlists : saved_as

    stores ||--o{ vendors : may_have
    vendors ||--o{ vendor_payouts : receives

    stores ||--o{ content_pages : has
    stores ||--o{ banners : has
    stores ||--o{ audit_logs : has
    users ||--o{ audit_logs : creates
    stores ||--o{ notification_logs : has
    stores ||--o{ integration_credentials : has
    stores ||--o{ report_exports : has

    payments ||--o{ payment_webhook_logs : traced_by
    shipments ||--o{ courier_webhook_logs : traced_by
```

## 4. Store, Module, And Settings Entities

```mermaid
erDiagram
    stores ||--o{ store_settings : has
    stores ||--o{ store_modules : enables
    modules ||--o{ store_modules : configured_as

    stores {
        bigint id PK
        string name
        string domain
        string currency
        string timezone
        enum commerce_mode
        enum status
        datetime created_at
        datetime updated_at
    }

    store_settings {
        bigint id PK
        bigint store_id FK
        string key
        text value
        string value_type
        boolean is_encrypted
    }

    modules {
        bigint id PK
        string key
        string name
        text description
        boolean default_enabled
    }

    store_modules {
        bigint id PK
        bigint store_id FK
        string module_key
        boolean enabled
        string package
        datetime configured_at
    }
```

Key constraints:

- `stores.domain` should be unique.
- `store_settings(store_id, key)` should be unique.
- `modules.key` should be unique.
- `store_modules(store_id, module_key)` should be unique.
- `commerce_mode` should default to `single_vendor`.

## 5. User, Role, Permission, And Vendor Access

```mermaid
erDiagram
    stores ||--o{ users : has
    vendors ||--o{ users : has_vendor_users
    users }o--o{ roles : assigned
    roles }o--o{ permissions : grants

    users {
        bigint id PK
        bigint store_id FK
        bigint vendor_id FK "nullable"
        string name
        string email "nullable"
        string phone "nullable"
        string password_hash
        enum status
        datetime last_login_at
    }

    roles {
        bigint id PK
        bigint store_id FK "nullable"
        string name
        string key
    }

    permissions {
        bigint id PK
        string key
        text description
    }

    role_user {
        bigint role_id FK
        bigint user_id FK
    }

    permission_role {
        bigint permission_id FK
        bigint role_id FK
    }
```

Key constraints:

- `users(store_id, email)` unique where email is not null.
- `users(store_id, phone)` unique where phone is not null.
- Vendor users must have `vendor_id` set only when multi-vendor mode is enabled.
- Vendor users must be scoped to their own vendor-owned data.

## 6. Catalog And Compliance Entities

```mermaid
erDiagram
    stores ||--o{ categories : owns
    categories ||--o{ categories : parent_child
    stores ||--o{ brands : owns
    stores ||--o{ products : owns
    vendors ||--o{ products : may_own
    categories ||--o{ products : categorizes
    brands ||--o{ products : brands
    products ||--o{ product_variants : has
    products ||--o{ product_images : has
    product_variants ||--o{ product_images : may_have
    products ||--o| product_compliance_fields : may_have

    categories {
        bigint id PK
        bigint store_id FK
        bigint parent_id FK "nullable"
        string name
        string slug
        string image_path "nullable"
        integer sort_order
        enum status
        string seo_title
        text seo_description
    }

    brands {
        bigint id PK
        bigint store_id FK
        string name
        string slug
        enum status
    }

    products {
        bigint id PK
        bigint store_id FK
        bigint vendor_id FK "nullable"
        bigint category_id FK
        bigint brand_id FK "nullable"
        string name
        string slug
        string sku "nullable"
        decimal price
        decimal offer_price "nullable"
        decimal cost_price "nullable"
        enum status
        enum approval_status
        boolean stock_tracking_enabled
        string seo_title
        text seo_description
        string canonical_url
    }

    product_variants {
        bigint id PK
        bigint product_id FK
        string sku
        string name
        json attributes_json
        decimal price
        decimal offer_price "nullable"
        enum status
    }

    product_images {
        bigint id PK
        bigint product_id FK
        bigint variant_id FK "nullable"
        string path
        string alt_text
        integer sort_order
    }

    product_compliance_fields {
        bigint id PK
        bigint product_id FK
        text ingredients
        text usage_instructions
        text warning
        date expiry_date
        string batch_number
        string certification
        string license_number
    }
```

Key constraints:

- `products(store_id, slug)` should be unique.
- `categories(store_id, slug)` should be unique.
- `brands(store_id, slug)` should be unique.
- `product_variants(product_id, sku)` should be unique where SKU is present.
- `product_compliance_fields.product_id` should be unique because one product has one compliance record.
- `vendor_id` on products is nullable for single-vendor mode.

## 7. Inventory Entities

```mermaid
erDiagram
    products ||--o{ inventory_stocks : tracked_by
    product_variants ||--o{ inventory_stocks : tracked_by
    products ||--o{ inventory_movements : has
    product_variants ||--o{ inventory_movements : has
    orders ||--o{ inventory_movements : may_create
    users ||--o{ inventory_movements : creates

    inventory_stocks {
        bigint id PK
        bigint store_id FK
        bigint product_id FK
        bigint variant_id FK "nullable"
        bigint warehouse_id FK "nullable"
        integer quantity_on_hand
        integer quantity_reserved
        integer low_stock_threshold
        datetime updated_at
    }

    inventory_movements {
        bigint id PK
        bigint store_id FK
        bigint product_id FK
        bigint variant_id FK "nullable"
        bigint order_id FK "nullable"
        integer quantity_delta
        enum movement_type
        string reason
        bigint created_by FK
        datetime created_at
    }
```

Key constraints:

- `inventory_stocks(store_id, product_id, variant_id)` should be unique in version 1.
- `quantity_on_hand` and `quantity_reserved` should not go below zero.
- Every manual or order-driven stock change must create an `inventory_movements` row.
- Multi-warehouse can use `warehouse_id` later; nullable in version 1 and excluded from the v1 unique key to avoid nullable unique-index ambiguity.

## 8. Cart, Customer, And Order Entities

```mermaid
erDiagram
    stores ||--o{ customers : has
    customers ||--o{ customer_addresses : has
    customers ||--o{ carts : owns
    carts ||--o{ cart_items : contains
    products ||--o{ cart_items : added_as
    product_variants ||--o{ cart_items : added_as

    customers ||--o{ orders : places
    orders ||--o{ order_items : contains
    vendors ||--o{ order_items : may_fulfill
    products ||--o{ order_items : sold_as
    product_variants ||--o{ order_items : sold_as
    orders ||--o{ order_status_histories : records

    customers {
        bigint id PK
        bigint store_id FK
        string name
        string phone
        string email "nullable"
        enum status
        integer total_orders
        decimal total_spend
    }

    customer_addresses {
        bigint id PK
        bigint customer_id FK
        string name
        string phone
        string line1
        string line2
        string city
        string zone
        string postal_code
        boolean is_default
    }

    carts {
        bigint id PK
        bigint store_id FK
        bigint customer_id FK "nullable"
        string token "nullable"
        string coupon_code "nullable"
        datetime expires_at
    }

    cart_items {
        bigint id PK
        bigint cart_id FK
        bigint product_id FK
        bigint variant_id FK "nullable"
        integer quantity
        decimal unit_price_snapshot
    }

    orders {
        bigint id PK
        bigint store_id FK
        bigint customer_id FK "nullable"
        string order_number
        enum status
        enum payment_status
        string payment_method
        json customer_snapshot_json
        json address_snapshot_json
        decimal subtotal
        decimal discount_total
        decimal delivery_charge
        decimal total
        text notes
    }

    order_items {
        bigint id PK
        bigint order_id FK
        bigint vendor_id FK "nullable"
        bigint product_id FK
        bigint variant_id FK "nullable"
        string name_snapshot
        string sku_snapshot
        integer quantity
        decimal unit_price
        decimal line_total
    }

    order_status_histories {
        bigint id PK
        bigint order_id FK
        string from_status
        string to_status
        text note
        bigint changed_by FK
        datetime created_at
    }
```

Key constraints:

- `orders(store_id, order_number)` should be unique.
- Guest orders may have `customer_id` null but must retain `customer_snapshot_json`.
- `order_items` must use snapshot fields for name, SKU, unit price, and line total.
- `order_items.vendor_id` is nullable in single-vendor mode and populated in multi-vendor mode.

## 9. Payment, Shipping, And Webhook Entities

```mermaid
erDiagram
    orders ||--o{ payments : has
    orders ||--o{ shipments : has
    payments ||--o{ payment_webhook_logs : traced_by
    shipments ||--o{ courier_webhook_logs : traced_by

    payments {
        bigint id PK
        bigint store_id FK
        bigint order_id FK
        string provider
        enum status
        decimal amount
        string transaction_id "nullable"
        string provider_reference "nullable"
        datetime paid_at
        json metadata_json
    }

    payment_webhook_logs {
        bigint id PK
        bigint store_id FK
        string provider
        string event_id "nullable"
        string transaction_id "nullable"
        string payload_hash
        json payload_json
        datetime processed_at
        enum status
    }

    shipments {
        bigint id PK
        bigint store_id FK
        bigint order_id FK
        string courier
        string tracking_id "nullable"
        string tracking_url "nullable"
        enum status
        enum cod_status
        json metadata_json
    }

    courier_webhook_logs {
        bigint id PK
        bigint store_id FK
        string provider
        string event_id "nullable"
        string tracking_id "nullable"
        string payload_hash
        json payload_json
        datetime processed_at
        enum status
    }
```

Key constraints:

- `payments(provider, transaction_id)` should be unique where transaction ID is present.
- `payment_webhook_logs(provider, event_id)` should be unique where event ID is present.
- `payment_webhook_logs.payload_hash` helps detect duplicate payloads.
- `shipments(courier, tracking_id)` should be indexed where tracking ID is present.
- Webhook processing must be idempotent.

## 10. Promotion, Review, Wishlist, Vendor, And Support Entities

```mermaid
erDiagram
    stores ||--o{ coupons : owns
    coupons ||--o{ coupon_redemptions : used_as
    orders ||--o{ coupon_redemptions : applies_to
    customers ||--o{ coupon_redemptions : used_by

    customers ||--o{ reviews : writes
    products ||--o{ reviews : receives
    customers ||--o{ wishlists : owns
    products ||--o{ wishlists : saved_as

    stores ||--o{ vendors : may_have
    vendors ||--o{ vendor_payouts : receives

    coupons {
        bigint id PK
        bigint store_id FK
        string code
        enum discount_type
        decimal discount_value
        decimal minimum_order_value
        integer usage_limit
        integer used_count
        datetime starts_at
        datetime ends_at
        enum status
    }

    coupon_redemptions {
        bigint id PK
        bigint coupon_id FK
        bigint order_id FK
        bigint customer_id FK "nullable"
        decimal discount_amount
        datetime created_at
    }

    reviews {
        bigint id PK
        bigint store_id FK
        bigint product_id FK
        bigint customer_id FK
        integer rating
        text comment
        enum status
    }

    wishlists {
        bigint id PK
        bigint customer_id FK
        bigint product_id FK
        datetime created_at
    }

    vendors {
        bigint id PK
        bigint store_id FK
        string name
        string phone
        string email
        enum status
        decimal commission_rate
        json payout_details_json
    }

    vendor_payouts {
        bigint id PK
        bigint store_id FK
        bigint vendor_id FK
        date period_start
        date period_end
        decimal gross_sales
        decimal commission_amount
        decimal payout_amount
        enum status
        datetime paid_at
    }
```

Key constraints:

- `coupons(store_id, code)` should be unique.
- `wishlists(customer_id, product_id)` should be unique.
- `reviews(product_id, customer_id)` may be unique if only one review per customer/product is allowed.
- Vendor payout details should be encrypted where possible.

## 11. Content, Audit, Notification, Integration, And Export Entities

```mermaid
erDiagram
    stores ||--o{ content_pages : has
    stores ||--o{ banners : has
    stores ||--o{ audit_logs : has
    users ||--o{ audit_logs : creates
    stores ||--o{ notification_logs : has
    stores ||--o{ integration_credentials : has
    stores ||--o{ report_exports : has

    content_pages {
        bigint id PK
        bigint store_id FK
        string title
        string slug
        text body
        enum status
        string seo_title
        text seo_description
    }

    banners {
        bigint id PK
        bigint store_id FK
        string title
        string image_path
        string link_url
        string placement
        integer sort_order
        enum status
    }

    audit_logs {
        bigint id PK
        bigint store_id FK
        bigint vendor_id FK "nullable"
        bigint actor_user_id FK
        string action
        string entity_type
        bigint entity_id
        json before_json
        json after_json
        string ip_address
        datetime created_at
    }

    notification_logs {
        bigint id PK
        bigint store_id FK
        string channel
        string recipient
        string template_key
        enum status
        string provider
        string provider_reference
        text error_message
        datetime created_at
    }

    integration_credentials {
        bigint id PK
        bigint store_id FK
        string provider
        string credential_key
        text encrypted_value
        boolean active
    }

    report_exports {
        bigint id PK
        bigint store_id FK
        bigint requested_by FK
        string report_type
        string file_path
        enum status
        datetime expires_at
    }
```

Key constraints:

- `content_pages(store_id, slug)` should be unique.
- `integration_credentials(store_id, provider, credential_key)` should be unique.
- Audit logs should not be editable by normal admin users.
- Report exports should expire based on package/support policy.

## 12. Recommended Indexes

High-priority indexes:

- `products(store_id, slug)`
- `products(store_id, status)`
- `products(store_id, vendor_id)`
- `categories(store_id, slug)`
- `customers(store_id, phone)`
- `orders(store_id, order_number)`
- `orders(store_id, status, created_at)`
- `orders(store_id, payment_status)`
- `order_items(order_id)`
- `order_items(vendor_id)`
- `inventory_stocks(store_id, product_id, variant_id)`
- `inventory_movements(store_id, product_id, created_at)`
- `payments(order_id)`
- `payments(provider, transaction_id)`
- `shipments(order_id)`
- `shipments(courier, tracking_id)`
- `audit_logs(store_id, entity_type, entity_id)`
- `audit_logs(store_id, created_at)`

## 13. Notes For Version 1

- Separate client deployment means cross-client joins are not needed in version 1.
- Keeping `store_id` is still useful for consistency, future SaaS readiness, and internal platform tooling.
- Multi-vendor tables can exist but remain unused unless the module is enabled.
- Multi-warehouse support can be deferred while leaving `warehouse_id` nullable in inventory design; when enabled, use a non-null default warehouse or a revised stock key strategy.
- Compliance fields should be generic: `product_compliance_fields`, not category-specific names like herbal-only fields.
