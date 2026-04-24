# Ecommerce Platform Detailed WBS (With Development Hours)

This WBS is task-level and aligned with your direction:
- Phase 1 first: Single-vendor MVP.
- Phase 2 later: Multi-vendor as optional module.
- One codebase, separate client deployment, module-based enable/disable.

## Estimation Basis

- Hours are development effort (Backend Core, admin panel work inside Backend Core, and storefront integration where applicable).
- QA/UAT/PM/training hours are not included here.
- Hours were adjusted to half of previous estimates (rounded to whole numbers).

## Phase 1: Single-Vendor MVP

### Module-Wise Total (Phase 1)

| Module | Total Dev Hours |
|---|---:|
| M1 Core Foundation | 24 |
| M2 Auth And RBAC | 34 |
| M3 Store Settings And Module Control | 23 |
| M4 Categories | 16 |
| M5 Products And Variants | 53 |
| M6 Inventory | 37 |
| M7 Cart | 23 |
| M8 Checkout And Order Creation | 41 |
| M9 Orders And Fulfillment | 46 |
| M10 Payments (MVP) | 29 |
| M11 Shipping And Courier (MVP) | 33 |
| M12 Customers | 22 |
| M13 Content And Policy Pages | 12 |
| M14 Promotions/Coupons (Optional In MVP Package) | 26 |
| M15 Dashboard And Reports | 28 |
| M16 Security, Audit, And Logging Baseline | 22 |
| M17 CI/CD And Deployment Automation | 27 |
| **Phase 1 Total (Core MVP without M14 optional)** | **470** |
| **Phase 1 Total (Core MVP + M14 optional)** | **496** |

### M1 Core Foundation (24h)

| Task | Hours |
|---|---:|
| Repo setup for Backend Core + storefront | 3 |
| workspace setup for Backend Core + storefront | 3 |
| Environment management | 2 |
| config management | 2 |
| Global API response contract | 2 |
| Global API error contract | 3 |
| Base middleware stack (auth, validation, logging hooks) | 3 |
| Module registry skeleton | 2 |
| Seed scripts for baseline data | 2 |
| bootstrap scripts for baseline data | 2 |
| **M1 Total** | **24** |

### M2 Auth And RBAC (34h)

| Task | Hours |
|---|---:|
| Admin login | 2 |
| Admin logout | 2 |
| Customer register | 2 |
| Customer login | 3 |
| Customer logout | 3 |
| Password reset flow | 2 |
| Access token lifecycle | 3 |
| refresh token lifecycle | 3 |
| Roles create | 3 |
| Roles edit | 3 |
| Roles delete | 3 |
| Permission mapping + API guard enforcement | 4 |
| Auth-related audit log events | 1 |
| **M2 Total** | **34** |

### M3 Store Settings And Module Control (23h)

| Task | Hours |
|---|---:|
| Store profile create | 2 |
| Store profile update | 2 |
| Policy pages create | 2 |
| Policy pages edit | 2 |
| Policy pages publish | 2 |
| SEO defaults create | 2 |
| SEO defaults edit | 2 |
| Module enable backend enforcement | 3 |
| Module disable backend enforcement | 3 |
| Module control screens in admin | 3 |
| **M3 Total** | **23** |

### M4 Categories (16h)

| Task | Hours |
|---|---:|
| Category create | 2 |
| Category edit | 2 |
| Category delete (with parent validation) | 1 |
| Category delete (with child validation) | 1 |
| Category list | 1 |
| Category search | 1 |
| Category pagination | 1 |
| Parent-child mapping and sorting | 3 |
| Parent-child mapping and reorder | 2 |
| Storefront category tree endpoint + caching | 2 |
| **M4 Total** | **16** |

### M5 Products And Variants (53h)

