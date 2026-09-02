# Sub-Project 2: Patients & Treatment Records — Design Spec

Date: 2026-08-14
Owner: Sub-project 2
Status: Approved for implementation

## 1. Goals

Promote patient data from free-text fields on `appointments` to a first-class
`patients` table, then add a `treatment_records` table that records each
clinical visit for a patient. Update the appointment booking flow to pick an
existing patient (or quickly create a new one). Give staff a patient profile
that aggregates contact info, appointments, and the treatment history.

Out of scope (deferred to later sub-projects):
- Billing, invoices, payments (sub-project 4)
- Inventory of supplies (sub-project 6)
- Public booking (sub-project 7)
- Patient portal (sub-project 8)
- File attachments / X-ray images on records (sub-project 9)

## 2. Data Model

### 2.1 `patients` table

| Column                       | Type             | Notes                                                                 |
|------------------------------|------------------|-----------------------------------------------------------------------|
| `id`                         | bigint pk        |                                                                       |
| `patient_code`               | string(32)       | unique, format `P-YYYY-####` (auto-generated on create)               |
| `name`                       | string(255)      | required                                                              |
| `phone`                      | string(32)       | nullable (recommended)                                                |
| `email`                      | string(255)      | nullable                                                              |
| `date_of_birth`              | date             | nullable                                                              |
| `gender`                     | string(16)       | nullable; `enum` semantically: `male` / `female` / `other`            |
| `address_line_1`             | string(255)      | nullable                                                              |
| `address_line_2`             | string(255)      | nullable                                                              |
| `city`                       | string(128)      | nullable                                                              |
| `state`                      | string(128)      | nullable                                                              |
| `postal_code`                | string(32)       | nullable                                                              |
| `country`                    | string(64)       | nullable                                                              |
| `emergency_contact_name`     | string(255)      | nullable                                                              |
| `emergency_contact_phone`    | string(32)       | nullable                                                              |
| `medical_history`            | longText         | nullable — allergies, conditions, medications                         |
| `dental_history`             | longText         | nullable — previous treatments, concerns                              |
| `notes`                      | longText         | nullable — general                                                    |
| `is_active`                  | boolean          | default `true`                                                        |
| `created_at`, `updated_at`   | timestamps       |                                                                       |
| `deleted_at`                 | timestamp        | soft delete                                                           |

Indexes: `(name)`, `(phone)`, `(email)`, `(patient_code)`, `(is_active)`.

### 2.2 `treatment_records` table

| Column                 | Type            | Notes                                                                                          |
|------------------------|-----------------|------------------------------------------------------------------------------------------------|
| `id`                   | bigint pk       |                                                                                                |
| `patient_id`           | fk → patients   | `constrained()->restrictOnDelete()`                                                            |
| `doctor_id`            | fk → doctors    | nullable, `constrained()->restrictOnDelete()`                                                   |
| `appointment_id`       | fk → appointments| nullable, `constrained()->nullOnDelete()` (walk-in visits OK)                                  |
| `visited_at`           | dateTime        | required                                                                                       |
| `chief_complaint`      | text            | nullable                                                                                       |
| `diagnosis`            | longText        | nullable                                                                                       |
| `treatment_notes`      | longText        | nullable                                                                                       |
| `tooth_chart`          | json            | nullable; shape `{teeth:[{fdi:int, procedures:[{code,name,surface?}]}]}`                        |
| `follow_up_date`       | date            | nullable                                                                                       |
| `status`               | string(16)      | default `in_progress`; enum: `in_progress`, `completed`, `cancelled`                           |
| `created_at`, `updated_at` | timestamps   |                                                                                                |

Indexes: `(patient_id, visited_at)`, `(doctor_id, visited_at)`, `(status)`.

### 2.3 `appointments` table — 3-step migration

