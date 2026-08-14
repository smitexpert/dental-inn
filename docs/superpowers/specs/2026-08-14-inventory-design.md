# Sub-Project 3: Inventory — Design Spec

Date: 2026-08-14
Owner: Sub-project 3
Status: Approved for implementation

## 1. Goals

Introduce an inventory module that tracks the dental clinic's consumables,
equipment, medications and materials. Stock is tracked **per location** so the
same product can live in "Main Storage" and "Operatory 1" simultaneously with
different quantities. Every change to stock is recorded as an immutable
`StockMovement` row (source of truth) and a derived `StockLevel` row per
(product × location) is kept in sync. A simple "low stock" view surfaces
products that have fallen to or below their `reorder_level`.

Out of scope (deferred to later sub-projects):
- Invoicing / billing on stock consumption (sub-project 4)
- POS sales to patients (sub-project 4)
- Auto-reorder / purchase order generation (sub-project 6)
- Barcode printing / scanner integration (sub-project 6)
- Email / SMS notifications on low stock (sub-project 6)
- Expiry / batch tracking (sub-project 9)
- Multi-clinic stock transfer (sub-project 9)

## 2. Data Model

### 2.1 `products` table

| Column             | Type            | Notes                                                                                    |
|--------------------|-----------------|------------------------------------------------------------------------------------------|
| `id`               | bigint pk       |                                                                                          |
| `sku`              | string(64)      | unique                                                                                   |
| `barcode`          | string(64)      | nullable, unique                                                                         |
| `name`             | string(255)     | required                                                                                 |
| `category`         | string(32)      | enum: `consumable`, `equipment`, `medication`, `material`, `other`                        |
| `unit`             | string(16)      | enum: `piece`, `box`, `vial`, `syringe`, `ml`, `gram`, `kg`, `liter`, `pack`              |
| `cost_price`       | decimal(18,2)   | nullable — last-known unit cost                                                          |
| `selling_price`    | decimal(18,2)   | nullable — null for internal-use-only items                                              |
| `reorder_level`    | integer         | default 0                                                                                |
| `reorder_quantity` | integer         | default 0 — suggested order qty                                                          |
| `description`      | text            | nullable                                                                                 |
| `is_active`        | boolean         | default `true`                                                                           |
| `track_stock`      | boolean         | default `true` — services / non-stockable items flip this off                            |
| `created_at`, `updated_at` | timestamps |                                                                                          |
| `deleted_at`       | timestamp       | soft delete                                                                              |

Indexes: `(name)`, `(category)`, `(is_active)`, `(track_stock)`.

### 2.2 `suppliers` table

| Column                | Type          | Notes                                                                                  |
|-----------------------|---------------|----------------------------------------------------------------------------------------|
| `id`                  | bigint pk     |                                                                                        |
| `name`                | string(255)   | required, unique                                                                       |
| `contact_person`      | string(255)   | nullable                                                                               |
| `phone`               | string(64)    | nullable                                                                               |
| `email`               | string(255)   | nullable                                                                               |
| `address`             | text          | nullable                                                                               |
| `notes`               | text          | nullable                                                                               |
| `is_active`           | boolean       | default `true`                                                                         |
| `created_at`, `updated_at` | timestamps |                                                                                        |

Indexes: `(name)`, `(is_active)`.

### 2.3 `product_supplier` pivot

| Column           | Type      | Notes                                                              |
|------------------|-----------|--------------------------------------------------------------------|
| `id`             | bigint pk |                                                                    |
| `product_id`     | fk → products | `constrained()->cascadeOnDelete()`                              |
| `supplier_id`    | fk → suppliers | `constrained()->cascadeOnDelete()`                             |
| `supplier_sku`   | string(64) | nullable — their reference for this product                       |
| `unit_cost`      | decimal(18,4) | nullable                                                         |
| `lead_time_days` | integer   | nullable                                                           |
| `is_preferred`   | boolean   | default `false` — one preferred supplier per product               |
| `created_at`, `updated_at` | timestamps |                                                          |

