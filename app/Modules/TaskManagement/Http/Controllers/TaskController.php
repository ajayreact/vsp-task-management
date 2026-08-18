<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\RecurrenceFrequency;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TaskType;
use App\Modules\TaskManagement\Http\Requests\TaskRequest;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\DeliverableReview;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskAssignment;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use App\Modules\TaskManagement\Models\TaskComment;
use App\Modules\TaskManagement\Models\TaskReminder;
use App\Modules\TaskManagement\Models\TaskStatusChange;
use App\Modules\TaskManagement\Models\TaskSubtask;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Services\TaskListExporter;
use App\Modules\TaskManagement\Services\TaskWorkflow;
use App\Support\Pagination;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $canSeeEverything = $user->can(Ability::ViewAllTasks->value);
        $filters = $this->listFilters($request, $canSeeEverything);

        $tasks = $this->filteredTasksQuery($request, $filters)
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (Task $task) => $this->summarise($task));

        return Inertia::render('TaskManagement/tasks/index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => TaskStatus::options(),
            'priorities' => TaskPriority::options(),
            'pageTitle' => $canSeeEverything ? 'Tasks' : 'My Tasks',
            'can' => [
                'create' => $user->can('create', Task::class),
                'viewAll' => $canSeeEverything,
            ],
        ]);
    }

    public function exportExcel(Request $request, TaskListExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Task::class);

        $canSeeEverything = $request->user()->can(Ability::ViewAllTasks->value);
        $filters = $this->listFilters($request, $canSeeEverything);
        $tasks = $this->filteredTasksQuery($request, $filters)->get();

        return $exporter->excel($tasks);
    }

    public function exportPdf(Request $request, TaskListExporter $exporter)
    {
        $this->authorize('viewAny', Task::class);

        $canSeeEverything = $request->user()->can(Ability::ViewAllTasks->value);
        $filters = $this->listFilters($request, $canSeeEverything);
        $tasks = $this->filteredTasksQuery($request, $filters)->get();

        return $exporter->pdf($tasks);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Task::class);

        return Inertia::render('TaskManagement/tasks/create', [
            ...$this->formOptions(),
            'defaultProjectId' => $request->integer('project') ?: null,
            'assignableEmployees' => $request->user()->can(Ability::AssignTasks->value)
                ? $this->assignableEmployees()
                : [],
            'can' => [
                'assign' => $request->user()->can(Ability::AssignTasks->value),
            ],
        ]);
    }

    public function store(TaskRequest $request, TaskWorkflow $workflow): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $validated = $request->validated();
        $assigneeId = $validated['assigned_employee_id'] ?? null;
        unset($validated['assigned_employee_id']);

        $task = Task::create([
            ...$validated,
            'status' => TaskStatus::Draft,
            'created_by_user_id' => $request->user()->id,
        ]);

        $task->statusHistory()->create([
            'from_status' => null,
            'to_status' => TaskStatus::Draft,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        if ($assigneeId !== null) {
            $this->authorize('assign', $task);

            $employee = Employee::query()->findOrFail($assigneeId);
            $workflow->assign($task, $employee, $request->user());

            return to_route('tasks.show', $task)->with('success', 'Task created and assigned.');
        }

        return to_route('tasks.show', $task)->with('success', 'Task created as a draft.');
    }

    public function show(Request $request, Task $task): Response
    {
        $this->authorize('view', $task);

        if ($request->user()->can('viewAdminDetails', $task)) {
            return $this->renderAdminTaskShow($request, $task);
        }

        return $this->renderEmployeeTaskShow($request, $task);
    }

    protected function renderAdminTaskShow(Request $request, Task $task): Response
    {
        $user = $request->user();

        $task->load([
            'project:id,name,code,tm_company_id',
            'project.company:id,name',
            'department:id,name',
            'assignee:id,user_id,employee_code',
            'assignee.user:id,name',
            'creator:id,name',
            'ownedRecurrenceRule',
        ]);

        return Inertia::render('TaskManagement/tasks/show', $this->adminTaskShowPayload($request, $task, $user));
    }

    protected function renderEmployeeTaskShow(Request $request, Task $task): Response
    {
        $user = $request->user();
        $employeeId = $user->employee?->id;

        $task->load([
            'project:id,name,code,tm_company_id',
            'project.company:id,name',
            'assignee:id,user_id,employee_code',
            'assignee.user:id,name',
        ]);

        $subtasksQuery = $task->subtasks()
            ->with('assignee.user:id,name')
            ->when($employeeId !== null, fn (Builder $query) => $query->where('assigned_employee_id', $employeeId))
            ->orderBy('sort_order')
            ->orderBy('id');

        $subtasks = $subtasksQuery->get();
        $timeEntriesQuery = $task->timeEntries()
            ->with('employee.user:id,name')
            ->where('is_running', false)
            ->orderByDesc('started_at')
            ->limit(20);

        if ($employeeId !== null) {
            $timeEntriesQuery->where('employee_id', $employeeId);
        } else {
            $timeEntriesQuery->whereRaw('1 = 0');
        }

        return Inertia::render('TaskManagement/tasks/show-employee', [
            'task' => [
                ...$this->summarise($task),
                'description' => $task->description,
                'company_name' => $task->project->company->name,
            ],
            'allowedTransitions' => $user->can('progress', $task)
                ? array_map(
                    fn (TaskStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                    $task->status->allowedNext(),
                )
                : [],
            'timer' => $this->timerPayload($task, $employeeId),
            'timeEntries' => $timeEntriesQuery->get()->map(fn (TimeEntry $entry) => [
                'id' => $entry->id,
                'employee_name' => $entry->employee->user->name,
                'started_at' => $entry->started_at->toIso8601String(),
                'ended_at' => $entry->ended_at?->toIso8601String(),
                'hours' => $entry->hours(),
                'source' => $entry->source->label(),
                'note' => $entry->note,
                'can_delete' => $user->can('delete', $entry),
            ]),
            'attachments' => $this->attachmentPayload($task, $user),
            'deliverables' => $this->deliverablePayload($task, $user),
            'comments' => $this->commentPayload($task, $user),
            'checklist' => $this->checklistPayload($task),
            'subtasks' => [
                'items' => $subtasks->map(fn (TaskSubtask $subtask) => [
                    'id' => $subtask->id,
                    'title' => $subtask->title,
                    'description' => $subtask->description,
                    'status' => $subtask->status->value,
                    'status_label' => $subtask->status->label(),
                    'assignee_name' => $subtask->assignee?->user->name,
                    'assigned_employee_id' => $subtask->assigned_employee_id,
                    'due_at' => $subtask->due_at?->toIso8601String(),
                    'completed_at' => $subtask->completed_at?->toIso8601String(),
                    'sort_order' => $subtask->sort_order,
                    'can_update' => $user->can('updateOwn', $subtask) || $user->can('toggle', $subtask),
                ]),
                'completed' => $subtasks->where('status', SubtaskStatus::Completed)->count(),
                'total' => $subtasks->count(),
            ],
            'subtaskStatuses' => SubtaskStatus::options(),
            'submitReview' => $this->submitReviewContext($task, $user),
            'can' => [
                'claim' => $user->can('claim', $task),
                'respond' => $user->can('respond', $task) && $task->status === TaskStatus::Assigned,
                'logTime' => $user->can('logTime', $task),
                'attachFiles' => $user->can('attachFiles', $task),
                'comment' => $user->can('comment', $task),
                'completeChecklist' => $user->can('completeChecklist', $task),
                'submitProof' => $user->can('submitProof', $task),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function adminTaskShowPayload(Request $request, Task $task, User $user): array
    {
        return [
            'task' => [
                ...$this->summarise($task),
                'description' => $task->description,
                'estimated_hours' => $task->estimated_hours,
                'company_name' => $task->project->company->name,
                'created_by' => $task->creator->name,
                'started_at' => $task->started_at?->toIso8601String(),
                'completed_at' => $task->completed_at?->toIso8601String(),
                'assigned_user_id' => $task->assignee?->user_id,
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
            'timer' => $this->timerPayload($task, $user->employee?->id),
            'timeEntries' => $task->timeEntries()
                ->with('employee.user:id,name')
                ->where('is_running', false)
                ->orderByDesc('started_at')
                ->limit(20)
                ->get()
                ->map(fn (TimeEntry $entry) => [
                    'id' => $entry->id,
                    'employee_name' => $entry->employee->user->name,
                    'started_at' => $entry->started_at->toIso8601String(),
                    'ended_at' => $entry->ended_at?->toIso8601String(),
                    'hours' => $entry->hours(),
                    'source' => $entry->source->label(),
                    'note' => $entry->note,
                    'can_delete' => $user->can('delete', $entry),
                ]),
            'attachments' => $this->attachmentPayload($task, $user),
            'deliverables' => $this->deliverablePayload($task, $user),
            'comments' => $this->commentPayload($task, $user),
            'checklist' => $this->checklistPayload($task),
            'subtasks' => [
                'items' => $task->subtasks()
                    ->with('assignee.user:id,name')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->map(fn (TaskSubtask $subtask) => [
                        'id' => $subtask->id,
                        'title' => $subtask->title,
                        'description' => $subtask->description,
                        'status' => $subtask->status->value,
                        'status_label' => $subtask->status->label(),
                        'assignee_name' => $subtask->assignee?->user->name,
                        'assigned_employee_id' => $subtask->assigned_employee_id,
                        'due_at' => $subtask->due_at?->toIso8601String(),
                        'completed_at' => $subtask->completed_at?->toIso8601String(),
                        'sort_order' => $subtask->sort_order,
                    ]),
                'completed' => $task->subtasks()->where('status', SubtaskStatus::Completed)->count(),
                'total' => $task->subtasks()->count(),
            ],
            'subtaskStatuses' => SubtaskStatus::options(),
            'subtaskAssignableEmployees' => $user->can('manageSubtasks', $task) ? $this->assignableEmployees() : [],
            'reminders' => $task->reminders()
                ->with(['recipient:id,name', 'creator:id,name'])
                ->orderBy('remind_at')
                ->orderBy('id')
                ->get()
                ->map(fn (TaskReminder $reminder) => [
                    'id' => $reminder->id,
                    'remind_at' => $reminder->remind_at->toIso8601String(),
                    'message' => $reminder->message,
                    'recipient_name' => $reminder->recipient->name,
                    'recipient_user_id' => $reminder->recipient_user_id,
                    'created_by' => $reminder->creator->name,
                    'sent_at' => $reminder->sent_at?->toIso8601String(),
                    'can_delete' => $user->can('delete', $reminder),
                ]),
            'reminderRecipients' => $user->can('manageReminders', $task) ? $this->reminderRecipients() : [],
            'recurrence' => [
                'can_manage' => $user->can('manageRecurrence', $task),
                'frequencies' => RecurrenceFrequency::options(),
                'rule' => $task->ownedRecurrenceRule ? [
                    'frequency' => $task->ownedRecurrenceRule->frequency->value,
                    'interval' => $task->ownedRecurrenceRule->interval,
                    'start_date' => $task->ownedRecurrenceRule->start_date->format('Y-m-d'),
                    'end_date' => $task->ownedRecurrenceRule->end_date?->format('Y-m-d'),
                    'max_occurrences' => $task->ownedRecurrenceRule->max_occurrences,
                    'occurrences_generated' => $task->ownedRecurrenceRule->occurrences_generated,
                    'is_active' => $task->ownedRecurrenceRule->is_active,
                ] : null,
            ],
            'can' => [
                'update' => $user->can('update', $task),
                'delete' => $user->can('delete', $task),
                'assign' => $user->can('assign', $task),
                'claim' => $user->can('claim', $task),
                'respond' => $user->can('respond', $task) && $task->status === TaskStatus::Assigned,
                'logTime' => $user->can('logTime', $task),
                'attachFiles' => $user->can('attachFiles', $task),
                'comment' => $user->can('comment', $task),
                'manageChecklist' => $user->can('manageChecklist', $task),
                'manageSubtasks' => $user->can('manageSubtasks', $task),
                'manageReminders' => $user->can('manageReminders', $task),
                'manageRecurrence' => $user->can('manageRecurrence', $task),
                'submitProof' => $user->can('submitProof', $task),
                'reviewProof' => $user->can('reviewProof', $task),
            ],
        ];
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
     * @return array{scope: string, search: string, project: int|null, status: string, priority: string}
     */
    protected function listFilters(Request $request, bool $canSeeEverything): array
    {
        // Someone who can only see their own work has no use for the other
        // scopes, so the default and the only option is "mine".
        $scope = $canSeeEverything ? $request->string('scope')->value() ?: 'all' : 'mine';

        return [
            'scope' => $scope,
            'search' => $request->string('search')->trim()->value(),
            'project' => $request->integer('project') ?: null,
            'status' => $request->string('status')->value(),
            'priority' => $request->string('priority')->value(),
        ];
    }

    /**
     * @param  array{scope: string, search: string, project: int|null, status: string, priority: string}  $filters
     */
    protected function filteredTasksQuery(Request $request, array $filters): Builder
    {
        $user = $request->user();
        $scope = $filters['scope'];

        return Task::query()
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
                if ($employeeId === null) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function (Builder $mine) use ($employeeId) {
                    $mine->where('assigned_employee_id', $employeeId)
                        ->orWhereHas('subtasks', fn (Builder $subtasks) => $subtasks->where('assigned_employee_id', $employeeId));
                });
            })
            ->when($scope === 'unassigned', fn (Builder $query) => $query->whereNull('assigned_employee_id'))
            ->when($filters['search'], fn (Builder $query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['project'], fn (Builder $query, int $id) => $query->where('tm_project_id', $id))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['priority'], fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->orderByRaw("field(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderByRaw('due_at is null, due_at');
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
     * @return array<int, array{user_id: int, label: string}>
     */
    protected function reminderRecipients(): array
    {
        return Employee::query()
            ->with('user:id,name')
            ->assignable()
            ->orderBy('employee_code')
            ->get(['id', 'user_id', 'employee_code'])
            ->map(fn (Employee $employee) => [
                'user_id' => $employee->user_id,
                'label' => $employee->user->name.' · '.$employee->employee_code,
            ])
            ->all();
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
     * @return list<array<string, mixed>>
     */
    protected function deliverablePayload(Task $task, User $user): array
    {
        return $task->deliverables()
            ->with(['submitter.user:id,name', 'reviews.reviewer:id,name', 'media', 'shareLink'])
            ->orderByDesc('version')
            ->get()
            ->map(fn (Deliverable $deliverable) => [
                'id' => $deliverable->id,
                'version' => $deliverable->version,
                'status' => $deliverable->status->value,
                'status_label' => $deliverable->status->label(),
                'notes' => $deliverable->notes,
                'submitted_by' => $deliverable->submitter->user->name,
                'submitted_at' => $deliverable->submitted_at->toIso8601String(),
                'can_share' => $user->can('share', $deliverable),
                'share_url' => $deliverable->shareLink?->publicUrl(),
                'files' => $deliverable->getMedia('proofs')->map(fn (Media $media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'mime' => $media->mime_type,
                ])->all(),
                'reviews' => $deliverable->reviews->map(fn (DeliverableReview $review) => [
                    'id' => $review->id,
                    'round' => $review->round,
                    'decision' => $review->decision->label(),
                    'comments' => $review->comments,
                    'reviewer' => $review->reviewer->name,
                    'reviewed_at' => $review->reviewed_at->toIso8601String(),
                ])->all(),
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function commentPayload(Task $task, User $user): array
    {
        return $task->comments()
            ->with('author:id,name,avatar')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (TaskComment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author_name' => $comment->author->name,
                'author_avatar' => $comment->author->avatar,
                'created_at' => $comment->created_at?->toIso8601String(),
                'updated_at' => $comment->updated_at?->toIso8601String(),
                'can_edit' => $user->can('update', $comment),
                'can_delete' => $user->can('delete', $comment),
            ])->all();
    }

    /**
     * @return array{items: list<array<string, mixed>>, completed: int, total: int}
     */
    protected function checklistPayload(Task $task): array
    {
        $items = $task->checklistItems()
            ->with('completedBy:id,name')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (TaskChecklistItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'is_completed' => $item->is_completed,
                'completed_by' => $item->completedBy?->name,
                'completed_at' => $item->completed_at?->toIso8601String(),
                'sort_order' => $item->sort_order,
            ])->all();

        return [
            'items' => $items,
            'completed' => $task->checklistItems()->where('is_completed', true)->count(),
            'total' => $task->checklistItems()->count(),
        ];
    }

    /**
     * @return array{can_submit: bool, is_assignee: bool, blocked_reason: string|null, status_label: string}
     */
    protected function submitReviewContext(Task $task, User $user): array
    {
        $isAssignee = $user->can('respond', $task);
        $canSubmit = $user->can('submitProof', $task);

        $blockedReason = null;

        if ($isAssignee && ! $canSubmit) {
            $blockedReason = match ($task->status) {
                TaskStatus::Assigned => 'Accept this task before you can submit deliverables for review.',
                TaskStatus::Accepted => 'Move this task to In Progress before submitting deliverables for review.',
                TaskStatus::InReview => 'Your submission is awaiting review. You can upload a revised version once changes are requested.',
                TaskStatus::Approved, TaskStatus::Completed => 'This task has already been approved or completed.',
                default => 'Deliverables can only be submitted while the task is in progress or after changes have been requested.',
            };
        }

        return [
            'can_submit' => $canSubmit,
            'is_assignee' => $isAssignee,
            'blocked_reason' => $blockedReason,
            'status_label' => $task->status->label(),
        ];
    }

    /**
     * Working files on the task. Separate from deliverable proofs.
     *
     * @return list<array<string, mixed>>
     */
    protected function attachmentPayload(Task $task, User $user): array
    {
        $media = $task->getMedia('attachments');
        $uploaderIds = $media
            ->map(fn (Media $item) => $item->getCustomProperty('uploaded_by_user_id'))
            ->filter()
            ->unique()
            ->values();

        $names = $uploaderIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $uploaderIds)->pluck('name', 'id');

        return $media->map(fn (Media $item) => [
            'id' => $item->id,
            'name' => $item->file_name,
            'url' => $item->getUrl(),
            'mime' => $item->mime_type,
            'size' => $item->size,
            'uploaded_by' => $names->get($item->getCustomProperty('uploaded_by_user_id')) ?? 'Unknown',
            'uploaded_at' => $item->created_at?->toIso8601String(),
            'can_delete' => $user->can('deleteAttachment', [$task, $item]),
        ])->values()->all();
    }

    /**
     * @return array{running: bool, started_at: string|null, yours: bool}
     */
    protected function timerPayload(Task $task, ?int $employeeId): array
    {
        $running = $task->timeEntries()->where('is_running', true)->latest('id')->first();

        return [
            'running' => $running !== null,
            'started_at' => $running?->started_at->toIso8601String(),
            'yours' => $running !== null && $running->employee_id === $employeeId,
        ];
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