Current columns include `patient_name` (string, NOT NULL), `patient_phone` (string,
nullable), and a placeholder `patient_id` (nullable, no FK). All three remain
during the migration; the placeholder FK gets a real constraint. Then in
subsequent migrations the legacy columns are dropped and the FK becomes
non-nullable. The order:

1. **`2026_08_14_000005_add_patient_id_fk_to_appointments_table.php`**
   - Drop the `patient_id` column that was added without FK in sub-project 1.
   - Re-add `patient_id` as `unsignedBigInteger` nullable.
   - `foreign('patient_id')->references('id')->on('patients')->nullOnDelete()`.
   - `index('patient_id')`.

2. **`2026_08_14_000006_backfill_patients_from_appointments_table.php`**
   - **Not a real SQL migration** — just a no-op `up`/`down` stub.
   - The actual data migration is performed by an artisan command
     `patients:backfill-from-appointments`. Running it from the migration keeps
     the same deploy script and avoids auto-running destructive data changes
     on every test bootstrap.
   - On `up` the migration calls `Artisan::call('patients:backfill-from-appointments')`
     and on `down` it calls the inverse (clear `patient_id` on every appointment).
   - **Dedupe rules (in the command):**
     1. Group all appointments by `phone` (when present and non-empty).
        - For each group, create a single Patient with that phone and the most
          common name (or first name if tied). Update every appointment in
          the group to point to it.
     2. For appointments with no phone, group by exact `name` (case-sensitive).
        - For each group, create a single Patient with that name and `phone = null`.
     3. Any appointments still unassigned become individual Patients
        (each its own patient).
   - The command logs counts: `patients_created`, `appointments_linked`,
     `ambiguous_groups` (groups with multiple distinct names or phones).
   - Reversible: the `down` sets `appointments.patient_id = NULL` and soft-deletes
     every Patient that was created (tracked via `patient_code LIKE 'MIG-%'`).

3. **`2026_08_14_000007_drop_legacy_patient_fields_from_appointments_table.php`**
   - Drop `patient_name`, `patient_phone`.
   - Add `patient_id` index check / migrate to `foreignId('patient_id')->constrained()->restrictOnDelete()`.
   - Run `php artisan migrate` would still need the backfill step to have set
     `patient_id` on every row; the migration `up` asserts no null `patient_id`s
     (using `DB::statement('ALTER TABLE ... NOT NULL')`).

### 2.4 Patient auto-generated code

Pattern `P-YYYY-####` (e.g. `P-2026-0001`). The `####` portion is the count of
patients created in the current year + 1, zero-padded to 4 digits. Implemented
in `Patient::booted()` creating event — for the rare collision case (concurrent
requests), catch `UniqueConstraintViolation` and retry up to 3 times.

## 3. Models

### 3.1 `App\Models\Patient`

- Soft deletes
- `HasFactory`, `LogsActivity`
- Casts: `date_of_birth` → `date`, `is_active` → `boolean`
- Relations: `appointments()`, `treatmentRecords()`, `creator()` (User)
- `patientCode()` method returns the auto code
- `scopeActive($q)` filter
- `scopeSearch($q, $term)` — searches name, phone, email, patient_code
- `scopeDuplicatesOf(self $candidate)` — used in the "warn but don't block"
  check on create

### 3.2 `App\Models\TreatmentRecord`

- No soft deletes (audit trail; record is cancelled not deleted)
- `HasFactory`, `LogsActivity`
- Casts: `visited_at` → `datetime`, `follow_up_date` → `date`, `tooth_chart` → `array`
- Relations: `patient()`, `doctor()`, `appointment()`
- `scopeForPatient($q, Patient $p)`, `scopeInProgress($q)`

## 4. Enums

### 4.1 `App\Enums\TreatmentStatus` (new)

```
case InProgress = 'in_progress';
case Completed  = 'completed';
case Cancelled  = 'cancelled';

public function label(): string
public function isTerminal(): bool
```

### 4.2 `App\Enums\Gender` (new)

```
case Male = 'male'; case Female = 'female'; case Other = 'other';
public function label(): string
```

