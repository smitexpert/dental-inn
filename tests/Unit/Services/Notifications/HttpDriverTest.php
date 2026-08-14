<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Services\Notifications\HttpDriver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpDriverTest extends TestCase
{
    public function test_returns_failure_when_url_missing(): void
    {
        $driver = new HttpDriver(['url' => null]);

        $result = $driver->send('sms', '+8801712345678', null, 'hello');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('url', $result['error']);
        $this->assertNull($result['provider_id']);
    }

    public function test_posts_payload_and_returns_success_with_provider_id(): void
    {
        Http::fake([
            '*' => Http::response(['id' => 'abc-123'], 200),
        ]);

        $driver = new HttpDriver(['url' => 'https://example.test/notify', 'timeout' => 2]);

        $result = $driver->send(
            channel: 'sms',
            recipient: '+8801712345678',
            subject: null,
            body: 'Hello world',
            meta: ['foo' => 'bar'],
        );

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertSame('abc-123', $result['provider_id']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://example.test/notify'
                && $body['channel'] === 'sms'
                && $body['recipient'] === '+8801712345678'
                && $body['body'] === 'Hello world'
                && $body['meta'] === ['foo' => 'bar'];
        });
    }

    public function test_returns_failure_on_non_2xx_response(): void
    {
        Http::fake([
            '*' => Http::response('server error', 500),
        ]);

        $driver = new HttpDriver(['url' => 'https://example.test/notify']);

        $result = $driver->send('email', 'a@b.test', 'Subj', 'body');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('HTTP 500', $result['error']);
        $this->assertNull($result['provider_id']);
    }
}
