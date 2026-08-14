# Doctors & Appointments — Design Spec

**Sub-project:** 1 of N (Foundation is done)
**Date:** 2026-08-14
**Status:** Approved (Foundation shipped)

## 1. Purpose

Enable the clinic to manage its doctors and the appointments they receive. This sub-project delivers:

- **Doctors**: a directory of clinic doctors with per-doctor weekly schedule, time-off, and booking metadata.
- **Appointments**: a free-text patient booking system that shows in a week/day calendar, generates available slots, prevents double-booking, and supports the standard reception workflow (book → confirm → check in → complete, plus cancel / no-show).

A full `Patient` model is out of scope for this sub-project and ships in sub-project 2. Until then, the appointment stores `patient_name` + `patient_phone` as free text. A `patient_id` FK is reserved (nullable) so sub-project 2 can backfill without a destructive migration.

## 2. Data Model

### 2.1 `doctors` (1:1 with `users`)

A doctor IS a `User` with the `doctor` role. The `doctors` row holds the per-doctor profile.

| Column               | Type                | Notes                                                   |
|----------------------|---------------------|---------------------------------------------------------|
| `id`                 | bigint              | PK                                                      |
| `user_id`            | bigint FK UNIQUE    | `users.id` ON DELETE CASCADE                            |
| `specialization`     | string(128) NULL    | e.g. "Orthodontics"                                     |
| `license_number`     | string(64) NULL     | e.g. "BMDC-12345"                                       |
| `bio`                | text NULL           | short profile                                           |
| `years_of_experience`| unsignedTinyInt NULL| 0-65                                                    |
| `consultation_fee`   | decimal(18, 2) NULL | single displayed value (not a ledger)                   |
| `color`              | string(7)           | hex like `#0EA5E9` for calendar color (default primary) |
| `is_available`       | boolean             | soft toggle to stop taking bookings without deactivating|
| `created_at`         | timestamp           |                                                         |
| `updated_at`         | timestamp           |                                                         |

Indexes: unique on `user_id`.

### 2.2 `doctor_schedules`

Per-doctor weekly availability. Overrides `ClinicSettings.working_hours` for that doctor on that day. If a doctor has no schedule rows for a day, fall back to the clinic working hours; if a doctor has ANY row for a day, ignore the clinic hours for that day.

| Column        | Type           | Notes                                  |
|---------------|----------------|----------------------------------------|
| `id`          | bigint         | PK                                     |
| `doctor_id`   | bigint FK      | `doctors.id` ON DELETE CASCADE         |
| `day_of_week` | unsignedTinyInt| 0 = Sunday, 6 = Saturday               |
| `start_time`  | time           | e.g. "09:00"                           |
| `end_time`    | time           | e.g. "18:00" — must be after start     |
| `is_active`   | boolean        | default true                           |
| `created_at`  | timestamp      |                                        |
| `updated_at`  | timestamp      |                                        |

Indexes: `(doctor_id, day_of_week)`.

### 2.3 `doctor_time_offs`

Blocked ranges: vacations, sick days, off-site meetings. Subtracted from available slot generation.

| Column      | Type     | Notes                                       |
|-------------|----------|---------------------------------------------|
| `id`        | bigint   | PK                                          |
| `doctor_id` | bigint FK| `doctors.id` ON DELETE CASCADE              |
| `start_at`  | datetime | inclusive                                   |
| `end_at`    | datetime | exclusive                                   |
| `reason`    | string(255) NULL | optional free text                  |
| `created_at`| timestamp|                                             |
| `updated_at`| timestamp|                                             |

Indexes: `(doctor_id, start_at)`.

### 2.4 `appointments`

