# Money Exchange — Design Spec

**Date:** 2026-08-14
**Sub-project:** 5 (Money Exchange)
**Stack:** Laravel 13 + Inertia 3 + Vue 3 (plain JS) + PrimeVue 4 + Tailwind v4
**Base currency:** BDT (per `ClinicSettings`)

---

## 1. Goals

Provide multi-currency cash management for the clinic:

1. Maintain a **currency catalogue** (ISO 4217) used across the app.
2. Track **daily exchange rates** with full audit history.
3. Maintain a per-currency **cash drawer** that records every physical movement of cash.
4. Support **cash exchanges** between currencies atomically.
5. Support **manual cash in/out** with required reason.
6. Provide a **reconciliation** flow that pins the drawer balance to a physical count.
7. Show a **dashboard** summarising drawer value (converted to base currency).
8. Run a **daily artisan command** that compares physical drawer vs ledger (1000 Cash) and reports mismatches.

Out of scope for this sub-project:
- External rate fetching (admin-managed only; future hook).
- Per-location cash drawers (single clinic-wide drawer per currency).

---

## 2. Data Model

### 2.1 `currencies`

ISO 4217 currency catalogue.

| Column      | Type                  | Notes                          |
|-------------|-----------------------|--------------------------------|
| `code`      | `string(3)` UNIQUE    | Primary key, e.g. `USD`         |
| `name`      | `string(80)`          | e.g. `US Dollar`                |
| `symbol`    | `string(8)`           | e.g. `$`                        |
| `decimals`  | `unsignedTinyInteger` | default 2                       |
| `is_active` | `boolean`             | default true                    |
| timestamps  |                       |                                |

`code` is the primary key (no `id` column) so it is portable across systems and matches ISO 4217.

### 2.2 `exchange_rates`

| Column                | Type                | Notes                                          |
|-----------------------|---------------------|------------------------------------------------|
| `id`                  | bigint PK           |                                                |
| `from_currency_code`  | `string(3)` FK      |                                                |
| `to_currency_code`    | `string(3)` FK      |                                                |
| `rate`                | `decimal(18,8)`     | multiplier: 1 unit from = `rate` units to      |
| `effective_at`        | `datetime` (index)  |                                                |
| `created_by`          | `?int` FK users     |                                                |
| `created_at`          | datetime            |                                                |
| `updated_at`          | datetime            |                                                |

Unique: `(from_currency_code, to_currency_code, effective_at)`.

Lookup for a rate uses `effective_at <= now()`, ordered DESC, take first.

### 2.3 `cash_drawers`

One drawer per currency. Per-clinic (single clinic today; design leaves room for multi-tenant later).

| Column                | Type                | Notes                                        |
|-----------------------|---------------------|----------------------------------------------|
| `id`                  | bigint PK           |                                              |
| `currency_code`       | `string(3)` UNIQUE  | FK `currencies.code`                         |
| `balance`             | `decimal(18,4)`     | default 0; running sum of movements          |
| `last_reconciled_at`  | `?datetime`         |                                              |
| `last_reconciled_balance` | `?decimal(18,4)` | snapshot of balance at reconciliation        |
| `created_at`          | datetime            |                                              |
| `updated_at`          | datetime            |                                              |

### 2.4 `cash_movements`

Immutable audit log. `created_at` only (no updates).

| Column                  | Type                | Notes                                       |
|-------------------------|---------------------|---------------------------------------------|
| `id`                    | bigint PK           |                                             |
| `currency_code`         | `string(3)` FK      |                                             |
| `type`                  | enum                | cash_in, cash_out, exchange_in, exchange_out, adjustment, sale, expense, refund |
| `amount`                | `decimal(18,4)`     | signed: positive = in, negative = out       |
| `exchange_rate_id`      | `?int` FK           | only for exchange_in/exchange_out           |
| `counter_currency_code` | `?string(3)` FK     | only for exchange ops                       |
| `counter_amount`        | `?decimal(18,4)`    | only for exchange ops                       |
| `reason`                | `?string(255)`      | required for cash_in/cash_out/adjustment    |
| `reference_type`        | `?string` morph     | nullable                                    |
| `reference_id`          | `?bigint`           | nullable                                    |
| `performed_by`          | `int` FK users      |                                             |
| `created_at`            | datetime            | immutable                                   |

