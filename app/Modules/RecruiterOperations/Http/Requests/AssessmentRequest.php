<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Enums\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or rename an assessment. On create the Version 1 settings may be
 * given too. Only available types (training quiz) are accepted.
 */
class AssessmentRequest extends FormRequest
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
        $creating = $this->isMethod('post');

        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => $creating
                ? ['nullable', Rule::in(array_map(fn (AssessmentType $type) => $type->value, AssessmentType::available()))]
                : ['prohibited'],
            ...($creating ? AssessmentVersionRequest::settingsRules() : []),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return AssessmentVersionRequest::settingsAttributes();
    }
}