| Column             | Type                                                              | Notes                                                 |
|--------------------|-------------------------------------------------------------------|-------------------------------------------------------|
| `id`               | bigint                                                            | PK                                                    |
| `patient_name`     | string(255)                                                       | free text — moves to `patients` in sub-project 2      |
| `patient_phone`    | string(32) NULL                                                   |                                                       |
| `patient_id`       | bigint NULL FK                                                    | reserved for future backfill                          |
| `doctor_id`        | bigint FK                                                         | `doctors.id` ON DELETE RESTRICT                       |
| `scheduled_at`     | datetime                                                          | UTC start                                             |
| `duration_minutes` | unsignedSmallInt                                                  | default from `ClinicSettings.appointment_slot_minutes`|
| `status`           | enum('scheduled','confirmed','checked_in','completed','cancelled','no_show') | default `scheduled`                    |
| `notes`            | text NULL                                                         | staff notes (not visible to patient)                  |
| `created_by`       | bigint FK                                                         | `users.id` ON DELETE RESTRICT                         |
| `cancelled_at`     | datetime NULL                                                     | set when status becomes `cancelled`                   |
| `cancelled_reason` | string(255) NULL                                                  | optional free text                                    |
| `created_at`       | timestamp                                                         |                                                       |
| `updated_at`       | timestamp                                                         |                                                       |

Indexes: `(doctor_id, scheduled_at)`, `(scheduled_at)`, `(status)`.

A uniqueness check on overlapping windows is enforced **in code** in a `SELECT ... FOR UPDATE` transaction (MySQL pre-8.0.33 has no `EXCLUDE USING gist`, and a non-overlap check requires a per-row trigger or a serializable transaction; we use a row-locked count overlap query). See §4.

## 3. Permissions

Six new permissions, appended to `RoleSeeder` (never replacing existing grants).

| Permission               | Admin | Doctor        | Receptionist |
|--------------------------|:-----:|:-------------:|:------------:|
| `manage-doctors`         |  X    |               |              |
| `view-doctors`           |  X    |  X (all)      |  X (all)     |
| `manage-appointments`    |  X    |               |  X           |
| `view-own-appointments`  |       |  X (own only) |              |
| `cancel-appointments`    |  X    |               |  X           |
| `check-in-appointments`  |  X    |               |  X           |

Doctor and receptionist retain `view-dashboard`. Admin receives all (existing behaviour: `$admin->syncPermissions(Permission::all())`).

Doctor scope: when `view-own-appointments` is the only permission, controllers limit queries to `appointments.doctor_id` matching the doctor's `doctors.id` for the authenticated user.

## 4. Status Transitions

```
scheduled ──► confirmed ──► checked_in ──► completed
    │              │              │
    ▼              ▼              ▼
cancelled       cancelled     no_show
                  (no_show also reachable from confirmed)
```

Allowed transitions (enforced in `AppointmentStatusController`):

| From        | Allowed To                          |
|-------------|-------------------------------------|
| scheduled   | confirmed, cancelled, no_show       |
| confirmed   | checked_in, cancelled, no_show      |
| checked_in  | completed, no_show                  |
| completed   | (terminal)                          |
| cancelled   | (terminal)                          |
| no_show     | (terminal)                          |

