# Data Flow Diagram (DFD)

Project: Modular API-Based Ecommerce Platform  
Date: 12 April 2026  
Version: 1.0

## 1. Purpose

This document defines how data moves through the ecommerce platform. It covers the major external actors, system processes, data stores, and third-party integrations for the API-based ecommerce backend, admin panel, and customer storefront.

## 2. DFD Legend

- Rectangle: external actor or external system.
- Rounded rectangle: process.
- Cylinder: data store.
- Arrow: data movement.

## 3. Level 0 Context Diagram

```mermaid
flowchart LR
    Customer["Customer / Guest"]
    Admin["Admin / Store Staff"]
    Owner["Store Owner"]
    Payment["Payment Gateway"]
    Courier["Courier Service"]
    Notify["Email / SMS / WhatsApp Provider"]
    Analytics["Analytics / Tracking Platform"]
    Platform("Ecommerce Platform")

    Customer -->|"Browse, cart, checkout, account data"| Platform
    Platform -->|"Products, prices, order status, notifications"| Customer

    Admin -->|"Catalog, inventory, order, customer, content changes"| Platform
    Platform -->|"Dashboards, reports, alerts, exports"| Admin

    Owner -->|"Package, settings, business rules"| Platform
    Platform -->|"KPIs, sales, COD, stock, business reports"| Owner

    Platform -->|"Payment request, order amount"| Payment
    Payment -->|"Payment status, transaction ID, webhook"| Platform

    Platform -->|"Shipment request, COD amount, address"| Courier
    Courier -->|"Tracking ID, delivery status, COD update"| Platform

    Platform -->|"Order and status messages"| Notify
    Notify -->|"Delivery status of message"| Platform

    Platform -->|"Tracking events"| Analytics
```

## 4. Level 1 Platform Data Flow

```mermaid
flowchart TD
    Customer["Customer / Guest"]
    Admin["Admin / Staff"]
    P1("1. Storefront Interface")
    P2("2. Backend Core Interface")
    P3("3. Commerce Core")
    P4("4. Integration Layer")
    P5("5. Reporting Engine")

    D1[("Product Catalog DB")]
    D2[("Customer DB")]
    D3[("Cart DB")]
    D4[("Order DB")]
    D5[("Inventory DB")]
    D6[("Payment DB")]
    D7[("Content and Settings DB")]
    D8[("Audit and Notification Logs")]
    D9[("Report Exports")]

    ExtPay["Payment Gateway"]
    ExtCourier["Courier Service"]
    ExtMsg["Email / SMS / WhatsApp"]
    ExtAnalytics["Analytics Platform"]

    Customer -->|"Search, browse, cart, checkout"| P1
    P1 -->|"Product query"| D1
    P1 -->|"Cart data"| D3
    P1 -->|"Customer profile/address"| D2
    P1 -->|"Checkout request"| P3

    Admin -->|"Product, stock, order, content updates"| P2
    P2 -->|"Catalog changes"| D1
    P2 -->|"Inventory changes"| D5
    P2 -->|"Order updates"| D4
    P2 -->|"Content/settings changes"| D7
    P2 -->|"Admin activity"| D8

    P3 -->|"Validate stock"| D5
    P3 -->|"Create/update order"| D4
    P3 -->|"Payment record"| D6
    P3 -->|"Customer record"| D2
    P3 -->|"Notification event"| P4
    P3 -->|"Payment/courier event"| P4

    P4 -->|"Payment request"| ExtPay
    ExtPay -->|"Payment webhook/status"| P4
    P4 -->|"Payment update"| D6
    P4 -->|"Order payment status"| D4

    P4 -->|"Shipment booking"| ExtCourier
    ExtCourier -->|"Tracking/delivery/COD status"| P4
    P4 -->|"Shipment update"| D4

    P4 -->|"Order messages"| ExtMsg
    ExtMsg -->|"Message status"| P4
    P4 -->|"Notification logs"| D8

    P1 -->|"Tracking events"| ExtAnalytics
    P5 -->|"Read orders, products, customers, payments, inventory"| D4
    P5 --> D1
    P5 --> D2
    P5 --> D5
    P5 --> D6
    P5 -->|"Generated reports"| D9
    P5 -->|"Dashboard and exports"| Admin
```

## 5. Level 2 Checkout And Order Data Flow

