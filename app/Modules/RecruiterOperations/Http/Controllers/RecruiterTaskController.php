<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskPriority;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Enums\RecruiterWorkType;
use App\Modules\RecruiterOperations\Exceptions\RecruiterTaskWorkflowException;
use App\Modules\RecruiterOperations\Http\Requests\StoreRecruiterTaskRequest;
use App\Modules\RecruiterOperations\Http\Requests\UpdateRecruiterTaskRequest;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Models\RecruiterTaskEvent;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Modules\RecruiterOperations\Services\RecruiterTaskExporter;
use App\Modules\RecruiterOperations\Services\RecruiterTaskService;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Recruiter task screens. Status and assignee never change here: those go
 * through RecruiterTaskWorkflowController and RecruiterTaskWorkflow.
 */
class RecruiterTaskController extends Controller
{
    private const FIELD_LABELS = [
        'title' => 'Title',
        'description' => 'Description',
        'instructions' => 'Instructions',
        'work_type' => 'Work type',
        'priority' => 'Priority',
        'due_at' => 'Due date',
        'target_count' => 'Target',
    ];

    public function __construct(
        protected RecruiterTaskService $tasks,
        protected RecruiterDirectory $directory,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RecruiterTask::class);

        $user = $request->user();
        $teamView = $user->can('viewTeam', RecruiterTask::class);
        $filters = $this->listFilters($request, $teamView);

