# Ecommerce Platform API Documentation

This document covers the implemented Laravel API routes for the ecommerce platform.

## Base URL

`http://localhost:8000/api/v1`

## Standard Headers

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>
```

Use `Authorization` only for protected endpoints.

## Response Shape

Most successful API responses use:

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {},
  "meta": {}
}
```

Validation failures return HTTP `422` with field errors.

## Postman Collection

Use this upload-ready collection:

[Ecommerce_Platform_API.postman_collection.json](Ecommerce_Platform_API.postman_collection.json)

In Postman: **Import** -> **Upload Files** -> select the JSON collection file.

Set:

- `base_url`: `http://localhost:8000/api/v1`
- `customer_token`: token from customer login/register
- `admin_token`: token for an admin user

## Auth - Customer

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/auth/customer/register` | Public | Register customer |
| POST | `/auth/customer/login` | Public | Login customer |
| POST | `/auth/customer/password/forgot` | Public | Request password reset |
| POST | `/auth/customer/password/reset` | Public | Reset password |
| GET | `/auth/customer/me` | Customer token | Current customer |
| POST | `/auth/customer/logout` | Customer token | Logout current token |
| POST | `/auth/customer/logout-all` | Customer token | Logout all tokens |

Register body:

Do not send a `role` or `roles` field for customer registration. The API always creates this account as a non-admin customer and automatically assigns the `customer` role.

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+8801700000000",
  "password": "Password123",
  "password_confirmation": "Password123"
}
```

Login body:

```json
{
  "email": "john@example.com",
  "password": "Password123"
}
```

## Catalog

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| GET | `/catalog/products` | Public | None |
| GET | `/catalog/products/{slug}` | Public | None |
| GET | `/categories` | Admin token | `categories.view` |
| POST | `/categories` | Admin token | `categories.create` |
| GET | `/categories/{id}` | Admin token | `categories.view` |
| PUT | `/categories/{id}` | Admin token | `categories.update` |
| DELETE | `/categories/{id}` | Admin token | `categories.delete` |
| GET | `/products` | Admin token | `products.view` |
| POST | `/products` | Admin token | `products.create` |
| GET | `/products/{id}` | Admin token | `products.view` |
| PUT | `/products/{id}` | Admin token | `products.update` |
| DELETE | `/products/{id}` | Admin token | `products.delete` |
| POST | `/products/{id}/publish` | Admin token | `products.publish` |
| POST | `/products/{id}/unpublish` | Admin token | `products.publish` |

## Cart

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/cart/guest` | Public | Create guest cart |
| GET | `/cart/guest/{cartToken}` | Public | Show guest cart |
| POST | `/cart/guest/{cartToken}/items` | Public | Add guest cart item |
| PUT | `/cart/guest/{cartToken}/items/{itemId}` | Public | Update guest cart item |
| DELETE | `/cart/guest/{cartToken}/items/{itemId}` | Public | Remove guest cart item |
| DELETE | `/cart/guest/{cartToken}` | Public | Clear guest cart |
| GET | `/customer/cart` | Customer token | Show customer cart |
| POST | `/customer/cart/items` | Customer token | Add customer cart item |
| PUT | `/customer/cart/items/{itemId}` | Customer token | Update customer cart item |
| DELETE | `/customer/cart/items/{itemId}` | Customer token | Remove customer cart item |
| DELETE | `/customer/cart` | Customer token | Clear customer cart |

Cart item body:

```json
{
  "product_id": 1,
  "product_variant_id": 1,
  "quantity": 1
}
```

## Checkout

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/checkout/validate` | Customer token | Validate checkout |
| POST | `/checkout/submit` | Customer token | Submit checkout and create order/payment |

Checkout resolves delivery charge from active admin-configured delivery rates that match the shipping address. If no delivery rates are configured, it falls back to the static checkout config.

Checkout body:

```json
{
  "shipping_address_id": 1,
  "billing_address_id": 1,
  "billing_same_as_shipping": true,
  "shipping_method": "standard",
  "payment_method": "cod"
}
```

For submit, include an idempotency key in the request body:

```json
{
  "shipping_address_id": 1,
  "billing_address_id": 1,
  "billing_same_as_shipping": true,
  "shipping_method": "standard",
  "payment_method": "cod",
  "idempotency_key": "checkout-001"
}
```

## Shipping

| Method | Endpoint | Auth | Permission | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/shipping/zones` | Admin token | `shipping.view` | List delivery zones |
| POST | `/shipping/zones` | Admin token | `shipping.create` | Create delivery zone |
| GET | `/shipping/zones/{id}` | Admin token | `shipping.view` | Show delivery zone |
| PUT | `/shipping/zones/{id}` | Admin token | `shipping.update` | Update delivery zone |
| DELETE | `/shipping/zones/{id}` | Admin token | `shipping.delete` | Delete delivery zone |
| GET | `/shipping/rates` | Admin token | `shipping.view` | List delivery rates |
| POST | `/shipping/rates` | Admin token | `shipping.create` | Create delivery rate |
| GET | `/shipping/rates/{id}` | Admin token | `shipping.view` | Show delivery rate |
| PUT | `/shipping/rates/{id}` | Admin token | `shipping.update` | Update delivery rate |
| DELETE | `/shipping/rates/{id}` | Admin token | `shipping.delete` | Delete delivery rate |

Delivery zone body:

```json
{
  "name": "Dhaka City",
  "code": "dhaka-city",
  "country": "BD",
  "cities": ["Dhaka"],
  "status": "active",
  "sort_order": 1
}
```

Delivery rate body:

```json
{
  "delivery_zone_id": 1,
  "name": "Inside Dhaka",
  "code": "inside-dhaka",
  "amount": 60,
  "status": "active",
  "sort_order": 1
}
```

## Customer Account

| Method | Endpoint | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/customer/profile` | Customer token | Show profile |
| PUT | `/customer/profile` | Customer token | Update profile |
| GET | `/customer/addresses` | Customer token | List addresses |
| POST | `/customer/addresses` | Customer token | Create address |
| GET | `/customer/addresses/{id}` | Customer token | Show address |
| PUT | `/customer/addresses/{id}` | Customer token | Update address |
| DELETE | `/customer/addresses/{id}` | Customer token | Delete address |
| GET | `/customer/orders` | Customer token | Customer order history |
| GET | `/customer/orders/{id}` | Customer token | Customer order detail |