| Task | Hours |
|---|---:|
| Product create | 3 |
| Product edit | 3 |
| Product delete | 1 |
| Product archive | 1 |
| Product list (admin) | 3 |
| Product filter (admin) | 3 |
| Product search (admin) | 3 |
| Product publish status control | 1 |
| Product unpublish status control | 1 |
| Variant create | 2 |
| Variant edit | 2 |
| Variant delete | 1 |
| Product image upload | 4 |
| Product image order | 4 |
| Product image update | 4 |
| Product image remove | 4 |
| SEO and compliance fields integration | 3 |
| Slug validation and uniqueness rules | 2 |
| SKU validation and uniqueness rules | 2 |
| Storefront product list optimization | 3 |
| Storefront product detail optimization | 3 |
| **M5 Total** | **53** |

### M6 Inventory (37h)

| Task | Hours |
|---|---:|
| Inventory stock model and migration | 3 |
| Stock adjustment create | 3 |
| Stock adjustment reverse handling | 2 |
| Stock adjustment correction handling | 2 |
| Inventory movement log automation | 3 |
| Reservation engine with order lifecycle | 6 |
| release engine with order lifecycle | 6 |
| Low-stock threshold and rule handling | 3 |
| Admin inventory list views | 3 |
| Admin inventory history views | 4 |
| Concurrency protection for stock writes | 2 |
| **M6 Total** | **37** |

### M7 Cart (23h)

| Task | Hours |
|---|---:|
| Guest cart token lifecycle | 3 |
| Cart add item | 3 |
| Cart update quantity | 2 |
| Cart remove item | 2 |
| Cart totals recalculation pipeline | 4 |
| Guest cart merge with logged-in customer cart | 3 |
| Cart API recovery handling | 3 |
| Cart state recovery handling | 3 |
| **M7 Total** | **23** |

### M8 Checkout And Order Creation (41h)

| Task | Hours |
|---|---:|
| Checkout validation pipeline | 3 |
| Saved address support in checkout | 3 |
| new address support in checkout | 3 |
| Delivery zone resolve during checkout | 4 |
| Delivery rate resolve during checkout | 4 |
| Atomic order creation transaction | 6 |
| Idempotency handling for checkout submit | 3 |
| Price snapshot storage on order items | 3 |
| Failure rollback and stock compensation | 4 |
| Order confirmation response | 4 |
| Order confirmation events | 4 |
| **M8 Total** | **41** |

### M9 Orders And Fulfillment (46h)

| Task | Hours |
|---|---:|
| Admin order list | 4 |
| Admin order filter | 4 |
| Admin order detail timeline | 3 |
| Order status transition engine and rules | 7 |
| Order status history tracking | 3 |
| Admin order notes | 2 |
| Cancel flow with stock reconciliation | 3 |
| Basic return request flow request | 4 |
| Basic return request flow approve | 4 |
| Basic return request flow reject | 4 |
| Basic return request flow received | 4 |
| Invoice and packing slip generation | 4 |
| **M9 Total** | **46** |

### M10 Payments (MVP) (29h)

| Task | Hours |
|---|---:|
| Payment entity and payment state model | 4 |
| COD payment workflow | 3 |
| Admin manual payment update controls | 2 |
| Webhook verification framework | 6 |
| Webhook idempotency framework | 6 |
| Gateway adapter interface and mock adapters | 5 |
| Order-payment state sync rules | 3 |
| **M10 Total** | **29** |

### M11 Shipping And Courier (MVP) (33h)

| Task | Hours |
|---|---:|
| Delivery zone create | 3 |
| Delivery zone edit | 3 |
| Delivery zone delete | 3 |
| Delivery rate rule create | 3 |
| Delivery rate rule edit | 3 |
| Delivery rate rule delete | 3 |
| Shipment create from order | 2 |
| Shipment assign from order | 2 |
| Tracking ID update | 1 |
| Tracking URL update | 1 |
| Shipment state transitions and mapping | 3 |
| Courier webhook normalization | 2 |
| Courier webhook idempotency | 2 |
| Courier adapter interface stubs | 2 |
| **M11 Total** | **33** |

### M12 Customers (22h)

