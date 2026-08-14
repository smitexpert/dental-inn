# Sub-Project 2: Patients & Treatment Records — Implementation Plan

Date: 2026-08-14
Related spec: `docs/superpowers/specs/2026-08-14-patients-treatment-records-design.md`

Conventions:
- Quality gate: `composer quality` (= pint --test + phpstan + tests)
- One commit per task (commit message body lists exact files)
- Run tests via `docker exec dental_app php artisan test --testsuite=Feature`
  or `composer test`; backend tests use SQLite in `database/database.sqlite`
- Commands like `php artisan <cmd>` are run via
  `docker exec dental_app php artisan <cmd>`
- All new models get `@property` PHPDoc; new FormRequests implement
  `rules()` + `authorize()`

---

## Task 1: Enums + Patient model + migration

Files:
- `app/Enums/TreatmentStatus.php` (new)
- `app/Enums/Gender.php` (new)
- `app/Models/Patient.php` (new)
- `database/migrations/2026_08_14_000005_create_patients_table.php` (new)

Steps:
1. Create `TreatmentStatus` enum with `InProgress`, `Completed`, `Cancelled`
   plus `label()` and `isTerminal()` methods (mirror `AppointmentStatus`).
2. Create `Gender` enum with `Male`, `Female`, `Other` plus `label()`.
3. Create `Patient` model with `SoftDeletes`, `HasFactory`, `LogsActivity`,
   fillable fields, casts (`date_of_birth` → date, `is_active` → boolean),
   relations (`appointments`, `treatmentRecords`), scopes (`active`, `search`,
   `duplicatesOf`), and a `booted()` hook that auto-generates `patient_code`
   in `P-YYYY-####` format on creating, with retry on unique collision.
4. Create migration:
   - `id`, `patient_code` (unique), `name`, `phone` nullable, `email`
     nullable, `date_of_birth` nullable, `gender` nullable, address fields,
     emergency contact fields, `medical_history` longText, `dental_history`
     longText, `notes` longText, `is_active` default true, timestamps,
     `deleted_at`. Indexes on `name`, `phone`, `email`, `is_active`.

Tests:
- `tests/Unit/TreatmentStatusEnumTest.php` (asserts labels and `isTerminal`)
- `tests/Unit/GenderEnumTest.php` (asserts labels)

Verify:
- `docker exec dental_app php artisan test --filter=TreatmentStatusEnumTest`
- `docker exec dental_app php artisan test --filter=GenderEnumTest`

Commit: `feat(patients): enums + Patient model + migration`

---

## Task 2: TreatmentRecord model + migration

Files:
- `app/Models/TreatmentRecord.php` (new)
- `database/migrations/2026_08_14_000006_create_treatment_records_table.php` (new)

Steps:
1. Create `TreatmentRecord` model with `HasFactory`, `LogsActivity`,
   fillable, casts (`visited_at` → datetime, `follow_up_date` → date,
   `tooth_chart` → array, `status` → `TreatmentStatus` enum).
2. Relations: `patient()`, `doctor()`, `appointment()`.
3. Scopes: `forPatient($q, Patient $p)`, `inProgress($q)`.
4. Migration:
   - `id`, `patient_id` fk (`constrained()->restrictOnDelete`),
     `doctor_id` fk nullable (`constrained()->restrictOnDelete`),
     `appointment_id` fk nullable (`constrained()->nullOnDelete`),
     `visited_at`, `chief_complaint` text, `diagnosis` longText,
     `treatment_notes` longText, `tooth_chart` json, `follow_up_date` date,
     `status` string default `in_progress`, timestamps.
   - Indexes `(patient_id, visited_at)`, `(doctor_id, visited_at)`, `(status)`.

Tests: none (covered indirectly in later feature tests).

Verify: `docker exec dental_app php artisan migrate` runs cleanly.

Commit: `feat(treatment-records): TreatmentRecord model + migration`

---

## Task 3: Add patient_id FK to appointments + drop legacy FK placeholder

Files:
- `database/migrations/2026_08_14_000007_add_patient_id_fk_to_appointments_table.php` (new)

