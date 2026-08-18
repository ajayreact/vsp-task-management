<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\NotificationSystemSound;
use App\Modules\TaskManagement\Services\TaskManagementNotificationSoundService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class NotificationSoundSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageTaskManagementSettings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'source' => ['required', Rule::in(['system', 'custom'])],
            'system_sound' => ['required', Rule::enum(NotificationSystemSound::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('source') !== 'custom') {
                return;
            }

            if (app(TaskManagementNotificationSoundService::class)->customMedia() === null) {
                $validator->errors()->add('source', 'Upload a custom notification sound before selecting custom upload.');
            }
        });
    }
}
