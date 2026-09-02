# Sub-Project 3: Inventory — Implementation Plan

Date: 2026-08-14
Related spec: `docs/superpowers/specs/2026-08-14-inventory-design.md`

Conventions:
- Quality gate: `composer quality` (= pint --test + phpstan + tests)
- One commit per task (commit message body lists exact files)
- Run tests via `docker exec dental_app php artisan test --testsuite=Feature`
  or `composer test`
- Commands like `php artisan <cmd>` are run via
  `docker exec dental_app php artisan <cmd>`
- All new models get `@property` PHPDoc; new FormRequests implement
  `rules()` + `authorize()`
- All money in `decimal(18, 2)` for display prices, `decimal(18, 4)` for
  `unit_cost` on movements
- All stock changes go through `StockMovementService` inside
  `DB::transaction(...)`

---

## Task 1: Enums + Product model + migration

Files:
- `app/Enums/ProductCategory.php` (new)
- `app/Enums/ProductUnit.php` (new)
- `app/Enums/StockLocationType.php` (new)
- `app/Enums/StockMovementType.php` (new)
- `app/Models/Product.php` (new)
- `database/migrations/2026_08_14_000010_create_products_table.php` (new)

Steps:
1. Four enums with `value`, `label()` (and `sign()` on `StockMovementType`).
2. `Product` model with `HasFactory`, `LogsActivity`, `SoftDeletes`,
   fillable, casts (`cost_price`/`selling_price` → decimal:2, `is_active`/
   `track_stock` → bool, `category` → enum, `unit` → enum), relations
   (`stockLevels`, `movements`, `suppliers` via pivot), scopes
   (`active`, `tracked`, `search`, `lowStock`), and a `getTotalQuantityAttribute()`.
3. Migration: `id`, `sku` unique, `barcode` nullable unique, `name`, `category`,
   `unit`, `cost_price`/`selling_price` decimal(18,2) nullable, `reorder_level`/
   `reorder_quantity` int default 0, `description` text nullable, `is_active`/
   `track_stock` bool default true, timestamps, `deleted_at`. Indexes on
   `name`, `category`, `is_active`, `track_stock`.

Tests:
- `tests/Unit/ProductCategoryEnumTest.php`
- `tests/Unit/ProductUnitEnumTest.php`
- `tests/Unit/StockMovementTypeEnumTest.php` (asserts sign())

Verify: `docker exec dental_app php artisan test --filter='EnumTest'`

Commit: `feat(inventory): enums + Product model + migration`

---

## Task 2: Supplier model + migration + product_supplier pivot

Files:
- `app/Models/Supplier.php` (new)
- `database/migrations/2026_08_14_000011_create_suppliers_table.php` (new)
- `database/migrations/2026_08_14_000012_create_product_supplier_table.php` (new)

Steps:
1. `Supplier` model: `HasFactory`, `LogsActivity`, fillable, casts
   (`is_active` → bool), relations (`products()` belongsToMany Product via
   `product_supplier` with pivot fields).
2. Add `suppliers()` belongsToMany to `Product` (pivot fields:
   `supplier_sku`, `unit_cost`, `lead_time_days`, `is_preferred`).
3. `suppliers` migration: `name` unique, contact fields, `notes` text,
   `is_active` bool default true, timestamps. Indexes on `name`, `is_active`.
4. `product_supplier` migration: id, `product_id` fk cascade, `supplier_id`
   fk cascade, `supplier_sku` string nullable, `unit_cost` decimal(18,4)
   nullable, `lead_time_days` int nullable, `is_preferred` bool default false,
   timestamps. Unique `(product_id, supplier_id)`, index on `supplier_id`.

Tests: none (covered in feature tests).

Verify: `docker exec dental_app php artisan migrate` clean.

Commit: `feat(inventory): Supplier model + product_supplier pivot`

---

## Task 3: StockLocation model + migration + default seeder

Files:
- `app/Models/StockLocation.php` (new)
- `database/migrations/2026_08_14_000013_create_stock_locations_table.php` (new)
- `database/seeders/StockLocationSeeder.php` (new)
- `database/seeders/DatabaseSeeder.php` (update — add seeder call)

