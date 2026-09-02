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
use App\Modules\TaskManagement\Models\TaskStatusChange;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Models\Timesheet;
use App\Modules\TaskManagement\Services\MyTodoService;
use App\Modules\TaskManagement\Services\WorkloadCalculator;
use App\Modules\TaskManagement\Support\WorkWeek;
use Illuminate\Database\Eloquent\Builder;

/**
 * Staff home. Lives outside Core/TM so it can read Task Management
 * without breaking the one-way module boundary.
 */
class CommandCenter
{
    public function __construct(
        protected WorkloadCalculator $workload,
        protected MyTodoService $myTodos,
    ) {}

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
            'overview' => $canTasks ? $this->overview($user, $employee, $agency) : [],
            'team' => $canTasks && $agency ? $this->team($user) : null,
            'attention' => $canTasks && $agency ? $this->attention() : null,
            'activity' => $canTasks ? $this->activity($employee, $agency) : [],
            'actions' => $canTasks ? $this->actions($employee, $agency) : [],
            'approvals' => $canTasks ? $this->approvals($user, $employee, $agency) : [],
            'timer' => $canTasks ? $this->timer($employee) : null,
            'my_todo' => $canTasks ? $this->myTodos->dashboardSnapshot($user) : null,
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
    protected function overview(User $user, ?Employee $employee, bool $agency): array
    {
        $tasks = $this->taskQuery($employee, $agency);
        $listBase = $agency ? '/tasks?scope=all' : '/tasks?scope=mine';
        $cards = [];

        if ($agency) {
            $cards[] = $this->stat(
                'total',
                'Total tasks',
                (clone $tasks)->count(),
                $listBase,
            );
        }

        $cards[] = $this->stat(
            'in_progress',
            $agency ? 'In progress' : 'My in progress',
            (clone $tasks)->where('status', TaskStatus::InProgress)->count(),
            $listBase.'&status=in_progress',
        );

        if ($agency) {
            $cards[] = $this->stat(
                'pending_acceptance',
                'Pending acceptance',
                (clone $tasks)->where('status', TaskStatus::Assigned)->count(),
                $listBase.'&status=assigned',
            );
        }

        $cards[] = $this->stat(
            'in_review',
            $agency ? 'Under review' : 'My under review',
            (clone $tasks)->where('status', TaskStatus::InReview)->count(),
            $listBase.'&status=in_review',
        );

        $cards[] = $this->stat(
            'changes_requested',
            $agency ? 'Changes requested' : 'My changes requested',
            (clone $tasks)->where('status', TaskStatus::Revision)->count(),
            $listBase.'&status=revision',
        );

        $cards[] = $this->stat(
            'open_board',
            'Open board',
            Task::query()->claimable()->count(),
            '/tasks/board',
        );

        $cards[] = $this->stat(
            'completed_today',
            $agency ? 'Completed today' : 'Completed today',
            (clone $tasks)
                ->where('status', TaskStatus::Completed)
                ->whereDate('completed_at', today())
                ->count(),
            $listBase.'&status=completed&completed=today',
        );

        $cards[] = $this->stat(
            'overdue',
            $agency ? 'Overdue' : 'My overdue',
            (clone $tasks)->overdue()->count(),
            $listBase.'&overdue=1',
        );

        if ($agency && $user->can(Ability::ViewWorkload->value)) {
            $capacity = $this->capacity($user, $employee, true);

            $cards[] = [
                'key' => 'team_workload',
                'label' => 'Team workload',
                'count' => $capacity['average'],
                'display' => $capacity['average'].'%',
                'href' => '/tasks/workload',
                'hint' => $capacity['overallocated'].' over-allocated',
            ];
        }

        return $cards;
    }

