<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Models\RecruiterTaskEvent;

/**
 * Operational task counts for the Recruiter Operations dashboard. Counts of
 * work in each state only; no per-recruiter comparison, ranking or score.
 */
class RecruiterTaskSummary
{
    /**
     * @return array{assigned_today: int, in_progress: int, pending: int, completed_today: int, due_today: int, upcoming: list<array<string, mixed>>}
     */
    public function forEmployee(Employee $employee): array
    {
        $mine = fn () => RecruiterTask::query()->forEmployee($employee);

        return [
            'assigned_today' => RecruiterTaskEvent::query()
                ->whereIn('event', [RecruiterTaskEventType::Assigned->value, RecruiterTaskEventType::Reassigned->value])
                ->where('to_employee_id', $employee->id)
                ->whereBetween('occurred_at', [now()->startOfDay(), now()->endOfDay()])
                ->distinct()
                ->count('ro_task_id'),
            'in_progress' => $mine()->where('status', RecruiterTaskStatus::InProgress->value)->count(),
            'pending' => $mine()->pending()->count(),
            'completed_today' => $mine()
                ->where('status', RecruiterTaskStatus::Completed->value)
                ->whereBetween('completed_at', [now()->startOfDay(), now()->endOfDay()])
                ->count(),
            'due_today' => $mine()->pending()->dueToday()->count(),
            'upcoming' => $mine()
                ->pending()
                ->orderByRaw('due_at is null')
                ->orderBy('due_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (RecruiterTask $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status->value,
                    'status_label' => $task->status->label(),
                    'work_type_label' => $task->work_type->label(),
                    'due_at' => $task->due_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{assigned: int, in_progress: int, on_hold: int, declined: int, due_today: int, overdue: int, completed_today: int}
     */
    public function forTeam(): array
    {
        $counts = RecruiterTask::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'assigned' => (int) ($counts[RecruiterTaskStatus::Assigned->value] ?? 0),
            'in_progress' => (int) ($counts[RecruiterTaskStatus::InProgress->value] ?? 0),
            'on_hold' => (int) ($counts[RecruiterTaskStatus::OnHold->value] ?? 0),
            'declined' => (int) ($counts[RecruiterTaskStatus::Declined->value] ?? 0),
            'due_today' => RecruiterTask::query()->pending()->dueToday()->count(),
            'overdue' => RecruiterTask::query()->pending()->where('due_at', '<', now())->count(),
            'completed_today' => RecruiterTask::query()
                ->where('status', RecruiterTaskStatus::Completed->value)
                ->whereBetween('completed_at', [now()->startOfDay(), now()->endOfDay()])
                ->count(),
        ];
    }
}