## 5. Repositories

### 5.1 `App\Repositories\Contracts\PatientRepository`

```php
public function paginate(int $perPage, array $filters): LengthAwarePaginator;
public function create(array $data): Patient;
public function update(Patient $patient, array $data): Patient;
public function delete(Patient $patient): void;
public function search(string $term, int $limit = 10): array; // for /api/patients/search
public function findDuplicates(?string $phone, ?string $name, ?string $email, ?int $ignoreId = null): Collection;
```

### 5.2 `App\Repositories\Contracts\TreatmentRecordRepository`

```php
public function paginateForPatient(Patient $patient, int $perPage = 25): LengthAwarePaginator;
public function find(int $id): TreatmentRecord;
public function create(array $data): TreatmentRecord;
public function update(TreatmentRecord $record, array $data): TreatmentRecord;
public function markCompleted(TreatmentRecord $record, ?string $notes = null): TreatmentRecord;
public function cancel(TreatmentRecord $record, ?string $reason = null): TreatmentRecord;
```

Both implementations live in `App\Repositories\Eloquent\*`.

## 6. Controllers

### 6.1 `App\Http\Controllers\Patients\PatientController`

- `index` — `view-patients`
- `create` — `manage-patients`
- `store` — `manage-patients`
- `show($patient)` — `view-patients`; loads `appointments.doctor.user`, `treatmentRecords.doctor.user`
- `edit($patient)` — `manage-patients`
- `update` — `manage-patients`
- `destroy` — `manage-patients`; soft delete
- `search` (used by `/api/patients/search`) — `view-patients`

### 6.2 `App\Http\Controllers\Patients\TreatmentRecordController`

Routes are nested under `/patients/{patient}/treatment-records`. Actions:
- `index($patient)` — list treatment records for one patient (used by Vue timeline
  when paginated; profile page renders first N)
- `create($patient)` — `manage-treatment-records`
- `store($patient)` — `manage-treatment-records`
- `show($patient, $record)` — `view-treatment-records`
- `edit($patient, $record)` — `manage-treatment-records`; 403 if not in_progress
- `update($patient, $record)` — `manage-treatment-records`; 403 if not in_progress
- `complete($patient, $record)` — `manage-treatment-records`; POST shortcut
  that flips status to `completed`
- `cancel($patient, $record)` — `manage-treatment-records`; POST shortcut

All controllers inject their respective repository via constructor.

## 7. Form Requests

### 7.1 `StorePatientRequest` / `UpdatePatientRequest`

```
patient_code: nullable, string, max:32, unique:patients,patient_code
name:         required, string, max:255
phone:        nullable, string, max:32
email:        nullable, email, max:255
date_of_birth:nullable, date
gender:       nullable, in:male,female,other
address_line_1, address_line_2, city, state, postal_code, country:
              nullable, string, max:255/128/64
emergency_contact_name: nullable, string, max:255
emergency_contact_phone: nullable, string, max:32
medical_history, dental_history, notes: nullable, string
is_active:    boolean
```
Authorize checks `manage-patients`.

### 7.2 `StoreTreatmentRecordRequest` / `UpdateTreatmentRecordRequest`

```
patient_id:      required, exists:patients,id
doctor_id:       nullable, exists:doctors,id
appointment_id:  nullable, exists:appointments,id
visited_at:      required, date
chief_complaint: nullable, string, max:5000
diagnosis:       nullable, string
treatment_notes: nullable, string
tooth_chart:     nullable, array
tooth_chart.teeth: array
tooth_chart.teeth.*.fdi: required_with:tooth_chart.teeth.*, integer
tooth_chart.teeth.*.procedures: array
follow_up_date:  nullable, date
status:          required, in:in_progress,completed,cancelled
```
Authorize checks `manage-treatment-records`.

## 8. Permissions

