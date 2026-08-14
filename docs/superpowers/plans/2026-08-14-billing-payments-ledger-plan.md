# Billing, Payments & Ledger — Implementation Plan

**Date:** 2026-08-14
**Spec:** `docs/superpowers/specs/2026-08-14-billing-payments-ledger-design.md`

## Conventions

- One commit per task.
- `docker exec dental_app php artisan <cmd>` for backend commands.
- After every 5-7 tasks: `composer quality`, `npm test`, `npm run lint`, `npx vite build`.
- Every FormRequest has `authorize()` + `rules()`.
- Every test asserts something specific.
- PHPStan level 6 — 0 errors.

---

## Phase 1 — Enums & Migrations (foundation)

### Task 1 — Enums
Add `InvoiceStatus`, `DiscountType`, `PaymentMethod`, `ExpenseStatus`, `AccountType` enums.
Commit: `feat(billing): core enums (invoice/payment/expense/account)`

### Task 2 — Accounts migration
Create `accounts` table.
Commit: `feat(billing): accounts migration`

### Task 3 — Ledger entries migration
Create `ledger_entries` table.
Commit: `feat(billing): ledger_entries migration`

### Task 4 — Invoices migrations
Create `invoices` + `invoice_items` tables.
Commit: `feat(billing): invoices + invoice_items migrations`

### Task 5 — Payments migration
Create `payments` table.
Commit: `feat(billing): payments migration`

### Task 6 — Expenses migrations
Create `expense_categories` + `expenses` tables.
Commit: `feat(billing): expense categories + expenses migrations`

### Task 7 — Run migrations + commit
`docker exec dental_app php artisan migrate`
Commit: `chore(billing): run new migrations`

---

## Phase 2 — Models

### Task 8 — Account + LedgerEntry models
Commit: `feat(billing): Account + LedgerEntry models`

### Task 9 — Invoice + InvoiceItem models
Commit: `feat(billing): Invoice + InvoiceItem models`

### Task 10 — Payment model
Commit: `feat(billing): Payment model`

### Task 11 — ExpenseCategory + Expense models
Commit: `feat(billing): ExpenseCategory + Expense models`

---

## Phase 3 — Repositories

### Task 12 — AccountRepository (contract + eloquent)
Commit: `feat(billing): AccountRepository`

### Task 13 — InvoiceRepository
Commit: `feat(billing): InvoiceRepository`

### Task 14 — PaymentRepository
Commit: `feat(billing): PaymentRepository`

### Task 15 — ExpenseCategoryRepository + ExpenseRepository
Commit: `feat(billing): ExpenseCategoryRepository + ExpenseRepository`

---

## Phase 4 — Seeders & Permissions

### Task 16 — ChartOfAccountsSeeder
Commit: `feat(billing): ChartOfAccountsSeeder`

### Task 17 — ExpenseCategorySeeder
Commit: `feat(billing): ExpenseCategorySeeder`

### Task 18 — Permissions in RoleSeeder
Add new permissions and role assignments.
Commit: `feat(billing): billing permissions in RoleSeeder`

### Task 19 — DatabaseSeeder wires new seeders
Commit: `chore(billing): wire new seeders into DatabaseSeeder`

### Task 20 — Run seeders + commit
`docker exec dental_app php artisan db:seed --class=ChartOfAccountsSeeder` (and others)
Commit: `chore(billing): seed chart of accounts + categories`

---

## Phase 5 — Service Layer (ledger-first)

### Task 21 — LedgerService
`recordEntry` + `recordInvoiceIssue` + `recordPaymentReceived` + `recordExpenseApproved`.
Commit: `feat(billing): LedgerService (recordEntry + helpers)`

### Task 22 — InvoiceService (totals + numbering)
Commit: `feat(billing): InvoiceService (totals + auto-numbering)`

### Task 23 — PaymentService
Commit: `feat(billing): PaymentService (record + recompute + ledger)`

### Task 24 — ExpenseService
Commit: `feat(billing): ExpenseService (approve + ledger)`

### Task 25 — Service provider bindings
Register repositories + services.
Commit: `feat(billing): bind billing repositories + services`

---

## Phase 6 — Form Requests

