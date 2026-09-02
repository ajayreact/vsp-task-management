<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TaskCreationService
{
    public function __construct(
        protected TaskWorkflow $workflow,
        protected TaskNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $taskAttributes
     * @param  list<array{title: string}>  $checklistItems
     * @param  list<array{title: string, description?: string|null, assigned_employee_id?: int|null, due_at?: string|null, status?: string|null}>  $subtasks
     * @param  list<UploadedFile>  $files
     */
    public function create(
        User $user,
        array $taskAttributes,
        ?int $assigneeId,
        array $checklistItems,
        array $subtasks,
        array $files,
    ): Task {
        return DB::transaction(function () use ($user, $taskAttributes, $assigneeId, $checklistItems, $subtasks, $files) {
            $task = Task::create([
                ...$taskAttributes,
                'status' => TaskStatus::Draft,
                'created_by_user_id' => $user->id,
            ]);

            $task->statusHistory()->create([
                'from_status' => null,
                'to_status' => TaskStatus::Draft,
                'changed_by_user_id' => $user->id,
                'changed_at' => now(),
            ]);

            foreach (array_values($checklistItems) as $position => $item) {
                $task->checklistItems()->create([
                    'title' => trim($item['title']),
                    'sort_order' => $position + 1,
                ]);
            }

            foreach (array_values($subtasks) as $position => $subtaskData) {
                $status = SubtaskStatus::from($subtaskData['status'] ?? SubtaskStatus::Pending->value);

                $subtask = $task->subtasks()->create([
                    'title' => trim($subtaskData['title']),
                    'description' => isset($subtaskData['description']) ? trim((string) $subtaskData['description']) : null,
                    'status' => $status,
                    'assigned_employee_id' => $subtaskData['assigned_employee_id'] ?? null,
                    'due_at' => $subtaskData['due_at'] ?? null,
                    'completed_at' => $status->isCompleted() ? now() : null,
                    'sort_order' => $position + 1,
                ]);

                if ($subtask->assigned_employee_id !== null) {
                    $this->notifier->subtaskAssigned($task, $subtask, $user);
                }
            }

            foreach ($files as $file) {
                $task->addMedia($file)
                    ->withCustomProperties([
                        'uploaded_by_user_id' => $user->id,
                    ])
                    ->toMediaCollection('attachments');
            }

            if ($assigneeId !== null) {
                $employee = Employee::query()->findOrFail($assigneeId);
                $this->workflow->assign($task, $employee, $user);
            }

            return $task->fresh();
        });
    }
}