```mermaid
flowchart TD
    Customer["Customer / Guest"]
    P1("1.1 Cart Management")
    P2("1.2 Checkout Validation")
    P3("1.3 Order Creation")
    P4("1.4 Payment Handling")
    P5("1.5 Order Notification")

    D1[("Product Catalog DB")]
    D2[("Cart DB")]
    D3[("Customer and Address DB")]
    D4[("Inventory DB")]
    D5[("Order DB")]
    D6[("Payment DB")]
    D7[("Coupon and Promotion DB")]
    D8[("Notification Log")]

    Pay["Payment Gateway"]
    Msg["Email / SMS / WhatsApp"]

    Customer -->|"Add/update/remove item"| P1
    P1 -->|"Read product, variant, price"| D1
    P1 -->|"Save cart item"| D2
    P1 -->|"Cart summary"| Customer

    Customer -->|"Checkout details"| P2
    P2 -->|"Read cart"| D2
    P2 -->|"Validate customer/address"| D3
    P2 -->|"Validate stock"| D4
    P2 -->|"Validate coupon"| D7
    P2 -->|"Validated checkout data"| P3

    P3 -->|"Create order and order items"| D5
    P3 -->|"Reserve or reduce stock"| D4
    P3 -->|"Create payment intent/record"| D6
    P3 -->|"Payment required?"| P4

    P4 -->|"COD selected"| D6
    P4 -->|"Online payment request"| Pay
    Pay -->|"Payment status/webhook"| P4
    P4 -->|"Update payment status"| D6
    P4 -->|"Update order payment status"| D5

    P3 -->|"Order event"| P5
    P4 -->|"Payment event"| P5
    P5 -->|"Send order message"| Msg
    P5 -->|"Save notification status"| D8
    P5 -->|"Order confirmation/status"| Customer
```

## 6. Level 2 Product And Inventory Data Flow

```mermaid
flowchart TD
    Admin["Product / Inventory Admin"]
    Storefront["Storefront"]
    P1("2.1 Product Management")
    P2("2.2 Inventory Management")
    P3("2.3 Product Publishing")
    P4("2.4 Search Index Update")
    P5("2.5 Product Query API")

    D1[("Product Catalog DB")]
    D2[("Category / Brand / Attribute DB")]
    D3[("Inventory DB")]
    D4[("Media Storage")]
    D5[("Audit Log")]
    Search[("Search Index")]

    Admin -->|"Product data, price, variants, SEO"| P1
    P1 -->|"Save product and variants"| D1
    P1 -->|"Save category, brand, attributes"| D2
    P1 -->|"Upload images"| D4
    P1 -->|"Admin action"| D5

    Admin -->|"Stock adjustment, low-stock rule"| P2
    P2 -->|"Update stock quantity"| D3
    P2 -->|"Stock movement log"| D3
    P2 -->|"Admin action"| D5

    P1 -->|"Publish/unpublish request"| P3
    P3 -->|"Visibility/status update"| D1
    P3 -->|"Indexable product data"| P4
    P4 -->|"Update search document"| Search

    Storefront -->|"Product listing/search/detail request"| P5
    P5 -->|"Search query"| Search
    P5 -->|"Product detail query"| D1
    P5 -->|"Stock status query"| D3
    P5 -->|"Product response"| Storefront
```

## 7. Level 2 Delivery, COD, Return, And Refund Data Flow

```mermaid
flowchart TD
    Admin["Order Admin / Support"]
    Customer["Customer"]
    P1("3.1 Courier Assignment")
    P2("3.2 Delivery Tracking")
    P3("3.3 COD Reconciliation")
    P4("3.4 Return / Exchange / Refund")

    D1[("Order DB")]
    D2[("Shipment DB")]
    D3[("Payment DB")]
    D4[("Inventory DB")]
    D5[("Customer DB")]
    D6[("Audit Log")]

    Courier["Courier Service"]
    Payment["Payment Gateway / Manual Refund"]
    Msg["Email / SMS / WhatsApp"]

    Admin -->|"Assign courier, parcel data"| P1
    P1 -->|"Read order and address"| D1
    P1 -->|"Shipment booking request"| Courier
    Courier -->|"Tracking ID"| P1
    P1 -->|"Save shipment"| D2
    P1 -->|"Update order status"| D1

    Courier -->|"Delivery status update"| P2
    P2 -->|"Update shipment status"| D2
    P2 -->|"Update order delivery status"| D1
    P2 -->|"Notify delivery status"| Msg

    Courier -->|"COD collected / failed / pending"| P3
    P3 -->|"Update COD status"| D3
    P3 -->|"Update order financial status"| D1

    Customer -->|"Return/refund/exchange request"| P4
    Admin -->|"Approve/reject resolution"| P4
    P4 -->|"Read order/customer"| D1
    P4 --> D5
    P4 -->|"Refund request if online payment"| Payment
    Payment -->|"Refund status"| P4
    P4 -->|"Update payment/refund status"| D3
    P4 -->|"Restore stock if applicable"| D4
    P4 -->|"Update order status"| D1
    P4 -->|"Support/admin action"| D6
    P4 -->|"Notify resolution"| Msg
```

## 8. Level 2 Reporting And Analytics Data Flow

