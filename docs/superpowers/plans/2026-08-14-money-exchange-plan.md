# Money Exchange — Implementation Plan

**Date:** 2026-08-14
**Spec:** `docs/superpowers/specs/2026-08-14-money-exchange-design.md`

## Conventions

- One commit per task.
- `docker exec dental_app php artisan <cmd>` for backend commands.
- After every 5-7 tasks: `composer quality`, `npm test`, `npm run lint`, `npx vite build`.
- Every FormRequest has `authorize()` + `rules()`.
- Every test asserts something specific.
- PHPStan level 6 — 0 errors.

---

## Phase 1 — Enums, Migrations, Factories

### Task 1 — `CashMovementType` enum
Commit: `feat(exchange): cash movement type enum`

### Task 2 — `currencies` migration
`code` as primary key, size 3, ISO 4217.
Commit: `feat(exchange): currencies migration`

### Task 3 — `exchange_rates` migration
Commit: `feat(exchange): exchange rates migration`

### Task 4 — `cash_drawers` migration
Commit: `feat(exchange): cash drawers migration`

### Task 5 — `cash_movements` migration + `exchange_group_id` (string 36) column
Commit: `feat(exchange): cash movements migration`

### Task 6 — Run migrations
Commit: `chore(exchange): run exchange migrations`

### Task 7 — Factories
`CurrencyFactory`, `ExchangeRateFactory`, `CashDrawerFactory`, `CashMovementFactory`.
Commit: `feat(exchange): exchange factories`

---

## Phase 2 — Models

### Task 8 — `Currency` model
Commit: `feat(exchange): Currency model`

### Task 9 — `ExchangeRate` model
Commit: `feat(exchange): ExchangeRate model`

### Task 10 — `CashDrawer` model
Commit: `feat(exchange): CashDrawer model`

### Task 11 — `CashMovement` model (LogsActivity, immutable, created_at only)
Commit: `feat(exchange): CashMovement model`

---

## Phase 3 — Repositories

### Task 12 — `CurrencyRepository`
Commit: `feat(exchange): CurrencyRepository`

### Task 13 — `ExchangeRateRepository`
Commit: `feat(exchange): ExchangeRateRepository`

### Task 14 — `CashDrawerRepository` (with `lockByCode`)
Commit: `feat(exchange): CashDrawerRepository`

### Task 15 — `CashMovementRepository`
Commit: `feat(exchange): CashMovementRepository`

### Task 16 — Bind repositories in `AppServiceProvider`
Commit: `feat(exchange): bind exchange repositories`

---

## Phase 4 — Services

### Task 17 — `ExchangeRateService`
Commit: `feat(exchange): ExchangeRateService`

### Task 18 — `CashDrawerService` (cash in/out/exchange/adjust)
Commit: `feat(exchange): CashDrawerService`

### Task 19 — `CashDrawerReconciliationService`
Commit: `feat(exchange): CashDrawerReconciliationService`

### Task 20 — Bind services in `AppServiceProvider`
Commit: `feat(exchange): bind exchange services`

---

## Phase 5 — Seeders & Permissions

### Task 21 — `CurrencySeeder` (9 ISO currencies)
Commit: `feat(exchange): CurrencySeeder`

### Task 22 — `ExchangeRateSeeder` (baseline cross rates as of today)
Commit: `feat(exchange): ExchangeRateSeeder`

### Task 23 — `CashDrawerSeeder` (idempotent, one drawer per active currency)
Commit: `feat(exchange): CashDrawerSeeder`

### Task 24 — Permissions in `RoleSeeder` + wire seeders in `DatabaseSeeder`
Commit: `feat(exchange): exchange permissions and seeders wiring`

### Task 25 — Run seeders
Commit: `chore(exchange): seed currencies, rates, and cash drawers`

### Task 26 — `php artisan test` checkpoint
Commit: `chore(exchange): post-seeder test run`

