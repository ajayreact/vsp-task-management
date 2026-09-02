<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonalTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $todo = $this->route('personalTodo');

        if ($todo === null) {
            return $this->user()?->can('create', \App\Modules\TaskManagement\Models\PersonalTodo::class) ?? false;
        }

        return $this->user()?->can('update', $todo) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ];
    }
}
