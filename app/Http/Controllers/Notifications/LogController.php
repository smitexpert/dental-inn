<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(): Response
    {
        $query = NotificationLog::query()
            ->with(['template'])
            ->orderByDesc('created_at');

        $filters = request()->only(['channel', 'status', 'recipient', 'template_id', 'from', 'to']);

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['recipient'])) {
            $query->where('recipient', 'like', '%'.$filters['recipient'].'%');
        }
        if (! empty($filters['template_id'])) {
            $query->where('template_id', (int) $filters['template_id']);
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $page = $query->paginate((int) request()->integer('per_page', 25));

        return Inertia::render('Notifications/Logs/Index', [
            'logs' => $page,
            'channels' => NotificationChannel::cases(),
            'statuses' => NotificationStatus::cases(),
            'filters' => $filters,
        ]);
    }
}
