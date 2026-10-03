<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A reviewer's decision on one language of a draft lesson.
 */
class TrainingContentReviewRequest extends FormRequest
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
            'status' => ['required', Rule::enum(TrainingContentReview::class)],
            'note' => ['nullable', 'string', 'max:2000'],
            'next' => ['sometimes', 'boolean'],
        ];
    }
}
