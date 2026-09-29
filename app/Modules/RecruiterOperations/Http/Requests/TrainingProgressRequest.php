<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Where the recruiter is in the lesson audio and time spent since the last
 * report. Carries no completion state: completing a lesson is a separate,
 * explicit action.
 */
class TrainingProgressRequest extends FormRequest
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
            'audio_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'spent_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ];
    }
}
