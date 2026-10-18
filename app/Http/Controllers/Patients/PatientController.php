<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patients\QuickStorePatientRequest;
use App\Http\Requests\Patients\StorePatientRequest;
use App\Http\Requests\Patients\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Repositories\Contracts\PatientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function __construct(private readonly PatientRepository $patients) {}

    public function index(): Response
    {
        $page = $this->patients->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'is_active']),
        );

        $kpis = [
            ['id' => 'total', 'label' => 'Total', 'value' => (string) Patient::count()],
            ['id' => 'new_month', 'label' => 'New this month',
                'value' => (string) Patient::whereMonth('created_at', now()->month)->count()],
            ['id' => 'active', 'label' => 'Active', 'value' => (string) Patient::where('is_active', true)->count()],
            ['id' => 'allergies', 'label' => 'With allergies',
                'value' => (string) Patient::whereNotNull('medical_history')->where('medical_history', '!=', '')->count()],
        ];

        return Inertia::render('Patients/Index', [
            'patients' => [
                'data' => PatientResource::collection($page->getCollection())->resolve(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
            'filters' => request()->only(['q', 'is_active']),
            'kpis' => $kpis,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Patients/Create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $patient = $this->patients->create($data);

        return redirect()->route('patients.show', $patient)->with('success', 'Patient created.');
    }

    public function show(Patient $patient): Response
    {
        $patient->load([
            'appointments' => fn ($q) => $q->with('doctor.user')->limit(20),
            'treatmentRecords' => fn ($q) => $q->with('doctor.user')->limit(20),
            'prescriptions' => fn ($q) => $q->with(['items', 'doctor.user'])->limit(20),
            'invoices' => fn ($q) => $q->with('payments')->limit(50),
        ]);

        $duplicates = $this->patients->findDuplicates(
            $patient->phone,
            $patient->name,
            $patient->email,
            $patient->id,
        );

        return Inertia::render('Patients/Show', [
            'patient' => (new PatientResource($patient))->resolve(),
            'billing' => $this->patients->billingSummary($patient),
            'invoices' => $patient->invoices->map(fn ($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'invoice_date' => $i->invoice_date->toDateString(),
                'status' => $i->status->value,
                'status_label' => $i->status->label(),
                'total' => (string) $i->total,
                'paid_amount' => (string) $i->paid_amount,
                'balance_due' => $i->balance_due,
                'show_url' => route('billing.invoices.show', $i),
            ])->all(),
            'appointments' => $patient->appointments->map(fn ($a) => [
                'id' => $a->id,
                'doctor_name' => $a->doctor?->user?->name,
                'doctor_color' => $a->doctor?->color,
                'scheduled_at' => $a->scheduled_at?->toIso8601String(),
                'status' => $a->status?->value,
                'status_label' => $a->status?->label(),
            ])->all(),
            'treatment_records' => $patient->treatmentRecords->map(fn ($r) => [
                'id' => $r->id,
                'doctor_name' => $r->doctor?->user?->name,
                'visited_at' => $r->visited_at->toIso8601String(),
                'chief_complaint' => $r->chief_complaint,
                'status' => $r->status->value,
                'status_label' => $r->status->label(),
            ])->all(),
            'prescriptions' => $patient->prescriptions->map(fn ($p) => [
                'id' => $p->id,
                'prescription_number' => $p->prescription_number,
                'doctor_name' => $p->doctor?->user?->name,
                'issued_at' => $p->issued_at?->toIso8601String(),
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'item_count' => $p->relationLoaded('items') ? $p->items->count() : null,
            ])->all(),
            'duplicates' => $duplicates->map(fn (Patient $p) => [
                'id' => $p->id,
                'patient_code' => $p->patient_code,
                'name' => $p->name,
                'phone' => $p->phone,
                'email' => $p->email,
            ])->all(),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        return Inertia::render('Patients/Edit', [
            'patient' => (new PatientResource($patient))->resolve(),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->patients->update($patient, $request->validated());

        return redirect()->route('patients.show', $patient)->with('success', 'Patient updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->patients->delete($patient);

        return redirect()->route('patients.index')->with('success', 'Patient archived.');
    }

    public function search(Request $request): JsonResponse
    {
        $term = (string) $request->string('q', '');
        $limit = (int) $request->integer('limit', 10);

        return response()->json([
            'data' => $this->patients->search($term, $limit),
        ]);
    }

    public function quickStore(QuickStorePatientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        // When the front-end dialog only knows the patient's age, derive
        // an approximate date_of_birth so the cashier doesn't have to
        // ask for the full DOB during a walk-in booking.
        if (isset($data['age']) && $data['age'] !== null && $data['age'] !== '' && empty($data['date_of_birth'])) {
            $data['date_of_birth'] = now()->subYears((int) $data['age'])->toDateString();
        }
        unset($data['age']);

        $patient = $this->patients->create($data);

        return redirect()->back()->with([
            'success' => 'Patient created.',
            'patient' => (new PatientResource($patient))->resolve(),
        ]);
    }
}
