<?php

namespace App\Modules\Core\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class NotificationPreferenceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'browser_notifications' => ['required', 'boolean'],
            'notification_sound' => ['required', 'boolean'],
            'in_app_notifications' => ['required', 'boolean'],
        ];
    }
}
