<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ProductCategory;
use PHPUnit\Framework\TestCase;

class ProductCategoryEnumTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('consumable', ProductCategory::Consumable->value);
        $this->assertSame('equipment', ProductCategory::Equipment->value);
        $this->assertSame('medication', ProductCategory::Medication->value);
        $this->assertSame('material', ProductCategory::Material->value);
        $this->assertSame('other', ProductCategory::Other->value);
    }

    public function test_labels_are_human_readable(): void
    {
        $this->assertSame('Consumable', ProductCategory::Consumable->label());
        $this->assertSame('Equipment', ProductCategory::Equipment->label());
        $this->assertSame('Medication', ProductCategory::Medication->label());
        $this->assertSame('Material', ProductCategory::Material->label());
        $this->assertSame('Other', ProductCategory::Other->label());
    }
}
