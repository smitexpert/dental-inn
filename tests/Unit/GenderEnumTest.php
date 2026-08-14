<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Gender;
use PHPUnit\Framework\TestCase;

class GenderEnumTest extends TestCase
{
    public function test_label_for_each_case(): void
    {
        $this->assertSame('Male', Gender::Male->label());
        $this->assertSame('Female', Gender::Female->label());
        $this->assertSame('Other', Gender::Other->label());
    }

    public function test_string_values(): void
    {
        $this->assertSame('male', Gender::Male->value);
        $this->assertSame('female', Gender::Female->value);
        $this->assertSame('other', Gender::Other->value);
    }
}