Indexes: `(currency_code, created_at)`, `(reference_type, reference_id)`.

---

## 3. Enums

### 3.1 `CashMovementType`

`cash_in`, `cash_out`, `exchange_in`, `exchange_out`, `adjustment`, `sale`, `expense`, `refund`

Each has `label()` and `color()` (PrimeVue severity).

---

## 4. Services

### 4.1 `ExchangeRateService`

- `getRate(string $from, string $to, ?Carbon $at = null): ExchangeRate`
  - If `from === to` returns a synthetic rate of 1.0 (no DB row).
  - Throws if no rate exists.
- `getRateOrNull(...)`: same but returns null instead of throwing.
- `setRate(string $from, string $to, string $rate, Carbon $effectiveAt, int $userId): ExchangeRate`
  - `updateOrCreate` on `(from, to, effective_at)`.
- `latestRates(): array<string, array<string, string>>` — for UI.

### 4.2 `CashDrawerService`

The single entry point for cash-movement writes. Every public method is a `DB::transaction`.

- `cashIn(string $code, string $amount, string $reason, ?Model $reference, int $userId): CashMovement`
  - Rejects if `amount <= 0` or `reason` empty.
  - Locks drawer row, increments balance, writes movement with `type=cash_in`.
- `cashOut(string $code, string $amount, string $reason, ?Model $reference, int $userId): CashMovement`
  - Same as `cashIn` but `type=cash_out` and `amount = -amount`. Refuses if balance would go negative.
- `exchange(string $from, string $to, string $amount, ?ExchangeRate $rateOverride, int $userId): array{0: CashMovement, 1: CashMovement}`
  - Looks up rate; if `from === to` throws.
  - Computes `counter = amount * rate`.
  - Locks both drawers in a deterministic order (lexical) to avoid deadlocks.
  - Writes `exchange_out` for `from` (negative) and `exchange_in` for `to` (positive), each with `counter_*` and `exchange_rate_id`.
  - Both reference a `ReferencePair` (we use the `exchange_out` row as the morph reference and store the `exchange_in` id inside `reason` json? — simpler: a shared `exchange_group_id` string on both rows so the audit trail is linked without inventing a model).
  - Returns both movements.
- `adjust(string $code, string $delta, string $reason, int $userId): CashMovement`
  - For variance on reconciliation; can be positive or negative delta.

### 4.3 `CashDrawerReconciliationService`

- `computeFor(string $code): array`
  - Returns drawer balance, last 7 days of in/out totals, latest count of movements.
- `run(string $code, string $countedBalance, int $userId): CashMovement`
  - If `countedBalance == drawer.balance` → just stamp `last_reconciled_at`.
  - Else create `adjust` movement for the variance and stamp.

### 4.4 `ExchangeReconcileCashVsLedger` (artisan command)

`exchange:reconcile-cash-vs-ledger`

- For each currency, sum its `cash_movements.amount` (in that currency).
- Convert to base currency at the latest rate (or 1.0 if base).
- Compare to sum of `ledger_entries` for `Account 1000` converted to base.
- Print a per-currency table with a status (`match` / `drift`).

---

## 5. Controllers / Routes

`routes/exchange.php` (loaded from `routes/web.php` inside the `auth + active` group):

| Method | URI                              | Permission                  | Name                       |
|--------|----------------------------------|-----------------------------|----------------------------|
| GET    | `/exchange`                      | `view-exchange-rates`       | `exchange.dashboard`       |
| GET    | `/exchange/rates`                | `view-exchange-rates`       | `exchange.rates.index`     |
| POST   | `/exchange/rates`                | `manage-exchange-rates`     | `exchange.rates.store`     |
| GET    | `/exchange/rates/{rate}/edit`    | `manage-exchange-rates`     | `exchange.rates.edit`      |
| PATCH  | `/exchange/rates/{rate}`         | `manage-exchange-rates`     | `exchange.rates.update`    |
| DELETE | `/exchange/rates/{rate}`         | `manage-exchange-rates`     | `exchange.rates.destroy`   |
| GET    | `/exchange/movements`            | `view-cash-movements`       | `exchange.movements.index` |
| GET    | `/exchange/movements/create`     | `record-cash-movements`     | `exchange.movements.create`|
| POST   | `/exchange/movements`            | `record-cash-movements`     | `exchange.movements.store` |
| GET    | `/exchange/reconcile`            | `reconcile-cash-drawer`     | `exchange.reconcile.index` |
| POST   | `/exchange/reconcile/run`        | `reconcile-cash-drawer`     | `exchange.reconcile.run`   |

