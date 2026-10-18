<?php

declare(strict_types=1);

namespace App\Http\Controllers\Prescriptions;

use App\Enums\DosageFrequency;
use App\Enums\MealRelation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Prescriptions\StorePrescriptionRequest;
use App\Http\Requests\Prescriptions\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Patient;
use App\Models\Prescription;
use App\Repositories\Contracts\DoctorRepository;
use App\Repositories\Contracts\PrescriptionRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PrescriptionController extends Controller
{
    public function __construct(
        private readonly PrescriptionRepository $prescriptions,
        private readonly DoctorRepository $doctors,
    ) {}

    public function index(Patient $patient): Response
    {
        $page = $this->prescriptions->paginateForPatient($patient, 25);

        $rows = PrescriptionResource::collection($page->getCollection())
            ->collection
            ->map(fn (PrescriptionResource $r): array => $r->resolve())
            ->values();
        $page->setCollection($rows);

        return Inertia::render('Prescriptions/Index', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'prescriptions' => $page,
        ]);
    }

    public function create(Patient $patient): Response
    {
        $contextAppointmentId = request()->integer('appointment_id') ?: null;

        return Inertia::render('Prescriptions/Create', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'doctors' => $this->doctors->listActive(),
            'frequencyOptions' => DosageFrequency::options(),
            'mealRelationOptions' => MealRelation::options(),
            'contextAppointmentId' => $contextAppointmentId,
        ]);
    }

    public function store(StorePrescriptionRequest $request, Patient $patient): RedirectResponse
    {
        $this->prescriptions->create($patient, (int) $request->user()->id, $request->validated());

        return redirect()
            ->route('patients.prescriptions.index', [$patient])
            ->with('success', 'Prescription created.');
    }

    public function show(Patient $patient, Prescription $prescription): Response
    {
        $this->ensureBelongs($patient, $prescription);
        $prescription->load(['patient', 'doctor.user', 'appointment', 'items.medicine', 'creator']);

        return Inertia::render('Prescriptions/Show', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'prescription' => (new PrescriptionResource($prescription))->resolve(),
        ]);
    }

    public function edit(Patient $patient, Prescription $prescription): Response|RedirectResponse
    {
        $this->ensureBelongs($patient, $prescription);
        $prescription->load(['patient', 'doctor.user', 'appointment', 'items.medicine', 'creator']);

        if (! $prescription->isEditable()) {
            return redirect()
                ->route('patients.prescriptions.show', [$patient, $prescription])
                ->with('error', 'Only draft prescriptions can be edited.');
        }

        return Inertia::render('Prescriptions/Edit', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
            ],
            'prescription' => (new PrescriptionResource($prescription))->resolve(),
            'doctors' => $this->doctors->listActive(),
            'frequencyOptions' => DosageFrequency::options(),
            'mealRelationOptions' => MealRelation::options(),
        ]);
    }

    public function update(UpdatePrescriptionRequest $request, Patient $patient, Prescription $prescription): RedirectResponse
    {
        $this->ensureBelongs($patient, $prescription);

        if (! $prescription->isEditable()) {
            return redirect()
                ->route('patients.prescriptions.show', [$patient, $prescription])
                ->with('error', 'Only draft prescriptions can be edited.');
        }

        $this->prescriptions->update($prescription, $request->validated());

        return redirect()
            ->route('patients.prescriptions.show', [$patient, $prescription])
            ->with('success', 'Prescription updated.');
    }

    public function issue(Patient $patient, Prescription $prescription): RedirectResponse
    {
        $this->ensureBelongs($patient, $prescription);

        if (! $prescription->isEditable()) {
            return redirect()
                ->route('patients.prescriptions.show', [$patient, $prescription])
                ->with('error', 'Only draft prescriptions can be issued.');
        }

        $this->prescriptions->issue($prescription);

        return redirect()
            ->route('patients.prescriptions.show', [$patient, $prescription])
            ->with('success', 'Prescription issued.');
    }

    public function cancel(Patient $patient, Prescription $prescription): RedirectResponse
    {
        $this->ensureBelongs($patient, $prescription);

        if ($prescription->status->isTerminal() && $prescription->status->value !== 'cancelled') {
            return redirect()
                ->route('patients.prescriptions.show', [$patient, $prescription])
                ->with('error', 'Only draft or issued prescriptions can be cancelled.');
        }

        $this->prescriptions->cancel($prescription);

        return redirect()
            ->route('patients.prescriptions.show', [$patient, $prescription])
            ->with('success', 'Prescription cancelled.');
    }

    private function ensureBelongs(Patient $patient, Prescription $prescription): void
    {
        abort_unless((int) $prescription->patient_id === (int) $patient->id, 404);
    }
}
