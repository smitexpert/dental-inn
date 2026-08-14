# Doctors & Appointments — Implementation Plan

**Sub-project:** 1
**Spec:** `docs/superpowers/specs/2026-08-14-doctors-appointments-design.md`
**Date:** 2026-08-14

## Conventions (from Foundation)

- One commit per task. `git add` exact paths, no `git add -A`.
- Pint auto-formats; run after writing PHP files.
- Every FormRequest has `authorize()` checking a permission.
- Every test has at least one `assert*` call.
- PHPStan level 6 = 0 errors.
- Migration filename: `YYYY_MM_DD_HHMMSS_create_<table>_table.php`
- Tables: `id`, `created_at`, `updated_at`. FKs named `<singular>_id` with `constrained()`.
- Vue imports from `@/Components/ui`, never from `primevue/*`.
- Plain JS, JSDoc typedefs, no `.ts`.

All `docker exec` commands assume the `dental_app` container is running. From the project root: `docker exec dental_app php artisan <cmd>`, `docker exec dental_app composer <cmd>`, `docker exec dental_app vendor/bin/pint`, `docker exec dental_app vendor/bin/phpstan …`, `docker exec dental_app php artisan test`.

---

## Task 1 — Extend ClinicSettings with appointment settings

**Why:** Add `appointment_default_status` and other appointment settings. The spec only mentions using `appointment_slot_minutes` (already exists). Add `appointment_buffer_minutes` (default 0) to allow buffer time between bookings. (This was the simplest, most-useful addition consistent with the spec; we will use it in the slot algorithm.)

**Files:**

- `app/Settings/ClinicSettings.php` — add `public int $appointment_buffer_minutes;`
- `database/seeders/SettingsSeeder.php` — add `'appointment_buffer_minutes' => 0,`
- `app/Http/Resources/SettingsResource.php` — add `appointment_buffer_minutes` to the array.
- `tests/Feature/Settings/UpdateClinicSettingsTest.php` — add `appointment_buffer_minutes => 0` to both payload arrays.

**Tests:** existing settings test must still pass.

**Commands:**

```bash
docker exec dental_app vendor/bin/pint app/Settings database/seeders app/Http/Resources tests/Feature/Settings
docker exec dental_app php artisan test --filter=UpdateClinicSettingsTest
```

**Commit:** `feat(settings): add appointment_buffer_minutes to ClinicSettings`

---

## Task 2 — Doctor model + migration + factory + repository

**Files:**

- `database/migrations/2026_08_14_000001_create_doctors_table.php`
  - `id`, `user_id` (FK unique), `specialization`, `license_number`, `bio` (text), `years_of_experience` (unsignedTinyInt nullable), `consultation_fee` (decimal(18,2) nullable), `color` (string(7) default `'#0EA5E9'`), `is_available` (boolean default true), timestamps.
- `app/Models/Doctor.php` — Eloquent with `LogsActivity`, `user()` belongsTo, `schedules()` hasMany, `timeOffs()` hasMany, `appointments()` hasMany. PHPDoc `@property` for relations.
- `database/factories/DoctorFactory.php` — generates a User + Doctor with sensible defaults.
- `app/Repositories/Contracts/DoctorRepository.php` — `paginate($perPage, $filters)`, `create($data)`, `update($doctor, $data)`, `toggleAvailability($doctor)`.
- `app/Repositories/Eloquent/DoctorEloquentRepository.php` — implements; filter `q` searches user name/email, `specialization` exact match.
- `app/Providers/AppServiceProvider.php` — bind `DoctorRepository => DoctorEloquentRepository`.

**Tests:** `tests/Feature/Doctors/DoctorModelTest.php` (or inline in Task 5 — skip model-only tests, rely on feature tests).

**Commands:**

```bash
docker exec dental_app php artisan migrate
docker exec dental_app vendor/bin/pint app/Models app/Repositories database/factories app/Providers
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker exec dental_app php artisan test
```

**Commit:** `feat(doctors): add Doctor model, migration, factory, and repository`

---

## Task 3 — DoctorSchedule model + migration + repository

**Files:**

- `database/migrations/2026_08_14_000002_create_doctor_schedules_table.php`
  - `id`, `doctor_id` (FK cascade), `day_of_week` (unsignedTinyInt), `start_time` (time), `end_time` (time), `is_active` (boolean default true), timestamps. Index `(doctor_id, day_of_week)`.