### Controllers

- `Exchange\DashboardController@index`
- `Exchange\ExchangeRateController` (index, create, store, edit, update, destroy)
- `Exchange\CashMovementController` (index, create, store)
- `Exchange\ReconciliationController` (index, run)

---

## 6. FormRequests

- `StoreExchangeRateRequest` (`manage-exchange-rates`): `from`, `to` (size 3, different, exist), `rate` (numeric, > 0), `effective_at` (date, required).
- `UpdateExchangeRateRequest`: same but `sometimes` rules.
- `StoreCashMovementRequest` (`record-cash-movements`):
  - `type` (enum), `currency_code`, `amount` (> 0), `reason` (required when type in cash_in/cash_out/adjustment, string max 255),
  - exchange ops require `to_currency_code`, `counter_amount` (computed on server).
- `ReconcileDrawerRequest` (`reconcile-cash-drawer`): `currency_code`, `counted_balance` (numeric).

---

## 7. UI (Vue 3 + Inertia + PrimeVue + UI wrappers)

All new Vue pages live under `resources/js/Pages/Exchange/`.

### Pages
- `Exchange/Dashboard.vue` — Stat cards per currency, 7-day trend, KPI for total value in base.
- `Exchange/Rates/Index.vue` — DataTable with latest rate per pair + "Add rate" button.
- `Exchange/Rates/FormFields.vue` — shared form fields.
- `Exchange/Movements/Index.vue` — DataTable with filters (type, currency, date range, search).
- `Exchange/Movements/Create.vue` — Type picker (radio) → form changes.
  - When type = `exchange_in` or `exchange_out` show currency pair picker + computed counter.
- `Exchange/Reconcile.vue` — Currency picker, displays expected vs input, posts run.

### Sidebar

`useNav.js`: replace the `comingSoon` Exchange entry with a real group containing the four children above, with `Reconcile` gated on `reconcile-cash-drawer`.

### Components used from `@/Components/ui`
`Stat`, `DataTable`, `Card`, `Button`, `Input`, `Select`, `DatePicker`, `Badge`, `EmptyState`, `Alert`, `Form` family (`FormField`, `FormLabel`, `FormError`), `Tabs`, `Breadcrumbs`.

---

## 8. Permissions

Added to `RoleSeeder`:
- `manage-exchange-rates` (admin)
- `view-exchange-rates` (admin, doctor, receptionist)
- `record-cash-movements` (admin, receptionist)
- `view-cash-movements` (admin, doctor, receptionist)
- `reconcile-cash-drawer` (admin)

---

## 9. Dashboard update

`DashboardController` adds a `cash_balance` stat: `sum(cash_drawers.balance * latest rate to base)`. Returned as a string (decimal).

---

## 10. Seeders

### 10.1 `CurrencySeeder`
Idempotent. Seeds 9 currencies listed in the spec.

### 10.2 `ExchangeRateSeeder` (optional)
Seeds a baseline set of rates as of "today" so the UI shows non-empty data:
- USD→BDT: 110.0
- EUR→BDT: 120.0
- GBP→BDT: 140.0
- INR→BDT: 1.32
- AUD→BDT: 72.0
- CAD→BDT: 81.0
- JPY→BDT: 0.74
- BDT→USD: 0.00909091 (so 1 / 110)
- BDT→EUR: 0.00833333
- (and all 9×9 pair base rates using the matrix above)

For simplicity in v1, we seed the **8 cross rates from BDT** and let the UI fetch from the API for other directions using inversion; the rate service is responsible for inversion if no direct rate exists. We also add the inverse rates to keep symmetric.

### 10.3 `CashDrawerSeeder`
Idempotent. Creates a drawer for every active currency, balance 0.

### 10.4 `DatabaseSeeder` updates
Wire the three seeders in.

---

## 11. Repositories

- `CurrencyRepository` (list, find by code).
- `ExchangeRateRepository` (paginate, latest, find by pair at time, create/update/delete).
- `CashMovementRepository` (paginate with filters, find by id, sum by currency since date).
- `CashDrawerRepository` (find by code with lock, all, update balance).

