<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Services\Assessments\QuestionRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A question for the Question Bank or a draft version. The option rules per
 * type (how many options, how many correct) are checked by
 * AssessmentQuestionService with the same QuestionRules the Excel import uses.
 */
class AssessmentQuestionRequest extends FormRequest
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
            'type' => ['required', Rule::in(array_map(fn (QuestionType $type) => $type->value, QuestionType::cases()))],
            'prompt' => ['required', 'string', 'max:2000'],
            'points' => ['nullable', 'integer', 'min:1', 'max:'.QuestionRules::MAX_POINTS],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:'.QuestionRules::MAX_CATEGORY_LENGTH],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array', 'max:'.QuestionRules::MAX_OPTIONS],
            'options.*.text' => ['nullable', 'string', 'max:5000'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'prompt' => 'question',
            'options.*.text' => 'option text',
        ];
    }
}
