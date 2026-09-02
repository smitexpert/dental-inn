<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

class PreviewTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('manage-notification-templates');
    }

    public function rules(): array
    {
        return [
            'variables' => ['array'],
            'variables.*' => ['nullable'],
        ];
    }
}