Steps:
1. Drop the existing unindexed `patient_id` column.
2. Re-add `patient_id` as `unsignedBigInteger` nullable.
3. `foreign('patient_id')->references('id')->on('patients')->nullOnDelete()`.
4. `index('patient_id')`.

Verify: `docker exec dental_app php artisan migrate` succeeds.

Commit: `feat(appointments): wire real patient_id FK`

---

## Task 4: Backfill artisan command

Files:
- `app/Console/Commands/BackfillPatientsFromAppointments.php` (new)

Steps:
1. Signature: `patients:backfill-from-appointments`.
2. Group appointments by phone (non-empty), per group create one Patient
   with most-common name, link all appointments in group.
3. Remaining un-linked appointments (no phone) group by name, create one
   Patient per group, link all.
4. Any still-unlinked become individual Patients.
5. Use `DB::transaction`; report counts via `$this->info`.
6. `down` not part of the command (handled in next task's migration).

Tests:
- `tests/Feature/Patients/BackfillCommandTest.php`:
  - Seeds 6 appointments (3 with same phone + 2 names, 1 different phone,
    1 with no phone but shared name, 1 walk-in).
  - Runs the command, asserts:
    - 3 patients created (phone A, phone B, name "John Smith")
    - All appointments have patient_id set
  - Add a "reversible" test: re-link with phone changed, re-run, ensure
    the existing patients are kept (idempotent), and new patients only
    created when truly new.

Verify: `docker exec dental_app php artisan test --filter=BackfillCommandTest`

Commit: `feat(patients): backfill-from-appointments command`

---

## Task 5: Backfill migration + drop legacy fields

Files:
- `database/migrations/2026_08_14_000008_backfill_patients_data.php` (new)
- `database/migrations/2026_08_14_000009_drop_legacy_patient_fields_from_appointments.php` (new)

Steps:
1. First migration `up` runs `Artisan::call('patients:backfill-from-appointments')`.
   `down` sets all `appointments.patient_id = NULL` and soft-deletes any
   Patient where `patient_code` starts with `P-2026-` AND `created_at` within
   the last 5 minutes (best-effort undo).
2. Second migration:
   - Assert no `appointments.patient_id IS NULL`.
   - `dropColumn('patient_name')`, `dropColumn('patient_phone')`.
   - Replace the soft FK with
     `$table->foreignId('patient_id')->nullable(false)->change()`.
     For SQLite, since ALTER TABLE is limited, we use
     `DB::statement(...)` to recreate the table without the legacy columns
     and with a strict NOT NULL.
   - In `down`: re-add the two string columns as nullable, drop the FK,
     make `patient_id` nullable again.

Verify: `docker exec dental_app php artisan migrate:rollback --step=2` then
`migrate` works in both directions.

Commit: `feat(appointments): backfill + drop legacy patient fields`

---

## Task 6: Patient + TreatmentRecord factories

Files:
- `database/factories/PatientFactory.php` (new)
- `database/factories/TreatmentRecordFactory.php` (new)

Steps:
1. `PatientFactory` provides a `definition()` with name, phone, email,
   `date_of_birth`, gender, `is_active = true`, address fields. `inactive()`
   state. `withMedicalHistory()` state.
2. `TreatmentRecordFactory` builds a Patient + optional Doctor +
   optional Appointment, sets `visited_at = now()`, status `in_progress`,
   `chief_complaint`. `completed()` and `cancelled()` states.

Verify: `docker exec dental_app php artisan tinker --execute="echo App\Models\Patient::factory()->create()->id;"`

Commit: `test(patients): factories`

---

## Task 7: Update Appointment model + repository

Files:
- `app/Models/Appointment.php`
- `app/Http/Resources/AppointmentResource.php`
- `app/Repositories/Contracts/AppointmentRepository.php`
- `app/Repositories/Eloquent/AppointmentEloquentRepository.php`

Steps:
1. Remove `patient_name`, `patient_phone` from `$fillable`. Add `patient_id`.
2. Add `patient()` belongs-to relation.
3. Update `getActivitylogOptions` to log `patient_id` instead of name.
4. Update `AppointmentResource` to surface `patient` object (id, name, phone,
   patient_code) and remove the old fields.
5. Update `AppointmentRepository::paginate` to filter by `patient.name` or
   `patient.phone` instead of legacy columns. Update `overlappingCount` is
   unaffected.

Verify: `docker exec dental_app php artisan test --testsuite=Feature --filter=Appointments`

Commit: `refactor(appointments): switch to Patient relation`

---

## Task 8: Update Appointment FormRequests + controller

Files:
- `app/Http/Requests/Appointments/StoreAppointmentRequest.php`
- `app/Http/Requests/Appointments/UpdateAppointmentRequest.php`
- `app/Http/Controllers/Appointments/AppointmentController.php`

Steps:
1. Replace `patient_name` + `patient_phone` with
   `patient_id: required, exists:patients,id`.
2. Controller's `store` and `update` already pass `$data` to the repository
   — no change needed beyond the request shape.

Verify: tests in Task 11 will cover.

Commit: `refactor(appointments): FormRequest uses patient_id`

---

## Task 9: Patient repository (Contract + Eloquent)

Files:
- `app/Repositories/Contracts/PatientRepository.php` (new)
- `app/Repositories/Eloquent/PatientEloquentRepository.php` (new)

Steps:
1. `paginate(perPage, filters)` supports `q`, `is_active`.
2. `create`, `update`, `delete`.
3. `search(term, limit)` returns array of `{id, patient_code, name, phone}`
   for typeahead.
4. `findDuplicates(phone, name, email, ignoreId?)` — matches by phone (exact)
   OR by name+email combination.

Verify: covered by feature tests in Tasks 11/13.

Commit: `feat(patients): repository`

---

## Task 10: TreatmentRecord repository (Contract + Eloquent)

Files:
- `app/Repositories/Contracts/TreatmentRecordRepository.php` (new)
- `app/Repositories/Eloquent/TreatmentRecordEloquentRepository.php` (new)

Steps:
1. `paginateForPatient(Patient $p, int $perPage)`.
2. `find(int $id)` with relations.
3. `create`, `update`.
4. `markCompleted`, `cancel` set status + log activity.

Verify: covered by feature tests in Task 17.

Commit: `feat(treatment-records): repository`

---

## Task 11: Patient + TreatmentRecord resources

Files:
- `app/Http/Resources/PatientResource.php` (new)
- `app/Http/Resources/TreatmentRecordResource.php` (new)

Steps:
1. PatientResource surfaces all columns + computed `age` (years) +
   `full_address` (joined).
2. TreatmentRecordResource surfaces all fields + computed `status_label`,
   `doctor_name`.

Verify: typecheck via PHPStan.

Commit: `feat(patients): resources`

---

## Task 12: Patient FormRequests

Files:
- `app/Http/Requests/Patients/StorePatientRequest.php` (new)
- `app/Http/Requests/Patients/UpdatePatientRequest.php` (new)
- `app/Http/Requests/Patients/QuickStorePatientRequest.php` (new)

Steps:
1. StorePatientRequest + UpdatePatientRequest: full validation set per
   spec section 7.1. Authorize checks `manage-patients`.
2. UpdatePatientRequest adds `patient_id` to `Rule::unique` ignore.
3. QuickStorePatientRequest: only `name` + `phone` required, used for
   the quick-add dialog. Authorize checks `manage-patients`.

Verify: covered by feature tests in Task 13.

Commit: `feat(patients): FormRequests`

---

## Task 13: TreatmentRecord FormRequests

Files:
- `app/Http/Requests/Patients/StoreTreatmentRecordRequest.php` (new)
- `app/Http/Requests/Patients/UpdateTreatmentRecordRequest.php` (new)

Steps:
1. Rules per spec section 7.2. Authorize checks `manage-treatment-records`.

Verify: covered by feature tests in Task 17.

Commit: `feat(treatment-records): FormRequests`

---

## Task 14: Patient controller + search endpoint

Files:
- `app/Http/Controllers/Patients/PatientController.php` (new)
- `routes/patients.php` (new — partial, treatments still pending)

Steps:
1. Controller injects `PatientRepository`.
2. `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
3. `search` returns JSON `{ data: [{id, patient_code, name, phone}] }`.
4. Mount view-permission and manage-permission route groups in
   `routes/patients.php`.
5. Wire `routes/patients.php` in `routes/web.php`.

Verify:
- `docker exec dental_app php artisan test --filter=PatientControllerTest`
  (added in Task 16)
- `curl localhost/api/patients/search?q=...` returns JSON (manual via
  existing Feature tests)

Commit: `feat(patients): controller + routes`

---

## Task 15: Patient feature tests

Files:
- `tests/Feature/Patients/CreatePatientTest.php`
- `tests/Feature/Patients/ListPatientsTest.php`
- `tests/Feature/Patients/ShowPatientTest.php`
- `tests/Feature/Patients/UpdatePatientTest.php`
- `tests/Feature/Patients/PatientSearchApiTest.php`
- `tests/Feature/Patients/DuplicateWarnTest.php` (light, asserts HTTP 201
  even with duplicate — duplicates warned only in UI)

Steps:
1. Each test sets up a role with appropriate permission, creates a user,
   `actingAs`, exercises the endpoint, asserts response + DB state.
2. PatientSearchApiTest asserts the JSON shape and limit=10 by default.

Verify: `docker exec dental_app php artisan test --filter=Patients`

Commit: `test(patients): feature tests`

---

## Task 16: Quality gate checkpoint #1

Run:
- `composer quality`
- `npx vite build`

Fix any pint / phpstan / test failures before continuing.

Commit: `chore(patients): quality gate 1`

---

## Task 17: TreatmentRecord controller + routes

Files:
- `app/Http/Controllers/Patients/TreatmentRecordController.php` (new)
- `routes/treatment-records.php` (new)

Steps:
1. Controller actions per spec section 6.2.
2. Routes nested under `/patients/{patient}/treatment-records/...`.
3. Mount in `routes/web.php`.

Verify: covered by feature tests in Task 18.

Commit: `feat(treatment-records): controller + routes`

---

## Task 18: TreatmentRecord feature tests

Files:
- `tests/Feature/TreatmentRecords/CreateTreatmentRecordTest.php`
- `tests/Feature/TreatmentRecords/ShowTreatmentRecordTest.php`
- `tests/Feature/TreatmentRecords/UpdateWhileInProgressTest.php`
- `tests/Feature/TreatmentRecords/CannotEditAfterCompletedTest.php`
- `tests/Feature/TreatmentRecords/CompleteAndCancelTest.php`

Steps:
1. Each test sets up Patient + Doctor, exercises endpoint, asserts DB state.
2. Edit-after-completed test asserts 403 / redirect with error.

Verify: `docker exec dental_app php artisan test --filter=TreatmentRecords`

Commit: `test(treatment-records): feature tests`

---

## Task 19: Update AppointmentFactory + Appointment feature tests

Files:
- `database/factories/AppointmentFactory.php`
- `tests/Feature/Appointments/CreateAppointmentTest.php`
- `tests/Feature/Appointments/AvailableSlotsTest.php`
- `tests/Feature/Appointments/AppointmentStatusTest.php`

Steps:
1. AppointmentFactory now creates a Patient and uses `patient_id`.
2. Update existing tests to pass `patient_id` and create a Patient.
3. Update `overlappingCount` tests are unaffected.

Verify: `docker exec dental_app php artisan test --testsuite=Feature --filter=Appointments`

Commit: `test(appointments): use Patient in fixtures`

---

## Task 20: Extend RoleSeeder

Files:
- `database/seeders/RoleSeeder.php`

Steps:
1. Add 4 new permissions to `$permissions`.
2. Admin gets all.
3. Doctor gets `view-patients`, `view-treatment-records`, `manage-treatment-records`.
4. Receptionist gets `view-patients`, `view-treatment-records`, `manage-patients`.

Verify: `docker exec dental_app php artisan db:seed --class=RoleSeeder`

Commit: `feat(permissions): add patient + treatment-record perms`

---

## Task 21: Frontend — sidebar + nav tests

Files:
- `resources/js/Composables/useNav.js`
- `resources/js/__tests__/Composables/useNav.spec.js`

Steps:
1. Update `Patients` nav entry: `href: '/patients'`, drop `comingSoon`,
   `permission: 'view-patients'`.
2. Update test to assert Patients is present and not marked as
   `comingSoon`.

Verify: `npm test -- useNav`

Commit: `feat(nav): Patients link live`

---

## Task 22: Frontend — Patients Index + Create + Edit + Show

Files:
- `resources/js/Pages/Patients/Index.vue` (new)
- `resources/js/Pages/Patients/Create.vue` (new)
- `resources/js/Pages/Patients/Edit.vue` (new)
- `resources/js/Pages/Patients/Show.vue` (new)

Steps:
1. `Index.vue` — search input, active filter, table with patient_code, name,
   phone, email, age, status, link to profile. Mirror `Doctors/Index.vue`
   patterns.
2. `Create.vue` — full patient form, mirrors `Doctors/Create.vue`.
3. `Edit.vue` — same form pre-filled.
4. `Show.vue` — three sections: contact card, appointments list
   (compact), treatment records timeline (chronological list, with status
   badges, click to view). Includes a "New treatment record" button if the
   user has `manage-treatment-records`.

Verify: `npx vite build` succeeds; new test in Task 26.

Commit: `feat(ui): Patients pages`

---

## Task 23: Frontend — QuickAddDialog

Files:
- `resources/js/Components/PatientQuickAddDialog.vue` (new)
- `resources/js/__tests__/Components/PatientQuickAddDialog.spec.js` (new)

Steps:
1. Dialog with `name` + `phone` fields, "Create" button posts to
   `/patients` (Inertia), emits `created` with patient on success.
2. Test asserts it renders and emits.

Commit: `feat(ui): PatientQuickAddDialog`

---

## Task 24: Frontend — TreatmentRecord pages

Files:
- `resources/js/Pages/Patients/TreatmentRecords/Index.vue` (new)
- `resources/js/Pages/Patients/TreatmentRecords/Create.vue` (new)
- `resources/js/Pages/Patients/TreatmentRecords/Show.vue` (new)
- `resources/js/Pages/Patients/TreatmentRecords/Edit.vue` (new)

Steps:
1. Index lists all records for a patient (paginated).
2. Create — form with chief_complaint, diagnosis, treatment_notes, status,
   tooth_chart textarea (JSON), visited_at, follow_up_date, doctor_id.
3. Show — full record view; "Edit" only if `status === 'in_progress'`.
4. Edit — pre-filled form, submit posts to update.

Commit: `feat(ui): TreatmentRecord pages`

---

## Task 25: Frontend — update Appointment booking UI

Files:
- `resources/js/Pages/Appointments/Create.vue`
- `resources/js/Pages/Appointments/Edit.vue`
- `resources/js/Pages/Appointments/Show.vue` (display `patient` from resource)

Steps:
1. Replace `patient_name` + `patient_phone` fields with a `UiSelect`
   populated via `GET /api/patients/search?q=...` (debounce 300ms).
2. Add an "Add new patient" button next to it that opens
   `PatientQuickAddDialog`; on `created` event, append to options and
   select.
3. Pass `patient` object to Edit.vue (so picker can preselect).
4. `Show.vue` reads `appointment.patient` (replaces `patient_name` display).

Verify: `npx vite build`.

Commit: `feat(ui): appointment booking uses patient picker`

---

## Task 26: Frontend tests + quality gate #2

Files:
- `resources/js/__tests__/Pages/Patients/Index.spec.js` (new, smoke)
- `resources/js/__tests__/Pages/Patients/Show.spec.js` (new, smoke)

Run:
- `npm test`
- `npm run lint`
- `npx vite build`
- `composer quality`

Fix anything that fails.

Commit: `chore(patients): quality gate 2`

---

## Task 27: Final verification

Run all 4 quality gates in order:
1. `composer quality`
2. `npm test`
3. `npm run lint`
4. `npx vite build`

If any fail, fix and recommit. If all pass, commit an empty marker:
`git commit --allow-empty -m "feat(patients-treatment): sub-project 2 complete"`

---

## Quality bar reminders

- No `TODO` comments in committed code
- PHPStan level 6 — 0 errors. Add `@property` PHPDoc to new models.
- Pint clean
- ESLint clean
- Every test asserts something specific
- Every FormRequest has `authorize()` + `rules()`
- Soft FK enforcement: `restrictOnDelete` for Patient, Doctor on
  TreatmentRecord; `nullOnDelete` for Appointment
