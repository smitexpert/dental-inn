<?php

declare(strict_types=1);

namespace App\Support\Inertia;

use App\Http\Resources\SettingsResource;
use App\Settings\ClinicSettings;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareClinicSettings
{
    public function __construct(private readonly ClinicSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share('settings', fn () => SettingsResource::fromSettings($this->settings));

        return $next($request);
    }
}