| Task | Hours |
|---|---:|
| Customer list (admin) | 2 |
| Customer filter (admin) | 2 |
| Customer search (admin) | 2 |
| Customer profile detail | 1 |
| Customer address create | 3 |
| Customer address edit | 3 |
| Customer address delete | 3 |
| Customer order history | 2 |
| Internal notes for support workflow | 2 |
| Internal tags for support workflow | 2 |
| **M12 Total** | **22** |

### M13 Content And Policy Pages (12h)

| Task | Hours |
|---|---:|
| Policy page create | 1 |
| Policy page edit | 1 |
| Policy page delete | 1 |
| Policy page publish | 1 |
| content page create | 1 |
| content page edit | 1 |
| content page delete | 1 |
| content page publish | 1 |
| Homepage banner block management | 2 |
| Footer content management | 1 |
| contact content management | 1 |
| **M13 Total** | **12** |

### M14 Promotions/Coupons (Optional In MVP Package) (26h)

| Task | Hours |
|---|---:|
| Coupon create | 4 |
| Coupon edit | 4 |
| Coupon delete | 4 |
| Coupon validation engine in checkout | 4 |
| Coupon usage limits and expiry handling | 3 |
| Offer scheduling baseline | 2 |
| campaign scheduling baseline | 3 |
| Coupon usage reporting (basic) | 2 |
| **M14 Total** | **26** |

### M15 Dashboard And Reports (28h)

| Task | Hours |
|---|---:|
| Dashboard KPI aggregation | 5 |
| Sales report | 4 |
| Orders report | 4 |
| Inventory report | 3 |
| Customer report | 4 |
| Export jobs CSV | 4 |
| Export jobs XLSX | 4 |
| **M15 Total** | **28** |

### M16 Security, Audit, And Logging Baseline (22h)

| Task | Hours |
|---|---:|
| Audit log event model and storage | 3 |
| Rate limiting auth endpoints | 3 |
| Rate limiting checkout endpoints | 3 |
| Rate limiting public endpoints | 3 |
| Input validation hardening | 3 |
| Permission boundary hardening | 3 |
| Structured logging and error-trace hooks | 4 |
| **M16 Total** | **22** |

### M17 CI/CD And Deployment Automation (27h)

| Task | Hours |
|---|---:|
| Dockerization and environment templates | 4 |
| CI pipeline lint | 3 |
| CI pipeline test | 3 |
| CI pipeline build | 3 |
| CD flow for staging and production | 4 |
| Backup automation scripts | 3 |
| restore automation scripts | 4 |
| Release and rollback automation checklist | 3 |
| **M17 Total** | **27** |

## Phase 2: Multi-Vendor Module (Post-MVP)

### Module-Wise Total (Phase 2)

| Module | Total Dev Hours |
|---|---:|
| MV1 Multi-Vendor Activation And Gating | 13 |
| MV2 Vendor Onboarding And Access | 29 |
| MV3 Vendor Catalog Governance | 34 |
| MV4 Vendor Inventory Boundaries | 17 |
| MV5 Vendor Order Split And Processing | 46 |
| MV6 Commission And Payout | 39 |
| MV7 Vendor Portal UI | 36 |
| MV8 Marketplace Security Hardening | 20 |
| MV9 Marketplace Reports And Admin Controls | 18 |
| MV10 Rollout, Migration, And Recovery Tooling | 15 |
| **Phase 2 Total** | **267** |

### MV1 Multi-Vendor Activation And Gating (13h)

| Task | Hours |
|---|---:|
| Commerce mode switch and config controls | 2 |
| Multi-vendor module route gating | 3 |
| Multi-vendor module API gating | 3 |
| Multi-vendor module UI gating | 3 |
| Backward compatibility for single-vendor clients | 2 |
| **MV1 Total** | **13** |

### MV2 Vendor Onboarding And Access (29h)

| Task | Hours |
|---|---:|
| Vendor invite | 3 |
| Vendor self-registration | 2 |
| Vendor profile create | 2 |
| Vendor profile edit | 2 |
| Vendor approve | 2 |
| Vendor reject | 2 |
| Vendor suspend | 2 |
| Vendor reactivate | 2 |
| Vendor login reset | 2 |
| Vendor session reset | 2 |
| Vendor password reset | 2 |
| Vendor role baseline | 3 |
| Vendor permission baseline | 3 |
| **MV2 Total** | **29** |

