# Bank & Cash — Design Spec

**Date:** 2026-08-15
**Sub-project:** 5 (formerly Money Exchange) — re-scoped to **Bank & Cash**
**Stack:** Laravel 13 + Inertia 3 + Vue 3 (plain JS) + daisyUI 4 + Tailwind v4
**Base currency:** BDT (per `ClinicSettings::currency_code`)
**Status:** Approved (Sections 1–3); this document completes Sections 4–7.

---

## 1. Goals

Replace the multi-currency cash-drawer + exchange-rate system with a single
**Bank & Cash** ledger per clinic:

1. A unified catalogue of **bank & cash accounts** (cash, bank, external).
2. **Transfers** between accounts that record money movement atomically.
3. A single **`cash_movements` log** (one row per effect) that backs every account.
4. **Reconciliation** of each account against a physical count.
5. **Dashboard stat** that sums the balance of cash + bank accounts.
6. **No FX, no exchange rates.** Currency is a single setting in `clinic` settings.

Out of scope:
- Multi-currency / FX. The clinic operates in a single currency.
- Multi-tenant (single clinic).
- Per-location accounts (all accounts are clinic-wide).
- The legacy `ExchangeReconcileCashVsLedger` artisan command (its purpose is
  subsumed by the per-account reconciliation; the drawer→ledger comparison
  no longer makes sense).

---

## 2. Data Model

### 2.1 `bank_accounts` (NEW)

Replaces both `cash_drawers` and the per-currency bookkeeping that
`currencies` enabled. Naming: **`BankAccount`** is chosen to avoid colliding
with the existing `App\Models\Account` chart-of-accounts ledger model
(`App\Enums\AccountType`) — see Deviations §7.1.

| Column                    | Type              | Notes                                                                                 |
|---------------------------|-------------------|---------------------------------------------------------------------------------------|
| `id`                      | bigint PK         |                                                                                       |
| `name`                    | `string(120)`     | e.g. "Main Cash", "Dutch-Bangla Bank Operating"                                       |
| `type`                    | enum              | `cash` / `bank` / `external`                                                          |
| `bank_name`               | `?string(120)`    | bank accounts only                                                                    |
| `account_name`            | `?string(120)`    | bank accounts only                                                                    |
| `branch`                  | `?string(120)`    | bank accounts only                                                                    |
| `account_number`          | `?string(64)`     | bank accounts only                                                                    |
| `currency_code`           | `string(3)`       | defaulted from `ClinicSettings::currency_code`; UI hides after v1                     |
| `opening_balance`         | `decimal(18,4)`   | snapshot at creation; immutable                                                      |
| `balance`                 | `decimal(18,4)`   | running total, never set directly except via the reconciliation path                 |
| `is_system`               | `boolean`         | true for the seeded `Main Cash` and `External Adjustment` accounts                    |
| `is_active`               | `boolean`         | soft-deactivate to hide from new-movement pickers                                     |
| `last_reconciled_at`      | `?datetime`       |                                                                                       |
| `last_reconciled_balance` | `?decimal(18,4)`  |                                                                                       |
| `created_by`              | `int` FK users    |                                                                                       |
| `created_at` / `updated_at` | timestamps     |                                                                                       |

Unique: `(bank_name, account_number)` — nulls treated as distinct so cash/external accounts
may omit the tuple (MySQL `unique` already permits multiple NULLs in a composite; SQLite
also allows it).

### 2.2 `transfers` (NEW)

| Column             | Type            | Notes                                              |
|--------------------|-----------------|----------------------------------------------------|
| `id`               | bigint PK       |                                                    |
| `from_account_id`  | FK bank_accounts|                                                    |
| `to_account_id`    | FK bank_accounts| different from `from_account_id`                   |
| `amount`           | `decimal(18,4)` | > 0                                                |
| `occurred_at`      | `datetime`      | user-supplied (defaults to now)                    |
| `reference`        | `?string(120)`  |                                                    |
| `note`             | `?string(255)`  |                                                    |
| `transfer_group_id`| `string(36)` UNIQUE | UUID linking the two `cash_movements` rows     |
| `performed_by`     | FK users        |                                                    |
| timestamps         |                 |                                                    |

