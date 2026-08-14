<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Database\Factories\NotificationLogFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_row_can_be_created(): void
    {
        $log = NotificationLogFactory::new()->create();

        $this->assertNotNull($log->id);
        $this->assertNotNull($log->created_at);
    }

    public function test_row_cannot_be_updated(): void
    {
        $log = NotificationLogFactory::new()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NotificationLog rows are immutable.');

        $log->error_message = 'tampered';
        $log->save();
    }

    public function test_row_cannot_be_deleted(): void
    {
        $log = NotificationLogFactory::new()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NotificationLog rows are immutable.');

        $log->delete();
    }
}
