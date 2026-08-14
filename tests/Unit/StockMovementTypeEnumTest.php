<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\StockMovementType;
use PHPUnit\Framework\TestCase;

class StockMovementTypeEnumTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('purchase', StockMovementType::Purchase->value);
        $this->assertSame('sale', StockMovementType::Sale->value);
        $this->assertSame('consumption', StockMovementType::Consumption->value);
        $this->assertSame('transfer_in', StockMovementType::TransferIn->value);
        $this->assertSame('transfer_out', StockMovementType::TransferOut->value);
        $this->assertSame('adjustment', StockMovementType::Adjustment->value);
        $this->assertSame('return', StockMovementType::Return->value);
    }

    public function test_sign_for_inbound_types_is_positive(): void
    {
        $this->assertSame(1, StockMovementType::Purchase->sign());
        $this->assertSame(1, StockMovementType::TransferIn->sign());
        $this->assertSame(1, StockMovementType::Return->sign());
    }

    public function test_sign_for_outbound_types_is_negative(): void
    {
        $this->assertSame(-1, StockMovementType::Sale->sign());
        $this->assertSame(-1, StockMovementType::Consumption->sign());
        $this->assertSame(-1, StockMovementType::TransferOut->sign());
    }

    public function test_sign_for_adjustment_is_zero(): void
    {
        $this->assertSame(0, StockMovementType::Adjustment->sign());
    }

    public function test_adjustment_does_not_affect_location_directly(): void
    {
        $this->assertFalse(StockMovementType::Adjustment->affectsLocation());
        $this->assertTrue(StockMovementType::Purchase->affectsLocation());
        $this->assertTrue(StockMovementType::Consumption->affectsLocation());
    }

    public function test_labels(): void
    {
        $this->assertSame('Purchase', StockMovementType::Purchase->label());
        $this->assertSame('Transfer in', StockMovementType::TransferIn->label());
        $this->assertSame('Transfer out', StockMovementType::TransferOut->label());
        $this->assertSame('Adjustment', StockMovementType::Adjustment->label());
    }
}
