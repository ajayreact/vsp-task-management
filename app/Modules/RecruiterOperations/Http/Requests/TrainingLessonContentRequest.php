<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Services\TrainingLessonStructure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The structured content of a draft lesson in one language. Section bodies
 * are plain text in the light lesson format; nothing is treated as HTML.
 * Review states are set in the review workflow, never by saving content.
 */
class TrainingLessonContentRequest extends FormRequest
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
            'sections' => ['required', 'array', 'min:1', 'max:'.TrainingLessonStructure::MAX_SECTIONS],
            'sections.*.kind' => ['required', Rule::enum(TrainingSectionKind::class)],
            'sections.*.heading' => ['nullable', 'string', 'max:'.TrainingLessonStructure::MAX_HEADING],
            'sections.*.body' => ['required', 'string', 'max:'.TrainingLessonStructure::MAX_BODY],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sections.*.kind' => 'section type',
            'sections.*.heading' => 'section heading',
            'sections.*.body' => 'section content',
        ];
    }
}