---

## Phase 6 — FormRequests & Controllers & Routes

### Task 27 — `StoreExchangeRateRequest` + `UpdateExchangeRateRequest`
Commit: `feat(exchange): exchange rate FormRequests`

### Task 28 — `StoreCashMovementRequest`
Commit: `feat(exchange): cash movement FormRequest`

### Task 29 — `ReconcileDrawerRequest`
Commit: `feat(exchange): reconcile drawer FormRequest`

### Task 30 — `DashboardController` update (cash_balance stat)
Commit: `feat(exchange): dashboard cash balance stat`

### Task 31 — `Exchange\DashboardController`
Commit: `feat(exchange): Exchange dashboard controller`

### Task 32 — `Exchange\ExchangeRateController` (CRUD)
Commit: `feat(exchange): ExchangeRateController`

### Task 33 — `Exchange\CashMovementController` (index, create, store)
Commit: `feat(exchange): CashMovementController`

### Task 34 — `Exchange\ReconciliationController` (index, run)
Commit: `feat(exchange): ReconciliationController`

### Task 35 — `routes/exchange.php` + wire into `routes/web.php`
Commit: `feat(exchange): routes file and web wiring`

### Task 36 — Integrate `CashDrawerService` with `PaymentService`
Commit: `feat(exchange): wire cash drawer into PaymentService`

### Task 37 — `php artisan route:list` checkpoint
Commit: `chore(exchange): route list verified`

---

## Phase 7 — Vue Pages

### Task 38 — `useNav` update (add Exchange group, replace comingSoon)
Commit: `feat(exchange): useNav sidebar group`

### Task 39 — `Exchange/Dashboard.vue` (Stat cards, trend placeholder, currency table)
Commit: `feat(exchange): Dashboard Vue page`

### Task 40 — `Exchange/Rates/Index.vue` (DataTable, create/edit inline)
Commit: `feat(exchange): Rates Index Vue page`

### Task 41 — `Exchange/Movements/Index.vue` (paginated history with filters)
Commit: `feat(exchange): Movements Index Vue page`

### Task 42 — `Exchange/Movements/Create.vue` (form, type-driven fields, exchange calculator)
Commit: `feat(exchange): Movements Create Vue page`

### Task 43 — `Exchange/Reconcile.vue` (count vs expected, submit)
Commit: `feat(exchange): Reconcile Vue page`

### Task 44 — `npx vite build` + `npm run lint` checkpoint
Commit: `chore(exchange): vite build clean`

---

## Phase 8 — Tests

### Task 45 — `ExchangeRateServiceTest`
Commit: `test(exchange): ExchangeRateService tests`

### Task 46 — `CashDrawerServiceTest`
Commit: `test(exchange): CashDrawerService tests`

### Task 47 — `CashDrawerReconciliationTest`
Commit: `test(exchange): CashDrawerReconciliationService tests`

### Task 48 — `ExchangeRateControllerTest`
Commit: `test(exchange): ExchangeRateController tests`

### Task 49 — `CashMovementControllerTest`
Commit: `test(exchange): CashMovementController tests`

### Task 50 — `ReconciliationControllerTest`
Commit: `test(exchange): ReconciliationController tests`

### Task 51 — `PaymentCashDrawerIntegrationTest` (cash payment creates movement)
Commit: `test(exchange): payment cash drawer integration`

### Task 52 — `ExchangeReconcileCashVsLedgerCommandTest`
Commit: `test(exchange): reconcile-cash-vs-ledger command test`

### Task 53 — Frontend smoke test for `useNav`
Commit: `test(exchange): useNav sidebar items`

### Task 54 — `composer quality` + `npm test` + `npm run lint` + `npx vite build`
Commit: `chore(exchange): all quality gates green`

### Task 55 — Final commit: sub-project 5 complete
Commit: `feat(exchange): sub-project 5 complete` (empty if all committed)
