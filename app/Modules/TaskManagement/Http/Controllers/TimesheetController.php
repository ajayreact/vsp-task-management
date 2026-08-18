<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Models\Timesheet;
use App\Modules\TaskManagement\Services\TimesheetService;
use App\Support\Pagination;
use App\Support\TabularExporter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimesheetController extends Controller
{
    public function __construct(protected TimesheetService $timesheets) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Timesheet::class);

        $user = $request->user();
        $canReview = $user->can(Ability::ApproveTimesheets->value);
        $filters = $this->listFilters($request, $canReview);

        $timesheets = $this->filteredTimesheetsQuery($request, $filters, $canReview)
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (Timesheet $sheet) => $this->summarise($sheet));

        return Inertia::render('TaskManagement/timesheets/index', [
            'timesheets' => $timesheets,
            'filters' => $filters,
            'statuses' => TimesheetStatus::options(),
            'can' => [
                'review' => $canReview,
            ],
        ]);
    }

    public function exportExcel(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Timesheet::class);

        $canReview = $request->user()->can(Ability::ApproveTimesheets->value);
        $filters = $this->listFilters($request, $canReview);
        $timesheets = $this->filteredTimesheetsQuery($request, $filters, $canReview)->get();

        return $exporter->excel(
            'Timesheets',
            $this->exportHeaders(),
            $this->exportRows($timesheets),
            'timesheets-'.now()->format('Y-m-d-His'),
        );
    }

    public function exportPdf(Request $request, TabularExporter $exporter)
    {
        $this->authorize('viewAny', Timesheet::class);

        $canReview = $request->user()->can(Ability::ApproveTimesheets->value);
        $filters = $this->listFilters($request, $canReview);
        $timesheets = $this->filteredTimesheetsQuery($request, $filters, $canReview)->get();

        return $exporter->pdf(
            'Timesheets',
            $this->exportHeaders(),
            $this->exportRows($timesheets),
            'timesheets-'.now()->format('Y-m-d-His'),
        );
    }

    public function show(Request $request, Timesheet $timesheet): Response
    {
        $this->authorize('view', $timesheet);

        $timesheet->load(['employee.user:id,name', 'approver:id,name']);

        $entries = $timesheet->entries()
            ->with('task:id,title')
            ->where('is_running', false)
            ->orderBy('started_at')
            ->get()
            ->map(fn (TimeEntry $entry) => [
                'id' => $entry->id,
                'task_id' => $entry->tm_task_id,
                'task_title' => $entry->task->title,
                'started_at' => $entry->started_at->toIso8601String(),
                'ended_at' => $entry->ended_at?->toIso8601String(),
                'hours' => $entry->hours(),
                'source' => $entry->source->label(),
                'note' => $entry->note,
                'is_billable' => $entry->is_billable,
            ]);

        return Inertia::render('TaskManagement/timesheets/show', [
            'timesheet' => [
                ...$this->summarise($timesheet),
                'review_note' => $timesheet->review_note,
                'approver_name' => $timesheet->approver?->name,
                'submitted_at' => $timesheet->submitted_at?->toIso8601String(),
                'approved_at' => $timesheet->approved_at?->toIso8601String(),
            ],
            'entries' => $entries,
            'can' => [
                'submit' => $request->user()->can('submit', $timesheet),
                'review' => $request->user()->can('review', $timesheet),
            ],
        ]);
    }

    public function submit(Timesheet $timesheet): RedirectResponse
    {
        $this->authorize('submit', $timesheet);

        return $this->run(fn () => $this->timesheets->submit($timesheet), 'Timesheet submitted.');
    }

    public function approve(Request $request, Timesheet $timesheet): RedirectResponse
    {
        $this->authorize('review', $timesheet);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        return $this->run(
            fn () => $this->timesheets->approve($timesheet, $request->user(), $validated['note'] ?? null),
            'Timesheet approved.',
        );
    }

    public function reject(Request $request, Timesheet $timesheet): RedirectResponse
    {
        $this->authorize('review', $timesheet);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        return $this->run(
            fn () => $this->timesheets->reject($timesheet, $request->user(), $validated['note'] ?? null),
            'Timesheet sent back.',
        );
    }

    /**
     * @return array{scope: string, status: string}
     */
    protected function listFilters(Request $request, bool $canReview): array
    {
        return [
            'scope' => $canReview ? ($request->string('scope')->value() ?: 'mine') : 'mine',
            'status' => $request->string('status')->value(),
        ];
    }

    /**
     * @param  array{scope: string, status: string}  $filters
     */
    protected function filteredTimesheetsQuery(Request $request, array $filters, bool $canReview): Builder
    {
        $user = $request->user();

        return Timesheet::query()
            ->with(['employee.user:id,name'])
            ->when(
                ! $canReview || $filters['scope'] !== 'team',
                function (Builder $query) use ($user) {
                    $employeeId = $user->employee?->id;
                    $employeeId === null ? $query->whereRaw('1 = 0') : $query->where('employee_id', $employeeId);
                },
            )
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('period_start');
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Timesheet $timesheet): array
    {
        return [
            'id' => $timesheet->id,
            'employee_name' => $timesheet->employee->user->name,
            'period_start' => $timesheet->period_start->toDateString(),
            'period_end' => $timesheet->period_end->toDateString(),
            'total_hours' => $timesheet->total_hours,
            'status' => $timesheet->status->value,
            'status_label' => $timesheet->status->label(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function exportHeaders(): array
    {
        return ['Week start', 'Week end', 'Employee', 'Hours', 'Status'];
    }

    /**
     * @param  Collection<int, Timesheet>  $timesheets
     * @return list<list<string|null>>
     */
    protected function exportRows($timesheets): array
    {
        return $timesheets->map(fn (Timesheet $sheet) => [
            $sheet->period_start->toDateString(),
            $sheet->period_end->toDateString(),
            $sheet->employee->user->name,
            (string) $sheet->total_hours,
            $sheet->status->label(),
        ])->all();
    }

    protected function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
