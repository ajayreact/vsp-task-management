<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Services\Assessments\AssessmentAttemptService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Answers keyed by question id. Only the shape is checked here; which
 * questions and options belong to the attempt is checked by
 * AssessmentAttemptService. Scores and timers from the browser are ignored.
 */
class AssessmentAnswersRequest extends FormRequest
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
            'answers' => ['present', 'array', 'max:1000'],
            'answers.*' => ['array:option_ids,text'],
            'answers.*.option_ids' => ['nullable', 'array', 'max:26'],
            'answers.*.option_ids.*' => ['integer', 'min:1'],
            'answers.*.text' => ['nullable', 'string', 'max:'.AssessmentAttemptService::MAX_TEXT_LENGTH],
        ];
    }

    /**
     * @return array<int|string, array{option_ids?: list<int|string>|null, text?: string|null}>
     */
    public function answers(): array
    {
        /** @var array<int|string, array{option_ids?: list<int|string>|null, text?: string|null}> $answers */
        $answers = $this->validated('answers', []);

        return $answers;
    }
}
