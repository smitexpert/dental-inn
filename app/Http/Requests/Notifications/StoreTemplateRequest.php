<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use App\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('manage-notification-templates');
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9\-]+$/'],
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            'subject' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'is_active' => ['boolean'],
            'locale' => ['required', 'string', 'max:8'],
        ];
    }
}
