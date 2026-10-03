<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Whether a draft lesson needs a compliance review, and its outcome.
 */
class TrainingComplianceReviewRequest extends FormRequest
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
            'status' => ['required', Rule::enum(TrainingComplianceStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
