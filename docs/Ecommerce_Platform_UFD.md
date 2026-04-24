# User Flow Diagram (UFD)

Project: Modular API-Based Ecommerce Platform  
Date: 12 April 2026  
Version: 1.0

## 1. Purpose

This document defines the main user flows for the API-based ecommerce platform. It covers customer shopping, checkout, admin operations, payment, delivery, inventory, and return/refund flows.

## 2. High-Level Platform Flow

```mermaid
flowchart LR
    Customer["Customer"] --> Storefront["Storefront App"]
    Guest["Guest Customer"] --> Storefront
    Storefront --> API["Backend Core API"]
    AdminUser["Admin / Staff"] --> BackendCore["Backend Core App"]
    BackendCore --> API
    API --> DB["Database"]
    API --> Cache["Cache / Queue"]
    API --> Search["Search Service"]
    API --> Payment["Payment Gateway"]
    API --> Courier["Courier Service"]
    API --> Notify["Email / SMS / WhatsApp"]
    API --> Analytics["Analytics / Tracking"]
```

## 3. Customer Shopping Flow

```mermaid
flowchart TD
    A["Enter Storefront"] --> B["Browse Home / Category / Search"]
    B --> C["View Product Listing"]
    C --> D["Apply Filter / Sort"]
    D --> E["Open Product Detail"]
    E --> F{"Product Available?"}
    F -- "No" --> G["Show Out of Stock / Notify Option"]
    F -- "Yes" --> H["Select Variant / Quantity"]
    H --> I{"Customer Action"}
    I -- "Add to Cart" --> J["Cart Updated"]
    I -- "Buy Now" --> K["Go to Checkout"]
    I -- "Wishlist" --> L{"Logged In?"}
    L -- "Yes" --> M["Save to Wishlist"]
    L -- "No" --> N["Prompt Login / Continue Browsing"]
    J --> O["Continue Shopping or Checkout"]
    O --> B
    O --> K
```

## 4. Checkout Flow

```mermaid
flowchart TD
    A["Start Checkout"] --> B{"Guest Checkout Enabled?"}
    B -- "Yes" --> C["Enter Customer Details"]
    B -- "No" --> D["Login / Register Required"]
    D --> E["Customer Account"]
    E --> F["Select / Add Address"]
    C --> F
    F --> G["Select Delivery Zone"]
    G --> H["Calculate Delivery Charge"]
    H --> I["Apply Coupon if Any"]
    I --> J["Validate Cart, Stock, Price, Coupon"]
    J --> K{"Valid?"}
    K -- "No" --> L["Show Error and Correction"]
    L --> J
    K -- "Yes" --> M["Select Payment Method"]
    M --> N{"Payment Method"}
    N -- "COD" --> O["Create COD Order"]
    N -- "Online Payment" --> P["Redirect / Initiate Payment"]
    P --> Q{"Payment Result"}
    Q -- "Success" --> R["Create / Confirm Paid Order"]
    Q -- "Failed / Cancelled" --> S["Show Payment Failed"]
    O --> T["Show Order Success"]
    R --> T
    T --> U["Notify Customer and Admin"]
```

## 5. Admin Order Processing Flow

```mermaid
flowchart TD
    A["Admin Receives New Order"] --> B["View Order Detail"]
    B --> C{"Order Valid?"}
    C -- "No" --> D["Cancel / Mark Fraud / Contact Customer"]
    C -- "Yes" --> E["Confirm Order"]
    E --> F["Reserve / Reduce Stock by Configured Rule"]
    F --> G["Pack Order"]
    G --> H{"Courier Integration Enabled?"}
    H -- "Yes" --> I["Create Courier Booking"]
    H -- "No" --> J["Manual Courier Assignment"]
    I --> K["Save Tracking ID"]
    J --> K
    K --> L["Mark Shipped"]
    L --> M["Notify Customer"]
    M --> N{"Delivery Result"}
    N -- "Delivered" --> O["Mark Delivered"]
    N -- "Failed" --> P["Mark Failed Delivery"]
    N -- "Returned" --> Q["Start Return Flow"]
    O --> R["Update Sales / COD Report"]
```