### 2.3 `cash_movements` (REBUILT, same table name)

| Column              | Type             | Notes                                                                  |
|---------------------|------------------|------------------------------------------------------------------------|
| `id`                | bigint PK        |                                                                        |
| `account_id`        | FK bank_accounts | replaces `currency_code`                                               |
| `type`              | enum             | `cash_in`, `cash_out`, `transfer_in`, `transfer_out`, `adjustment`, `sale`, `expense`, `refund` |
| `amount`            | `decimal(18,4)`  | signed: positive = in, negative = out                                  |
| `counter_account_id`| `?int` FK        | only for `transfer_in` / `transfer_out`                                |
| `counter_amount`    | `?decimal(18,4)` | only for `transfer_in` / `transfer_out` (always equal to `amount`)     |
| `transfer_group_id` | `?string(36)`    | only for `transfer_in` / `transfer_out`; matches the parent transfer   |
| `reason`            | `?string(255)`   | required for `cash_in` / `cash_out` / `adjustment`                     |
| `reference_type`    | `?string` morph  |                                                                        |
| `reference_id`      | `?bigint`        |                                                                        |
| `performed_by`      | FK users         |                                                                        |
| `created_at`        | datetime         | immutable                                                              |

Dropped columns: `currency_code`, `exchange_rate_id`, `counter_currency_code`,
`exchange_group_id`.

Indexes: `(account_id, created_at)`, `(transfer_group_id)`, `(reference_type, reference_id)`.

### 2.4 Dropped tables

`currencies`, `exchange_rates`, `cash_drawers` — drop in the migration. Hard cut;
no backfill (no FX, no historical rates to preserve).

### 2.5 Single migration

`2026_08_15_000001_rebuild_exchange_as_bank_accounts.php`:

1. `down()` is a no-op (hard cut, no rollback path).
2. Drop `cash_drawers`, `exchange_rates`, `currencies` (in FK order).
3. Create `bank_accounts`, `transfers`.
4. `cash_movements` is altered in place: drop old columns, add new ones.
5. No data migration. The dev DB is empty for these tables.

---

## 3. Enums

### 3.1 `App\Enums\BankAccountType` (NEW)

`cash`, `bank`, `external`. Each has `label()`. `external` accounts sit outside
the clinic's physical cash & bank — they are used as offset accounts for
reconciliation variance (see §7.9) and to hold money held by third parties
(contractor float, owner capital, etc.). The existing ledger's Account 1000/1010
mapping is unchanged — see §6.4.

### 3.2 `App\Enums\CashMovementType` (UPDATED)

- Drop `ExchangeIn`, `ExchangeOut`.
- Add `TransferIn`, `TransferOut`.
- Keep `CashIn`, `CashOut`, `Adjustment`, `Sale`, `Expense`, `Refund`.

Sign convention is unchanged (positive for inflows, negative for outflows).
`label()`, `color()` and `isInflow()`/`isOutflow()` updated to handle the new cases.
`isExchange()` is removed.

---

## 4. UI (Vue 3 + Inertia + daisyUI)

All new pages live under `resources/js/Pages/Bank/`. The sidebar entry in
`useNav.js` is updated to point at the new namespace. Form conventions
(`<UiForm>`, `<UiField>`, `<UiInput>`, `<UiSelect>`, `<UiFormActions>`,
`<Head title="…">`) match the existing `Billing/Accounts/*` pages.

### 4.1 Page tree

```
resources/js/Pages/Bank/
  Dashboard.vue               # KPIs + per-account table
  Accounts/
    Index.vue                 # list, search, filter by type, deactivate
    Create.vue                # create form (incl. opening balance + bank fields)
    Edit.vue                  # edit form (deactivate / reactivate)
  Transfers/
    Index.vue                 # list, filter by date range / from / to
    Create.vue                # from + to pickers, amount, occurred_at, reference, note
    Show.vue                  # the two linked cash_movements rows
  Reconcile.vue               # pick account, expected vs counted, submit
```

