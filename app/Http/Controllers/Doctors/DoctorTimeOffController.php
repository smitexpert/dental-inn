<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctors\StoreTimeOffRequest;
use App\Models\Doctor;
use App\Models\DoctorTimeOff;
use App\Repositories\Contracts\DoctorTimeOffRepository;
use Illuminate\Http\RedirectResponse;

class DoctorTimeOffController extends Controller
{
    public function __construct(private readonly DoctorTimeOffRepository $timeOffs) {}

    public function store(StoreTimeOffRequest $request, Doctor $doctor): RedirectResponse
    {
        $this->timeOffs->create($doctor, $request->validated());

        return redirect()->route('doctors.show', $doctor)->with('success', 'Time off added.');
    }

    public function destroy(Doctor $doctor, DoctorTimeOff $timeOff): RedirectResponse
    {
        abort_unless($timeOff->doctor_id === $doctor->id, 404);

        $this->timeOffs->delete($timeOff);

        return redirect()->route('doctors.show', $doctor)->with('success', 'Time off removed.');
    }
}
