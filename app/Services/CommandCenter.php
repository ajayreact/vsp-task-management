<?php

namespace App\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Models\Timesheet;
use App\Modules\TaskManagement\Services\WorkloadCalculator;
use App\Modules\TaskManagement\Support\WorkWeek;
use Illuminate\Database\Eloquent\Builder;

/**
 * Staff home. Lives outside Core/TM so it can read Task Management
 * without breaking the one-way module boundary.
 */
class CommandCenter
{
    public function __construct(protected WorkloadCalculator $workload) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array
    {
        $employee = $user->employee;
        $agency = $this->seesAgency($user);
        $canTasks = $user->can(Ability::AccessTasks->value);

        return [
            'scope' => $agency ? 'agency' : 'personal',
            'modules' => [
                'tasks' => $canTasks,
            ],
            'kpis' => $this->kpis($user, $employee, $agency, $canTasks),
            'actions' => $canTasks ? $this->actions($employee, $agency) : [],
            'approvals' => $canTasks ? $this->approvals($user, $employee, $agency) : [],
            'timer' => $canTasks ? $this->timer($employee) : null,
            'can' => [
                'create_task' => $canTasks && $user->can(Ability::ManageTasks->value),
                'open_board' => $canTasks,
                'view_workload' => $canTasks && $user->can(Ability::ViewWorkload->value),
                'approve_timesheets' => $canTasks && $user->can(Ability::ApproveTimesheets->value),
                'review_proofs' => $canTasks && $user->can(Ability::ReviewDeliverables->value),
            ],
        ];
    }