### 4.2 Sidebar — `resources/js/Composables/useNav.js`

Replace the existing `Exchange` group with a `Bank & Cash` group:

```js
{
  label: 'Bank & Cash',
  href: '/bank',
  icon: 'wallet',
  permission: 'view-accounts',
  children: [
    { label: 'Dashboard',  href: '/bank',                 permission: 'view-accounts' },
    { label: 'Accounts',   href: '/bank/accounts',        permission: 'view-accounts' },
    { label: 'Transfers',  href: '/bank/transfers',       permission: 'view-transfers' },
    { label: 'Reconcile',  href: '/bank/reconcile',       permission: 'reconcile-accounts' },
  ],
}
```

The `NavIcon` registry already maps `wallet`. The `useNav.js` filter logic
(permission gating) needs no changes.

### 4.3 Pages — detail

#### `Bank/Dashboard.vue`

- Head title: "Bank & Cash".
- Three `<UiStat>` tiles: **Total cash + bank balance** (sum of `accounts.balance`
  where `type ∈ {cash, bank}`), **Number of active accounts**, **Reconciled today**.
- `<UiCard title="Accounts">` table: name, type (Badge), balance, last reconciled.
- Buttons in the header: "New transfer", "New account".
- Empty state: "No accounts yet. Run `php artisan db:seed --class=SystemAccountsSeeder`".

#### `Bank/Accounts/Index.vue`

- Head title: "Bank accounts".
- Search box (`q`) and type filter (`cash | bank | external | all`).
- Table: name, type, balance, last reconciled, status, actions (Edit, Deactivate).
- Pagination.
- "New account" button → `/bank/accounts/create`.

#### `Bank/Accounts/Create.vue`

- Head title: "New account".
- Fields:
  - `name` — required, string, max 120.
  - `type` — required, select: cash / bank / external.
  - Bank-only (shown via `v-if="form.type === 'bank'"`):
    - `bank_name` — required.
    - `account_name` — required.
    - `branch` — optional.
    - `account_number` — required; unique with `bank_name` (server validates).
  - `currency_code` — read-only display, set from `ClinicSettings`.
  - `opening_balance` — required, numeric, ≥ 0, two decimals.
- Validation messages mirror the FormRequest rules (see §5).
- On submit → `POST /bank/accounts`. On success → flash + redirect to index.

#### `Bank/Accounts/Edit.vue`

- Head title: "Edit account".
- Same fields as Create, except:
  - `opening_balance` is read-only (it's an immutable opening; corrections go
    through an `adjustment` movement — see Deviations §7.4).
  - `is_active` checkbox.
- Submit → `PATCH /bank/accounts/{account}`.

#### `Bank/Transfers/Index.vue`

- Head title: "Transfers".
- Filters: date range (`date_from`, `date_to`), from-account, to-account, free-text.
- Table: occurred_at, from, to, amount, reference, performer.
- "New transfer" → `/bank/transfers/create`.

#### `Bank/Transfers/Create.vue`

- Head title: "New transfer".
- Fields:
  - `from_account_id` — required, select (active cash + bank only).
  - `to_account_id` — required, select, must differ.
  - `amount` — required, numeric, > 0.
  - `occurred_at` — required, datetime (default now).
  - `reference` — optional.
  - `note` — optional.
- On submit → `POST /bank/transfers`.

#### `Bank/Transfers/Show.vue`

- Head title: "Transfer {id}".
- Shows the two linked `cash_movements` rows side-by-side:
  - Source row (`transfer_out`, negative amount, account = from).
  - Destination row (`transfer_in`, positive amount, account = to).
- Same `transfer_group_id` shown for both.
- Read-only — no edit / delete (immutability is enforced server-side too).

#### `Bank/Reconcile.vue`

- Head title: "Reconcile account".
- Step 1: pick an account (active cash + bank only).
- Step 2: form pre-fills expected balance, user types counted balance (numeric, ≥ 0).
- On submit → `POST /bank/reconcile/run`.
- Success: shows `last_reconciled_balance` and (when applicable) the adjustment
  movement created.

