<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctors\UpdateScheduleRequest;
use App\Models\Doctor;
use App\Repositories\Contracts\DoctorScheduleRepository;
use Illuminate\Http\RedirectResponse;

class DoctorScheduleController extends Controller
{
    public function __construct(private readonly DoctorScheduleRepository $schedules) {}

    public function update(UpdateScheduleRequest $request, Doctor $doctor): RedirectResponse
    {
        $this->schedules->replaceForDoctor($doctor, $request->validated('schedules'));

        return redirect()->route('doctors.show', $doctor)->with('success', 'Schedule updated.');
    }
}