### MV3 Vendor Catalog Governance (34h)

| Task | Hours |
|---|---:|
| Vendor product create | 3 |
| Vendor product edit | 2 |
| Vendor product delete | 2 |
| Vendor product archive | 2 |
| Admin product approval workflow | 4 |
| Admin product rejection workflow | 4 |
| Vendor media validation | 3 |
| Vendor compliance validation | 3 |
| Vendor catalog list | 2 |
| Vendor catalog search | 2 |
| Vendor catalog filter | 3 |
| Vendor catalog notifications | 2 |
| Vendor catalog audit trails | 2 |
| **MV3 Total** | **34** |

### MV4 Vendor Inventory Boundaries (17h)

| Task | Hours |
|---|---:|
| Vendor-scoped stock visibility | 4 |
| Vendor stock adjust with strict ownership checks | 5 |
| Cross-vendor stock isolation policy enforcement | 5 |
| Vendor low-stock alert jobs | 3 |
| **MV4 Total** | **17** |

### MV5 Vendor Order Split And Processing (46h)

| Task | Hours |
|---|---:|
| Split order items by vendor during order creation | 8 |
| Vendor order list | 4 |
| Vendor order filter | 5 |
| Vendor order detail and item actions | 5 |
| Vendor-allowed status transitions | 5 |
| Vendor sub-shipment handling | 5 |
| Vendor cancel request workflow | 5 |
| Vendor refund request workflow | 5 |
| Vendor order history and audit tracking | 4 |
| **MV5 Total** | **46** |

### MV6 Commission And Payout (39h)

| Task | Hours |
|---|---:|
| Commission rule create | 5 |
| Commission rule edit | 5 |
| Commission calculation engine | 7 |
| Vendor earnings ledger | 4 |
| Payout request workflow | 5 |
| Manual payout settlement + references | 4 |
| Commission reports | 5 |
| payout reports | 4 |
| **MV6 Total** | **39** |

### MV7 Vendor Portal UI (36h)

| Task | Hours |
|---|---:|
| Vendor dashboard KPIs | 5 |
| Vendor product screens | 6 |
| Vendor inventory screens | 4 |
| Vendor order screens | 5 |
| Vendor earnings screens | 4 |
| Vendor payout screens | 4 |
| Vendor profile screens | 4 |
| Vendor settings screens | 4 |
| **MV7 Total** | **36** |

### MV8 Marketplace Security Hardening (20h)

| Task | Hours |
|---|---:|
| Vendor scope middleware deep hardening | 6 |
| Object-level authorization tests and fixes | 5 |
| Anti-fraud controls for sensitive vendor actions | 4 |
| Expanded audit hooks for vendor operations | 5 |
| **MV8 Total** | **20** |

### MV9 Marketplace Reports And Admin Controls (18h)

| Task | Hours |
|---|---:|
| Marketplace sales report | 5 |
| Vendor performance report | 5 |
| Commission liability report | 4 |
| Admin marketplace filters and controls | 4 |
| **MV9 Total** | **18** |

### MV10 Rollout, Migration, And Recovery Tooling (15h)

| Task | Hours |
|---|---:|
| Enablement migration scripts for existing clients | 5 |
| Feature-flag rollout scripts and controls | 4 |
| Multi-vendor module rollback scripts | 3 |
| Post-launch monitoring setup updates | 3 |
| **MV10 Total** | **15** |

## Grand Totals

| Scope | Hours |
|---|---:|
| Phase 1 Core MVP (without optional coupons module) | 470 |
| Phase 1 Core MVP (with optional coupons module) | 496 |
| Phase 2 Multi-Vendor Module | 267 |
| **Overall Total (Core MVP without optional coupons + Phase 2)** | **737** |
| **Overall Total (Core MVP with optional coupons + Phase 2)** | **763** |

