<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\SendNotificationRequest;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SendController extends Controller
{
    public function __construct(private readonly NotificationService $service) {}

    public function create(): Response
    {
        $templates = NotificationTemplate::query()
            ->where('is_active', true)
            ->orderBy('key')
            ->get(['id', 'key', 'channel', 'subject', 'locale']);

        return Inertia::render('Notifications/Send/Create', [
            'channels' => NotificationChannel::cases(),
            'templates' => $templates,
        ]);
    }

    public function store(SendNotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['template_key'])) {
            $results = $this->service->send(
                templateKey: $data['template_key'],
                channelValue: $data['channel'],
                recipients: $data['recipients'],
                variables: (array) ($data['variables'] ?? []),
            );
        } else {
            $results = $this->service->sendDirect(
                channelValue: $data['channel'],
                recipients: $data['recipients'],
                subject: $data['subject'] ?? null,
                body: (string) ($data['body'] ?? ''),
            );
        }

        $sent = 0;
        $skipped = 0;
        foreach ($results as $r) {
            if (($r['sent'] ?? false) === true) {
                $sent++;
            } elseif (isset($r['skipped'])) {
                $skipped++;
            }
        }

        return redirect()->route('notifications.send.create')
            ->with('success', "Queued $sent notification(s); skipped $skipped duplicate(s).");
    }
}
