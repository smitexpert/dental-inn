<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\PreviewTemplateRequest;
use App\Http\Requests\Notifications\StoreTemplateRequest;
use App\Http\Requests\Notifications\UpdateTemplateRequest;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function __construct(private readonly NotificationService $service) {}

    public function index(): Response
    {
        $query = NotificationTemplate::query()
            ->orderBy('key')
            ->orderBy('channel');

        if (request()->filled('q')) {
            $query->where('key', 'like', '%'.(string) request()->string('q').'%');
        }
        if (request()->filled('channel')) {
            $query->where('channel', (string) request()->string('channel'));
        }

        $page = $query->paginate((int) request()->integer('per_page', 25));

        return Inertia::render('Notifications/Templates/Index', [
            'templates' => $page,
            'channels' => NotificationChannel::cases(),
            'filters' => request()->only(['q', 'channel']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Notifications/Templates/Create', [
            'channels' => NotificationChannel::cases(),
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        NotificationTemplate::query()->create($request->validated());

        return redirect()->route('notifications.templates.index')
            ->with('success', 'Template created.');
    }

    public function edit(NotificationTemplate $template): Response
    {
        return Inertia::render('Notifications/Templates/Edit', [
            'template' => $template,
            'channels' => NotificationChannel::cases(),
        ]);
    }

    public function update(UpdateTemplateRequest $request, NotificationTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        return redirect()->route('notifications.templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(NotificationTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('notifications.templates.index')
            ->with('success', 'Template deleted.');
    }

    public function preview(PreviewTemplateRequest $request, NotificationTemplate $template): array
    {
        $rendered = $this->service->renderTemplate(
            $template,
            (array) $request->input('variables', []),
        );

        return [
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
        ];
    }
}