Unique: `(product_id, supplier_id)`.
Index: `(supplier_id)`.

### 2.4 `stock_locations` table

| Column                | Type          | Notes                                                                                    |
|-----------------------|---------------|------------------------------------------------------------------------------------------|
| `id`                  | bigint pk     |                                                                                          |
| `name`                | string(128)   | required, unique                                                                         |
| `location_type`       | string(16)    | enum: `storage`, `operatory`, `cabinet`, `other`                                          |
| `is_active`           | boolean       | default `true`                                                                           |
| `created_at`, `updated_at` | timestamps |                                                                                          |

### 2.5 `stock_levels` table

| Column            | Type          | Notes                                                                                |
|-------------------|---------------|--------------------------------------------------------------------------------------|
| `id`              | bigint pk     |                                                                                      |
| `product_id`      | fk → products | `constrained()->restrictOnDelete()`                                                  |
| `location_id`     | fk → stock_locations | `constrained()->restrictOnDelete()`                                            |
| `quantity`        | integer       | default 0 — non-negative                                                             |
| `last_counted_at` | timestamp     | nullable — last physical count                                                       |
| `created_at`, `updated_at` | timestamps |                                                                                  |

Unique: `(product_id, location_id)`.

### 2.6 `stock_movements` table

| Column           | Type          | Notes                                                                                    |
|------------------|---------------|------------------------------------------------------------------------------------------|
| `id`             | bigint pk     |                                                                                          |
| `product_id`     | fk → products | `constrained()->restrictOnDelete()`                                                      |
| `location_id`    | fk → stock_locations | nullable, `constrained()->nullOnDelete()` (adjustments have no location)              |
| `type`           | string(16)    | enum: `purchase`, `sale`, `consumption`, `transfer_in`, `transfer_out`, `adjustment`, `return` |
| `quantity`       | integer       | **signed** — positive for in, negative for out                                           |
| `reason`         | string(255)   | nullable — free text                                                                     |
| `reference_type` | string(64)    | nullable morph                                                                          |
| `reference_id`    | bigint        | nullable                                                                                 |
| `performed_by`   | fk → users    | `constrained()->restrictOnDelete()`                                                      |
| `unit_cost`      | decimal(18,4) | nullable — captured at moment of movement                                               |
| `created_at`     | timestamp     | movements are immutable — no `updated_at`                                               |

Indexes: `(product_id, created_at)`, `(type)`, `(reference_type, reference_id)`.

## 3. Enums

| Enum                | Cases                                                                                  |
|---------------------|----------------------------------------------------------------------------------------|
| `ProductCategory`   | Consumable, Equipment, Medication, Material, Other                                      |
| `ProductUnit`       | Piece, Box, Vial, Syringe, Ml, Gram, Kg, Liter, Pack                                    |
| `StockLocationType` | Storage, Operatory, Cabinet, Other                                                     |
| `StockMovementType` | Purchase, Sale, Consumption, TransferIn, TransferOut, Adjustment, Return                |

Each enum exposes `value`, `label()`, and (for `StockMovementType`) a
`sign()` helper that returns `+1` for in-flows and `-1` for out-flows.

## 4. Routes

All under `/inventory` and registered in a new `routes/inventory.php`
file. Each route guards with the appropriate `permission:*` middleware
already wired in earlier sub-projects.

### 4.1 Products

| Method | URI                                          | Permission                  | Name                       |
|--------|----------------------------------------------|-----------------------------|----------------------------|
| GET    | `/inventory/products`                        | `view-products`             | `inventory.products.index` |
| GET    | `/inventory/products/api/search`             | `view-products`             | `inventory.products.search`|
| GET    | `/inventory/products/create`                 | `manage-products`           | `inventory.products.create`|
| POST   | `/inventory/products`                        | `manage-products`           | `inventory.products.store` |
| GET    | `/inventory/products/{product}`              | `view-products`             | `inventory.products.show`  |
| GET    | `/inventory/products/{product}/edit`         | `manage-products`           | `inventory.products.edit`  |
| PATCH  | `/inventory/products/{product}`              | `manage-products`           | `inventory.products.update`|
| DELETE | `/inventory/products/{product}`              | `manage-products`           | `inventory.products.destroy`|
| GET    | `/inventory/products/{product}/movements`    | `view-stock-movements`      | `inventory.products.movements`|
| POST   | `/inventory/products/{product}/purchase`     | `record-stock-movements`    | `inventory.products.purchase`|
| POST   | `/inventory/products/{product}/consume`      | `record-stock-movements`    | `inventory.products.consume`|
| POST   | `/inventory/products/{product}/transfer`     | `record-stock-movements`    | `inventory.products.transfer`|
| POST   | `/inventory/products/{product}/adjust`       | `manage-products`           | `inventory.products.adjust` |

