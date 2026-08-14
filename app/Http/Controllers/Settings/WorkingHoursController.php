<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWorkingHoursRequest;
use App\Settings\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkingHoursController extends Controller
{
    public function edit(ClinicSettings $settings): Response
    {
        return Inertia::render('Settings/WorkingHours', [
            'working_hours' => json_decode($settings->working_hours_json, true) ?: [],
        ]);
    }

    public function update(UpdateWorkingHoursRequest $request, ClinicSettings $settings): RedirectResponse
    {
        $settings->working_hours_json = json_encode($request->validated('working_hours'));
        $settings->save();
        return back()->with('success', 'Working hours updated.');
    }
}