Steps:
1. `StockLocation` model: `HasFactory`, fillable, casts, scopes (`active`),
   relations (`stockLevels`, `movements`).
2. Migration: `id`, `name` unique, `location_type` string(16), `is_active`
   bool default true, timestamps.
3. Seeder: if `StockLocation::count() === 0`, create
   `name="Main Storage"`, `location_type="storage"`, `is_active=true`.
4. Add `$this->call(StockLocationSeeder::class)` to `DatabaseSeeder::run()`.

Tests: none (verified via migrate + seed).

Verify: `docker exec dental_app php artisan migrate --seed` clean.

Commit: `feat(inventory): StockLocation model + default seeder`

---

## Task 4: StockLevel model + migration

Files:
- `app/Models/StockLevel.php` (new)
- `database/migrations/2026_08_14_000014_create_stock_levels_table.php` (new)

Steps:
1. `StockLevel` model: fillable, casts (`quantity` int, `last_counted_at`
   datetime), relations (`product`, `location`). Scope `atLocation($q, $id)`.
2. Migration: `id`, `product_id` fk restrict, `location_id` fk restrict,
   `quantity` int default 0, `last_counted_at` timestamp nullable, timestamps.
   Unique `(product_id, location_id)`.

Tests: none.

Verify: `docker exec dental_app php artisan migrate` clean.

Commit: `feat(inventory): StockLevel model + migration`

---

## Task 5: StockMovement model + migration

Files:
- `app/Models/StockMovement.php` (new)
- `database/migrations/2026_08_14_000015_create_stock_movements_table.php` (new)

