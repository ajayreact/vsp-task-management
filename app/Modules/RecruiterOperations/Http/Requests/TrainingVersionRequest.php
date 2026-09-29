<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Notes and estimate for a draft version.
 */
class TrainingVersionRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:5000'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:6000'],
        ];
    }
}
