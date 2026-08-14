<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusEnumTest extends TestCase
{
    public function test_can_transition_scheduled_to_confirmed(): void
    {
        $this->assertTrue(AppointmentStatus::Scheduled->canTransitionTo(AppointmentStatus::Confirmed));
    }

    public function test_cannot_transition_completed_back(): void
    {
        $this->assertFalse(AppointmentStatus::Completed->canTransitionTo(AppointmentStatus::Scheduled));
    }

    public function test_terminal_states(): void
    {
        $this->assertTrue(AppointmentStatus::Completed->isTerminal());
        $this->assertTrue(AppointmentStatus::Cancelled->isTerminal());
        $this->assertTrue(AppointmentStatus::NoShow->isTerminal());
        $this->assertFalse(AppointmentStatus::Scheduled->isTerminal());
    }
}