## 6. Product And Inventory Flow

```mermaid
flowchart TD
    A["Admin Opens Product Module"] --> B{"New or Existing Product?"}
    B -- "New" --> C["Create Product"]
    B -- "Existing" --> D["Edit Product"]
    C --> E["Add Category, Brand, Tags"]
    D --> E
    E --> F["Add Variants / SKU / Price / Offer Price"]
    F --> G["Add Images and Descriptions"]
    G --> H["Set Stock Quantity"]
    H --> I["Set SEO Fields"]
    I --> J{"Publish Now?"}
    J -- "Yes" --> K["Publish Product"]
    J -- "No" --> L["Save Draft"]
    K --> M["Product Visible on Storefront"]
    M --> N["Orders Affect Stock"]
    N --> O{"Low Stock?"}
    O -- "Yes" --> P["Show Low Stock Alert"]
    O -- "No" --> Q["Continue Monitoring"]
```

## 7. Return, Refund, And Exchange Flow

```mermaid
flowchart TD
    A["Customer Requests Return / Exchange"] --> B["Support Reviews Request"]
    B --> C{"Policy Eligible?"}
    C -- "No" --> D["Reject Request with Reason"]
    C -- "Yes" --> E["Approve Return / Exchange"]
    E --> F["Receive Returned Product"]
    F --> G{"Product Condition OK?"}
    G -- "No" --> H["Reject / Partial Resolution"]
    G -- "Yes" --> I{"Resolution Type"}
    I -- "Refund" --> J["Process Refund"]
    I -- "Exchange" --> K["Create Exchange Shipment"]
    I -- "Store Credit" --> L["Issue Store Credit / Wallet if Enabled"]
    J --> M["Update Order and Payment Status"]
    K --> M
    L --> M
    M --> N["Restore Stock if Applicable"]
    N --> O["Notify Customer"]
```

## 8. Package / Module Activation Flow

```mermaid
flowchart TD
    A["Client Selects Package"] --> B["Provision Client Deployment"]
    B --> C["Create Store Configuration"]
    C --> D["Enable Package Modules"]
    D --> E{"Module Dependencies Valid?"}
    E -- "No" --> F["Show Missing Dependency"]
    F --> D
    E -- "Yes" --> G["Configure Store Settings"]
    G --> H["Configure Payment / Courier / Notifications if Included"]
    H --> I["Assign Admin Roles"]
    I --> J["Launch Storefront"]
    J --> K["Monitor Usage and Upgrade Needs"]
```

## 9. Optional Multi-Vendor Flow

```mermaid
flowchart TD
    A["Client Enables Multi-Vendor Module"] --> B["Store Owner Configures Vendor Rules"]
    B --> C["Vendor Applies / Is Created"]
    C --> D{"Vendor Approved?"}
    D -- "No" --> E["Reject / Keep Pending"]
    D -- "Yes" --> F["Vendor Gets Vendor Portal Access"]
    F --> G["Vendor Adds Products"]
    G --> H{"Product Approval Required?"}
    H -- "Yes" --> I["Store Admin Reviews Product"]
    H -- "No" --> J["Product Published"]
    I --> K{"Approved?"}
    K -- "No" --> L["Return to Vendor for Changes"]
    K -- "Yes" --> J
    J --> M["Customer Places Order"]
    M --> N["Order Items Grouped by Vendor"]
    N --> O["Vendor Fulfills Own Items"]
    O --> P["Store Owner Tracks Commission and Payout"]
```

## 10. Key Flow Notes

- Guest checkout should be configurable by store.
- COD must work without online payment gateway.
- Online payment must use verified callbacks/webhooks and idempotent processing.
- Courier integration must be adapter-based so providers can be changed.
- Inventory changes must be recorded in stock movement history.
- Disabled modules must be hidden from admin and blocked at API level.
- Customer business data must remain exportable.
- Admin actions on order, stock, payment, and permission changes must be audit logged.
- Multi-vendor flow must stay disabled for normal single-vendor clients and only activate through package/module configuration.
- Each client should be provisioned as a separate deployment with isolated database/storage unless a future SaaS model is intentionally chosen.
