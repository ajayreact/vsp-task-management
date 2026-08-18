<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class TaskAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.UploadLimits::TASK_ATTACHMENT_MAX_FILES],
            'files.*' => [
                'required',
                File::types(Task::attachmentExtensions())->max(UploadLimits::TASK_ATTACHMENT_MAX_KILOBYTES),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var list<UploadedFile>|null $files */
            $files = $this->file('files');

            if (! is_array($files) || $files === []) {
                return;
            }

            if (UploadLimits::combinedUploadBytes($files) > UploadLimits::DOCUMENTED_POST_MAX_BYTES) {
                $validator->errors()->add('files', UploadLimits::combinedRequestExceededMessage());
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Choose at least one file to attach.',
            'files.max' => 'You can attach at most '.UploadLimits::TASK_ATTACHMENT_MAX_FILES.' files at a time.',
        ];
    }
}
