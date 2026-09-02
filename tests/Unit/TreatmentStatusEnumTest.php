<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TreatmentStatus;
use PHPUnit\Framework\TestCase;

class TreatmentStatusEnumTest extends TestCase
{
    public function test_label_for_each_case(): void
    {
        $this->assertSame('In progress', TreatmentStatus::InProgress->label());
        $this->assertSame('Completed', TreatmentStatus::Completed->label());
        $this->assertSame('Cancelled', TreatmentStatus::Cancelled->label());
    }

    public function test_terminal_only_for_completed_and_cancelled(): void
    {
        $this->assertFalse(TreatmentStatus::InProgress->isTerminal());
        $this->assertTrue(TreatmentStatus::Completed->isTerminal());
        $this->assertTrue(TreatmentStatus::Cancelled->isTerminal());
    }

    public function test_string_values(): void
    {
        $this->assertSame('in_progress', TreatmentStatus::InProgress->value);
        $this->assertSame('completed', TreatmentStatus::Completed->value);
        $this->assertSame('cancelled', TreatmentStatus::Cancelled->value);
    }
}
