<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ProductUnit;
use PHPUnit\Framework\TestCase;

class ProductUnitEnumTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('piece', ProductUnit::Piece->value);
        $this->assertSame('box', ProductUnit::Box->value);
        $this->assertSame('vial', ProductUnit::Vial->value);
        $this->assertSame('syringe', ProductUnit::Syringe->value);
        $this->assertSame('ml', ProductUnit::Ml->value);
        $this->assertSame('gram', ProductUnit::Gram->value);
        $this->assertSame('kg', ProductUnit::Kg->value);
        $this->assertSame('liter', ProductUnit::Liter->value);
        $this->assertSame('pack', ProductUnit::Pack->value);
    }

    public function test_labels_are_human_readable(): void
    {
        $this->assertSame('Piece', ProductUnit::Piece->label());
        $this->assertSame('Box', ProductUnit::Box->label());
        $this->assertSame('Millilitre (ml)', ProductUnit::Ml->label());
    }

    public function test_short_labels_are_short(): void
    {
        $this->assertSame('pc', ProductUnit::Piece->shortLabel());
        $this->assertSame('ml', ProductUnit::Ml->shortLabel());
        $this->assertSame('g', ProductUnit::Gram->shortLabel());
        $this->assertSame('kg', ProductUnit::Kg->shortLabel());
        $this->assertSame('L', ProductUnit::Liter->shortLabel());
    }
}