---

## 12. Integration with Billing

- `PaymentService::record` now also calls `CashDrawerService::cashIn` when `PaymentMethod::Cash` and the payment currency has an active drawer.
- The existing ledger entry is unchanged.
- A new `CashMovement` row is created inside the same transaction; failure rolls back both the payment and the drawer update.
- For invoice paid in foreign currency, the payment is recorded in the payer's currency; `Payment.currency_code` + `Payment.exchange_rate` are already supported.

---

## 13. Tests

### Backend (PHPUnit)
- `CashDrawerServiceTest`: cash in/out, exchange (both directions), exchange same-currency throws, exchange inserts two rows, exchange rate override, exchange without rate throws, balance cannot go negative, adjust positive and negative, all in transactions.
- `ExchangeRateServiceTest`: getRate (direct, same, missing throws), getRateOrNull, setRate idempotent, latestRates.
- `CashMovementReconciliationTest`: reconcile matching, reconcile with variance creates adjust movement.
- `ExchangeRateControllerTest`, `CashMovementControllerTest`, `ReconciliationControllerTest`, `DashboardControllerTest` (cash_balance stat).
- `PaymentServiceTest`: extended to assert a `cash_movement` row is created for cash payments.
- `ExchangeReconcileCashVsLedgerCommandTest`: command matches a synthetic scenario.

### Frontend (Vitest)
- `useNav` filters the new items correctly when permissions are absent.
- `formatMoney` + new helpers (covered by existing tests; reused).
- `ExchangeDashboard.test.js`: renders the stat cards.
- Smoke test that `Create` form posts to the correct route.

---

## 14. Quality Gates

- `composer quality` (pint + phpstan level 6 + tests) — 0 errors
- `npm test`
- `npm run lint` (0 errors)
- `npx vite build`

---

## 15. File Layout (new files only)

```
app/
  Enums/CashMovementType.php
  Models/Currency.php
  Models/ExchangeRate.php
  Models/CashDrawer.php
  Models/CashMovement.php
  Repositories/Contracts/{Currency,ExchangeRate,CashMovement,CashDrawer}Repository.php
  Repositories/Eloquent/{Currency,ExchangeRate,CashMovement,CashDrawer}EloquentRepository.php
  Services/{ExchangeRateService,CashDrawerService,CashDrawerReconciliationService}.php
  Http/Controllers/Exchange/{DashboardController,ExchangeRateController,CashMovementController,ReconciliationController}.php
  Http/Requests/Exchange/{StoreExchangeRateRequest,UpdateExchangeRateRequest,StoreCashMovementRequest,ReconcileDrawerRequest}.php
  Console/Commands/ExchangeReconcileCashVsLedger.php
database/
  migrations/2026_08_14_000023_create_currencies_table.php
  migrations/2026_08_14_000024_create_exchange_rates_table.php
  migrations/2026_08_14_000025_create_cash_drawers_table.php
  migrations/2026_08_14_000026_create_cash_movements_table.php
  factories/{Currency,ExchangeRate,CashDrawer,CashMovement}Factory.php
  seeders/{CurrencySeeder,ExchangeRateSeeder,CashDrawerSeeder}.php
routes/
  exchange.php
resources/js/Pages/Exchange/
  Dashboard.vue
  Rates/Index.vue
  Movements/Index.vue
  Movements/Create.vue
  Reconcile.vue
tests/
  Feature/Exchange/{ExchangeRateServiceTest,CashDrawerServiceTest,CashDrawerReconciliationTest,ExchangeRateControllerTest,CashMovementControllerTest,ReconciliationControllerTest,ExchangeReconcileCashVsLedgerCommandTest,PaymentCashDrawerIntegrationTest}.php
  Unit/Js/Exchange/ExchangeDashboard.test.js
```

---

## 16. Deviations & Open Questions

- `currencies.code` chosen as primary key. Acceptable in SQLite/MySQL.
- No multi-tenant drawer (single clinic assumed).
- `exchange_group_id` is a string column on `cash_movements` to pair the two rows of an exchange. (Added during planning; a separate `Exchange` model would over-engineer the audit trail.)
- Rate inversion: the service uses direct lookup first, then tries inverted pair, then throws. (A single `1.0` synthetic rate when `from === to`.)
- The artisan command prints to stdout; no DB writes.
