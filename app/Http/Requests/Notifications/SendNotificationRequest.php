<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use App\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('send-notifications');
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            'template_key' => ['nullable', 'string', 'max:80'],
            'subject' => ['nullable', 'string', 'max:160'],
            'body' => ['required_without:template_key', 'nullable', 'string', 'max:10000'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['required', 'string', 'max:160'],
            'variables' => ['array'],
            'variables.*' => ['nullable'],
        ];
    }
}
