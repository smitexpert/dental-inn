# Billing, Payments & Ledger — Design Spec

**Date:** 2026-08-14
**Project:** dental (Laravel 13 + Inertia 3 + Vue 3 + PrimeVue 4 unstyled + Tailwind v4)
**Sub-project:** 4 (largest)

## 1. Goal

Add a complete back-office billing & accounting stack to the dental clinic app:

- **A. Ledger (double-entry)** — every money movement posts a balanced debit/credit pair to `LedgerEntry`. Chart of accounts seeded. Immutability is enforced: there is no `updated_at` and no edit API.
- **B. Invoicing** — `Invoice` + `InvoiceItem`, statuses, automatic numbering, line items, totals, PDF download, daily overdue sweep.
- **C. Payments** — `Payment` records with receipt PDF; auto-update invoice `paid_amount` and status, post ledger entry.
- **D. Expenses** — `ExpenseCategory` + `Expense`; receptionist creates, admin approves; approval posts ledger entry.
- **E. Reports + Dashboard** — Income statement, balance sheet, A/R aging, ledger list; Dashboard tile values are wired to real data.

## 2. Domain model

### 2.1 Ledger (new)

```
accounts
  id, code (unique), name, type[asset|liability|equity|revenue|expense], parent_id (nullable), is_active
  created_at, updated_at

ledger_entries (immutable — only created_at; no updated_at, no deletes)
  id, entry_date (date), description (string), debit_account_id (FK accounts),
  credit_account_id (FK accounts), amount (decimal 18,4), currency_code (string 3),
  exchange_rate (decimal 18,8 default 1.0), reference_type (nullable morph),
  reference_id (nullable bigint), created_by (FK users), created_at
```

Seeded chart (idempotent via firstOrCreate on `code`):

| code | name                  | type     |
|-----:|-----------------------|----------|
| 1000 | Cash                  | asset    |
| 1010 | Bank                  | asset    |
| 1100 | Accounts Receivable   | asset    |
| 1200 | Inventory Asset       | asset    |
| 2000 | Accounts Payable      | liability|
| 2100 | Tax Payable           | liability|
| 3000 | Owner's Equity        | equity   |
| 3100 | Retained Earnings     | equity   |
| 4000 | Service Revenue       | revenue  |
| 4100 | Product Sales         | revenue  |
| 4900 | Other Income          | revenue  |
| 5000 | Cost of Goods Sold    | expense  |
| 6000 | Rent                  | expense  |
| 6100 | Utilities             | expense  |
| 6200 | Salaries              | expense  |
| 6300 | Supplies              | expense  |
| 6900 | Other Expenses        | expense  |

### 2.2 Invoicing (new)

```
invoices (soft deletes)
  id, invoice_number (unique), patient_id (nullable FK), appointment_id (nullable FK),
  treatment_record_id (nullable FK), invoice_date (date), due_date (date),
  status enum[draft|issued|partially_paid|paid|overdue|cancelled|refunded],
  subtotal decimal(18,2), tax_rate decimal(5,2) default 0,
  tax_amount decimal(18,2) default 0,
  discount_type enum[none|percentage|fixed] default none,
  discount_value decimal(18,2) default 0, discount_amount decimal(18,2) default 0,
  total decimal(18,2), paid_amount decimal(18,2) default 0,
  notes (nullable text), created_by (FK users), issued_at (nullable datetime),
  created_at, updated_at, deleted_at

invoice_items
  id, invoice_id (FK), description, quantity decimal(10,2), unit_price decimal(18,2),
  line_total decimal(18,2), product_id (nullable FK), treatment_record_id (nullable FK),
  sort_order (int default 0), created_at, updated_at
```

`total = subtotal - discount_amount + tax_amount` (recomputed on every save).
Numbering: `ClinicSettings.invoice_prefix + zero-padded(next_invoice_number)` inside `DB::transaction` with row lock on the settings row, then `next_invoice_number++`.

### 2.3 Payments (new)

```
payments (immutable — only created_at; no updated_at, no deletes)
  id, invoice_id (FK), payment_date (date), amount decimal(18,2),
  currency_code (string 3), exchange_rate decimal(18,8) default 1.0,
  method enum[cash|card|bank_transfer|mobile_wallet|other],
  reference (nullable string), notes (nullable text), received_by (FK users), created_at
```

On `recordPaymentReceived`:
1. Insert payment.
2. Increment invoice.paid_amount.
3. Recompute status (paid_amount >= total → paid, 0 < paid < total → partially_paid).
4. Post ledger: `Dr Cash (1000) / Bank (1010)` (based on `method`), `Cr Accounts Receivable (1100)`.

### 2.4 Expenses (new)

```
expense_categories
  id, name (unique), account_id (FK accounts), is_active, created_at, updated_at

expenses
  id, expense_date (date), category_id (FK), amount decimal(18,2),
  currency_code (string 3), exchange_rate decimal(18,8) default 1.0,
  payment_method enum[cash|card|bank_transfer|mobile_wallet|other],
  vendor (nullable string), description (nullable text),
  receipt_path (nullable string),  # medialibrary collection 'receipt'
  status enum[pending|approved|rejected] default pending,
  created_by (FK users), approved_by (nullable FK users),
  approved_at (nullable datetime), created_at, updated_at
```

On approval:
- Post ledger: `Dr <category.account> (6000/6100/6200/6300/6900)`, `Cr Cash/Bank` (based on payment_method).

