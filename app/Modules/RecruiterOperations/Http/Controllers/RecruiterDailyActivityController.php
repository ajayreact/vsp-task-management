<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterActivityType;
use App\Modules\RecruiterOperations\Http\Requests\StoreRecruiterDailyActivityRequest;
use App\Modules\RecruiterOperations\Http\Requests\UpdateRecruiterDailyActivityRequest;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Services\RecruiterDailyActivityExporter;
use App\Modules\RecruiterOperations\Services\RecruiterDailyActivityService;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The recruiter daily activity log. A recruiter sees only their own entries;
 * recruiter.team.view opens everyone's. The rules for recording and changing
 * entries live in RecruiterDailyActivityService.
 */
class RecruiterDailyActivityController extends Controller
{
    public function __construct(
        protected RecruiterDailyActivityService $activities,
        protected RecruiterDirectory $directory,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RecruiterDailyActivity::class);

        $user = $request->user();
        $teamView = $user->can('viewTeam', RecruiterDailyActivity::class);
        $filters = $this->listFilters($request, $teamView);

        $activities = $this->filteredQuery($user, $filters, $teamView)
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (RecruiterDailyActivity $activity) => $this->summarise($activity, $user));

        return Inertia::render('RecruiterOperations/activities/index', [
            'activities' => $activities,
            'filters' => $filters,
            'activityTypes' => RecruiterActivityType::options(),
            'tasks' => $this->taskFilterOptions($user, $teamView),
            'recruiters' => $teamView ? $this->directory->options() : [],
            'pageTitle' => $teamView ? 'Daily Activities' : 'My Daily Activities',
            'can' => [
                'create' => $user->can('create', RecruiterDailyActivity::class),
                'viewTeam' => $teamView,
            ],
        ]);
    }

    public function exportExcel(Request $request, RecruiterDailyActivityExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', RecruiterDailyActivity::class);

        $user = $request->user();
        $teamView = $user->can('viewTeam', RecruiterDailyActivity::class);

        return $exporter->excel(
            $this->filteredQuery($user, $this->listFilters($request, $teamView), $teamView)->get()
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', RecruiterDailyActivity::class);

        $user = $request->user();
        $employee = $user->employee;
        abort_if($employee === null, 403, 'Daily activities need an employee profile.');

        $options = $this->formOptions($user, $employee->id, null);
        $requestedTask = $request->integer('task');

        return Inertia::render('RecruiterOperations/activities/create', [
            ...$options,
            'defaults' => [
                'activity_date' => today()->toDateString(),
                'recruiter_task_id' => collect($options['tasks'])->contains('id', $requestedTask) ? (string) $requestedTask : '',
            ],
        ]);
    }

    public function store(StoreRecruiterDailyActivityRequest $request): RedirectResponse
    {
        $activity = $this->activities->create($request->user(), $request->validated());
        $date = $activity->activity_date->toDateString();

        return to_route('recruiter.activities.index', ['from' => $date, 'to' => $date])
            ->with('success', 'Activity recorded.');
    }

    public function show(Request $request, RecruiterDailyActivity $dailyActivity): Response
    {
        $this->authorize('view', $dailyActivity);

        $dailyActivity->load(['creator:id,name', 'updater:id,name']);

        return Inertia::render('RecruiterOperations/activities/show', [
            'activity' => [
                ...$this->summarise($dailyActivity, $request->user()),
                'remarks' => $dailyActivity->remarks,
                'created_by_name' => $dailyActivity->creator?->name,
                'updated_by_name' => $dailyActivity->updater?->name,
                'created_at' => $dailyActivity->created_at->toIso8601String(),
                'updated_at' => $dailyActivity->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function edit(Request $request, RecruiterDailyActivity $dailyActivity): Response
    {
        $this->authorize('update', $dailyActivity);

        $dailyActivity->load('employee.user:id,name');

        return Inertia::render('RecruiterOperations/activities/edit', [
            ...$this->formOptions($request->user(), $dailyActivity->employee_id, $dailyActivity->recruiter_task_id),
            'activity' => [
                'id' => $dailyActivity->id,
                'recruiter_name' => $dailyActivity->employee->user->name ?? null,
                'is_own' => $dailyActivity->isOwnedBy($request->user()->employee),
                'activity_date' => $dailyActivity->activity_date->toDateString(),
                'activity_type' => $dailyActivity->activity_type->value,
                'title' => $dailyActivity->title,
                'description' => $dailyActivity->description,
                'start_time' => RecruiterDailyActivity::clock($dailyActivity->start_time),
                'end_time' => RecruiterDailyActivity::clock($dailyActivity->end_time),
                'quantity' => $dailyActivity->quantity,
                'recruiter_task_id' => $dailyActivity->recruiter_task_id,
                'remarks' => $dailyActivity->remarks,
            ],
        ]);
    }

    public function update(UpdateRecruiterDailyActivityRequest $request, RecruiterDailyActivity $dailyActivity): RedirectResponse
    {
        $this->activities->update($dailyActivity, $request->validated(), $request->user());

        return to_route('recruiter.activities.show', $dailyActivity)->with('success', 'Activity updated.');
    }

    public function destroy(Request $request, RecruiterDailyActivity $dailyActivity): RedirectResponse
    {
        $date = $dailyActivity->activity_date->toDateString();
        $this->activities->delete($dailyActivity, $request->user());

        return to_route('recruiter.activities.index', ['from' => $date, 'to' => $date])
            ->with('success', 'Activity deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(User $user, int $employeeId, ?int $currentTaskId): array
    {
        return [
            'activityTypes' => RecruiterActivityType::options(),
            'tasks' => $this->activities->linkableTasks($employeeId, $currentTaskId)
                ->map(fn (RecruiterTask $task) => [
                    'id' => $task->id,
                    'label' => $task->title.' · '.$task->status->label(),
                ])
                ->values()
                ->all(),
            'minDate' => $this->activities->correctsTeam($user) ? null : RecruiterDailyActivity::earliestSelfServiceDate()->toDateString(),
            'maxDate' => today()->toDateString(),
        ];
    }

    /**
     * Defaults to today. A reversed range is read the right way round.
     *
     * @return array{from: string, to: string, type: string, task: int|null, recruiter: int|null}
     */
    protected function listFilters(Request $request, bool $teamView): array
    {
        $today = today()->toDateString();
        $from = $this->dateOrNull($request->string('from')->trim()->value()) ?? $today;
        $to = $this->dateOrNull($request->string('to')->trim()->value()) ?? $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from,
            'to' => $to,
            'type' => RecruiterActivityType::tryFrom($request->string('type')->value())->value ?? '',
            'task' => $request->integer('task') ?: null,
            'recruiter' => $teamView ? ($request->integer('recruiter') ?: null) : null,
        ];
    }

    /**
     * @param  array{from: string, to: string, type: string, task: int|null, recruiter: int|null}  $filters
     * @return Builder<RecruiterDailyActivity>
     */
    protected function filteredQuery(User $user, array $filters, bool $teamView): Builder
    {
        return $this->visibleTo($user, $teamView)
            ->with(['employee:id,user_id', 'employee.user:id,name', 'task:id,title'])
            ->betweenDates($filters['from'], $filters['to'])
            ->when($filters['type'], fn (Builder $query, string $type) => $query->forType($type))
            ->when($filters['task'], fn (Builder $query, int $task) => $query->forTask($task))
            ->when($filters['recruiter'], fn (Builder $query, int $employee) => $query->forEmployee($employee))
            ->orderByDesc('activity_date')
            ->orderByRaw('start_time is null')
            ->orderBy('start_time')
            ->orderByDesc('id');
    }

    /**
     * Without team visibility a person sees only their own log.
     *
     * @return Builder<RecruiterDailyActivity>
     */
    protected function visibleTo(User $user, bool $teamView): Builder
    {
        $query = RecruiterDailyActivity::query();

        if ($teamView) {
            return $query;
        }

        $employeeId = $user->employee?->id;

        return $employeeId === null ? $query->whereRaw('1 = 0') : $query->forEmployee($employeeId);
    }

    /**
     * Tasks that appear on the viewer's visible activities.
     *
     * @return list<array{id: int, label: string}>
     */
    protected function taskFilterOptions(User $user, bool $teamView): array
    {
        return RecruiterTask::query()
            ->whereIn('id', $this->visibleTo($user, $teamView)->whereNotNull('recruiter_task_id')->select('recruiter_task_id'))
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (RecruiterTask $task) => ['id' => $task->id, 'label' => $task->title])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(RecruiterDailyActivity $activity, User $user): array
    {
        return [
            'id' => $activity->id,
            'activity_date' => $activity->activity_date->toDateString(),
            'activity_type' => $activity->activity_type->value,
            'activity_type_label' => $activity->activity_type->label(),
            'title' => $activity->title,
            'description' => $activity->description,
            'start_time' => RecruiterDailyActivity::clock($activity->start_time),
            'end_time' => RecruiterDailyActivity::clock($activity->end_time),
            'duration_minutes' => $activity->duration_minutes,
            'quantity' => $activity->quantity,
            'task' => $activity->task !== null ? ['id' => $activity->task->id, 'title' => $activity->task->title] : null,
            'recruiter_name' => $activity->employee->user->name ?? null,
            'can' => [
                'update' => $user->can('update', $activity),
                'delete' => $user->can('delete', $activity),
            ],
        ];
    }

    private function dateOrNull(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false ? $value : null;
    }
}