### 4.2 Suppliers

| Method | URI                                          | Permission        | Name                            |
|--------|----------------------------------------------|-------------------|---------------------------------|
| GET    | `/inventory/suppliers`                       | `view-suppliers`  | `inventory.suppliers.index`     |
| GET    | `/inventory/suppliers/create`                | `manage-suppliers`| `inventory.suppliers.create`    |
| POST   | `/inventory/suppliers`                       | `manage-suppliers`| `inventory.suppliers.store`     |
| GET    | `/inventory/suppliers/{supplier}`            | `view-suppliers`  | `inventory.suppliers.show`      |
| GET    | `/inventory/suppliers/{supplier}/edit`       | `manage-suppliers`| `inventory.suppliers.edit`      |
| PATCH  | `/inventory/suppliers/{supplier}`            | `manage-suppliers`| `inventory.suppliers.update`    |

### 4.3 Locations

| Method | URI                                          | Permission       | Name                              |
|--------|----------------------------------------------|------------------|-----------------------------------|
| GET    | `/inventory/locations`                       | `view-products`  | `inventory.locations.index`       |
| GET    | `/inventory/locations/create`                | `manage-products`| `inventory.locations.create`      |
| POST   | `/inventory/locations`                       | `manage-products`| `inventory.locations.store`       |
| GET    | `/inventory/locations/{location}/edit`       | `manage-products`| `inventory.locations.edit`        |
| PATCH  | `/inventory/locations/{location}`            | `manage-products`| `inventory.locations.update`      |

### 4.4 Low Stock

| Method | URI                       | Permission      | Name                          |
|--------|---------------------------|-----------------|-------------------------------|
| GET    | `/inventory/low-stock`    | `view-products` | `inventory.low-stock`         |

## 5. Permissions Matrix

| Permission               | Admin | Doctor | Receptionist |
|--------------------------|:-----:|:------:|:------------:|
| `view-products`          |   Y   |   Y    |      Y       |
| `manage-products`        |   Y   |   N    |      N       |
| `view-suppliers`         |   Y   |   Y    |      Y       |
| `manage-suppliers`       |   Y   |   N    |      N       |
| `view-stock-movements`   |   Y   |   Y    |      Y       |
| `record-stock-movements` |   Y   |   N    |      Y       |

Extended in `RoleSeeder::run()` via additive `Permission::firstOrCreate(...)`
+ `syncPermissions([...])` calls — never replacing existing assignments.

## 6. Stock Movement Operations

All four stock operations (purchase, consume, transfer, adjust) funnel
through `StockMovementService::record()` which guarantees atomicity:

```
DB::transaction(function () {
    StockMovement::create($attrs);
    StockLevel::bump($product, $location, $delta);   // upsert + add
});
```

`StockLevel::bump()` either `updateOrCreate`s a level row with
`$existing->quantity + $delta` or fails if the new value would be negative.
The FormRequest for each operation is the user-facing validator; the
service is the safety net.

| Operation | Movement type        | Sign  | Stock effect                                  |
|-----------|----------------------|-------|-----------------------------------------------|
| Purchase  | `purchase`           | `+q`  | one `+q` row at `location_id`                 |
| Consume   | `consumption`        | `-q`  | one `-q` row at `location_id`                 |
| Transfer  | `transfer_in` + `transfer_out` | `+q` / `-q` | paired rows at dest / source                |
| Adjust    | `adjustment`         | signed `delta` | single row at `location_id` (or null for global) |

