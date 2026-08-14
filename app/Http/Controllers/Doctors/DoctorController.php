<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctors\StoreDoctorRequest;
use App\Http\Requests\Doctors\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Repositories\Contracts\DoctorRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DoctorController extends Controller
{
    public function __construct(private readonly DoctorRepository $doctors) {}

    public function index(): Response
    {
        return Inertia::render('Doctors/Index', [
            'doctors' => $this->doctors->paginate(
                request()->integer('per_page', 15),
                request()->only(['q', 'specialization']),
            ),
            'specializations' => Doctor::query()
                ->whereNotNull('specialization')
                ->distinct()
                ->pluck('specialization')
                ->filter()
                ->values(),
            'filters' => request()->only(['q', 'specialization']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Doctors/Create');
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $this->doctors->create($request->validated());

        return redirect()->route('doctors.index')->with('success', 'Doctor added.');
    }

    public function show(Doctor $doctor): Response
    {
        $doctor->load(['user', 'schedules', 'timeOffs']);

        return Inertia::render('Doctors/Show', [
            'doctor' => $this->serialize($doctor),
        ]);
    }

    public function edit(Doctor $doctor): Response
    {
        $doctor->load('user');

        return Inertia::render('Doctors/Edit', [
            'doctor' => $this->serialize($doctor),
        ]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $this->doctors->update($doctor, $request->validated());

        return redirect()->route('doctors.show', $doctor)->with('success', 'Doctor updated.');
    }

    public function toggleAvailability(Doctor $doctor): RedirectResponse
    {
        $this->doctors->toggleAvailability($doctor);

        return back()->with('success', 'Doctor availability changed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Doctor $doctor): array
    {
        return [
            'id' => $doctor->id,
            'user_id' => $doctor->user_id,
            'name' => $doctor->user->name,
            'email' => $doctor->user->email,
            'phone' => $doctor->user->phone,
            'is_active' => (bool) $doctor->user->is_active,
            'specialization' => $doctor->specialization,
            'license_number' => $doctor->license_number,
            'bio' => $doctor->bio,
            'years_of_experience' => $doctor->years_of_experience,
            'consultation_fee' => $doctor->consultation_fee,
            'color' => $doctor->color,
            'is_available' => (bool) $doctor->is_available,
            'schedules' => $doctor->schedules->map(fn ($s) => [
                'id' => $s->id,
                'day_of_week' => $s->day_of_week,
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'is_active' => (bool) $s->is_active,
            ])->all(),
            'time_offs' => $doctor->timeOffs->map(fn ($t) => [
                'id' => $t->id,
                'start_at' => $t->start_at->toIso8601String(),
                'end_at' => $t->end_at->toIso8601String(),
                'reason' => $t->reason,
            ])->all(),
        ];
    }
}
