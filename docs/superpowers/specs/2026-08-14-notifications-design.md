# Notifications — Design Spec

**Date:** 2026-08-14
**Sub-project:** 6 (Notifications) — FINAL
**Stack:** Laravel 13 + Inertia 3 + Vue 3 (plain JS) + PrimeVue 4 + Tailwind v4

---

## 1. Goals

Provide a multi-channel notification system (SMS / WhatsApp / Email) for the dental
clinic with:

1. A pluggable **driver interface** so any channel can be swapped without changing callers.
2. **Reusable templates** with `{{ var }}` placeholders, per-channel content, locale.
3. An **immutable audit log** of every send attempt (success or failure).
4. **Event-driven triggers** wired to Appointment, Invoice, and Payment lifecycle.
5. **Scheduled reminders** — 24h before and 1h before appointments — idempotent.
6. A **settings panel** that toggles reminder + notification categories on/off.
7. A **UI** for admins to manage templates, view logs, and send ad-hoc messages.
8. **Permissions** for fine-grained control (admin, doctor, receptionist).
9. **Dashboard** KPI: count of notifications sent today.

Out of scope:

- Real provider integration (Twilio / WhatsApp Business / SES). The `HttpDriver`
  POSTs to a generic webhook — operators can wire it to anything.
- Per-user notification preferences. Clinic-wide toggles only.
- Per-template audience targeting (e.g. exclude VIPs). Recipients are explicit.
- Locale fallback chain. Templates declare their own `locale` (default `en`).

---

## 2. Channels

Three channel values, validated as an enum:

| Channel    | Typical recipient          | Subject supported |
|------------|----------------------------|-------------------|
| `sms`      | E.164 phone, e.g. `+880…`  | No                |
| `whatsapp` | E.164 phone                | No                |
| `email`    | RFC 5322 email address     | Yes               |

A notification's body is always a UTF-8 string. Placeholders are `{{ var }}`.

---

## 3. Drivers

### 3.1 Interface

`App\Contracts\NotificationDriver`

```php
public function send(
    string $channel,
    string $recipient,
    ?string $subject,
    string $body,
    array $meta = []
): array; // ['success' => bool, 'error' => ?string, 'provider_id' => ?string]
```

### 3.2 Implementations

| Driver      | Use case                     | Side effect                                  |
|-------------|------------------------------|----------------------------------------------|
| `LogDriver` | dev / test / default         | Writes an `info()` line to Laravel log       |
| `HttpDriver`| production webhook POST      | POSTs JSON to a configured URL               |
| `MailDriver`| email-only fallback          | `Mail::raw` or `Mail::send` (raw HTML/text)  |

`LogDriver` is the default (`NOTIFICATIONS_DRIVER=log`) and is used by tests.

### 3.3 Config — `config/notifications.php`

```php
return [
    'default_driver' => env('NOTIFICATIONS_DRIVER', 'log'),
    'channels' => ['sms', 'whatsapp', 'email'],
    'drivers' => [
        'log'  => ['class' => LogDriver::class,  'config' => []],
        'http' => [
            'class'   => HttpDriver::class,
            'config'  => ['url' => env('NOTIFICATIONS_HTTP_URL'), 'timeout' => 5],
        ],
        'mail' => ['class' => MailDriver::class, 'config' => []],
    ],
];
```

`AppServiceProvider::register()` binds the default driver as a singleton.

---

## 4. Data Model

### 4.1 `notification_templates`

| Column      | Type                       | Notes                                  |
|-------------|----------------------------|----------------------------------------|
| `id`        | bigint PK                  |                                        |
| `key`       | `string(80)` UNIQUE        | e.g. `appointment-confirmation`       |
| `channel`   | enum `sms/whatsapp/email`  |                                        |
| `subject`   | `?string(160)`             | email only                             |
| `body`      | `longText`                 | `{{ var }}` placeholders               |
| `is_active` | `boolean` default true     |                                        |
| `locale`    | `string(8)` default `en`   |                                        |
| timestamps  |                            |                                        |

Indexes: `unique(key, channel, locale)` to allow per-locale variants.

### 4.2 `notification_logs`

Immutable audit log. `created_at` only.

| Column            | Type                         | Notes                                |
|-------------------|------------------------------|--------------------------------------|
| `id`              | bigint PK                    |                                      |
| `template_id`     | `?int` FK templates          | nullable for ad-hoc sends            |
| `channel`         | enum                         |                                      |
| `recipient`       | `string(160)`                |                                      |
| `subject`         | `?string(160)`               |                                      |
| `body_rendered`   | `longText`                   | post-render body                     |
| `status`          | enum `queued/sent/failed/bounced` | default `queued`                |
| `error_message`   | `?string(1000)`              | populated on failure                 |
| `reference_type`  | `?string` morph              |                                      |
| `reference_id`    | `?bigint`                    |                                      |
| `sent_at`         | `?datetime`                  | null until driver returns            |
| `created_at`      | datetime                     | immutable                            |

Indexes: `(status, created_at)`, `(channel, created_at)`,
`(reference_type, reference_id)`, `(template_id, created_at)`.

`updating` / `deleting` boot hooks throw `RuntimeException`.

### 4.3 Settings — `clinic` group (new keys)

| Key                                  | Type   | Default |
|--------------------------------------|--------|---------|
| `notification_default_channel`       | string | `sms`   |
| `appointment_reminder_24h_enabled`   | bool   | true    |
| `appointment_reminder_1h_enabled`    | bool   | true    |
| `invoice_notification_enabled`       | bool   | true    |
| `payment_notification_enabled`       | bool   | true    |

Added via `database/settings/2026_08_14_120000_add_notification_settings.php`.