        $tasks = $this->filteredQuery($user, $filters, $teamView)
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (RecruiterTask $task) => $this->summarise($task));

        return Inertia::render('RecruiterOperations/tasks/index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'statuses' => RecruiterTaskStatus::options(),
            'priorities' => RecruiterTaskPriority::options(),
            'workTypes' => RecruiterWorkType::options(),
            'recruiters' => $teamView ? $this->directory->options() : [],
            'pageTitle' => $teamView ? 'Recruiter Tasks' : 'My Tasks',
            'can' => [
                'create' => $user->can('create', RecruiterTask::class),
                'viewTeam' => $teamView,
            ],
        ]);
    }

    public function exportExcel(Request $request, RecruiterTaskExporter $exporter): StreamedResponse
    {
        return $exporter->excel($this->exportRows($request));
    }

    public function exportPdf(Request $request, RecruiterTaskExporter $exporter)
    {
        return $exporter->pdf($this->exportRows($request));
    }

    public function create(): Response
    {
        $this->authorize('create', RecruiterTask::class);

        return Inertia::render('RecruiterOperations/tasks/create', $this->formOptions());
    }

    public function store(StoreRecruiterTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', RecruiterTask::class);

        $validated = $request->validated();
        $assignee = Employee::query()->findOrFail($validated['assigned_employee_id']);

        try {
            $task = $this->tasks->create($request->user(), $validated, $assignee);
        } catch (RecruiterTaskWorkflowException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('recruiter.tasks.show', $task)->with('success', 'Recruiter task created and assigned.');
    }

    public function show(Request $request, RecruiterTask $recruiterTask): Response
    {
        $this->authorize('view', $recruiterTask);

        $user = $request->user();
        $recruiterTask->load(['assignee:id,user_id,employee_code', 'assignee.user:id,name', 'creator:id,name']);
        $actions = $this->actionsFor($user, $recruiterTask);

        return Inertia::render('RecruiterOperations/tasks/show', [
            'task' => [
                ...$this->summarise($recruiterTask),
                'description' => $recruiterTask->description,
                'instructions' => $recruiterTask->instructions,
                'created_by_name' => $recruiterTask->creator?->name,
                'completion_note' => $recruiterTask->completion_note,
                'accepted_at' => $recruiterTask->accepted_at?->toIso8601String(),
                'started_at' => $recruiterTask->started_at?->toIso8601String(),
                'cancelled_at' => $recruiterTask->cancelled_at?->toIso8601String(),
                'created_at' => $recruiterTask->created_at->toIso8601String(),
            ],
            'timeline' => $this->timeline($recruiterTask),
            'actions' => $actions,
            'recruiters' => $actions['reassign'] ? $this->directory->options() : [],
            'dailyActivities' => $this->linkedActivities($user, $recruiterTask),
            'canLogActivity' => $recruiterTask->isAssignedTo($user->employee)
                && ! $recruiterTask->status->isTerminal()
                && $recruiterTask->status !== RecruiterTaskStatus::Declined
                && $user->can('create', RecruiterDailyActivity::class),
        ]);
    }

    /**
     * Activities logged against the task that the viewer may see: everyone's
     * with team visibility, otherwise only their own. The total is the sum of
     * what was reported, shown beside the target without any percentage.
     *
     * @return array{items: list<array<string, mixed>>, reported_quantity: int|null}
     */
    protected function linkedActivities(User $user, RecruiterTask $task): array
    {
        $employeeId = $user->employee?->id;
        $teamView = $user->can('viewTeam', RecruiterDailyActivity::class);

        if (! $teamView && $employeeId === null) {
            return ['items' => [], 'reported_quantity' => null];
        }

        $activities = $task->dailyActivities()
            ->with(['employee:id,user_id', 'employee.user:id,name'])
            ->when(! $teamView, fn (Builder $query) => $query->where('employee_id', $employeeId))
            ->orderBy('activity_date')
            ->orderByRaw('start_time is null')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        $quantities = $activities->pluck('quantity')->filter(fn ($quantity) => $quantity !== null);

        return [
            'items' => $activities->map(fn (RecruiterDailyActivity $activity) => [
                'id' => $activity->id,
                'activity_date' => $activity->activity_date->toDateString(),
                'activity_type_label' => $activity->activity_type->label(),
                'title' => $activity->title,
                'start_time' => RecruiterDailyActivity::clock($activity->start_time),
                'end_time' => RecruiterDailyActivity::clock($activity->end_time),
                'duration_minutes' => $activity->duration_minutes,
                'quantity' => $activity->quantity,
                'recruiter_name' => $activity->employee->user->name ?? null,
            ])->values()->all(),
            'reported_quantity' => $quantities->isEmpty() ? null : (int) $quantities->sum(),
        ];
    }

    public function edit(RecruiterTask $recruiterTask): Response
    {
        $this->authorize('update', $recruiterTask);

        return Inertia::render('RecruiterOperations/tasks/edit', [
            ...$this->formOptions(),
            'task' => [
                'id' => $recruiterTask->id,
                'title' => $recruiterTask->title,
                'description' => $recruiterTask->description,
                'instructions' => $recruiterTask->instructions,
                'work_type' => $recruiterTask->work_type->value,
                'priority' => $recruiterTask->priority->value,
                'due_at' => $recruiterTask->due_at?->format('Y-m-d\TH:i'),
                'target_count' => $recruiterTask->target_count,
            ],
        ]);
    }

    public function update(UpdateRecruiterTaskRequest $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('update', $recruiterTask);

        try {
            $this->tasks->update($recruiterTask, $request->validated(), $request->user());
        } catch (RecruiterTaskWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('recruiter.tasks.show', $recruiterTask)->with('success', 'Recruiter task updated.');
    }

    public function destroy(RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('delete', $recruiterTask);

        try {
            $this->tasks->delete($recruiterTask);
        } catch (RecruiterTaskWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('recruiter.tasks.index')->with('success', 'Recruiter task deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'workTypes' => RecruiterWorkType::options(),
            'priorities' => RecruiterTaskPriority::options(),
            'recruiters' => $this->directory->options(),
        ];
    }

    /**
     * @return array{scope: string, search: string, recruiter: int|null, status: string, work_type: string, priority: string, due_date: string}
     */
    protected function listFilters(Request $request, bool $teamView): array
    {
        $dueDate = $request->string('due_date')->trim()->value();

        return [
            'scope' => $teamView && $request->string('scope')->value() === 'mine' ? 'mine' : ($teamView ? 'all' : 'mine'),
            'search' => $request->string('search')->trim()->value(),
            'recruiter' => $teamView ? ($request->integer('recruiter') ?: null) : null,
            'status' => RecruiterTaskStatus::tryFrom($request->string('status')->value())->value ?? '',
            'work_type' => RecruiterWorkType::tryFrom($request->string('work_type')->value())->value ?? '',
            'priority' => RecruiterTaskPriority::tryFrom($request->string('priority')->value())->value ?? '',
            'due_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) === 1 ? $dueDate : '',
        ];
    }

    /**
     * Without team visibility a person sees the tasks assigned to them and any
     * they created, never another recruiter's.
     *
     * @param  array{scope: string, search: string, recruiter: int|null, status: string, work_type: string, priority: string, due_date: string}  $filters
     * @return Builder<RecruiterTask>
     */
    protected function filteredQuery(User $user, array $filters, bool $teamView): Builder
    {
        $employeeId = $user->employee?->id;

        return RecruiterTask::query()
            ->with(['assignee:id,user_id,employee_code', 'assignee.user:id,name'])
            ->when(! $teamView, function (Builder $query) use ($user, $employeeId) {
                $query->where(function (Builder $visible) use ($user, $employeeId) {
                    $visible->where('created_by_user_id', $user->id);

                    if ($employeeId !== null) {
                        $visible->orWhere('assigned_employee_id', $employeeId);
                    }
                });
            })
            ->when($teamView && $filters['scope'] === 'mine', function (Builder $query) use ($employeeId) {
                // whereKey(null) would match nothing useful; a user without an
                // employee profile has no tasks of their own.
                $employeeId === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where('assigned_employee_id', $employeeId);
            })
            ->when($filters['recruiter'], fn (Builder $query, int $id) => $query->where('assigned_employee_id', $id))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['work_type'], fn (Builder $query, string $type) => $query->where('work_type', $type))
            ->when($filters['priority'], fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($filters['due_date'], function (Builder $query, string $date) {
                $day = Carbon::parse($date);
                $query->whereBetween('due_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
            })
            ->when($filters['search'], fn (Builder $query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->orderByRaw("case when status in ('completed', 'cancelled') then 1 else 0 end")
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->orderByDesc('id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, RecruiterTask>
     */
    protected function exportRows(Request $request)
    {
        $this->authorize('viewAny', RecruiterTask::class);

        $user = $request->user();
        $teamView = $user->can('viewTeam', RecruiterTask::class);

        return $this->filteredQuery($user, $this->listFilters($request, $teamView), $teamView)->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(RecruiterTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'work_type' => $task->work_type->value,
            'work_type_label' => $task->work_type->label(),
            'priority' => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'status' => $task->status->value,
            'status_label' => $task->status->label(),
            'assignee_name' => $task->assignee?->user?->name,
            'due_at' => $task->due_at?->toIso8601String(),
            'target_count' => $task->target_count,
            'achieved_count' => $task->achieved_count,
            'completed_at' => $task->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Which buttons to offer. Assignee-only moves compare the actual employee
     * record, so Super Admin is not offered another recruiter's Accept button
     * that the workflow would then refuse.
     *
     * @return array<string, bool>
     */
    protected function actionsFor(User $user, RecruiterTask $task): array
    {
        $status = $task->status;
        $isAssignee = $task->isAssignedTo($user->employee);

        return [
            'accept' => $isAssignee && $status === RecruiterTaskStatus::Assigned,
            'decline' => $isAssignee && $status === RecruiterTaskStatus::Assigned,
            'complete' => $isAssignee && $status === RecruiterTaskStatus::InProgress,
            'hold' => $status === RecruiterTaskStatus::InProgress && $user->can('hold', $task),
            'resume' => $status === RecruiterTaskStatus::OnHold && $user->can('resume', $task),
            'reopen' => $status === RecruiterTaskStatus::Completed && $user->can('reopen', $task),
            'cancel' => $status->canTransitionTo(RecruiterTaskStatus::Cancelled) && $user->can('cancel', $task),
            'reassign' => $status->isReassignable() && $user->can('reassign', $task),
            'edit' => $user->can('update', $task),
            'delete' => $user->can('delete', $task) && $task->isDeletable(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function timeline(RecruiterTask $task): array
    {
        return $task->events()
            ->with(['actor:id,name', 'fromEmployee:id,user_id', 'fromEmployee.user:id,name', 'toEmployee:id,user_id', 'toEmployee.user:id,name'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get()
            ->map(fn (RecruiterTaskEvent $event) => [
                'id' => $event->id,
                'event' => $event->event->value,
                'event_label' => $event->event->label(),
                'actor_name' => $event->actor?->name,
                'from_status' => $event->from_status?->value,
                'from_status_label' => $event->from_status?->label(),
                'to_status' => $event->to_status?->value,
                'to_status_label' => $event->to_status?->label(),
                'from_employee_name' => $event->fromEmployee?->user?->name,
                'to_employee_name' => $event->toEmployee?->user?->name,
                'reason' => $event->reason,
                'details' => $this->eventDetails($event),
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    protected function eventDetails(RecruiterTaskEvent $event): ?string
    {
        $metadata = $event->metadata ?? [];

        if ($event->event === RecruiterTaskEventType::Updated) {
            $fields = array_map(
                fn (string $field) => self::FIELD_LABELS[$field] ?? $field,
                array_keys($metadata['changes'] ?? []),
            );

            return $fields === [] ? null : 'Changed: '.implode(', ', $fields);
        }

        if ($event->event === RecruiterTaskEventType::Completed) {
            $achieved = $metadata['achieved_count'] ?? null;
            $target = $metadata['target_count'] ?? null;

            if ($achieved === null) {
                return null;
            }

            return $target !== null ? "Achieved {$achieved} of {$target}" : "Achieved {$achieved}";
        }

        return null;
    }
}
