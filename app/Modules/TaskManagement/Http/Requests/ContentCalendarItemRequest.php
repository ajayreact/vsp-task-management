<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarTopic;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContentCalendarItemRequest extends FormRequest
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
            'tm_company_id' => ['required', 'integer', Rule::exists('tm_companies', 'id')],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'post_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'content_type' => ['required', Rule::enum(ContentCalendarType::class)],
            'topic' => ['required', Rule::enum(ContentCalendarTopic::class)],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['required', Rule::enum(ContentCalendarPlatform::class)],
            'description' => ['nullable', 'string', 'max:10000'],
            'caption' => ['nullable', 'string', 'max:10000'],
            'hashtags' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ContentCalendarStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'published_url' => ['nullable', 'string', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'files' => ['nullable', 'array', 'max:'.UploadLimits::TASK_ATTACHMENT_MAX_FILES],
            'files.*' => ['required', 'file', 'max:'.UploadLimits::MAX_FILE_KILOBYTES],
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

            foreach ($files as $index => $file) {
                $message = UploadLimits::validateContentAttachmentFile($file);

                if ($message !== null) {
                    $validator->errors()->add("files.{$index}", $message);
                }
            }

            if (UploadLimits::combinedUploadBytes($files) > UploadLimits::DOCUMENTED_POST_MAX_BYTES) {
                $validator->errors()->add('files', UploadLimits::combinedRequestExceededMessage());
            }
        });
    }
}