---

## 5. Service Layer

### 5.1 `App\Services\NotificationService`

| Method                                                                                       | Purpose                          |
|----------------------------------------------------------------------------------------------|----------------------------------|
| `send(string $templateKey, string $channel, array $recipients, array $vars, ?Model $ref)`    | Render template, fan-out per recipient, log each attempt |
| `sendDirect(string $channel, array $recipients, ?string $subject, string $body, ?Model $ref)`| Ad-hoc, no template              |
| `renderTemplate(NotificationTemplate $t, array $vars): array`                                 | Returns `['subject' => …, 'body' => …]` |
| `resolveRecipient(string $channel, string $raw): ?string`                                    | Light validation                 |

Render rule: replace `{{ key }}` with `$vars['key']` (or empty string if missing).
Strict mode: throw `InvalidArgumentException` if a placeholder is not in vars.

Each call writes one `notification_logs` row per recipient with status
`sent` or `failed` (we never persist `queued` from the service — the driver
is synchronous).

### 5.2 Idempotency

The service refuses to send the same `(template_id, recipient, reference_id)`
combination twice within the same calendar day. Reminder cron jobs rely on
this to avoid spamming.

---

## 6. Events & Listeners

### 6.1 Events (plain Laravel events, not queued)

- `App\Events\AppointmentCreated(Appointment $appointment)`
- `App\Events\AppointmentCancelled(Appointment $appointment)`
- `App\Events\InvoiceIssued(Invoice $invoice)`
- `App\Events\PaymentReceived(Payment $payment)`

### 6.2 Listeners

- `App\Listeners\SendAppointmentCreatedNotification`
- `App\Listeners\SendAppointmentCancelledNotification`
- `App\Listeners\SendInvoiceIssuedNotification`
- `App\Listeners\SendPaymentReceivedNotification`

Each listener:

1. Resolves the patient from the reference.
2. Reads recipient from `patient.phone` (sms/whatsapp) or `patient.email`.
3. Picks the channel from `clinic.notification_default_channel` (or the event's
   channel if specified).
4. Checks the appropriate settings toggle — bails out if disabled.
5. Builds a `vars` array.
6. Calls `NotificationService::send($templateKey, $channel, $recipients, $vars, $reference)`.

Wired in `AppServiceProvider::boot()`:

```php
Event::listen(AppointmentCreated::class, SendAppointmentCreatedNotification::class);
// …
```

(Manual registration rather than auto-discovery for clarity.)

### 6.3 Triggers wired to the model layer

Each service that creates the relevant model dispatches the event:

| Action                            | Event              |
|-----------------------------------|--------------------|
| `AppointmentService::create()`    | `AppointmentCreated` |
| `AppointmentService::cancel()`    | `AppointmentCancelled` |
| `InvoiceService::createInvoice()` | `InvoiceIssued`    |
| `PaymentService::record()`        | `PaymentReceived`  |

---

## 7. Scheduled Commands

In `routes/console.php`:

```php
Schedule::command('notifications:send-24h-reminders')->dailyAt('08:00');
Schedule::command('notifications:send-hourly-reminders')->hourly();
```

### 7.1 `notifications:send-24h-reminders`

- Iterates all confirmed/scheduled appointments scheduled in the window
  `[now+23h, now+25h]` (inclusive).
- For each, checks `notification_logs` for a row with `template_id` =
  `appointment-reminder-24h` AND `reference_id` = appointment id.
- Sends if absent and setting enabled.

### 7.2 `notifications:send-hourly-reminders`

- Iterates appointments scheduled in `[now+30min, now+90min]`.
- Sends `appointment-reminder-1h` if not yet logged.
- Skips appointments already cancelled / no-show.

Both commands are no-ops when the relevant setting is disabled.

---

## 8. UI

### 8.1 Templates

| Page              | Route                                                      |
|-------------------|------------------------------------------------------------|
| Index             | `/notifications/templates`                                 |
| Create            | `/notifications/templates/create`                          |
| Edit              | `/notifications/templates/{template}/edit`                |
| Preview (POST)    | `/notifications/templates/{template}/preview`              |

Index lists templates, with a "New template" button. Preview accepts sample
variables and returns the rendered subject + body (Inertia `partial` response).

### 8.2 Logs

`/notifications/logs` — paginated table with filters:

- channel (dropdown)
- status (multi-select chips)
- template (dropdown)
- date range (from / to)

### 8.3 Send

`/notifications/send` — single page:

1. Pick channel (sms/whatsapp/email).
2. Pick template (filtered by channel).
3. Enter recipient(s) (textarea, one per line).
4. Enter variables (key/value rows).
5. Click "Preview" — shows rendered body.
6. Click "Send" — submits, returns to Logs page.

---

## 9. Permissions

Added to `RoleSeeder`:

| Permission                       | admin | doctor | receptionist |
|----------------------------------|:-----:|:------:|:------------:|
| `manage-notification-templates`  |  X    |        |              |
| `view-notification-logs`         |  X    |   X    |      X       |
| `send-notifications`             |  X    |        |      X       |
| `manage-notification-settings`   |  X    |        |              |

---

## 10. Dashboard

Add a 7th KPI card to `DashboardController::index()`:

```php
'notifications_today' => NotificationLog::whereDate('created_at', today())->count(),
```

Insertion order: after the existing 6 KPIs, before the chart.

---

## 11. Non-Goals / Future

- Real provider SDKs (Twilio, Meta WABA, SES).
- Per-user opt-out / quiet hours.
- Multi-locale fallback chain.
- SMS delivery reports / two-way messaging.
- Bounce handling (status field exists, but no webhook listener).