- `app/Models/DoctorSchedule.php` — with `doctor()` belongsTo, `LogsActivity`. PHPDoc.
- `app/Repositories/Contracts/DoctorScheduleRepository.php` — `replaceForDoctor($doctor, array $rows)`, `forDoctor($doctor)`.
- `app/Repositories/Eloquent/DoctorScheduleEloquentRepository.php` — `replaceForDoctor` deletes existing then bulk inserts within a transaction.

**Bind in AppServiceProvider.**

**Commands:**

```bash
docker exec dental_app php artisan migrate
docker exec dental_app vendor/bin/pint app/Models app/Repositories app/Providers
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
```

**Commit:** `feat(doctors): add DoctorSchedule model, migration, repository`

---

## Task 4 — DoctorTimeOff model + migration + repository

**Files:**

- `database/migrations/2026_08_14_000003_create_doctor_time_offs_table.php`
  - `id`, `doctor_id` (FK cascade), `start_at` (datetime), `end_at` (datetime), `reason` (string nullable), timestamps. Index `(doctor_id, start_at)`.
- `app/Models/DoctorTimeOff.php` — `doctor()` belongsTo, `LogsActivity`. PHPDoc.
- `app/Repositories/Contracts/DoctorTimeOffRepository.php` — `paginate($perPage, $filters)`, `create($doctor, $data)`, `delete($timeOff)`, `overlapping($doctor, $start, $end)`.
- `app/Repositories/Eloquent/DoctorTimeOffEloquentRepository.php`.

**Bind in AppServiceProvider.**

**Commands:**

```bash
docker exec dental_app php artisan migrate
docker exec dental_app vendor/bin/pint app/Models app/Repositories app/Providers
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
```

**Commit:** `feat(doctors): add DoctorTimeOff model, migration, repository`

---

## Task 5 — Appointment model + migration + factory + repository + status enum

**Files:**