    /**
     * @return array<string, mixed>
     */
    protected function team(User $user): array
    {
        $listBase = '/tasks?scope=all';
        $tasks = Task::query();
        $availability = $this->teamAvailability($user);

        return [
            'availability' => $availability,
            'timers' => [
                'count' => TimeEntry::query()->where('is_running', true)->count(),
                'href' => $listBase.'&status=in_progress',
                'entries' => TimeEntry::query()
                    ->with(['employee.user:id,name', 'task:id,title'])
                    ->where('is_running', true)
                    ->latest('started_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (TimeEntry $entry) => [
                        'id' => $entry->id,
                        'employee' => $entry->employee->user->name,
                        'task' => $entry->task->title,
                        'href' => '/tasks/'.$entry->tm_task_id,
                        'started_at' => $entry->started_at->toIso8601String(),
                    ])
                    ->all(),
            ],
            'pending' => [
                'need_review' => $this->pendingStat(
                    (clone $tasks)->where('status', TaskStatus::InReview)->count(),
                    $listBase.'&status=in_review',
                ),
                'overdue' => $this->pendingStat(
                    (clone $tasks)->overdue()->count(),
                    $listBase.'&overdue=1',
                ),
                'unassigned' => $this->pendingStat(
                    (clone $tasks)->needsAssignment()->count(),
                    $listBase.'&scope=unassigned&status=draft',
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function teamAvailability(User $user): ?array
    {
        if (! $user->can(Ability::ViewWorkload->value)) {
            return null;
        }

        $week = WorkWeek::containing();
        $rows = Employee::query()
            ->assignable()
            ->get()
            ->map(fn (Employee $member) => $this->workload->forEmployee($member, $week));

        return [
            'available' => $rows->where('band', 'bench')->count(),
            'working' => $rows->where('band', 'on_track')->count(),
            'overloaded' => $rows->where('band', 'overallocated')->count(),
            'href' => '/tasks/workload',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attention(): array
    {
        return [
            'overdue' => Task::query()
                ->overdue()
                ->with(['assignee.user:id,name'])
                ->orderBy('due_at')
                ->limit(6)
                ->get()
                ->map(fn (Task $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'assignee' => $task->assignee?->user->name ?? 'Unassigned',
                    'due_at' => $task->due_at?->toIso8601String(),
                    'href' => '/tasks/'.$task->id,
                ])
                ->all(),
            'unassigned' => Task::query()
                ->needsAssignment()
                ->with('project:id,name')
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(fn (Task $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project' => $task->project->name,
                    'href' => '/tasks/'.$task->id,
                ])
                ->all(),
            'overdue_href' => '/tasks?scope=all&overdue=1',
            'unassigned_href' => '/tasks?scope=unassigned&status=draft',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function activity(?Employee $employee, bool $agency): array
    {
        return TaskStatusChange::query()
            ->with(['changedBy:id,name', 'task:id,title'])
            ->when(
                ! $agency && $employee !== null,
                fn (Builder $query) => $query->whereHas(
                    'task',
                    fn (Builder $tasks) => $tasks->where('assigned_employee_id', $employee->id),
                ),
            )
            ->orderByDesc('changed_at')
            ->limit(12)
            ->get()
            ->map(fn (TaskStatusChange $change) => [
                'id' => $change->id,
                'message' => $this->activityMessage($change),
                'at' => $change->changed_at->toIso8601String(),
                'href' => '/tasks/'.$change->tm_task_id,
            ])
            ->all();
    }

    protected function activityMessage(TaskStatusChange $change): string
    {
        $name = $change->changedBy?->name ?? 'Someone';
        $title = $change->task->title;

        return match ($change->to_status) {
            TaskStatus::InReview => "{$name} submitted \"{$title}\" for review",
            TaskStatus::Revision => "Changes requested on \"{$title}\"",
            TaskStatus::InProgress => $change->from_status === TaskStatus::Open
                ? "{$name} claimed \"{$title}\" from the open board"
                : "{$name} started work on \"{$title}\"",
            TaskStatus::Completed => "\"{$title}\" was completed",
            TaskStatus::Open => "\"{$title}\" was published to the open board",
            TaskStatus::Assigned => "{$name} assigned \"{$title}\"",
            default => "{$name} moved \"{$title}\" to {$change->to_status->label()}",
        };
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
     * @return array{average: float, overallocated: int}
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
            ];
        }

        if ($employee !== null) {
            $load = $this->workload->forEmployee($employee, $week);

            return [
                'average' => $load['utilisation_pct'],
                'overallocated' => $load['band'] === 'overallocated' ? 1 : 0,
            ];
        }

        return ['average' => 0.0, 'overallocated' => 0];
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
     * @return array<string, mixed>
     */
    protected function stat(string $key, string $label, int|float $count, string $href, ?string $hint = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'display' => (string) $count,
            'href' => $href,
            'hint' => $hint,
        ];
    }

    /**
     * @return array{count: int, href: string}
     */
    protected function pendingStat(int $count, string $href): array
    {
        return [
            'count' => $count,
            'href' => $href,
        ];
    }
}
