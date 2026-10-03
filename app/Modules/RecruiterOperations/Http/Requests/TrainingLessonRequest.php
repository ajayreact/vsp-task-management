<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Enums\TrainingLessonContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A draft lesson. The body is plain text. The file must match the content
 * type, by extension and by its actual content.
 */
class TrainingLessonRequest extends FormRequest
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
        $type = TrainingLessonContentType::tryFrom((string) $this->input('content_type'));
        $fileRules = ['nullable', 'file', 'max:'.(int) config('recruiter-training.media.max_kilobytes', 614400)];

        if ($type !== null && $type->usesFile()) {
            $fileRules[] = 'extensions:'.implode(',', $type->allowedExtensions());
            $fileRules[] = 'mimetypes:'.implode(',', $type->allowedMimeTypes());
        } else {
            $fileRules[] = 'prohibited';
        }

        return [
            'module' => ['nullable', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'content_type' => ['required', Rule::enum(TrainingLessonContentType::class)],
            'body' => ['nullable', 'string', 'max:100000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'is_required' => ['required', 'boolean'],
            'external_url' => [
                'nullable',
                Rule::requiredIf($type === TrainingLessonContentType::ExternalResource),
                'url:http,https',
                'max:2048',
            ],
            'file' => $fileRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['external_url' => 'link', 'content_type' => 'content type'];
    }
}