| Permission                    | admin | doctor | receptionist |
|-------------------------------|-------|--------|--------------|
| `view-patients`               | yes   | yes    | yes          |
| `manage-patients`             | yes   | no     | yes          |
| `view-treatment-records`      | yes   | yes    | yes          |
| `manage-treatment-records`    | yes   | yes    | no           |

Added to `RoleSeeder` (extended, never replaced).

## 9. Routes

`routes/patients.php`:

```php
Route::middleware(['auth', 'active', 'permission:view-patients'])
    ->prefix('patients')->name('patients.')->group(function (): void {
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/api/search', [PatientController::class, 'search'])->name('api.search');
        Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-patients'])
    ->prefix('patients')->name('patients.')->group(function (): void {
        Route::get('/create', [PatientController::class, 'create'])->name('create');
        Route::post('/', [PatientController::class, 'store'])->name('store');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
        Route::patch('/{patient}', [PatientController::class, 'update'])->name('update');
        Route::delete('/{patient}', [PatientController::class, 'destroy'])->name('destroy');
    });
```

`routes/treatment-records.php`:

```php
Route::middleware(['auth', 'active', 'permission:view-treatment-records'])
    ->prefix('patients/{patient}/treatment-records')->name('patients.treatment-records.')->group(function (): void {
        Route::get('/', [TreatmentRecordController::class, 'index'])->name('index');
        Route::get('/{record}', [TreatmentRecordController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-treatment-records'])
    ->prefix('patients/{patient}/treatment-records')->name('patients.treatment-records.')->group(function (): void {
        Route::get('/create', [TreatmentRecordController::class, 'create'])->name('create');
        Route::post('/', [TreatmentRecordController::class, 'store'])->name('store');
        Route::get('/{record}/edit', [TreatmentRecordController::class, 'edit'])->name('edit');
        Route::patch('/{record}', [TreatmentRecordController::class, 'update'])->name('update');
        Route::post('/{record}/complete', [TreatmentRecordController::class, 'complete'])->name('complete');
        Route::post('/{record}/cancel', [TreatmentRecordController::class, 'cancel'])->name('cancel');
    });
```

Mounted in `routes/web.php`:
```
require __DIR__.'/patients.php';
require __DIR__.'/treatment-records.php';
```

## 10. Resources

`App\Http\Resources\PatientResource`:
```
id, patient_code, name, phone, email, date_of_birth, gender, full_address,
emergency_contact_name, emergency_contact_phone, medical_history, dental_history,
notes, is_active, age (computed), created_at, updated_at
```

`App\Http\Resources\TreatmentRecordResource`:
```
id, patient_id, doctor_id, doctor_name, appointment_id, visited_at, chief_complaint,
diagnosis, treatment_notes, tooth_chart, follow_up_date, status, status_label,
created_at, updated_at
```

`App\Http\Resources\AppointmentResource` — updated to use `patient` relation
instead of legacy columns. Output includes `patient.id`, `patient.name`,
`patient.phone`, `patient.patient_code`. Old `patient_name` / `patient_phone`
fields are removed.

## 11. Appointment Booking UI

The booking form (`Appointments/Create.vue` and `Edit.vue`) replaces the
free-text `patient_name` / `patient_phone` fields with:

- A `UiSelect` populated via `GET /api/patients/search?q=...` (server-side
  filter, debounce 300ms; first option when no search returns the 10 most
  recently updated patients).
- An "Add new patient" button next to the picker that opens a `UiDialog`
  with a mini patient form (`StorePatientRequest` validation, AJAX submit).
  On success the dialog closes, the new patient is appended to the picker,
  and the picker selects it.
- The form posts `patient_id` to the backend; the request controller
  resolves it and ignores any legacy fields.

The `Edit.vue` page receives a serialized `patient` object so the picker
shows the current patient as pre-selected.

## 12. Sidebar

In `resources/js/Composables/useNav.js` the Patients entry loses
`comingSoon: true` and the `href` becomes `/patients`. The `permission`
becomes `view-patients`.