### 6.1 Consume rule (negative stock prevention)

`ConsumeStockRequest::rules()` requires `quantity` > 0; the controller
re-reads the current `StockLevel` and `assert($current >= $requested)`
**inside** the transaction so concurrent ops cannot race past the check.

### 6.2 Transfer rule

`from_location_id !== to_location_id` enforced in the FormRequest; both
StockLevels must exist (auto-created with `quantity=0` if missing).

## 7. Low-Stock Algorithm

```php
$lowCount = Product::query()
    ->where('track_stock', true)
    ->where('is_active', true)
    ->whereHas('stockLevels', function ($q) {
        $q->selectRaw('MIN(quantity) as min_qty')
          ->groupBy('product_id')
          ->havingRaw('MIN(quantity) <= reorder_level');
    })
    ->count();
```

A "low product" is any active, stock-tracked product whose
**MIN(stock_level.quantity) across all its locations** is `<= reorder_level`
(the comparison uses `<=` so a product sitting at exactly the threshold
counts as low). Implemented as a `ProductRepository::lowStockCount()`
method using a single `selectRaw` subquery for efficiency. Exposed on the
Dashboard `low_stock_items` stat tile, the sidebar badge, and the
`/inventory/low-stock` page.

## 8. Dashboard Integration

`DashboardController::index()` keeps the same 6-tile shape; only
`low_stock_items` is now backed by `ProductRepository::lowStockCount()`.
The other 5 tiles remain placeholder for sub-projects 4 and 5.

## 9. Sidebar Navigation

`useNav.js` replaces the "Inventory" comingSoon stub with a real entry.
The Inventory group renders as a nested collapsible list (matching the
existing `SidebarItem` shape — single `<Link>` for the parent and three
sibling links for Products / Suppliers / Locations / Low Stock). The
sidebar badge is computed client-side from `/dashboard` props (already
shared via Inertia).

## 10. Vue Pages

| Path                                  | Component                              |
|---------------------------------------|----------------------------------------|
| `/inventory/products`                 | `Inventory/Products/Index.vue`         |
| `/inventory/products/create`          | `Inventory/Products/Create.vue`        |
| `/inventory/products/{id}`            | `Inventory/Products/Show.vue`          |
| `/inventory/products/{id}/edit`       | `Inventory/Products/Edit.vue`          |
| `/inventory/products/{id}/movements`  | `Inventory/Products/Movements.vue`     |
| `/inventory/suppliers`                | `Inventory/Suppliers/Index.vue`        |
| `/inventory/suppliers/create`         | `Inventory/Suppliers/Create.vue`       |
| `/inventory/suppliers/{id}`           | `Inventory/Suppliers/Show.vue`         |
| `/inventory/suppliers/{id}/edit`      | `Inventory/Suppliers/Edit.vue`         |
| `/inventory/locations`                | `Inventory/Locations/Index.vue`        |
| `/inventory/low-stock`                | `Inventory/LowStock.vue`               |

All forms use the existing `<UiForm>` / `<UiField>` / `<UiInput>` /
`<UiSelect>` / `<UiButton>` primitives — no direct PrimeVue imports.

## 11. Default Data

`StockLocationSeeder` runs on `php artisan db:seed` and is **idempotent**:
it creates a single `Main Storage` row only if `stock_locations` is empty.
Combined with the existing `DatabaseSeeder` chain, fresh installs get the
location automatically; existing installs (zero locations) get it on the
next seed run.

## 12. Out of Scope / Risks

- No batch / lot / expiry tracking yet (deferred to sub-project 9)
- No automatic reorder workflow — staff still creates a "purchase"
  movement manually after seeing the low-stock list
- No multi-currency support; one clinic currency assumed
- Stock-level writes happen via `updateOrCreate`; if two movements race
  for the same product/location, MySQL row-level locking prevents
  inconsistency, but a unique-constraint violation on a hypothetical
  `audit` table is not implemented
