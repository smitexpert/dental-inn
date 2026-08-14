<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateClinicSettingsRequest;
use App\Settings\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClinicSettingsController extends Controller
{
    public function edit(ClinicSettings $settings): Response
    {
        return Inertia::render('Settings/Clinic', [
            'settings' => $settings->toArray(),
        ]);
    }

    public function update(UpdateClinicSettingsRequest $request, ClinicSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();
        return back()->with('success', 'Clinic settings updated.');
    }
}
