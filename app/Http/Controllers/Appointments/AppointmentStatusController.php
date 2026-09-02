<?php

declare(strict_types=1);

namespace App\Http\Controllers\Appointments;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepository;
use App\Services\AppointmentBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppointmentStatusController extends Controller
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly AppointmentBillingService $billing,
    ) {}

    public function update(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:scheduled,confirmed,checked_in,completed,cancelled,no_show'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $next = AppointmentStatus::from($data['status']);

        $current = $appointment->status;
        if (! $current instanceof AppointmentStatus) {
            abort(422, 'Appointment has invalid current status.');
        }

        if (! $current->canTransitionTo($next)) {
            abort(422, "Cannot transition from {$current->label()} to {$next->label()}.");
        }

        if (in_array($next, [AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true)
            && ! $request->user()->can('cancel-appointments')) {
            abort(403);
        }

        if ($next === AppointmentStatus::CheckedIn && ! $request->user()->can('check-in-appointments')) {
            abort(403);
        }

        $this->appointments->updateStatus($appointment, $next, $data['reason'] ?? null);

        // Auto-draft a zero-priced Consultation invoice when the visit
        // is marked Completed so the cashier can collect without
        // remembering which doctor saw which patient. The service is
        // idempotent — re-completion is a no-op.
        if ($next === AppointmentStatus::Completed) {
            $this->billing->createInvoiceForCompletedAppointment(
                $appointment->fresh(),
                (int) $request->user()->id,
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => $next->value]);
        }

        return back()->with('success', "Appointment {$next->label()}.");
    }
}