### Task 26 — Account requests
`StoreAccountRequest`, `UpdateAccountRequest`.
Commit: `feat(billing): account FormRequests`

### Task 27 — Invoice requests
`StoreInvoiceRequest`, `UpdateInvoiceRequest`.
Commit: `feat(billing): invoice FormRequests`

### Task 28 — Payment + Expense requests
`RecordPaymentRequest`, `StoreExpenseRequest`, `UpdateExpenseRequest`, `ApproveExpenseRequest`.
Commit: `feat(billing): payment + expense FormRequests`

---

## Phase 7 — Backend Tests (early)

### Task 29 — LedgerService test
Commit: `test(billing): LedgerService tests`

### Task 30 — InvoiceService test
Commit: `test(billing): InvoiceService tests (numbering, totals)`

### Task 31 — PaymentService + ExpenseService tests
Commit: `test(billing): PaymentService + ExpenseService tests`

### Task 32 — Quality gate #1
Run `composer quality`, `npm test`, `npm run lint`, `npx vite build`.

---

## Phase 8 — Controllers

### Task 33 — AccountController
Commit: `feat(billing): AccountController (CRUD)`

### Task 34 — InvoiceController
Commit: `feat(billing): InvoiceController (CRUD + issue + cancel)`

### Task 35 — PaymentController
Commit: `feat(billing): PaymentController (record + receipt)`

### Task 36 — ExpenseController
Commit: `feat(billing): ExpenseController (CRUD + approve/reject)`

### Task 37 — Routes (billing.php)
Commit: `feat(billing): routes/billing.php`

### Task 38 — Wire routes in web.php
Commit: `feat(billing): wire billing routes`

### Task 39 — Console command `invoices:mark-overdue`
Commit: `feat(billing): invoices:mark-overdue command`

### Task 40 — Schedule overdue command
Register in routes/console.php (already runs in `withSchedule` callback).
Commit: `chore(billing): schedule invoices:mark-overdue`

---

## Phase 9 — Reports

### Task 41 — ReportsController (ledger + income + BS + AR)
Commit: `feat(billing): ReportsController (4 actions)`

### Task 42 — Reports routes + wire
Commit: `feat(billing): reports routes`

### Task 43 — Reports tests
Commit: `test(billing): reports controller tests`

---

## Phase 10 — Dashboard wiring

### Task 44 — DashboardController real values
Commit: `feat(billing): wire DashboardController real values`

---

## Phase 11 — Feature tests for controllers

### Task 45 — Invoice controller tests
Commit: `test(billing): InvoiceController feature tests`

### Task 46 — Payment + Expense + Account controller tests
Commit: `test(billing): Payment/Expense/Account controller tests`

### Task 47 — Quality gate #2
Run all quality gates.

---

## Phase 12 — PDF

### Task 48 — Install laravel-dompdf
Commit: `feat(billing): install barryvdh/laravel-dompdf`

### Task 49 — Invoice PDF template + action
Commit: `feat(billing): invoice PDF download`

### Task 50 — Receipt PDF template + action
Commit: `feat(billing): payment receipt PDF`

---

## Phase 13 — Frontend

### Task 51 — Sidebar updates (useNav.js)
Replace "Billing" placeholder, add Reports top-level.
Commit: `feat(billing): sidebar wiring (Billing group + Reports)`

### Task 52 — Invoice Vue pages (Index, Create, Edit, Show)
Commit: `feat(billing): Invoice Vue pages`

### Task 53 — Payment Vue pages (Index, Receipt)
Commit: `feat(billing): Payment Vue pages`

### Task 54 — Expense Vue pages (Index, Create, Edit, Show)
Commit: `feat(billing): Expense Vue pages`

### Task 55 — Account Vue pages (Index, Create, Edit, Show)
Commit: `feat(billing): Account Vue pages`

### Task 56 — Reports Vue pages (4 pages)
Commit: `feat(billing): Reports Vue pages`

---

## Phase 14 — Final

### Task 57 — Quality gate #3 (final)
Run all quality gates; fix any remaining issues.

### Task 58 — Final commit
Empty commit: `feat(billing-ledger): sub-project 4 complete` (only if any uncommitted remains).
