<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\RecurrenceFrequency;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskRecurrenceRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringTaskService
{
    /**
     * Generate the next occurrence after a completed task in a recurring series.
     * Idempotent: unique (rule, occurrence_number) prevents duplicates.
     */
    public function generateNextOccurrence(Task $completedTask): ?Task
    {
        $rule = $this->resolveRule($completedTask);

        if ($rule === null || ! $rule->is_active || $completedTask->status !== TaskStatus::Completed) {
            return null;
        }

        if (! $this->canGenerateAnother($rule)) {
            return null;
        }

        $nextNumber = $rule->occurrences_generated + 1;

        return DB::transaction(function () use ($rule, $nextNumber) {
            $existing = Task::query()
                ->where('tm_recurrence_rule_id', $rule->id)
                ->where('recurrence_occurrence_number', $nextNumber)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return null;
            }

            $source = Task::query()->whereKey($rule->source_tm_task_id)->lockForUpdate()->first();

            if ($source === null) {
                return null;
            }

            $task = $this->copyFromSource($source, $rule, $nextNumber);

            $rule->update([
                'occurrences_generated' => $nextNumber,
                'last_generated_at' => now(),
            ]);

            return $task;
        });
    }

    /**
     * Catch missed generations when completion happened while the scheduler was down.
     */
    public function processPendingGenerations(): int
    {
        $generated = 0;

        TaskRecurrenceRule::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(25, function ($rules) use (&$generated) {
                foreach ($rules as $rule) {
                    $latest = $this->latestTaskInSeries($rule);

                    if ($latest === null || $latest->status !== TaskStatus::Completed) {
                        continue;
                    }

                    if ($this->generateNextOccurrence($latest) !== null) {
                        $generated++;
                    }
                }
            });

        return $generated;
    }

    protected function resolveRule(Task $task): ?TaskRecurrenceRule
    {
        if ($task->tm_recurrence_rule_id === null) {
            return null;
        }

        return TaskRecurrenceRule::query()->find($task->tm_recurrence_rule_id);
    }

    protected function canGenerateAnother(TaskRecurrenceRule $rule): bool
    {
        if ($rule->max_occurrences !== null && $rule->occurrences_generated >= $rule->max_occurrences) {
            return false;
        }

        $source = Task::query()->find($rule->source_tm_task_id);

        if ($source === null) {
            return false;
        }

        $nextNumber = $rule->occurrences_generated + 1;
        $nextDue = $this->calculateDueAt($source, $rule, $nextNumber);

        if ($rule->end_date !== null) {
            $comparisonDate = $nextDue ?? $this->nextOccurrenceDate($rule);

            if ($comparisonDate->copy()->startOfDay()->greaterThan($rule->end_date)) {
                return false;
            }
        }

        return true;
    }

    protected function nextOccurrenceDate(TaskRecurrenceRule $rule): Carbon
    {
        $anchor = $rule->last_generated_at
            ? Carbon::parse($rule->last_generated_at)->startOfDay()
            : Carbon::parse($rule->start_date)->startOfDay();

        return match ($rule->frequency) {
            RecurrenceFrequency::Daily, RecurrenceFrequency::Custom => $anchor->copy()->addDays($rule->interval),
            RecurrenceFrequency::Weekly => $anchor->copy()->addWeeks($rule->interval),
            RecurrenceFrequency::Monthly => $anchor->copy()->addMonths($rule->interval),
        };
    }

    protected function latestTaskInSeries(TaskRecurrenceRule $rule): ?Task
    {
        return Task::query()
            ->where('tm_recurrence_rule_id', $rule->id)
            ->orderByDesc('recurrence_occurrence_number')
            ->orderByDesc('id')
            ->first();
    }

    protected function copyFromSource(Task $source, TaskRecurrenceRule $rule, int $occurrenceNumber): Task
    {
        $assigneeId = $this->validAssigneeId($source->assigned_employee_id);
        $dueAt = $this->calculateDueAt($source, $rule, $occurrenceNumber);

        $task = Task::create([
            'tm_project_id' => $source->tm_project_id,
            'department_id' => $source->department_id,
            'title' => $source->title,
            'description' => $source->description,
            'type' => $source->type,
            'priority' => $source->priority,
            'status' => TaskStatus::Draft,
            'assignment_mode' => $source->assignment_mode,
            'assigned_employee_id' => $assigneeId,
            'created_by_user_id' => $source->created_by_user_id,
            'estimated_hours' => $source->estimated_hours,
            'due_at' => $dueAt,
            'tm_recurrence_rule_id' => $rule->id,
            'recurrence_occurrence_number' => $occurrenceNumber,
        ]);

        $task->statusHistory()->create([
            'from_status' => null,
            'to_status' => TaskStatus::Draft,
            'changed_by_user_id' => $source->created_by_user_id,
            'changed_at' => now(),
        ]);

        foreach ($source->checklistItems()->orderBy('sort_order')->get() as $item) {
            $task->checklistItems()->create([
                'title' => $item->title,
                'is_completed' => false,
                'sort_order' => $item->sort_order,
            ]);
        }

        foreach ($source->subtasks()->orderBy('sort_order')->get() as $subtask) {
            $task->subtasks()->create([
                'title' => $subtask->title,
                'description' => $subtask->description,
                'status' => SubtaskStatus::Pending,
                'assigned_employee_id' => $this->validAssigneeId($subtask->assigned_employee_id),
                'due_at' => $subtask->due_at,
                'sort_order' => $subtask->sort_order,
            ]);
        }

        return $task;
    }

    protected function validAssigneeId(?int $employeeId): ?int
    {
        if ($employeeId === null) {
            return null;
        }

        return Employee::query()->assignable()->whereKey($employeeId)->exists() ? $employeeId : null;
    }

    protected function calculateDueAt(Task $source, TaskRecurrenceRule $rule, int $occurrenceNumber): ?Carbon
    {
        if ($source->due_at === null) {
            return null;
        }

        $base = Carbon::parse($source->due_at);

        return match ($rule->frequency) {
            RecurrenceFrequency::Daily, RecurrenceFrequency::Custom => $base->copy()->addDays($rule->interval * $occurrenceNumber),
            RecurrenceFrequency::Weekly => $base->copy()->addWeeks($rule->interval * $occurrenceNumber),
            RecurrenceFrequency::Monthly => $base->copy()->addMonths($rule->interval * $occurrenceNumber),
        };
    }
}