    protected function seesAgency(User $user): bool
    {
        return $user->hasAnyRole([
            SystemRole::SuperAdmin->value,
            SystemRole::Admin->value,
            SystemRole::TeamLead->value,
            SystemRole::Manager->value,
        ]) || $user->can(Ability::ViewAllTasks->value);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function kpis(User $user, ?Employee $employee, bool $agency, bool $canTasks): array
    {
        $cards = [];

        if ($canTasks) {
            $tasks = $this->taskQuery($employee, $agency);
            $inProgress = (clone $tasks)->where('status', TaskStatus::InProgress)->count();
            $inReview = (clone $tasks)->where('status', TaskStatus::InReview)->count();
            $openBoard = Task::query()->where('status', TaskStatus::Open)->count();
            $capacity = $this->capacity($user, $employee, $agency);

            $cards[] = $this->kpi(
                'in_progress',
                $agency ? 'In progress' : 'My in-progress',
                $inProgress,
                (string) $inProgress,
                '/tasks?status=in_progress',
            );
            $cards[] = $this->kpi(
                'in_review',
                $agency ? 'In review' : 'My in-review',
                $inReview,
                (string) $inReview,
                '/tasks?status=in_review',
            );
            $cards[] = $this->kpi('open_board', 'Open board', $openBoard, (string) $openBoard, '/tasks/board');
            $cards[] = $this->kpi(
                'workload',
                $agency ? 'Team workload' : 'My workload',
                $capacity['average'],
                $capacity['average'].'%',
                '/tasks/workload',
                $capacity['trend'],
                $capacity['overallocated'].' over-allocated',
            );
        }

        return $cards;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function actions(?Employee $employee, bool $agency): array
    {
        $query = Task::query()
            ->with(['assignee.user:id,name', 'project:id,name'])
            ->where('status', TaskStatus::Assigned)
            ->orderByDesc('id')
            ->limit(8);

        if (! $agency) {
            if ($employee === null) {
                return [];
            }
            $query->where('assigned_employee_id', $employee->id);
        }

        return $query->get()->map(fn (Task $task) => [
            'id' => $task->id,
            'title' => $task->title,
            'meta' => $task->assignee?->user->name ?? 'Unassigned',
            'project' => $task->project->name,
            'href' => '/tasks/'.$task->id,
            'kind' => 'acceptance',
            'kind_label' => 'Awaiting acceptance',
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function approvals(User $user, ?Employee $employee, bool $agency): array
    {
        $items = [];

        if ($user->can(Ability::ApproveTimesheets->value)) {
            $sheets = Timesheet::query()
                ->with('employee.user:id,name')
                ->where('status', TimesheetStatus::Submitted)
                ->orderByDesc('submitted_at')
                ->limit(6)
                ->get();

            foreach ($sheets as $sheet) {
                $items[] = [
                    'id' => 'timesheet-'.$sheet->id,
                    'title' => $sheet->employee->user->name.' · '.$sheet->total_hours.'h',
                    'meta' => $sheet->period_start->toDateString().' – '.$sheet->period_end->toDateString(),
                    'href' => '/tasks/timesheets/'.$sheet->id,
                    'kind' => 'timesheet',
                    'kind_label' => 'Timesheet',
                ];
            }
        }

        if ($user->can(Ability::ReviewDeliverables->value) || (! $agency && $employee !== null)) {
            $proofs = Deliverable::query()
                ->with(['task:id,title,assigned_employee_id', 'submitter.user:id,name'])
                ->whereIn('status', [DeliverableStatus::Submitted, DeliverableStatus::InReview])
                ->when(
                    ! $agency && $employee !== null && ! $user->can(Ability::ReviewDeliverables->value),
                    fn (Builder $query) => $query->whereHas(
                        'task',
                        fn (Builder $tasks) => $tasks->where('assigned_employee_id', $employee->id),
                    ),
                )
                ->orderByDesc('submitted_at')
                ->limit(6)
                ->get();

            foreach ($proofs as $proof) {
                $items[] = [
                    'id' => 'proof-'.$proof->id,
                    'title' => $proof->task->title.' · v'.$proof->version,
                    'meta' => $proof->submitter->user->name.' · '.$proof->status->label(),
                    'href' => '/tasks/'.$proof->tm_task_id,
                    'kind' => 'review',
                    'kind_label' => 'Creative review',
                ];
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function timer(?Employee $employee): ?array
    {
        if ($employee === null) {
            return null;
        }

        $entry = TimeEntry::query()
            ->with('task:id,title,status')
            ->where('employee_id', $employee->id)
            ->where('is_running', true)
            ->latest('id')
            ->first();

        if ($entry === null) {
            return null;
        }

        return [
            'task_id' => $entry->tm_task_id,
            'task_title' => $entry->task->title,
            'started_at' => $entry->started_at->toIso8601String(),
            'running' => true,
            'yours' => true,
        ];
    }

    /**
     * @return array{average: float, overallocated: int, trend: array{direction: string, label: string}|null}
     */
    protected function capacity(User $user, ?Employee $employee, bool $agency): array
    {
        $week = WorkWeek::containing();

        if ($agency && $user->can(Ability::ViewWorkload->value)) {
            $rows = Employee::query()->assignable()->get()
                ->map(fn (Employee $member) => $this->workload->forEmployee($member, $week));

            $count = $rows->count();
            $average = $count > 0 ? round((float) $rows->avg('utilisation_pct'), 1) : 0.0;
            $over = $rows->where('band', 'overallocated')->count();

            return [
                'average' => $average,
                'overallocated' => $over,
                'trend' => $this->workloadTrend($average),
            ];
        }

        if ($employee !== null) {
            $load = $this->workload->forEmployee($employee, $week);

            return [
                'average' => $load['utilisation_pct'],
                'overallocated' => $load['band'] === 'overallocated' ? 1 : 0,
                'trend' => $this->workloadTrend($load['utilisation_pct']),
            ];
        }

        return ['average' => 0.0, 'overallocated' => 0, 'trend' => null];
    }

    /**
     * @return Builder<Task>
     */
    protected function taskQuery(?Employee $employee, bool $agency): Builder
    {
        $query = Task::query();

        if (! $agency && $employee !== null) {
            $query->where('assigned_employee_id', $employee->id);
        }

        return $query;
    }

    /**
     * @param  array{direction: string, label: string}|null  $trend
     * @return array<string, mixed>
     */
    protected function kpi(
        string $key,
        string $label,
        int|float $value,
        string $display,
        string $href,
        ?array $trend = null,
        ?string $hint = null,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'display' => $display,
            'href' => $href,
            'trend' => $trend,
            'hint' => $hint,
        ];
    }

    /**
     * @return array{direction: string, label: string}
     */
    protected function workloadTrend(float $utilisation): array
    {
        if ($utilisation > 110) {
            return ['direction' => 'up', 'label' => 'Over capacity'];
        }

        if ($utilisation < 50) {
            return ['direction' => 'down', 'label' => 'Bench time'];
        }

        return ['direction' => 'flat', 'label' => 'On track'];
    }
}