Steps:
1. `StockMovement` model: `HasFactory`, `LogsActivity`, `disable timestamps`
   (we only have `created_at`; we'll set it manually) — actually we
   override `public $timestamps = false;` and add `CREATED_AT = 'created_at'`.
   Fillable, casts (`quantity` int, `unit_cost` decimal:4, `type` → enum),
   relations (`product`, `location`, `performer`, `reference` morphTo).
2. Migration: `id`, `product_id` fk restrict, `location_id` fk nullable
   `nullOnDelete`, `type` string(16), `quantity` int, `reason` string(255)
   nullable, `reference_type` string(64) nullable, `reference_id` bigint
   nullable, `performed_by` fk users restrict, `unit_cost` decimal(18,4)
   nullable, `created_at` timestamp. No `updated_at`. Indexes
   `(product_id, created_at)`, `(type)`, `(reference_type, reference_id)`.

Tests: none.

Verify: `docker exec dental_app php artisan migrate` clean.

Commit: `feat(inventory): StockMovement model + migration`

---

## Task 6: StockMovementService (transactional helper)

Files:
- `app/Services/StockMovementService.php` (new)

Steps:
1. Public method `record(array $attrs): StockMovement`:
   - Validates: `product_id`, `type` (enum), `quantity` non-zero, `location_id` (unless adjustment).
   - `DB::transaction(...)`:
     - `StockMovement::create([...filled, 'performed_by' => $userId, 'created_at' => now()])`
     - For non-adjustment types: `StockLevel::updateOrCreate(['product_id', 'location_id'], ['quantity' => DB::raw('quantity + '.$delta)])`
     - For adjustment: if `new_quantity` provided, set absolute; else compute delta from `quantity` sign.
2. Helper methods: `purchase`, `consume`, `transfer` (creates two rows),
   `adjust` (computes delta = new - current).
3. Throws `RuntimeException` on insufficient stock.

Tests: covered in Task 14 feature tests.

Verify: `docker exec dental_app php artisan test` (smoke).

Commit: `feat(inventory): StockMovementService with transactional ops`

---

## Task 7: Extend RoleSeeder with inventory permissions

Files:
- `database/seeders/RoleSeeder.php` (update)

Steps:
1. Add 6 permissions to the `$permissions` array:
   `view-products`, `manage-products`, `view-suppliers`, `manage-suppliers`,
   `view-stock-movements`, `record-stock-movements`.
2. `admin` → `syncPermissions(Permission::all())` (already all).
3. `doctor` → add `view-products`, `view-suppliers`, `view-stock-movements`.
4. `receptionist` → add `view-products`, `view-suppliers`, `view-stock-movements`,
   `record-stock-movements`.

Tests: none (verified via `php artisan db:seed --class=RoleSeeder`).

Verify: `docker exec dental_app php artisan db:seed --class=RoleSeeder`.

Commit: `feat(inventory): permissions in RoleSeeder`

---

## Task 8: Repositories

Files:
- `app/Repositories/Contracts/ProductRepository.php` (new)
- `app/Repositories/Eloquent/ProductEloquentRepository.php` (new)
- `app/Repositories/Contracts/SupplierRepository.php` (new)
- `app/Repositories/Eloquent/SupplierEloquentRepository.php` (new)
- `app/Repositories/Contracts/StockLocationRepository.php` (new)
- `app/Repositories/Eloquent/StockLocationEloquentRepository.php` (new)
- `app/Repositories/Contracts/StockMovementRepository.php` (new)
- `app/Repositories/Eloquent/StockMovementEloquentRepository.php` (new)
- `app/Providers/AppServiceProvider.php` (update — add 4 binds)

Steps:
1. `ProductRepository`: `paginate($perPage, $filters)` with q/category/
   is_active/low_stock, `create($data)`, `update($p, $data)`, `delete($p)`,
   `search($term, $limit)` for typeahead, `lowStockQuery()` returns query,
   `lowStockCount()` returns int.
2. `SupplierRepository`: paginate with q/is_active, create, update, search.
3. `StockLocationRepository`: paginate, create, update, `activeList()` for
   dropdowns.
4. `StockMovementRepository`: `paginateForProduct($product, $perPage)`,
   `paginateForLocation($location, $perPage)`.
5. Register all 4 in `AppServiceProvider::register()`.

Tests: covered in Task 14.

Verify: `docker exec dental_app php artisan test --filter='ProductRepository'`.

Commit: `feat(inventory): repositories + service-provider bindings`

---

## Task 9: FormRequests for CRUD

Files:
- `app/Http/Requests/Inventory/StoreProductRequest.php` (new)
- `app/Http/Requests/Inventory/UpdateProductRequest.php` (new)
- `app/Http/Requests/Inventory/StoreSupplierRequest.php` (new)
- `app/Http/Requests/Inventory/UpdateSupplierRequest.php` (new)
- `app/Http/Requests/Inventory/StoreStockLocationRequest.php` (new)
- `app/Http/Requests/Inventory/UpdateStockLocationRequest.php` (new)

Steps:
1. All have `authorize()` returning `$this->user()->can('...')`.
2. StoreProductRequest rules: `sku` required unique, `name` required,
   `category` required in:enum, `unit` required in:enum, `cost_price`/
   `selling_price` nullable decimal:0,2, `reorder_level`/`reorder_quantity`
   integer min:0, `description` nullable, `is_active`/`track_stock` bool.
3. UpdateProductRequest: same but `sku` unique ignoring the current row.
4. StoreSupplierRequest: `name` required unique, contact fields nullable,
   `is_active` bool.
5. StoreStockLocationRequest: `name` required unique, `location_type`
   in:enum, `is_active` bool.

Tests: covered in Task 14.

Verify: `docker exec dental_app php artisan test` smoke.

Commit: `feat(inventory): CRUD FormRequests`

---

## Task 10: Stock-operation FormRequests

Files:
- `app/Http/Requests/Inventory/PurchaseStockRequest.php` (new)
- `app/Http/Requests/Inventory/ConsumeStockRequest.php` (new)
- `app/Http/Requests/Inventory/TransferStockRequest.php` (new)
- `app/Http/Requests/Inventory/AdjustStockRequest.php` (new)

Steps:
1. `PurchaseStockRequest`: `supplier_id` required exists, `location_id`
   required exists, `quantity` integer min:1, `unit_cost` nullable
   decimal:0,4, `reason` nullable string, `performed_at` nullable date.
2. `ConsumeStockRequest`: `location_id` required, `quantity` integer min:1,
   `reason` nullable, `patient_id` nullable exists (for traceability).
3. `TransferStockRequest`: `from_location_id`/`to_location_id` required
   different, `quantity` integer min:1, `notes` nullable.
4. `AdjustStockRequest`: `location_id` nullable (null = global), `quantity`
   integer min:0 (the new absolute value), `reason` required.

All `authorize()` check `record-stock-movements` (or `manage-products`
for adjust).

Tests: covered in Task 14.

Verify: `docker exec dental_app php artisan test` smoke.

Commit: `feat(inventory): stock-operation FormRequests`

---

## Task 11: ProductController CRUD + search

Files:
- `app/Http/Controllers/Inventory/ProductController.php` (new)

Steps:
1. `index()`: paginate with filters, render `Inventory/Products/Index`.
2. `create()` / `edit($p)`: render forms.
3. `store(StoreProductRequest)`: `ProductRepository::create`, redirect.
4. `update(UpdateProductRequest, $p)`: update, redirect.
5. `destroy($p)`: delete, redirect.
6. `show($p)`: eager-load `stockLevels.location`, `suppliers`,
   `movements.performer` (limit 20), compute `totalQuantity`. Render
   `Inventory/Products/Show`.
7. `movements($p)`: paginated movements render
   `Inventory/Products/Movements`.
8. `search(Request)`: JSON typeahead result, limit 10–50.

Tests: `tests/Feature/Inventory/CreateProductTest.php`,
`tests/Feature/Inventory/UpdateProductTest.php`,
`tests/Feature/Inventory/ProductSearchApiTest.php`,
`tests/Feature/Inventory/LowStockBadgeTest.php`.

Verify: `docker exec dental_app php artisan test --filter='Inventory'`.

Commit: `feat(inventory): ProductController CRUD + search`

---

## Task 12: SupplierController CRUD

Files:
- `app/Http/Controllers/Inventory/SupplierController.php` (new)

Steps:
1. `index()`: paginate, render `Inventory/Suppliers/Index`.
2. `create()` / `edit($s)`: render forms.
3. `store(StoreSupplierRequest)`: create, redirect.
4. `update(UpdateSupplierRequest, $s)`: update, redirect.
5. `show($s)`: eager-load `products`, render `Inventory/Suppliers/Show`.

Tests: `tests/Feature/Inventory/CreateSupplierTest.php`,
`tests/Feature/Inventory/UpdateSupplierTest.php`,
`tests/Feature/Inventory/ShowSupplierTest.php`.

Verify: as above.

Commit: `feat(inventory): SupplierController CRUD`

---

## Task 13: StockLocationController

Files:
- `app/Http/Controllers/Inventory/StockLocationController.php` (new)

Steps:
1. `index()`: paginate, eager-load `stockLevels` count, render
   `Inventory/Locations/Index`.
2. `create()` / `edit($l)`: render.
3. `store(StoreStockLocationRequest)`: create.
4. `update(UpdateStockLocationRequest, $l)`: update.

Tests: `tests/Feature/Inventory/CreateStockLocationTest.php`,
`tests/Feature/Inventory/UpdateStockLocationTest.php`.

Verify: as above.

Commit: `feat(inventory): StockLocationController`

---

## Task 14: Stock operations controllers

Files:
- `app/Http/Controllers/Inventory/StockOperationController.php` (new)
- `app/Http/Controllers/Inventory/LowStockController.php` (new)

Steps:
1. `purchase(Product, PurchaseStockRequest)`: `StockMovementService::purchase`.
2. `consume(Product, ConsumeStockRequest)`: re-read level inside tx, check qty.
3. `transfer(Product, TransferStockRequest)`: `StockMovementService::transfer`.
4. `adjust(Product, AdjustStockRequest)`: `StockMovementService::adjust`.
5. `LowStockController::index()`: paginate low products, render
   `Inventory/LowStock`.

Tests: `tests/Feature/Inventory/PurchaseStockTest.php`,
`tests/Feature/Inventory/ConsumeStockTest.php`,
`tests/Feature/Inventory/TransferStockTest.php`,
`tests/Feature/Inventory/AdjustStockTest.php`,
`tests/Feature/Inventory/LowStockListTest.php`.

Verify: `docker exec dental_app php artisan test --filter='Inventory'`.

Commit: `feat(inventory): stock operations + low-stock page`

---

## Task 15: Routes + wire into web.php

Files:
- `routes/inventory.php` (new)
- `routes/web.php` (update — add require)

Steps: define all routes from spec §4 in `routes/inventory.php`, then
add `require __DIR__.'/inventory.php';` in `web.php` under the
authenticated group.

Tests: implicit (covered above).

Verify: `docker exec dental_app php artisan route:list --path=inventory`.

Commit: `feat(inventory): routes/inventory.php + wire into web.php`

---

## Task 16: Update DashboardController for low-stock

Files:
- `app/Http/Controllers/DashboardController.php` (update)
- `app/Http/Controllers/DashboardController.php` (constructor inject `ProductRepository`)

Steps: `low_stock_items` value uses
`ProductRepository::lowStockCount()`.

Tests: `tests/Feature/Inventory/DashboardLowStockTest.php`.

Verify: as above.

Commit: `feat(inventory): dashboard low_stock_items wired to data`

---

## Task 17: Sidebar nav — Inventory group

Files:
- `resources/js/Composables/useNav.js` (update)
- `resources/js/Components/layout/Sidebar.vue` (update)
- `resources/js/Components/layout/SidebarItem.vue` (update — add children support)

Steps:
1. Replace the comingSoon Inventory with a real `permission: view-products`
   entry.
2. Render as a collapsible group: parent "Inventory" + 4 children
   (Products, Suppliers, Locations, Low Stock). Active when current URL
   starts with `/inventory/...`. Badge shows the low-stock count from
   the shared `dashboard` props.
3. Update `SidebarItem` to accept an optional `children` array and a
   `defaultOpen` prop. Render children indented below.

Tests: visual only.

Verify: `npm run dev` and `npx vite build`.

Commit: `feat(inventory): sidebar Inventory group with badge`

---

## Task 18: Vue pages — Products

Files:
- `resources/js/Pages/Inventory/Products/Index.vue` (new)
- `resources/js/Pages/Inventory/Products/Create.vue` (new)
- `resources/js/Pages/Inventory/Products/Edit.vue` (new)
- `resources/js/Pages/Inventory/Products/Show.vue` (new)
- `resources/js/Pages/Inventory/Products/Movements.vue` (new)

Steps:
- Index: filters (q, category, active, low_stock), DataTable, pagination.
- Create / Edit: UiForm with name, sku, barcode, category, unit, prices,
  reorder_level/quantity, description, switches.
- Show: header card + 2 panels: stock per location (with quick
  Purchase/Consume/Transfer/Adjust actions) and recent movements (top 20).
- Movements: paginated list of movements for the product.

Tests: visual only.

Verify: `npm run dev` (visual).

Commit: `feat(inventory): Vue pages for products`

---

## Task 19: Vue pages — Suppliers

Files:
- `resources/js/Pages/Inventory/Suppliers/Index.vue` (new)
- `resources/js/Pages/Inventory/Suppliers/Create.vue` (new)
- `resources/js/Pages/Inventory/Suppliers/Edit.vue` (new)
- `resources/js/Pages/Inventory/Suppliers/Show.vue` (new)

Steps: same shape as Products; Show lists products supplied.

Tests: visual only.

Commit: `feat(inventory): Vue pages for suppliers`

---

## Task 20: Vue pages — Locations + LowStock

Files:
- `resources/js/Pages/Inventory/Locations/Index.vue` (new)
- `resources/js/Pages/Inventory/LowStock.vue` (new)

Steps:
- Locations Index: list of locations with active toggle, edit button, total
  products per location.
- LowStock: list of low products, "Create purchase" link (placeholder
  href `#` for sub-project 6).

Tests: visual only.

Commit: `feat(inventory): Vue pages for locations + low-stock`

---

## Task 21: Feature tests

Files: as listed per task.

Steps: write the feature tests already referenced in Tasks 11–16; each
asserts a specific behavior (auth, validation, redirect, DB row, etc.).

Verify: `docker exec dental_app php artisan test --filter='Inventory'`.

Commit: `test(inventory): full feature test coverage`

---

## Task 22: Quality gates

Commands:
- `docker exec dental_app composer quality`
- `cd /mnt/c3c37cc3-52e9-4cf6-887a-7d8eab6bda76/Workspace/Molla/dental && npm test`
- `npx eslint resources/js`
- `npx vite build`

Final commit: `git commit --allow-empty -m "feat(inventory): sub-project 3 complete"`.

Commit: `chore(inventory): quality gates green`
