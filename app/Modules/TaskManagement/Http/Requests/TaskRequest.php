<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskType;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

/**
 * Covers the descriptive fields for create/update. On create, an optional assignee
 * may be supplied; when present it is applied through TaskWorkflow after the draft
 * is saved, never by mass assignment on the model.
 */
class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isMethod('post') || ! $this->routeIs('tasks.store')) {
            return;
        }

        $checklist = collect($this->input('checklist', []))
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== '')
            ->values()
            ->all();

        $subtasks = collect($this->input('subtasks', []))
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== '')
            ->values()
            ->all();

        $this->merge([
            'checklist' => $checklist === [] ? null : $checklist,
            'subtasks' => $subtasks === [] ? null : $subtasks,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'tm_project_id' => [
                'required', 'integer',
                Rule::exists('tm_projects', 'id')->whereIn('status', ['planning', 'active']),
            ],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirement' => ['nullable', 'string', 'max:65535'],
            'type' => ['required', Rule::enum(TaskType::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'due_at' => ['nullable', 'date'],
        ];

        if ($this->isMethod('post') && $this->routeIs('tasks.store')) {
            $rules['assigned_employee_id'] = ['nullable', 'integer', Rule::exists('employees', 'id')];
            $rules['publish_to_open_board'] = ['sometimes', 'boolean'];
            $rules['checklist'] = ['nullable', 'array'];
            $rules['checklist.*.title'] = ['required', 'string', 'max:500'];
            $rules['subtasks'] = ['nullable', 'array'];
            $rules['subtasks.*.title'] = ['required', 'string', 'max:500'];
            $rules['subtasks.*.description'] = ['nullable', 'string', 'max:5000'];
            $rules['subtasks.*.status'] = ['nullable', Rule::enum(SubtaskStatus::class)];
            $rules['subtasks.*.assigned_employee_id'] = ['nullable', 'integer', Rule::exists('employees', 'id')];
            $rules['subtasks.*.due_at'] = ['nullable', 'date'];
            $rules['files'] = ['nullable', 'array', 'max:'.UploadLimits::TASK_ATTACHMENT_MAX_FILES];
            $rules['files.*'] = [
                'required',
                File::types(Task::attachmentExtensions())->max(UploadLimits::TASK_ATTACHMENT_MAX_KILOBYTES),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        if (! $this->isMethod('post') || ! $this->routeIs('tasks.store')) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            if ($this->boolean('publish_to_open_board') && $this->filled('assigned_employee_id')) {
                $validator->errors()->add(
                    'assigned_employee_id',
                    'Choose either a direct assignee or the open board, not both.',
                );
            }

            /** @var list<UploadedFile>|null $files */
            $files = $this->file('files');

            if (! is_array($files) || $files === []) {
                return;
            }

            if (UploadLimits::combinedUploadBytes($files) > UploadLimits::DOCUMENTED_POST_MAX_BYTES) {
                $validator->errors()->add('files', UploadLimits::combinedRequestExceededMessage());
            }

            foreach ($files as $index => $file) {
                $message = UploadLimits::validateTaskAttachmentFile($file);

                if ($message !== null) {
                    $validator->errors()->add("files.{$index}", $message);
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tm_project_id.exists' => 'Pick a project that is still open for new work.',
            'files.max' => 'You can attach at most '.UploadLimits::TASK_ATTACHMENT_MAX_FILES.' files at a time.',
        ];
    }
}
