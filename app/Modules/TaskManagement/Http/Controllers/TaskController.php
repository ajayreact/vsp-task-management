<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TaskType;
use App\Modules\TaskManagement\Http\Requests\TaskRequest;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskAssignment;
use App\Modules\TaskManagement\Models\TaskStatusChange;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $canSeeEverything = $user->can(Ability::ViewAllTasks->value);

        // Someone who can only see their own work has no use for the other
        // scopes, so the default and the only option is "mine".
        $scope = $canSeeEverything ? $request->string('scope')->value() ?: 'all' : 'mine';

        $filters = [
            'scope' => $scope,
            'search' => $request->string('search')->trim()->value(),
            'project' => $request->integer('project') ?: null,
            'status' => $request->string('status')->value(),
            'priority' => $request->string('priority')->value(),
        ];

        $tasks = Task::query()
            ->with([
                'project:id,name,code',
                'assignee:id,user_id',
                'assignee.user:id,name',
                'department:id,name',
            ])
            ->when($scope === 'mine', function (Builder $query) use ($user) {
                $employeeId = $user->employee?->id;

                // A user with no employee profile has no work of their own, and
                // whereKey(null) would otherwise match everything.
                $employeeId === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where('assigned_employee_id', $employeeId);
            })
            ->when($scope === 'unassigned', fn (Builder $query) => $query->whereNull('assigned_employee_id'))
            ->when($filters['search'], fn (Builder $query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['project'], fn (Builder $query, int $id) => $query->where('tm_project_id', $id))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['priority'], fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->orderByRaw("field(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderByRaw('due_at is null, due_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Task $task) => $this->summarise($task));

        return Inertia::render('TaskManagement/tasks/index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
            'can' => [
                'create' => $user->can('create', Task::class),
                'viewAll' => $canSeeEverything,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Task::class);

        return Inertia::render('TaskManagement/tasks/create', [
            ...$this->formOptions(),
            'defaultProjectId' => $request->integer('project') ?: null,
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $task = Task::create([
            ...$request->validated(),
            'status' => TaskStatus::Draft,
            'created_by_user_id' => $request->user()->id,
        ]);

        $task->statusHistory()->create([
            'from_status' => null,
            'to_status' => TaskStatus::Draft,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        return to_route('tasks.show', $task)->with('success', 'Task created as a draft.');
    }

    public function show(Request $request, Task $task): Response
    {
        $this->authorize('view', $task);

        $user = $request->user();

        $task->load([
            'project:id,name,code,tm_company_id',
            'project.company:id,name',
            'department:id,name',
            'assignee:id,user_id,employee_code',
            'assignee.user:id,name',
            'creator:id,name',
        ]);

        return Inertia::render('TaskManagement/tasks/show', [
            'task' => [
                ...$this->summarise($task),
                'description' => $task->description,
                'estimated_hours' => $task->estimated_hours,
                'company_name' => $task->project->company->name,
                'created_by' => $task->creator->name,
                'started_at' => $task->started_at?->toIso8601String(),
                'completed_at' => $task->completed_at?->toIso8601String(),
            ],
            'history' => $task->statusHistory()
                ->with('changedBy:id,name')
                ->orderByDesc('changed_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (TaskStatusChange $change) => [
                    'id' => $change->id,
                    'from' => $change->from_status?->label(),
                    'to' => $change->to_status->label(),
                    'by' => $change->changedBy?->name,
                    'at' => $change->changed_at->toIso8601String(),
                ]),
            'assignments' => $task->assignments()
                ->with(['employee.user:id,name', 'assignedBy:id,name'])
                ->orderByDesc('id')
                ->get()
                ->map(fn (TaskAssignment $assignment) => [
                    'id' => $assignment->id,
                    'employee_name' => $assignment->employee->user->name,
                    'mode' => $assignment->mode->label(),
                    'status' => $assignment->status->label(),
                    'assigned_by' => $assignment->assignedBy?->name,
                    'responded_at' => $assignment->responded_at?->toIso8601String(),
                    'decline_reason' => $assignment->decline_reason,
                ]),
            // Intersecting the state machine with the policy means the buttons
            // shown are exactly the moves that will actually be permitted.
            'allowedTransitions' => $user->can('progress', $task)
                ? array_map(
                    fn (TaskStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                    $task->status->allowedNext(),
                )
                : [],
            'assignableEmployees' => $user->can('assign', $task) ? $this->assignableEmployees() : [],
            'can' => [
                'update' => $user->can('update', $task),
                'delete' => $user->can('delete', $task),
                'assign' => $user->can('assign', $task),
                'claim' => $user->can('claim', $task),
                'respond' => $user->can('respond', $task) && $task->status === TaskStatus::Assigned,
            ],
        ]);
    }

    public function edit(Task $task): Response
    {
        $this->authorize('update', $task);

        return Inertia::render('TaskManagement/tasks/edit', [
            ...$this->formOptions(),
            'task' => [
                'id' => $task->id,
                'tm_project_id' => $task->tm_project_id,
                'department_id' => $task->department_id,
                'title' => $task->title,
                'description' => $task->description,
                'type' => $task->type->value,
                'priority' => $task->priority->value,
                'estimated_hours' => $task->estimated_hours,
                'due_at' => $task->due_at?->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return to_route('tasks.show', $task)->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return to_route('tasks.index')->with('success', 'Task deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'type' => $task->type->label(),
            'priority' => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'status' => $task->status->value,
            'status_label' => $task->status->label(),
            'assignment_mode' => $task->assignment_mode->value,
            'project' => ['id' => $task->project->id, 'name' => $task->project->name],
            'department_name' => $task->department?->name,
            'assignee_name' => $task->assignee?->user->name,
            'due_at' => $task->due_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    protected function assignableEmployees(): array
    {
        return Employee::query()
            ->with('user:id,name')
            ->assignable()
            ->orderBy('employee_code')
            ->get(['id', 'user_id', 'employee_code'])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'label' => $employee->user->name.' · '.$employee->employee_code,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'projects' => Project::query()
                ->acceptingWork()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => TaskType::options(),
            'priorities' => TaskPriority::options(),
        ];
    }
}