## 13. Frontend Pages

```
resources/js/Pages/Patients/Index.vue         # list with search + filter
resources/js/Pages/Patients/Create.vue        # full create form
resources/js/Pages/Patients/Edit.vue          # edit form
resources/js/Pages/Patients/Show.vue          # profile + appointments + timeline
resources/js/Pages/Patients/TreatmentRecords/Index.vue
resources/js/Pages/Patients/TreatmentRecords/Create.vue
resources/js/Pages/Patients/TreatmentRecords/Show.vue
resources/js/Pages/Patients/TreatmentRecords/Edit.vue
resources/js/Pages/Patients/QuickAddDialog.vue  # used inside Appointments
```

Quick-add dialog: a small form for `name` + `phone` (the two minimum fields).
The `POST /patients` is a regular Inertia form post that, on success, emits
a `created` event with the new patient. The booking page listens and inserts
into its picker.

## 14. Testing Strategy

### Backend
- `tests/Feature/Patients/CreatePatientTest.php` — happy path + dup warn
- `tests/Feature/Patients/ListPatientsTest.php` — search + filter
- `tests/Feature/Patients/ShowPatientTest.php` — loads profile
- `tests/Feature/Patients/UpdatePatientTest.php`
- `tests/Feature/Patients/PatientSearchApiTest.php` — typeahead JSON
- `tests/Feature/Patients/BackfillCommandTest.php` — runs artisan command on
  a fake batch of appointments, asserts dedupe outcomes
- `tests/Feature/TreatmentRecords/CreateTreatmentRecordTest.php`
- `tests/Feature/TreatmentRecords/EditWhileInProgressTest.php`
- `tests/Feature/TreatmentRecords/CannotEditAfterCompletedTest.php`
- `tests/Unit/TreatmentStatusEnumTest.php`
- `tests/Unit/GenderEnumTest.php`
- Updated `tests/Feature/Appointments/CreateAppointmentTest.php` to use `patient_id`

### Frontend
- `resources/js/__tests__/Composables/useNav.spec.js` — updated to verify
  Patients is no longer `comingSoon`
- `resources/js/__tests__/Pages/Patients/Index.spec.js` — minimal smoke render
- `resources/js/__tests__/Lib/patientCode.spec.js` — test the format helper
  if we extract one

## 15. Migration Safety

The backfill command is wrapped in a `DB::transaction`. Counts are reported
back to the user via `$this->info()`. The command is also exposed to
artisan as `patients:backfill-from-appointments` so it can be re-run manually
if needed. The down direction in the migration undoes the linkage; a
follow-up manual cleanup can remove the migration Patient rows.

The migration is `up` safe:
- Step 1 only adds the column + index + FK
- Step 2 calls the artisan command in a transaction
- Step 3 runs `ALTER TABLE appointments MODIFY patient_id BIGINT UNSIGNED NOT NULL`
  which is wrapped in `DB::transaction` with an explicit assert that
  `COUNT(*) WHERE patient_id IS NULL = 0`. If that fails, the migration
  throws and rolls back.

## 16. Out of Scope / Decisions Deferred

- Tooth chart UI (just a JSON text field with a "validate JSON" hint and a
  textarea for the MVP; a visual chart comes later)
- File uploads on records
- Patient merge tool
- Bulk import
- Patient portal

## 17. Risks

| Risk | Mitigation |
|------|------------|
| Backfill creates duplicate patients when phone is null | Group by exact name; log warnings for groups with >1 unique name |
| Unique patient_code collision on heavy concurrent insert | Retry up to 3 times in `creating` event |
| SQLite test environment lacks some JSON ops | Use plain `array` cast for `tooth_chart`; tests avoid `JSON_EXTRACT` |
| Frontend date picker missing | Use `<input type="date">` like Appointments/Create does today |
| UI for editing JSON tooth chart is awkward | MVP: a JSON textarea with validation; future iteration adds a visual chart |
