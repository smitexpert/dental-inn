<?php

declare(strict_types=1);

namespace App\Http\Controllers\Appointments;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointments\StoreAppointmentRequest;
use App\Http\Requests\Appointments\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Repositories\Contracts\AppointmentRepository;
use App\Repositories\Contracts\DoctorRepository;
use App\Settings\ClinicSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly DoctorRepository $doctors,
    ) {}

    public function index(): Response
    {
        $page = $this->appointments->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'doctor_id', 'status', 'from', 'to']),
        );
        $page->setCollection(AppointmentResource::collection($page->getCollection())->collection);

        return Inertia::render('Appointments/Index', [
            'appointments' => $page,
            'doctors' => $this->doctors->listActive(),
            'statuses' => AppointmentStatus::cases(),
            'filters' => request()->only(['q', 'doctor_id', 'status', 'from', 'to']),
        ]);
    }

    public function calendar(): Response
    {
        return Inertia::render('Appointments/Calendar', [
            'doctors' => $this->doctors->listActive(),
            'buffer_minutes' => app(ClinicSettings::class)->appointment_buffer_minutes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Appointments/Create', [
            'doctors' => $this->doctors->listActive(),
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $start = Carbon::parse($data['scheduled_at']);
        $end = $start->copy()->addMinutes((int) $data['duration_minutes']);
        if ($this->appointments->overlappingCount((int) $data['doctor_id'], $start, $end) > 0) {
            return back()->withErrors([
                'scheduled_at' => 'Selected time conflicts with another appointment.',
            ])->withInput();
        }

        $this->appointments->create($data);

        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function show(Appointment $appointment): Response
    {
        $appointment->load(['doctor.user', 'creator']);

        return Inertia::render('Appointments/Show', [
            'appointment' => (new AppointmentResource($appointment))->resolve(),
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $appointment->load(['doctor.user']);

        return Inertia::render('Appointments/Edit', [
            'appointment' => (new AppointmentResource($appointment))->resolve(),
            'doctors' => $this->doctors->listActive(),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validated();
        $start = Carbon::parse($data['scheduled_at']);
        $end = $start->copy()->addMinutes((int) $data['duration_minutes']);
        if ($this->appointments->overlappingCount((int) $data['doctor_id'], $start, $end, $appointment->id) > 0) {
            return back()->withErrors([
                'scheduled_at' => 'Selected time conflicts with another appointment.',
            ])->withInput();
        }

        $this->appointments->update($appointment, $data);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->appointments->delete($appointment);

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted.');
    }

    public function apiList(): JsonResponse
    {
        $request = request();
        $start = Carbon::parse((string) $request->string('from'));
        $end = Carbon::parse((string) $request->string('to'));
        $doctor = $request->integer('doctor_id') ?: null;
        $doctorModel = $doctor ? Doctor::find($doctor) : null;

        $items = $this->appointments->between($start, $end, $doctorModel);

        return response()->json([
            'data' => AppointmentResource::collection($items)->resolve(),
        ]);
    }
}