Address body:

```json
{
  "label": "Home",
  "recipient_name": "John Doe",
  "phone": "01700000000",
  "address_line_1": "Road 1, House 2",
  "address_line_2": null,
  "city": "Dhaka",
  "state": "Dhaka",
  "postal_code": "1207",
  "country": "BD",
  "is_default_shipping": true,
  "is_default_billing": true
}
```

## Admin Customer Support

| Method | Endpoint | Auth | Permission | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/customers` | Admin token | `customers.view` | List customers |
| GET | `/customers/{id}` | Admin token | `customers.view` | Support detail with addresses, orders, notes, tags |
| PATCH | `/customers/{id}/status` | Admin token | `customers.update` | Update status |
| POST | `/customers/{id}/notes` | Admin token | `customers.notes.create` | Add internal note |
| PUT | `/customers/{id}/tags` | Admin token | `customers.tags.update` | Replace tags |

Status body:

```json
{
  "status": "suspended"
}
```

Note body:

```json
{
  "note": "Customer prefers phone support.",
  "metadata": {
    "source": "call"
  }
}
```

Tags body:

```json
{
  "tags": ["vip", "fragile-delivery"]
}
```

## Admin Orders And Fulfillment

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| GET | `/orders` | Admin token | `orders.view` |
| GET | `/orders/{id}` | Admin token | `orders.view` |
| PATCH | `/orders/{id}/status` | Admin token | `orders.update_status` |
| POST | `/orders/{id}/notes` | Admin token | `orders.notes.create` |
| POST | `/orders/{id}/cancel` | Admin token | `orders.cancel` |
| POST | `/orders/{id}/return-requests` | Admin token | `orders.returns.create` |
| POST | `/orders/{id}/invoice` | Admin token | `orders.invoices.generate` |
| POST | `/orders/{id}/packing-slip` | Admin token | `orders.packing_slips.generate` |
| POST | `/orders/{id}/shipments` | Admin token | `orders.shipments.create` |

Order status body:

```json
{
  "status": "confirmed",
  "note": "Checked by admin.",
  "metadata": {}
}
```

Cancel body:

```json
{
  "reason": "Customer requested cancellation.",
  "metadata": {}
}
```

Return request body:

```json
{
  "reason": "Damaged item reported.",
  "metadata": {}
}
```

Shipment body:

```json
{
  "carrier_name": "Pathao",
  "tracking_number": "TRK123",
  "tracking_url": "https://example.com/track/TRK123",
  "status": "pending",
  "metadata": {}
}
```

## Payments

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| PATCH | `/payments/{id}/status` | Admin token | `payments.update` |
| POST | `/payments/{id}/cod/collect` | Admin token | `payments.cod.collect` |
| POST | `/payments/webhooks/{provider}` | Signed webhook | Public route with signature verification |

Payment status body:

```json
{
  "status": "paid",
  "transaction_id": "TXN-123",
  "provider_reference": "REF-123",
  "metadata": {}
}
```

Webhook headers:

```http
X-Webhook-Signature: <signature>
X-Webhook-Event-Id: <unique-event-id>
```

## Inventory

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| POST | `/inventory/variants/{variantId}/adjust` | Admin token | `inventory.adjust` |
| POST | `/inventory/variants/{variantId}/reserve` | Admin token | `inventory.reserve` |
| POST | `/inventory/variants/{variantId}/release` | Admin token | `inventory.release` |

Inventory body:

```json
{
  "quantity": 5,
  "reason": "manual_adjustment",
  "note": "Cycle count correction"
}
```

## Reports

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| GET | `/reports/dashboard` | Admin token | `reports.view` |
| GET | `/reports/orders` | Admin token | `reports.view` |
| GET | `/reports/sales` | Admin token | `reports.view` |
| GET | `/reports/inventory` | Admin token | `reports.view` |
| GET | `/reports/customers` | Admin token | `reports.view` |

Supported date filters on date-based reports:

```http
?from=2026-04-01&to=2026-04-30
```

## Settings

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| GET | `/settings/store-profile` | Admin token | `settings.view` |
| PUT | `/settings/store-profile` | Admin token | `settings.update` |
| GET | `/settings/module-toggles` | Admin token | `modules.view` |
| PUT | `/settings/module-toggles` | Admin token | `modules.update` |

## Admin Users

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| GET | `/users` | Admin token | `users.view` |
| POST | `/users` | Admin token | `users.create` |
| GET | `/users/{id}` | Admin token | `users.view` |
| PUT | `/users/{id}` | Admin token | `users.update` |
| DELETE | `/users/{id}` | Admin token | `users.delete` |

## OpenAPI

The OpenAPI contract is maintained here:

[Ecommerce_Platform_OpenAPI.yaml](Ecommerce_Platform_OpenAPI.yaml)

This file should be updated in the same slice whenever API routes, request bodies, or response resources change.
