<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Services\TaskManagementNotificationSoundService;
use Illuminate\Foundation\Http\FormRequest;

class NotificationSoundUploadRequest extends FormRequest
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
            'sound' => [
                'required',
                'file',
                'mimes:mp3,wav,ogg',
                'max:'.TaskManagementNotificationSoundService::MAX_UPLOAD_KILOBYTES,
            ],
        ];
    }
}