Cancellation and no-show set `cancelled_at` (no_show stores in `cancelled_at` for the same audit window; we don't introduce a new column).

## 5. Slot Generation Algorithm

`POST /appointments/slots?doctor_id=…&date=YYYY-MM-DD&duration=N`

1. Resolve effective working window for that weekday:
   - Look up `doctor_schedules` for the day.
   - If rows exist (and any is active), use those `(start_time, end_time)` ranges.
   - Else fall back to `ClinicSettings.working_hours_json` (decoded as `['mon'=>[['start'=>'09:00','end'=>'18:00']], …]`).
2. Subtract any `doctor_time_offs` whose range overlaps the day.
3. Generate candidate slots every `ClinicSettings.appointment_slot_minutes` minutes within each window, **trimming any slot that doesn't fit the requested `duration`**.
4. Subtract existing non-terminal appointments for that doctor on that day. Two appointments overlap iff `existing.scheduled_at < candidate_end AND existing.scheduled_at + existing.duration_minutes > candidate_start`.
5. Return array of `['start' => 'YYYY-MM-DD HH:MM:SS', 'end' => 'YYYY-MM-DD HH:MM:SS']` in UTC, plus `slot_minutes` for UI hint.

All datetimes in/out are UTC ISO; the UI converts via `date-fns-tz` + the shared `settings.timezone`.

## 6. Booking & Conflict Detection

`POST /appointments` validates:

- doctor exists and is available
- scheduled_at is in the future (>= now + 1 minute, <= now + 1 year)
- duration_minutes is one of {15, 30, 60}
- patient_name non-empty, patient_phone format-validated (E.164-ish: digits, `+`, spaces, dashes, length 6-32)

Inside a `DB::transaction` with `SELECT ... FOR UPDATE` on the doctor's row (`doctors` table), the controller:

1. Re-runs the same overlap check (defence-in-depth — another request might have committed between fetch and lock).
2. Inserts the appointment.
3. Returns 201 with Inertia `back()` flash.

Race condition notes: MySQL row lock prevents two concurrent transactions from both reading zero overlaps. Without it, double-bookings are possible. We use a single-statement `SELECT … FOR UPDATE` on `doctors WHERE id = ?` to serialize per-doctor.

## 7. Routes

All routes live in `routes/doctors.php` and `routes/appointments.php`, registered from `routes/web.php` inside the `auth + active` middleware group.

### Doctors (all under `auth + active`)

| Method | Path                                  | Permission            | Name                  |
|--------|---------------------------------------|-----------------------|-----------------------|
| GET    | `/doctors`                            | `view-doctors`        | `doctors.index`       |
| GET    | `/doctors/create`                     | `manage-doctors`      | `doctors.create`      |
| POST   | `/doctors`                            | `manage-doctors`      | `doctors.store`       |
| GET    | `/doctors/{doctor}`                   | `view-doctors`        | `doctors.show`        |
| GET    | `/doctors/{doctor}/edit`              | `manage-doctors`      | `doctors.edit`        |
| PATCH  | `/doctors/{doctor}`                   | `manage-doctors`      | `doctors.update`      |
| POST   | `/doctors/{doctor}/toggle-availability` | `manage-doctors`    | `doctors.toggle`      |
| GET    | `/doctors/{doctor}/schedules`         | `manage-doctors`      | `doctors.schedules`   |
| PATCH  | `/doctors/{doctor}/schedules`         | `manage-doctors`      | `doctors.schedules.update` |
| GET    | `/doctors/{doctor}/time-off`          | `manage-doctors`      | `doctors.timeoff`     |
| POST   | `/doctors/{doctor}/time-off`          | `manage-doctors`      | `doctors.timeoff.store` |
| DELETE | `/doctors/{doctor}/time-off/{timeOff}`| `manage-doctors`      | `doctors.timeoff.destroy` |

### Appointments (all under `auth + active`)

| Method | Path                                            | Permission                  | Name                          |
|--------|-------------------------------------------------|-----------------------------|-------------------------------|
| GET    | `/appointments`                                 | any of {manage-, view-own-} | `appointments.index`          |
| GET    | `/appointments/day/{date}`                      | same                        | `appointments.day`            |
| GET    | `/appointments/create`                          | `manage-appointments`       | `appointments.create`         |
| POST   | `/appointments`                                 | `manage-appointments`       | `appointments.store`          |
| GET    | `/appointments/{appointment}`                   | view scope                  | `appointments.show`           |
| GET    | `/appointments/{appointment}/edit`              | `manage-appointments`       | `appointments.edit`           |
| PATCH  | `/appointments/{appointment}`                   | `manage-appointments`       | `appointments.update`         |
| DELETE | `/appointments/{appointment}`                   | `manage-appointments`       | `appointments.destroy`        |
| PATCH  | `/appointments/{appointment}/status`            | `check-in-` / `cancel-`     | `appointments.status`         |
| POST   | `/appointments/slots`                           | `manage-appointments`       | `appointments.slots`          |

Doctor view scope: when a doctor hits `appointments.index` / `appointments.show` / `appointments.day`, the controller limits to their own `doctor_id`. They cannot edit, create, or delete.

## 8. UI Flows

### 8.1 Doctor index (`/doctors`)

- Search by name/email; filter by `specialization`.
- Table: name, specialization, fee, color swatch, is_available badge, actions (View, Edit, Manage schedule, Manage time-off, Toggle availability).
- "New doctor" button visible only to admins.

### 8.2 Doctor create / edit (`/doctors/create`, `/doctors/{doctor}/edit`)

- Fields: name, email, phone, specialization, license number, bio, years of experience, consultation fee, color picker (preset 6 swatches + custom hex), is_available toggle.
- Edit reuses the same form.
- On save: redirect to doctor show with success flash.

### 8.3 Doctor show (`/doctors/{doctor}`)

- Header card: name, role, email, phone, is_available, color, specialization, fee.
- Two side-by-side cards: Weekly schedule (table) and Upcoming time-off (table).
- Link to Manage schedule and Manage time-off (admin only).

### 8.4 Schedule manager (`/doctors/{doctor}/schedules`)

- Per-day editable list of (start, end) slots, with is_active toggle.
- "Add slot" / "Remove" per day.
- Submits full week as PATCH.

### 8.5 Time-off manager (`/doctors/{doctor}/time-off`)

- Table of upcoming time-off (start_at — end_at, reason).
- Inline form to add a new range (datetime-local start / end, reason).
- Delete button per row.

### 8.6 Appointments calendar (`/appointments`)

- Default week view starting today.
- Top bar: ‹ prev | This week | next ›, doctor filter (dropdown, "All doctors" default for admin/receptionist, locked to self for doctor), view toggle (Week / Day), "New appointment" button (admin + receptionist only).
- Week view: 7 columns (Mon–Sun), 30-minute rows from 08:00 to 20:00 (configurable in JS from clinic working hours). Each appointment rendered as a colored block with patient name + status badge. Color = status: scheduled=blue, confirmed=teal, checked_in=amber, completed=emerald, cancelled=slate, no_show=red.
- Day view: same grid, one column. URL `/appointments/day/YYYY-MM-DD`.

### 8.7 Booking form (`/appointments/create`)

- Fields: doctor (Select, only available doctors), date (DatePicker), duration (Select 15/30/60, default from settings), slot (Select populated from `/appointments/slots`), patient_name, patient_phone, notes.
- Workflow: user picks doctor + date + duration → on change, fetch slots → pick slot → fill patient fields → submit.
- On success: redirect to `/appointments/{id}` with success flash.
- On conflict (rare race): display 422 error and refetch slots.

### 8.8 Appointment show (`/appointments/{appointment}`)

- Header: status badge, doctor, patient name, scheduled_at (formatted in clinic timezone), duration.
- Action buttons (gated by permission + current status):
  - Scheduled → Confirm / Cancel / No-show
  - Confirmed → Check in / Cancel / No-show
  - Checked in → Complete / No-show
  - Terminal → no actions
- Notes panel.
- Activity log: rendered as a timeline (uses `spatie/laravel-activitylog` automatically since we apply `LogsActivity` trait).

### 8.9 Appointment edit (`/appointments/{appointment}/edit`)

- Allowed fields: scheduled_at, duration, doctor (re-runs conflict), patient_name, patient_phone, notes.
- Status changes go through the dedicated PATCH `/status` endpoint.

## 9. Repository Pattern

We follow the foundation convention: `Contracts/XRepository` interface + `Eloquent/XEloquentRepository` implementation, bound in `AppServiceProvider`. Filters are passed as `array $filters = []`.

New bindings:

- `DoctorRepository`, `DoctorScheduleRepository`, `DoctorTimeOffRepository`, `AppointmentRepository`.

## 10. Activity Logging

`Doctor`, `Appointment` get `LogsActivity` trait. Schedule and time-off changes are logged by their parent doctor via `logOnly` plus a custom log on save. Appointment status transitions are logged explicitly with a description like `"Appointment {id} status: {old} → {new}"` so the activity log timeline is readable.

## 11. Out of Scope (sub-project 2+)

- Full `Patient` model + migration of `patient_name/phone` data.
- Insurance, billing, payments.
- SMS / email reminders.
- Recurring appointments.
- Multi-clinic / multi-tenant.
- Reporting / analytics.

## 12. Quality Bar (from Foundation Appendix B)

- `declare(strict_types=1)` in every PHP file.
- `decimal(18, 2)` for `consultation_fee`.
- UTC storage, timezone conversion in UI via shared `settings.timezone`.
- Every FormRequest has `authorize()` that checks permission.
- PHPStan level 6 = 0 errors. New relations get `@property` PHPDoc on the model.
- Pint auto-formats. ESLint 0 errors. Vitest passes. `npx vite build` succeeds.
- No TODO comments committed.
