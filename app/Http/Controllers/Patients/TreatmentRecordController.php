<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patients;

use App\Enums\TreatmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patients\StoreTreatmentRecordRequest;
use App\Http\Requests\Patients\UpdateTreatmentRecordRequest;
use App\Http\Resources\TreatmentRecordResource;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Repositories\Contracts\DoctorRepository;
use App\Repositories\Contracts\TreatmentRecordRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TreatmentRecordController extends Controller
{
    public function __construct(
        private readonly TreatmentRecordRepository $records,
        private readonly DoctorRepository $doctors,
    ) {}

    public function index(Patient $patient): Response
    {
        $page = $this->records->paginateForPatient($patient, 25);

        // Resource::collection() wraps the response in an AnonymousResourceCollection
        // which adds a {data: [...]} envelope; resolving each resource to a plain
        // array keeps the paginator shape Inertia expects (records.data[0].id, not
        // records.data[0].data.id).
        $rows = TreatmentRecordResource::collection($page->getCollection())
            ->collection
            ->map(fn (TreatmentRecordResource $r): array => $r->resolve())
            ->values();

        $page->setCollection($rows);

        return Inertia::render('Patients/TreatmentRecords/Index', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'records' => $page,
        ]);
    }

    public function create(Patient $patient): Response
    {
        return Inertia::render('Patients/TreatmentRecords/Create', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'doctors' => $this->doctors->listActive(),
            'statuses' => [
                ['value' => TreatmentStatus::InProgress->value, 'label' => TreatmentStatus::InProgress->label()],
                ['value' => TreatmentStatus::Completed->value, 'label' => TreatmentStatus::Completed->label()],
                ['value' => TreatmentStatus::Cancelled->value, 'label' => TreatmentStatus::Cancelled->label()],
            ],
        ]);
    }

    public function store(StoreTreatmentRecordRequest $request, Patient $patient): RedirectResponse
    {
        $data = $request->validated();
        $data['patient_id'] = $patient->id;
        $record = $this->records->create($data);

        return redirect()
            ->route('patients.treatment-records.show', [$patient, $record])
            ->with('success', 'Treatment record created.');
    }

    public function show(Patient $patient, TreatmentRecord $record): Response
    {
        $this->ensureBelongs($patient, $record);
        $record->load(['patient', 'doctor.user', 'appointment']);

        return Inertia::render('Patients/TreatmentRecords/Show', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'record' => (new TreatmentRecordResource($record))->resolve(),
        ]);
    }

    public function edit(Patient $patient, TreatmentRecord $record): Response|RedirectResponse
    {
        $this->ensureBelongs($patient, $record);
        $record->load(['patient', 'doctor.user', 'appointment']);

        if (! $record->isEditable()) {
            return redirect()
                ->route('patients.treatment-records.show', [$patient, $record])
                ->with('error', 'This record can no longer be edited.');
        }

        return Inertia::render('Patients/TreatmentRecords/Edit', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'record' => (new TreatmentRecordResource($record))->resolve(),
            'doctors' => $this->doctors->listActive(),
            'statuses' => [
                ['value' => TreatmentStatus::InProgress->value, 'label' => TreatmentStatus::InProgress->label()],
                ['value' => TreatmentStatus::Completed->value, 'label' => TreatmentStatus::Completed->label()],
                ['value' => TreatmentStatus::Cancelled->value, 'label' => TreatmentStatus::Cancelled->label()],
            ],
        ]);
    }

    public function update(UpdateTreatmentRecordRequest $request, Patient $patient, TreatmentRecord $record): RedirectResponse
    {
        $this->ensureBelongs($patient, $record);

        if (! $record->isEditable()) {
            return redirect()
                ->route('patients.treatment-records.show', [$patient, $record])
                ->with('error', 'This record can no longer be edited.');
        }

        $this->records->update($record, $request->validated());

        return redirect()
            ->route('patients.treatment-records.show', [$patient, $record])
            ->with('success', 'Treatment record updated.');
    }

    public function complete(Patient $patient, TreatmentRecord $record): RedirectResponse
    {
        $this->ensureBelongs($patient, $record);

        if (! $record->isEditable()) {
            return redirect()
                ->route('patients.treatment-records.show', [$patient, $record])
                ->with('error', 'Only in-progress records can be completed.');
        }

        $this->records->markCompleted($record);

        return redirect()
            ->route('patients.treatment-records.show', [$patient, $record])
            ->with('success', 'Treatment record marked as completed.');
    }

    public function cancel(Patient $patient, TreatmentRecord $record): RedirectResponse
    {
        $this->ensureBelongs($patient, $record);

        if (! $record->isEditable()) {
            return redirect()
                ->route('patients.treatment-records.show', [$patient, $record])
                ->with('error', 'Only in-progress records can be cancelled.');
        }

        $this->records->cancel($record);

        return redirect()
            ->route('patients.treatment-records.show', [$patient, $record])
            ->with('success', 'Treatment record cancelled.');
    }

    private function ensureBelongs(Patient $patient, TreatmentRecord $record): void
    {
        abort_unless((int) $record->patient_id === (int) $patient->id, 404);
    }
}
