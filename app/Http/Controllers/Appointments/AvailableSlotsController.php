<?php

declare(strict_types=1);

namespace App\Http\Controllers\Appointments;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Settings\ClinicSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailableSlotsController extends Controller
{
    public function __construct(private readonly ClinicSettings $settings) {}

    public function index(Request $request, Doctor $doctor): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $duration = (int) ($data['duration_minutes'] ?? 30);
        $buffer = (int) $this->settings->appointment_buffer_minutes;

        $date = Carbon::parse($data['date'])->startOfDay();
        $dow = (int) $date->dayOfWeek;

        $schedule = $doctor->schedules()
            ->where('day_of_week', $dow)
            ->where('is_active', true)
            ->first();

        if (! $schedule) {
            return response()->json(['slots' => []]);
        }

        $workStart = $date->copy()->setTimeFromTimeString((string) $schedule->getAttributes()['start_time']);
        $workEnd = $date->copy()->setTimeFromTimeString((string) $schedule->getAttributes()['end_time']);

        $timeOffs = $doctor->timeOffs()
            ->where('start_at', '<', $workEnd)
            ->where('end_at', '>', $workStart)
            ->get(['start_at', 'end_at']);

        $existing = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ])
            ->where('scheduled_at', '<', $workEnd)
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$workStart])
            ->get(['scheduled_at', 'duration_minutes']);

        $slots = [];
        $step = 30;
        $slot = $workStart->copy();

        while ($slot->copy()->addMinutes($duration)->lte($workEnd)) {
            $slotEnd = $slot->copy()->addMinutes($duration);

            $blockedByTimeOff = $timeOffs->first(function ($t) use ($slot, $slotEnd) {
                $start = Carbon::parse($t->getAttributes()['start_at']);
                $end = Carbon::parse($t->getAttributes()['end_at']);

                return $start->lt($slotEnd) && $end->gt($slot);
            });

            $blockedByAppointment = $existing->first(function ($a) use ($slot, $slotEnd, $buffer) {
                $aStart = Carbon::parse($a->scheduled_at)->subMinutes($buffer);
                $aEnd = Carbon::parse($a->scheduled_at)->addMinutes((int) $a->duration_minutes + $buffer);

                return $aStart->lt($slotEnd) && $aEnd->gt($slot);
            });

            $isPast = $slot->isPast();

            if (! $blockedByTimeOff && ! $blockedByAppointment && ! $isPast) {
                $slots[] = $slot->format('Y-m-d\TH:i:s');
            }

            $slot->addMinutes($step);
        }

        return response()->json(['slots' => $slots]);
    }
}
