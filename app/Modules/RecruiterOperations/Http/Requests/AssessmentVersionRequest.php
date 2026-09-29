<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Settings of a draft assessment version.
 */
class AssessmentVersionRequest extends FormRequest
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
        return self::settingsRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::settingsAttributes();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function settingsRules(): array
    {
        return [
            'instructions' => ['nullable', 'string', 'max:5000'],
            'passing_percentage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_options' => ['nullable', 'boolean'],
            'show_result' => ['nullable', 'boolean'],
            'allow_review' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function settingsAttributes(): array
    {
        return [
            'passing_percentage' => 'pass mark',
            'time_limit_minutes' => 'time limit',
            'max_attempts' => 'maximum attempts',
        ];
    }
}
