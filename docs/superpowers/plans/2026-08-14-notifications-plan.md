# Notifications — Implementation Plan

**Date:** 2026-08-14
**Sub-project:** 6 (Notifications) — FINAL
**Base spec:** `docs/superpowers/specs/2026-08-14-notifications-design.md`

This plan executes the spec task-by-task, with one commit per task. Quality
gates (`composer quality`, `npm test`, `npm run lint`, `npx vite build`) run
after every 5 tasks. Final commit lands the milestone marker.

## Conventions

- Migrations: `YYYY_MM_DD_HHMMSS_<desc>.php` under `database/migrations/` (DB)
  or `database/settings/` (settings).
- Models live in `app/Models/`, services in `app/Services/`, enums in `app/Enums/`.
- Controllers grouped under `app/Http/Controllers/Notifications/`.
- Vue pages under `resources/js/Pages/Notifications/`.
- Routes in `routes/notifications.php`, included from `routes/web.php`.
- Plain JS only, JSDoc `@typedef` for IDE hints.
- Each FormRequest has `rules()` + `authorize()`.
- Each test asserts something specific — no empty test bodies.

---

## Task 1 — Foundation: contracts, config, driver skeletons

- `app/Contracts/NotificationDriver.php` interface
- `app/Services/Notifications/LogDriver.php`
- `app/Services/Notifications/HttpDriver.php`
- `app/Services/Notifications/MailDriver.php`
- `config/notifications.php`
- Wire binding in `AppServiceProvider::register()`

Commit: `feat(notifications): add driver interface and three implementations`

## Task 2 — Enums

- `app/Enums/NotificationChannel.php` (sms/whatsapp/email + helpers)
- `app/Enums/NotificationStatus.php` (queued/sent/failed/bounced)

Commit: `feat(notifications): channel and status enums`

## Task 3 — Templates migration + model

- `database/migrations/2026_08_14_120000_create_notification_templates_table.php`
- `app/Models/NotificationTemplate.php` (fillable, casts, LogsActivity)

Commit: `feat(notifications): notification_templates table and model`

## Task 4 — Logs migration + model (immutable)

- `database/migrations/2026_08_14_120100_create_notification_logs_table.php`
- `app/Models/NotificationLog.php` (timestamps off, immutability boot hooks, LogsActivity)

Commit: `feat(notifications): notification_logs immutable audit table`

## Task 5 — NotificationTemplate factory + seeder

- `database/factories/NotificationTemplateFactory.php`
- `database/seeders/NotificationTemplateSeeder.php` (idempotent, 6 templates)
- Wire into `DatabaseSeeder`

Commit: `feat(notifications): templates factory and idempotent seeder`

## Quality gate after task 5: `composer quality` (pint + phpstan + 178+ tests).

## Task 6 — NotificationService

- `app/Services/NotificationService.php`
- Methods: `send`, `sendDirect`, `renderTemplate`, `resolveRecipient`
- Idempotency via `notification_logs` dedupe on (template_id, recipient, reference_id, day)
- Bind as singleton in AppServiceProvider

Commit: `feat(notifications): NotificationService with rendering and logging`

## Task 7 — Events

- `app/Events/AppointmentCreated.php`
- `app/Events/AppointmentCancelled.php`
- `app/Events/InvoiceIssued.php`
- `app/Events/PaymentReceived.php`

Commit: `feat(notifications): domain events for triggers`

## Task 8 — Listeners + wiring

- `app/Listeners/SendAppointmentCreatedNotification.php`
- `app/Listeners/SendAppointmentCancelledNotification.php`
- `app/Listeners/SendInvoiceIssuedNotification.php`
- `app/Listeners/SendPaymentReceivedNotification.php`
- Register all four in `AppServiceProvider::boot()`

Commit: `feat(notifications): listeners for all four events`

## Task 9 — Service-level event dispatch

- `AppointmentService::create` → dispatch AppointmentCreated
- `AppointmentService::cancel` → dispatch AppointmentCancelled
- `InvoiceService::createInvoice` → dispatch InvoiceIssued
- `PaymentService::record` → dispatch PaymentReceived

Commit: `feat(notifications): dispatch events from domain services`

## Task 10 — Settings migration

- `database/settings/2026_08_14_120200_add_notification_settings.php`
- Add `notification_default_channel`, `appointment_reminder_24h_enabled`,
  `appointment_reminder_1h_enabled`, `invoice_notification_enabled`,
  `payment_notification_enabled` to `clinic` group.
- Update `app/Settings/ClinicSettings.php` with typed properties.

Commit: `feat(notifications): settings for notification toggles`

## Quality gate after task 10: `composer quality`.

## Task 11 — Permissions

- Extend `RoleSeeder` with the four new permissions and role mapping.

Commit: `feat(notifications): permissions and role mapping`

## Task 12 — Scheduled commands

- `app/Console/Commands/Notifications/Send24hReminders.php`
- `app/Console/Commands/Notifications/SendHourlyReminders.php`
- Schedule them in `routes/console.php`.

