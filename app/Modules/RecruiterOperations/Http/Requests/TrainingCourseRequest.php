<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Course details. Status and versions are never taken from the form; they
 * change only through the publish/archive actions.
 */
class TrainingCourseRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:ro_training_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['category_id' => 'category'];
    }
}