- `app/Enums/AppointmentStatus.php` — backed string enum with `cases()` and a `canTransitionTo(self $next): bool` method. Implements the spec's transition table.
- `database/migrations/2026_08_14_000004_create_appointments_table.php`
  - `id`, `patient_name` (string), `patient_phone` (string nullable), `patient_id` (FK nullable to `users.id` is wrong — reserve as nullable unsignedBigInt with no FK for now, since the `patients` table doesn't exist yet; document via comment), `doctor_id` (FK restrict), `scheduled_at` (datetime), `duration_minutes` (unsignedSmallInt), `status` (string default 'scheduled'), `notes` (text nullable), `created_by` (FK to users restrict), `cancelled_at` (datetime nullable), `cancelled_reason` (string nullable), timestamps. Indexes `(doctor_id, scheduled_at)`, `(scheduled_at)`, `(status)`.
- `app/Models/Appointment.php` — `doctor()`, `creator()` belongsTo, `LogsActivity`, casts `status => AppointmentStatus`, `scheduled_at => datetime`. PHPDoc. `endAt(): Carbon` accessor.
- `database/factories/AppointmentFactory.php` — `for($doctor)`, `scheduledAt($dateTime)`, `status(AppointmentStatus::…)`.
- `app/Repositories/Contracts/AppointmentRepository.php` — `paginate($perPage, $filters)` (filters: `from`, `to`, `doctor_id`, `status`, `q` searching patient name/phone), `create($data)`, `update($appointment, $data)`, `delete($appointment)`, `updateStatus($appointment, AppointmentStatus $new, ?string $reason)`, `overlappingCount($doctorId, $start, $end, ?int $ignoreId)`, `weeklyView($start, $end, ?int $doctorId)`.
- `app/Repositories/Eloquent/AppointmentEloquentRepository.php` — implements.

**Bind in AppServiceProvider.**

**Commands:**

```bash
docker exec dental_app php artisan migrate
docker exec dental_app vendor/bin/pint app/Enums app/Models app/Repositories database/factories app/Providers
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
```

**Commit:** `feat(appointments): add Appointment model, status enum, migration, factory, repository`

---

## Task 6 — Extend permissions in RoleSeeder

**Why:** The six new permissions must exist before the middleware/FormRequests run.

**Files:**

- `database/seeders/RoleSeeder.php` — add 6 permissions to the array, append to admin's `syncPermissions(Permission::all())` (still works), give doctor `view-doctors` and `view-own-appointments`, give receptionist `view-doctors`, `manage-appointments`, `cancel-appointments`, `check-in-appointments`.

**Tests:** existing tests should still pass. Run all.

**Commands:**

```bash
docker exec dental_app php artisan db:seed --class=RoleSeeder
docker exec dental_app vendor/bin/pint database/seeders
docker exec dental_app php artisan test
```

**Commit:** `feat(auth): add doctors and appointments permissions to RoleSeeder`

---

## Task 7 — DoctorFormRequest + DoctorController + routes

**Files:**

- `app/Http/Requests/Doctors/StoreDoctorRequest.php` — authorize `manage-doctors`. Rules: name, email unique, phone, password required+confirmed, role (must be 'doctor'), plus doctor fields (specialization, license_number, bio, years_of_experience, consultation_fee, color, is_available).
- `app/Http/Requests/Doctors/UpdateDoctorRequest.php` — same but email unique-ignores the doctor's user_id, password optional, doctor_id from route('doctor')->id.
- `app/Http/Controllers/Doctors/DoctorController.php` — `index`, `create`, `store`, `show`, `edit`, `update`, `toggleAvailability`. Uses `DoctorRepository` and `UserRepository` (we need to update the user too).
- `routes/doctors.php` — wired with the right permissions (see spec §7).
- `routes/web.php` — `require __DIR__.'/doctors.php';` and `require __DIR__.'/appointments.php';` (the latter file is created in Task 11 but the require can be added now).

**Tests:** `tests/Feature/Doctors/ListDoctorsTest.php`, `CreateDoctorTest.php`, `UpdateDoctorTest.php`, `ToggleAvailabilityTest.php`, `ShowDoctorTest.php`.

**Commands:**

```bash
docker exec dental_app vendor/bin/pint app/Http/Controllers/Doctors app/Http/Requests/Doctors routes
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker exec dental_app php artisan test --filter=Doctor
```

**Commit:** `feat(doctors): add DoctorController, FormRequests, and routes`

---

## Task 8 — DoctorSchedule + DoctorTimeOff controllers + routes

**Files:**

- `app/Http/Requests/Doctors/UpdateScheduleRequest.php` — authorize `manage-doctors`. Rules: `schedules` array of `[{day_of_week, start_time, end_time, is_active}]`, validation for each.
- `app/Http/Requests/Doctors/StoreTimeOffRequest.php` — authorize `manage-doctors`. Rules: `start_at`, `end_at` (after start), `reason`.
- `app/Http/Controllers/Doctors/DoctorScheduleController.php` — `edit($doctor)`, `update($request, $doctor)`.
- `app/Http/Controllers/Doctors/DoctorTimeOffController.php` — `index($doctor)`, `store`, `destroy`.
- Append routes in `routes/doctors.php`.

**Tests:** `tests/Feature/Doctors/UpdateScheduleTest.php`, `StoreTimeOffTest.php`, `DestroyTimeOffTest.php`.

**Commands:**

```bash
docker exec dental_app vendor/bin/pint app/Http app/Http/Requests routes
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker exec dental_app php artisan test --filter=Doctor
```

**Commit:** `feat(doctors): add schedule and time-off controllers with routes`

---

## Task 9 — Doctor index Vue page

**Files:**

- `resources/js/Pages/Doctors/Index.vue` — table with search + specialization filter, debounced `router.get`, color swatch, is_available badge, action buttons (View / Edit / Schedules / Time off / Toggle). New doctor button (admin only via `$page.props.auth.user.permissions`).

**Tests:** `resources/js/__tests__/Components/DoctorsIndexTable.test.js` — minimal test rendering with stub props asserting that admin sees "New doctor" and doctor doesn't.

**Commands:**

```bash
npm test -- --run
npm run lint
npx vite build
```

**Commit:** `feat(doctors): add Vue index page with search and filter`

---

## Task 10 — Doctor create / edit / show Vue pages

**Files:**

- `resources/js/Pages/Doctors/Create.vue` — form with all doctor + user fields. Color picker = 6 preset swatches + a custom input.
- `resources/js/Pages/Doctors/Edit.vue` — same form pre-populated.
- `resources/js/Pages/Doctors/Show.vue` — header card with name/email/phone/specialization/fee/color/is_available, two cards: schedule and upcoming time-off tables. Admin links to edit/schedules/time-off.

**Tests:** `resources/js/__tests__/Components/DoctorShow.test.js` — assert doctor info renders.

**Commands:**

```bash
npm test -- --run
npm run lint
npx vite build
```

**Commit:** `feat(doctors): add Vue create, edit, and show pages`

---

## Task 11 — Doctor schedule + time-off Vue pages

**Files:**

- `resources/js/Pages/Doctors/Schedules.vue` — per-day editable list of (start, end, active) slots, mirroring `WorkingHours.vue` style.
- `resources/js/Pages/Doctors/TimeOff.vue` — table of upcoming time-off + add-row form (datetime-local, reason).

**Commands:**

```bash
npm test -- --run
npm run lint
npx vite build
```

**Commit:** `feat(doctors): add Vue schedule and time-off management pages`

---

## Task 12 — AppointmentFormRequest + AppointmentController + routes

**Files:**

- `app/Http/Requests/Appointments/StoreAppointmentRequest.php` — authorize `manage-appointments`. Rules: `doctor_id` exists in `doctors` and `is_available`, `scheduled_at` (date, after now+1min, before now+1y), `duration_minutes` (in:15,30,60), `patient_name` required string max 255, `patient_phone` regex, `notes` nullable string.
- `app/Http/Requests/Appointments/UpdateAppointmentRequest.php` — same minus `scheduled_at` future-constraint.
- `app/Http/Controllers/Appointments/AppointmentController.php`
  - `index` — week view; reads `?from=YYYY-MM-DD` (defaults to today's week start), `?doctor_id`, `?view=week|day`. Returns events.
  - `day($date)` — delegates to index with `from=$date&view=day`.
  - `create` — load active doctors.
  - `store` — wrap in `DB::transaction` with `lockForUpdate` on `doctors` row + overlap re-check.
  - `show` — doctor scope.
  - `edit`, `update`, `destroy`.
- `routes/appointments.php` — wired per spec §7.

**Tests:** `tests/Feature/Appointments/CreateAppointmentTest.php`, `UpdateAppointmentTest.php`, `CancelAppointmentTest.php`, `ListAppointmentsTest.php`, `OverlapPreventionTest.php`.

**Commands:**

```bash
docker exec dental_app vendor/bin/pint app/Http/Controllers/Appointments app/Http/Requests/Appointments routes
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker exec dental_app php artisan test --filter=Appointment
```

**Commit:** `feat(appointments): add AppointmentController, FormRequests, and routes`

---

## Task 13 — AppointmentStatusController

**Files:**

- `app/Http/Requests/Appointments/UpdateStatusRequest.php` — authorize based on transition (use `cancel-appointments` for cancelled, `check-in-appointments` for check_in, `manage-appointments` for the rest). Rules: `status` in enum values, optional `cancelled_reason`.
- `app/Http/Controllers/Appointments/AppointmentStatusController.php` — `update($request, $appointment)`. Validates the transition via `AppointmentStatus::canTransitionTo`. Logs the change.

**Tests:** `tests/Feature/Appointments/StatusTransitionTest.php` — covers all valid + invalid transitions.

**Append route in `routes/appointments.php`.**

**Commands:**

```bash
docker exec dental_app vendor/bin/pint app/Http app/Http/Requests routes
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker exec dental_app php artisan test --filter=StatusTransition
```

**Commit:** `feat(appointments): add AppointmentStatusController with transition validation`

---

## Task 14 — AvailableSlotsController

**Files:**

- `app/Http/Controllers/Appointments/AvailableSlotsController.php` — `__invoke(Request $request)`. Implements the §5 algorithm. Returns `['slots' => [['start'=>…, 'end'=>…], …], 'slot_minutes' => N]`.
- `app/Support/Scheduling/SlotGenerator.php` — pure helper class that takes doctor, date, duration, and the time-off/existing appointments, returns the slot list. Easier to test.
- Tests: `tests/Feature/Appointments/AvailableSlotsTest.php` covering: empty schedule, doctor schedule override, time-off, existing appointments, buffer.

**Append route in `routes/appointments.php`.**

**Commands:**

```bash
docker exec dental_app vendor/bin/pint app/Http/Controllers/Appointments app/Support/Scheduling
docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker exec dental_app php artisan test --filter=AvailableSlots
```

**Commit:** `feat(appointments): add AvailableSlotsController and SlotGenerator`

---

## Task 15 — Appointment calendar Vue page (week + day views)

**Files:**

- `resources/js/Composables/useWeekRange.js` — helper that, given a date and a start day (Mon), returns `{ start, end, days: [{ date, label, iso }] }`.
- `resources/js/Composables/useAppointmentStatus.js` — `statusColor(status)` returns tailwind classes, `statusLabel(status)`.
- `resources/js/Pages/Appointments/Index.vue` — week view. Top bar: prev/next, doctor filter, view toggle, New appointment button. 7-column grid with 30-min rows.
- `resources/js/Pages/Appointments/Day.vue` — single column.

**Tests:** `resources/js/__tests__/Lib/appointmentStatus.test.js`, `useWeekRange.test.js`.

**Commands:**

```bash
npm test -- --run
npm run lint
npx vite build
```

**Commit:** `feat(appointments): add week and day calendar Vue pages`

---

## Task 16 — Appointment booking Vue page

**Files:**

- `resources/js/Pages/Appointments/Create.vue` — doctor select, date picker, duration select, slot select (fetches `/appointments/slots` on doctor/date/duration change), patient name, patient phone, notes. Submit posts to `/appointments`.

**Commands:**

```bash
npm test -- --run
npm run lint
npx vite build
```

**Commit:** `feat(appointments): add Vue booking page`

---

## Task 17 — Appointment show + edit Vue pages

**Files:**

- `resources/js/Pages/Appointments/Show.vue` — header with status badge, action buttons (gated by permission + current status using the same status composable), notes panel, activity log timeline. Status changes POST to `/appointments/{id}/status`.
- `resources/js/Pages/Appointments/Edit.vue` — editable fields, doctor change re-runs slot fetch.

**Tests:** `resources/js/__tests__/Components/AppointmentShow.test.js` — assert action visibility per status.

**Commands:**

```bash
npm test -- --run
npm run lint
npx vite build
```

**Commit:** `feat(appointments): add Vue show and edit pages`

---

## Task 18 — Update sidebar nav

**Files:**

- `resources/js/Composables/useNav.js` — replace the "Soon" Appointments entry with a real link: `{ label: 'Appointments', href: '/appointments', icon: 'calendar', permission: 'view-appointments' }`. Add new `view-appointments` to all three roles. Keep Patients/Billing/Inventory/Exchange as comingSoon.

Wait — the spec lists separate perms. We add `view-appointments` to the sidebar gate, granted to all roles. Update `RoleSeeder` to include it.

**Files:**

- `database/seeders/RoleSeeder.php` — add `view-appointments` to all three roles.

**Commands:**

```bash
docker exec dental_app php artisan db:seed --class=RoleSeeder
docker exec dental_app vendor/bin/pint database/seeders
docker exec dental_app php artisan test
```

**Commit:** `feat(nav): wire appointments route in sidebar nav`

---

## Task 19 — Backend tests (model, repo, controller, scope)

Already covered in tasks 7, 8, 12, 13, 14. Add the missing test:

- `tests/Feature/Appointments/DoctorScopeTest.php` — doctor only sees own appointments.

**Commands:**

```bash
docker exec dental_app vendor/bin/pint tests
docker exec dental_app php artisan test
```

**Commit:** `test(appointments): add doctor scope test for appointment visibility`

---

## Task 20 — Frontend tests for key composables

Already covered in tasks 9, 10, 15, 17. Add:

- `resources/js/__tests__/Components/AppointmentBookingForm.test.js` — minimal render with stub props.

**Commands:**

```bash
npm test -- --run
npm run lint
npx vite build
```

**Commit:** `test(appointments): add frontend test for booking form`

---

## Task 21 — Final quality gate

Run the full gate and fix any failures.

**Commands:**

```bash
docker exec dental_app composer quality
npm test
npm run lint
npx vite build
```

If everything passes, an empty final commit is not required; the previous task commits already include all the work. If any fix-up is needed, commit it with `chore(doctors-appointments): final quality gate fixes`.

**Commit:** `chore(doctors-appointments): final quality gate fixes` (only if needed)

---

## Summary

21 tasks, one commit per task, ~21 commits added.