Commit: `feat(notifications): scheduled reminder commands`

## Task 13 — Backend tests: drivers

- `tests/Unit/Services/Notifications/LogDriverTest.php`
- `tests/Unit/Services/Notifications/HttpDriverTest.php`
- `tests/Unit/Services/Notifications/MailDriverTest.php`

Commit: `test(notifications): driver unit tests`

## Task 14 — Backend tests: NotificationService

- `tests/Feature/Services/NotificationServiceTest.php`
- Render, send, log, idempotency, failure path.

Commit: `test(notifications): service tests covering render + idempotency`

## Task 15 — Backend tests: listeners + scheduled commands

- `tests/Feature/Listeners/SendAppointmentCreatedNotificationTest.php`
- `tests/Feature/Listeners/SendInvoiceIssuedNotificationTest.php`
- `tests/Feature/Console/SendRemindersTest.php`

Commit: `test(notifications): listener and reminder command tests`

## Quality gate after task 15: `composer quality` — expect 178+N tests green.

## Task 16 — Form requests + controllers

- `app/Http/Requests/Notifications/StoreTemplateRequest.php`
- `app/Http/Requests/Notifications/UpdateTemplateRequest.php`
- `app/Http/Requests/Notifications/PreviewTemplateRequest.php`
- `app/Http/Requests/Notifications/SendNotificationRequest.php`
- `app/Http/Controllers/Notifications/TemplateController.php` (index/create/store/edit/update/preview)
- `app/Http/Controllers/Notifications/LogController.php` (index)
- `app/Http/Controllers/Notifications/SendController.php` (create/store)

Commit: `feat(notifications): controllers and form requests`

## Task 17 — Routes

- `routes/notifications.php` (templates CRUD, logs, send)
- Include from `routes/web.php` under the auth + permission middleware.

Commit: `feat(notifications): routes file and web integration`

## Task 18 — Controller tests

- `tests/Feature/Controllers/TemplateControllerTest.php`
- `tests/Feature/Controllers/LogControllerTest.php`
- `tests/Feature/Controllers/SendControllerTest.php`

Commit: `test(notifications): controller tests`

## Task 19 — Vue: Templates Index

- `resources/js/Pages/Notifications/Templates/Index.vue`
- `resources/js/Pages/Notifications/Templates/_partials/TemplateRow.vue` (if needed)
- Update `useNav.js` to add the Notifications group.

Commit: `feat(notifications): templates index Vue page`

## Quality gate after task 20 (combined): `npm run lint`, `npx vite build`.

## Task 20 — Vue: Templates Create + Edit + Preview

- `resources/js/Pages/Notifications/Templates/Create.vue`
- `resources/js/Pages/Notifications/Templates/Edit.vue`
- Preview is inline (modal on Edit + Create) — no separate page; uses
  `TemplateController::preview` endpoint.

Commit: `feat(notifications): template create/edit/preview Vue pages`

## Task 21 — Vue: Logs Index

- `resources/js/Pages/Notifications/Logs/Index.vue`
- Server-side filters via GET query string; paginated.

Commit: `feat(notifications): logs index Vue page`

## Task 22 — Vue: Send page

- `resources/js/Pages/Notifications/Send/Index.vue`
- Channel + template select + recipient textarea + variable key/value rows + preview + send.

Commit: `feat(notifications): manual send Vue page`

## Task 23 — Frontend tests

- `resources/js/Pages/Notifications/Templates/Index.test.js`
- `resources/js/Pages/Notifications/Logs/Index.test.js`
- `resources/js/Pages/Notifications/Send/Index.test.js`

Commit: `test(notifications): frontend page tests`

## Quality gate after task 23: full quality suite.

## Task 24 — Dashboard KPI

- Update `app/Http/Controllers/DashboardController.php` to include
  `notifications_today` in the props.
- Update `resources/js/Pages/Dashboard.vue` to show a 7th KPI card.

Commit: `feat(notifications): dashboard notifications_today KPI`

## Task 25 — Final pass + milestone commit

- Full `composer quality`
- `npm run lint`
- `npm test`
- `npx vite build`
- Empty milestone commit: `feat(notifications): sub-project 6 complete — all sub-projects shipped`

Commit: milestone marker.

---

## Files Touched (estimate)

**Backend (PHP):** ~30 files
- 4 migrations (2 DB + 1 settings)
- 2 enum classes
- 3 models
- 1 factory + 1 seeder + DB seeder wire-up
- 1 contract + 3 drivers
- 1 service
- 4 events
- 4 listeners
- 2 console commands
- 4 form requests
- 3 controllers
- 1 settings class extension
- 2 service patches (dispatch events)
- 1 RoleSeeder patch
- 2 route files (new + web.php patch)
- 1 AppServiceProvider patch
- 1 routes/console.php patch
- ~12 test files

**Frontend (Vue):** ~5 pages + 1 sidebar + 1 dashboard + 3 test files.
