<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Services\RecruiterTaskService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Descriptive fields only. Status and assignee change through the workflow.
 */
class UpdateRecruiterTaskRequest extends FormRequest
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
        return RecruiterTaskService::rules();
    }
}
