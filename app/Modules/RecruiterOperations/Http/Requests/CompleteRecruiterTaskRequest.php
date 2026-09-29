<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Services\RecruiterTaskService;
use Illuminate\Foundation\Http\FormRequest;

class CompleteRecruiterTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('achieved_count') === '') {
            $this->merge(['achieved_count' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'achieved_count' => ['nullable', 'integer', 'min:0', 'max:'.RecruiterTaskService::MAX_COUNT],
            'completion_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