```mermaid
flowchart TD
    Owner["Store Owner"]
    Admin["Admin / Accountant"]
    P1("4.1 Dashboard Metrics")
    P2("4.2 Report Builder")
    P3("4.3 Export Generator")
    P4("4.4 Tracking Event Collector")

    D1[("Order DB")]
    D2[("Product Catalog DB")]
    D3[("Customer DB")]
    D4[("Inventory DB")]
    D5[("Payment DB")]
    D6[("Shipment DB")]
    D7[("Coupon / Promotion DB")]
    D8[("Report Export Storage")]
    Analytics["Google Analytics / GTM / Meta Pixel"]

    P1 -->|"Read order metrics"| D1
    P1 -->|"Read product metrics"| D2
    P1 -->|"Read stock metrics"| D4
    P1 -->|"Dashboard KPIs"| Owner
    P1 -->|"Dashboard KPIs"| Admin

    Admin -->|"Report filters"| P2
    P2 --> D1
    P2 --> D2
    P2 --> D3
    P2 --> D4
    P2 --> D5
    P2 --> D6
    P2 --> D7
    P2 -->|"Report result"| Admin

    Admin -->|"Export request"| P3
    P3 -->|"Read report dataset"| P2
    P3 -->|"CSV/XLSX file"| D8
    P3 -->|"Download link"| Admin

    P4 -->|"View product, add to cart, checkout, purchase"| Analytics
```

## 9. Level 2 Optional Multi-Vendor Data Flow

```mermaid
flowchart TD
    Vendor["Vendor / Seller"]
    Admin["Store Owner / Marketplace Admin"]
    Customer["Customer"]
    P1("5.1 Vendor Onboarding")
    P2("5.2 Vendor Product Management")
    P3("5.3 Vendor Order Processing")
    P4("5.4 Commission and Payout Reporting")

    D1[("Vendor DB")]
    D2[("Product Catalog DB")]
    D3[("Order DB")]
    D4[("Inventory DB")]
    D5[("Payment DB")]
    D6[("Audit Log")]

    Vendor -->|"Apply / profile data"| P1
    Admin -->|"Approve / suspend vendor"| P1
    P1 -->|"Vendor status"| D1
    P1 -->|"Admin action"| D6

    Vendor -->|"Product, price, stock"| P2
    P2 -->|"Vendor-owned product"| D2
    P2 -->|"Vendor stock"| D4
    Admin -->|"Approve/reject vendor product"| P2
    P2 -->|"Audit action"| D6

    Customer -->|"Order with vendor items"| P3
    P3 -->|"Vendor-level order grouping"| D3
    Vendor -->|"Fulfillment update"| P3
    P3 -->|"Order status update"| D3
    P3 -->|"Stock update"| D4

    P4 -->|"Read vendor sales/order/payment data"| D1
    P4 --> D3
    P4 --> D5
    P4 -->|"Commission and payout report"| Admin
    P4 -->|"Vendor payout view"| Vendor
```

## 10. Data Stores

| Data Store | Main Data |
|---|---|
| Vendor DB | Vendor profile, status, commission settings, payout information, vendor ownership boundaries |
| Product Catalog DB | Products, variants, SKU, prices, images, SEO fields, categories, brands, attributes |
| Customer DB | Customer profile, address, order history reference, support notes |
| Cart DB | Guest and customer cart items, quantities, coupon reference |
| Order DB | Orders, order items, statuses, invoices, notes, return/exchange state |
| Inventory DB | Stock quantity, reserved stock, low-stock threshold, stock movement history |
| Payment DB | Payment method, payment status, transaction ID, COD status, refund state |
| Shipment DB | Courier, tracking ID, delivery status, COD collection status |
| Content and Settings DB | Banners, pages, menus, store settings, package/module settings |
| Coupon and Promotion DB | Coupon rules, flash sale rules, free delivery rules, campaign settings |
| Audit and Notification Logs | Admin/vendor actions, notification attempts, webhook processing history |
| Report Export Storage | Generated CSV/XLSX report files |
| Media Storage | Product images, banners, imported files, documents |
| Search Index | Searchable product and category documents |

## 11. Important Data Control Rules

- Payment webhooks must be idempotent so duplicate callbacks do not double-confirm or double-refund an order.
- Inventory stock changes must always create stock movement records.
- Admin changes to order, payment, stock, permission, and settings must be audit logged.
- Disabled package modules must not expose data through admin menus, storefront UI, or API endpoints.
- Client business data must be exportable according to contract.
- Payment credentials, courier credentials, and API keys must be stored securely.
- Customer personal data must be protected with proper access control.
- Reports must read from source data without changing transactional records.
- In multi-vendor mode, vendors must only access their own products, order items, stock, and payout data.
- Each client deployment must keep database/storage isolated from other client deployments unless a future SaaS model is intentionally approved.