### 4.4 Breadcrumbs / page-title conventions

`Head title="…"` is the only meta surface — there is no breadcrumb component
in the app today (verified: `Breadcrumbs.vue` is exported in `@/Components/ui`
but no page uses it). We follow the existing `Billing/Accounts/*` pattern: a
single `<Head title>` + the page header inside `<AuthenticatedLayout>`.

---

## 5. Tests (PHPUnit + Vitest)

### 5.1 Backend

| File | Scenarios |
|------|-----------|
| `tests/Feature/Bank/AccountServiceTest.php` | Create account: persists opening_balance as balance, type=cash/bank/external happy paths, opening_balance=0 ok, opening_balance<0 throws, bank-only fields required when type=bank, bank-only fields rejected when type=cash, unique (bank_name, account_number) violated throws, system accounts can't be deactivated (`is_system=true` keeps `is_active=true`). |
| `tests/Feature/Bank/TransferServiceTest.php` | Cash-to-bank, bank-to-cash, bank-to-bank all succeed; bank-to-bank with insufficient balance throws; cash-to-cash with insufficient balance throws; same source/destination throws; amount<=0 throws; two `cash_movements` rows written with same `transfer_group_id`; balances updated atomically (rollback on failure leaves no partial rows); locks taken in lexical `id` order. |
| `tests/Feature/Bank/ReconciliationServiceTest.php` | Run with matching count: stamps `last_reconciled_*`, no movement row written. Run with positive variance (counted > expected): one transfer pair (source = account, destination = External Adjustment), account balance goes up. Run with negative variance (counted < expected): one transfer pair (source = External Adjustment, destination = account), account balance goes down. Already-reconciled today re-stamps `last_reconciled_at`. |
| `tests/Feature/Bank/AccountControllerTest.php` | Index lists + filters; create validates FormRequest; edit updates; deactivate flips `is_active`. |
| `tests/Feature/Bank/TransferControllerTest.php` | Create posts to `bank.transfers.store`; show renders both movements; permissions enforced. |
| `tests/Feature/Bank/ReconciliationControllerTest.php` | Index lists active accounts; run submits; reconcile-accounts permission enforced. |
| `tests/Feature/Billing/PaymentServiceTest.php` (extend) | Cash payment writes a `cash_movement` of type `sale` against the **Cash** bank account (NOT a per-currency drawer). Amount equals payment amount. Reason is "Cash payment for invoice {n}". Reference morph is the Payment. Foreign-currency payment still creates the movement (amount is the payment's amount, not converted — single-currency world). |
| `tests/Feature/DashboardControllerTest.php` (extend) | `cash_balance` stat equals the sum of `balance` for active cash+bank accounts. No FX / exchange-rate interaction. |

### 5.2 Test files deleted

- `tests/Feature/Exchange/CashDrawerServiceTest.php`
- `tests/Feature/Exchange/CashDrawerReconciliationTest.php`
- `tests/Feature/Exchange/CashMovementControllerTest.php`
- `tests/Feature/Exchange/ExchangeRateControllerTest.php`
- `tests/Feature/Exchange/ExchangeRateServiceTest.php`
- `tests/Feature/Exchange/ReconciliationControllerTest.php`
- `tests/Feature/Exchange/ExchangeReconcileCashVsLedgerCommandTest.php`

### 5.3 Frontend (Vitest)

| File | Scenarios |
|------|-----------|
| `tests/Js/Composables/useNav.spec.js` (extend) | New `Bank & Cash` group renders for users with `view-accounts`; child items filtered correctly when `record-transfers` / `reconcile-accounts` are absent. |
| `tests/Js/Pages/Bank/CreateAccount.spec.js` | Smoke: form posts to `/bank/accounts` with the correct payload and shows server errors. |
| `tests/Js/Pages/Bank/CreateTransfer.spec.js` | Smoke: form posts to `/bank/transfers` with the correct payload; same source/destination is rejected client-side. |

---

## 6. Quality gates, integration points, file layout

### 6.1 Quality gates

- `composer quality` — runs Pint (style), PHPStan level 6 (static analysis), and
  the PHPUnit suite. Must be 0 errors.
- `npm run lint` — 0 errors.
- `npm test` — Vitest passes.
- `npx vite build` — production bundle succeeds.

### 6.2 Routes — `routes/bank.php`

| Method | URI                              | Permission                | Name                       |
|--------|----------------------------------|---------------------------|----------------------------|
| GET    | `/bank`                          | `view-accounts`           | `bank.dashboard`           |
| GET    | `/bank/accounts`                 | `view-accounts`           | `bank.accounts.index`      |
| GET    | `/bank/accounts/create`          | `manage-accounts`         | `bank.accounts.create`     |
| POST   | `/bank/accounts`                 | `manage-accounts`         | `bank.accounts.store`      |
| GET    | `/bank/accounts/{account}/edit`  | `manage-accounts`         | `bank.accounts.edit`       |
| PATCH  | `/bank/accounts/{account}`       | `manage-accounts`         | `bank.accounts.update`     |
| GET    | `/bank/transfers`                | `view-transfers`          | `bank.transfers.index`     |
| GET    | `/bank/transfers/create`         | `record-transfers`        | `bank.transfers.create`    |
| POST   | `/bank/transfers`                | `record-transfers`        | `bank.transfers.store`     |
| GET    | `/bank/transfers/{transfer}`     | `view-transfers`          | `bank.transfers.show`      |
| GET    | `/bank/reconcile`                | `reconcile-accounts`      | `bank.reconcile.index`     |
| POST   | `/bank/reconcile/run`            | `reconcile-accounts`      | `bank.reconcile.run`       |

`routes/web.php` updates `require __DIR__.'/exchange.php';` → `require __DIR__.'/bank.php';`.

### 6.3 File layout (new / modified / removed)

**New**
```
app/
  Enums/BankAccountType.php
  Models/BankAccount.php
  Models/Transfer.php
  Repositories/Contracts/BankAccountRepository.php
  Repositories/Contracts/TransferRepository.php
  Repositories/Eloquent/BankAccountEloquentRepository.php
  Repositories/Eloquent/TransferEloquentRepository.php
  Services/BankAccountService.php
  Services/TransferService.php
  Services/ReconciliationService.php              # replaces CashDrawerReconciliationService
  Http/Controllers/Bank/
    DashboardController.php
    AccountController.php
    TransferController.php
    ReconciliationController.php
  Http/Requests/Bank/
    StoreBankAccountRequest.php
    UpdateBankAccountRequest.php
    StoreTransferRequest.php
    ReconcileAccountRequest.php
database/
  migrations/2026_08_15_000001_rebuild_exchange_as_bank_accounts.php
  factories/BankAccountFactory.php
  factories/TransferFactory.php
  seeders/SystemAccountsSeeder.php
routes/
  bank.php
resources/js/Pages/Bank/
  Dashboard.vue
  Accounts/Index.vue
  Accounts/Create.vue
  Accounts/Edit.vue
  Transfers/Index.vue
  Transfers/Create.vue
  Transfers/Show.vue
  Reconcile.vue
tests/
  Feature/Bank/AccountServiceTest.php
  Feature/Bank/TransferServiceTest.php
  Feature/Bank/ReconciliationServiceTest.php
  Feature/Bank/AccountControllerTest.php
  Feature/Bank/TransferControllerTest.php
  Feature/Bank/ReconciliationControllerTest.php
```

**Modified**
```
app/Models/CashMovement.php                    # currency_code -> account_id; drop exchange columns
app/Enums/CashMovementType.php                 # drop ExchangeIn/Out, add TransferIn/Out
app/Repositories/Contracts/CashMovementRepository.php
app/Repositories/Eloquent/CashMovementEloquentRepository.php
app/Services/PaymentService.php                # cash payment -> CashMovement via TransferService pattern
app/Http/Controllers/DashboardController.php   # cash_balance stat sums accounts of type cash+bank
app/Http/Controllers/Billing/PaymentController.php  # default currency_code/exchange_rate to clinic values
app/Http/Controllers/Billing/ExpenseController.php  # default currency_code/exchange_rate to clinic values
app/Providers/AppServiceProvider.php           # bind new repos; drop currency/drawer/exchange bindings; singleton new services
app/Models/Expense.php                         # currency_code still column; default at write site
app/Models/Payment.php                         # currency_code still column; default at write site
app/Models/LedgerEntry.php                     # currency_code still column; default at write site
database/factories/CashMovementFactory.php     # account_id instead of currency_code
database/factories/ExpenseFactory.php          # keep currency_code default BDT
database/seeders/DatabaseSeeder.php            # drop CurrencySeeder, ExchangeRateSeeder, CashDrawerSeeder; add SystemAccountsSeeder
database/seeders/RoleSeeder.php                # replace exchange permissions with bank permissions
resources/js/Composables/useNav.js             # replace Exchange group with Bank & Cash group
routes/web.php                                 # require bank.php instead of exchange.php
tests/Feature/Billing/PaymentServiceTest.php   # assert CashMovement against Cash account
tests/Feature/DashboardControllerTest.php      # assert cash_balance = sum of accounts.balance
```

**Removed**
```
app/Models/Currency.php
app/Models/ExchangeRate.php
app/Models/CashDrawer.php
app/Repositories/Contracts/CurrencyRepository.php
app/Repositories/Contracts/ExchangeRateRepository.php
app/Repositories/Contracts/CashDrawerRepository.php
app/Repositories/Eloquent/CurrencyEloquentRepository.php
app/Repositories/Eloquent/ExchangeRateEloquentRepository.php
app/Repositories/Eloquent/CashDrawerEloquentRepository.php
app/Services/ExchangeRateService.php
app/Services/CashDrawerService.php
app/Http/Controllers/Exchange/DashboardController.php
app/Http/Controllers/Exchange/ExchangeRateController.php
app/Http/Controllers/Exchange/CashMovementController.php
app/Http/Controllers/Exchange/ReconciliationController.php
app/Http/Requests/Exchange/StoreExchangeRateRequest.php
app/Http/Requests/Exchange/UpdateExchangeRateRequest.php
app/Http/Requests/Exchange/StoreCashMovementRequest.php
app/Http/Requests/Exchange/ReconcileDrawerRequest.php
app/Console/Commands/ExchangeReconcileCashVsLedger.php
database/factories/CurrencyFactory.php
database/factories/ExchangeRateFactory.php
database/factories/CashDrawerFactory.php
database/seeders/CurrencySeeder.php
database/seeders/ExchangeRateSeeder.php
database/seeders/CashDrawerSeeder.php
database/migrations/2026_08_14_000023_create_currencies_table.php
database/migrations/2026_08_14_000024_create_exchange_rates_table.php
database/migrations/2026_08_14_000025_create_cash_drawers_table.php
routes/exchange.php
resources/js/Pages/Exchange/Dashboard.vue
resources/js/Pages/Exchange/Reconcile.vue
resources/js/Pages/Exchange/Movements/Index.vue
resources/js/Pages/Exchange/Movements/Create.vue
resources/js/Pages/Exchange/Movements/Show.vue
resources/js/Pages/Exchange/Rates/Index.vue
tests/Feature/Exchange/                          # whole directory
```

### 6.4 Integration points

**PaymentService** — Today the cash branch calls `CashDrawerService::cashIn`
against `payment->currency_code`, lazily creating a drawer per payment
currency. New behaviour:

- `PaymentService::record` keeps the existing `LedgerService::recordPaymentReceived`
  call (unchanged — `LedgerEntry` still records in `payment->currency_code`,
  which we default to `ClinicSettings::currency_code` upstream).
- If `payment->method === Cash`, the service delegates to a new
  `BankAccountService::recordMovement(cashBankAccount, CashMovementType::Sale,
  amount, reason, payment)` helper that locks the account, writes one
  immutable `cash_movement` row, and bumps `accounts.balance`. The call
  happens inside the existing outer `DB::transaction`. The `cash_movement`'s
  `reference_type` is `'payment'`, `reference_id` is `$payment->id`, and
  `reason` is `"Cash payment for invoice {n}"`.
- The `cashBankAccount` is the **first active** `BankAccount` of type `cash`
  (the seeded `Main Cash`). If none exists (e.g. the seeder wasn't run), the
  service throws a clear `RuntimeException`. We do **not** auto-create the
  Cash account at write time — the seeder is the source of truth.

**DashboardController** — `cash_balance` becomes:

```php
$balance = (float) BankAccount::query()
    ->where('is_active', true)
    ->whereIn('type', [BankAccountType::Cash->value, BankAccountType::Bank->value])
    ->sum('balance');
```

The hint string still reads `sprintf('Total in %s', ClinicSettings::currency_code)` —
no FX, no rates.

**Billing (Payment / Expense controllers + Request classes)** — Today
`RecordPaymentRequest` and `StoreExpenseRequest` require `currency_code` and
`exchange_rate` from the user. New behaviour: those fields are auto-filled
with `ClinicSettings::currency_code` / `1.0` if absent, removed from the form,
and the corresponding columns on `payments` / `expenses` are kept for schema
stability but always store the clinic's currency. (No migration is needed —
the columns are already on the tables.)

**SystemAccountsSeeder** — Idempotently seeds two accounts:

- `Main Cash` (type `cash`, `is_system = true`, `balance = 0`,
  `opening_balance = 0`).
- `External Adjustment` (type `external`, `is_system = true`,
  `balance = 0`, `opening_balance = 0`).

These are the defaults; users add their own bank accounts and may rename or
add additional cash drawers (one is enough for v1, but the schema allows
many).

**LedgerService** — Untouched. Account 1000/1010 in the chart of accounts
still backs the `LedgerEntry` rows. The new `bank_accounts` table is the
**physical-money** ledger; the chart of accounts is the **accounting**
ledger. Both will agree on balance for the system "Main Cash" bank account,
because every `cash_movement` against that account is also mirrored by a
ledger entry (the existing wiring already does this for payments and
expenses). Transfers between bank_accounts write two `cash_movements` rows
but **do not** write ledger entries — a transfer is an internal reclassification
of physical money, not an income/expense event.

**AppServiceProvider** — Drop bindings for the deleted repos/services.
Add bindings:

```php
$this->app->bind(BankAccountRepository::class, BankAccountEloquentRepository::class);
$this->app->bind(TransferRepository::class,    TransferEloquentRepository::class);

$this->app->singleton(BankAccountService::class);
$this->app->singleton(TransferService::class);
$this->app->singleton(ReconciliationService::class);
```

### 6.5 ReconciliationService behaviour

The old `CashDrawerReconciliationService::run` writes a placeholder zero
amount movement when `counted == expected` (because `adjust(0)` throws) and
an `adjustment` row when there's variance. The new `ReconciliationService`:

1. Locks the bank_account row.
2. Compares `counted` to `balance`.
3. If they match: stamps `last_reconciled_at` + `last_reconciled_balance`,
   writes **no** movement row. The audit trail is the column update alone.
4. If they don't match: writes a **transfer pair** between the account and
   the seeded `External Adjustment` account, with the difference as the
   amount. This is preferable to a free-floating `adjustment` movement
   because the books still balance — every dollar of discrepancy is
   attributed to the External account (which the operator can later
   investigate or transfer out manually). See §7.9.

Either way, the operation is wrapped in a single `DB::transaction` and
locks the source account (and the External account, when transferring)
in lexical id order.

---

## 7. Deviations, open questions, follow-ups

### 7.1 Naming conflict: `Account` (chart-of-accounts) vs new "bank account"

`App\Models\Account` already exists for the **chart-of-accounts ledger**
(`AccountType` enum: asset / liability / equity / revenue / expense). Adding
a second `Account` model would break the autoloader and confuse every reader.
We picked **`BankAccount`** (table `bank_accounts`) to keep the two concepts
visually separate. The namespace is `App\Models\BankAccount` and the repo
contract is `App\Repositories\Contracts\BankAccountRepository`. Follow-up:
in a future rename pass we could rename the chart-of-accounts model to
`ChartOfAccount` / `LedgerAccount` and reclaim the bare `Account` name; for
v1 the disambiguation is enough.

### 7.2 `currency_code` and `exchange_rate` columns outside the Exchange slice

The `payments`, `expenses`, and `ledger_entries` tables still carry
`currency_code` and `exchange_rate` columns. The approved scope is "hard
cut on Exchange data" — we drop the `currencies` / `exchange_rates` tables
and the per-currency drawer bookkeeping, but **keep the columns** for
schema stability. They are now always written as `ClinicSettings::currency_code`
+ `1.0` at the controller layer. A future migration could drop these columns
once the data is confirmed clean.

### 7.3 The artisan command `exchange:reconcile-cash-vs-ledger`

Dropped. Its job was to compare `cash_drawers.balance` to the ledger's
"1000 Cash" account and report drift. With no drawers, the comparison is
pointless — the new `Bank/Dashboard.vue` reads the running balance directly
from `bank_accounts.balance`, and reconciliation is per-account.

### 7.4 Editing an opening balance

The design's `opening_balance` is immutable. If the user wants to fix a
typo, they must run reconciliation on the account — the variance between
the (incorrect) opening_balance-as-balance and the actual counted balance
is recorded as a transfer between the account and the `External Adjustment`
account (see §7.9). This is by design — keeping `opening_balance` immutable
lets the audit trail reconstruct the original intent. Follow-up: surface a
small UI affordance in `Bank/Accounts/Edit.vue` that links to the
reconciliation page when an account has had activity.

### 7.5 `transfer_group_id` is also on `cash_movements`

The design puts the UUID on both `transfers` and `cash_movements`. The
`transfers.transfer_group_id` is the authoritative copy; the
`cash_movements.transfer_group_id` is a denormalised convenience for
queries like "show me both legs of transfer X". Both are indexed; both are
unique within their table.

### 7.6 Soft-deactivation semantics

`is_active = false` excludes an account from the transfer pickers and the
dashboard total. The account's historical movements and balance are
preserved. **Deactivated accounts reject new `cash_movement` writes** (the
service throws). This matches the existing chart-of-accounts
deactivation behaviour.

### 7.7 Tests that touch legacy models

- `tests/Feature/Exchange/*` — entire directory removed.
- `tests/Feature/Billing/PaymentServiceTest.php`, `PaymentControllerTest.php`,
  `ExpenseControllerTest.php`, `ExpenseServiceTest.php`,
  `LedgerServiceTest.php` — keep, but the `currency_code` / `exchange_rate`
  inputs are removed from request payloads and asserted as `BDT` / `1.0`.

### 7.8 Open questions for the user

- **Currencies on legacy data rows**: do we want to backfill existing
  `payments.currency_code` and `expenses.currency_code` to `BDT` if they
  currently hold other values? (Today's dev DB is empty; this matters only
  if a populated DB ever runs this migration.) Default behaviour assumed:
  no backfill — the controller writes clinic-currency going forward, and a
  separate data-quality task audits existing rows.
- **Currency display in receipts**: today `ClinicSettings` exposes
  `currency_code`, `currency_symbol`, `currency_decimals`, `currency_position`.
  All four remain; nothing changes for receipts / invoice rendering.

### 7.9 `External` account type — what is it for?

`External` accounts hold money that exists outside the clinic's cash and
bank accounts (e.g. petty cash held by a contractor, or "owner capital"
injected into the business). The seeded `External Adjustment` account is
the offset used by the reconciliation path: a counted surplus/deficit on
a cash account is recorded as a transfer between that account and the
`External Adjustment` account, instead of a free-floating adjustment
movement. This keeps every balance change traceable to a movement whose
sums net to zero across the system. See §6.4 and §6.5.

If this is heavier than the user wants for v1, follow-up: drop the
`transfer_to_external` semantics and keep the simple adjustment movement
that the old service used.