Seed: Rent→6000, Utilities→6100, Salaries→6200, Supplies→6300, Other→6900.

### 2.5 Permissions (extend RoleSeeder)

New permissions:
- `manage-invoices` — admin, receptionist
- `view-invoices` — admin, doctor, receptionist
- `manage-payments` — admin, receptionist
- `view-payments` — admin, doctor, receptionist
- `manage-expenses` — admin, receptionist
- `view-expenses` — admin, doctor, receptionist
- `approve-expenses` — admin only
- `view-reports` — admin, doctor
- `manage-chart-of-accounts` — admin only

## 3. Architecture

### 3.1 Layers

- **Enums** — `InvoiceStatus`, `DiscountType`, `PaymentMethod`, `ExpenseStatus`, `AccountType`.
- **Models** — `Account`, `LedgerEntry`, `Invoice`, `InvoiceItem`, `Payment`, `ExpenseCategory`, `Expense`. PHPDoc `@property` for every relation.
- **Repositories** — `AccountRepository`, `InvoiceRepository`, `PaymentRepository`, `ExpenseCategoryRepository`, `ExpenseRepository`. Eloquent implementations.
- **Services** — `LedgerService` (single entry-point for money movement), `InvoiceService` (totals + numbering), `PaymentService` (record + recompute + ledger), `ExpenseService` (approve + ledger).
- **FormRequests** — One per (controller-action, model): `StoreInvoiceRequest`, `UpdateInvoiceRequest`, `RecordPaymentRequest`, `StoreExpenseRequest`, `ApproveExpenseRequest`, `StoreAccountRequest`, `UpdateAccountRequest`. All have `authorize()` checking permissions.
- **Controllers** — `InvoiceController`, `PaymentController` (with receipt action), `ExpenseController`, `AccountController`, `ReportsController`. Inertia renders + redirect responses.
- **Console Command** — `invoices:mark-overdue` (idempotent sweep).

### 3.2 Routes

- `routes/billing.php` — all invoice/payment/expense/account routes (mounted in `routes/web.php`).
- `routes/reports.php` — reports (mounted in `routes/web.php`).

### 3.3 Ledger invariant

Every money-affecting service method calls `LedgerService::recordEntry(...)` inside the same `DB::transaction`. There is no way to record a payment or approve an expense without the ledger write.

The service exposes:

```
LedgerService::recordEntry(
  Carbon $entryDate, string $description, int $debitAccountId, int $creditAccountId,
  string $amount, string $currencyCode, string $exchangeRate,
  ?Model $reference = null, ?int $createdBy = null
): LedgerEntry

LedgerService::recordInvoiceIssue(Invoice $invoice, ?int $userId = null): void
LedgerService::recordPaymentReceived(Payment $payment, string $cashAccountCode = '1000', ?string $bankAccountCode = '1010'): void
LedgerService::recordExpenseApproved(Expense $expense): void
```

## 4. Frontend (Vue 3 + Inertia + PrimeVue 4 unstyled + Tailwind v4)

### 4.1 Pages

- `Billing/Invoices/Index.vue` — list + filters (status, patient, date range).
- `Billing/Invoices/Create.vue` — invoice + line items child component.
- `Billing/Invoices/Edit.vue` — only for drafts.
- `Billing/Invoices/Show.vue` — invoice detail with line items, payment history, totals, action buttons.
- `Billing/Payments/Index.vue` — list of all payments.
- `Billing/Payments/Receipt.vue` — print-friendly view (route `/payments/{payment}/receipt`).
- `Billing/Expenses/Index.vue` — list + filters (status, category, date).
- `Billing/Expenses/Create.vue` — submit form with receipt upload.
- `Billing/Expenses/Edit.vue` — only for pending.
- `Billing/Expenses/Show.vue` — detail with approval action (admin).
- `Billing/Accounts/Index.vue` — chart of accounts list.
- `Billing/Accounts/Create.vue` + `Edit.vue` + `Show.vue`.
- `Reports/Ledger.vue`, `Reports/IncomeStatement.vue`, `Reports/BalanceSheet.vue`, `Reports/AccountsReceivable.vue`.

Shared components:
- `InvoiceLineItems.vue` — child for invoice line item editing.
- `ExpenseStatusBadge.vue`, `InvoiceStatusBadge.vue` — colored badges.
- `Money.vue` — format with currency.

### 4.2 Sidebar

`useNav.js`:
- Replace `Billing` `comingSoon` placeholder with nested group: Invoices, Payments, Expenses, Chart of Accounts.
- Add `Reports` top-level (admin/doctor): Ledger, Income Statement, Balance Sheet, A/R Aging.

## 5. PDF

- Install `barryvdh/laravel-dompdf` via composer.
- Templates:
  - `resources/views/pdf/invoice.blade.php` — uses `ClinicSettings` for header.
  - `resources/views/pdf/receipt.blade.php` — receipt for a payment.

## 6. Quality gates

- PHPStan level 6 — 0 errors (extend ignoreErrors if needed for ledger morphTo relations).
- Pint clean.
- PHPUnit (PHPUnit, not Pest) — every new service has feature coverage.
- `npx vite build` — no errors.
- `npm run lint` — 0 errors.
- `npm test` — vitest passes (UI tests for components).

## 7. Out of scope

- Multi-currency conversion (exchange_rate captured but no auto-translation).
- Refunds as separate flow (status `refunded` exists; no separate table).
- Tax breakdown by tax rate (single rate per invoice).
- Recurring invoices.
- Email-sending of invoices.
