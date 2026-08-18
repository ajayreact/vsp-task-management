<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Services\WorkloadCalculator;
use App\Modules\TaskManagement\Support\WorkWeek;
use App\Support\Pagination;
use App\Support\TabularExporter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkloadController extends Controller
{
    public function index(Request $request, WorkloadCalculator $calculator): Response
    {
        abort_unless($request->user()->can(Ability::ViewWorkload->value), 403);

        $week = WorkWeek::containing($request->date('week') ?? now());

        $rows = $this->employeesQuery()
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (Employee $employee) => $this->summarise($employee, $calculator, $week));

        return Inertia::render('TaskManagement/workload', [
            'week' => [
                'start' => $week->start->toDateString(),
                'end' => $week->end->toDateString(),
            ],
            'rows' => $rows,
            'filters' => [
                'week' => $week->start->toDateString(),
            ],
        ]);
    }

    public function exportExcel(Request $request, WorkloadCalculator $calculator, TabularExporter $exporter): StreamedResponse
    {
        abort_unless($request->user()->can(Ability::ViewWorkload->value), 403);

        $week = WorkWeek::containing($request->date('week') ?? now());
        $rows = $this->employeesQuery()->get()
            ->map(fn (Employee $employee) => $this->summarise($employee, $calculator, $week));

        return $exporter->excel(
            'Workload',
            $this->exportHeaders(),
            $this->exportRows($rows),
            'workload-'.now()->format('Y-m-d-His'),
        );
    }

    public function exportPdf(Request $request, WorkloadCalculator $calculator, TabularExporter $exporter)
    {
        abort_unless($request->user()->can(Ability::ViewWorkload->value), 403);

        $week = WorkWeek::containing($request->date('week') ?? now());
        $rows = $this->employeesQuery()->get()
            ->map(fn (Employee $employee) => $this->summarise($employee, $calculator, $week));

        return $exporter->pdf(
            'Workload',
            $this->exportHeaders(),
            $this->exportRows($rows),
            'workload-'.now()->format('Y-m-d-His'),
        );
    }

    protected function employeesQuery(): Builder
    {
        return Employee::query()
            ->with('user:id,name')
            ->assignable()
            ->orderBy('employee_code');
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     employee_code: string,
     *     assigned_hours: float,
     *     available_hours: float,
     *     utilisation_pct: float,
     *     band: string
     * }
     */
    protected function summarise(Employee $employee, WorkloadCalculator $calculator, WorkWeek $week): array
    {
        $load = $calculator->forEmployee($employee, $week);

        return [
            'id' => $employee->id,
            'name' => $employee->user->name,
            'employee_code' => $employee->employee_code,
            ...$load,
        ];
    }

    /**
     * @return list<string>
     */
    protected function exportHeaders(): array
    {
        return ['Employee', 'Code', 'Assigned', 'Available', 'Utilisation %', 'Status/band'];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<list<string|float|null>>
     */
    protected function exportRows($rows): array
    {
        return $rows->map(fn (array $row) => [
            $row['name'],
            $row['employee_code'],
            $row['assigned_hours'],
            $row['available_hours'],
            $row['utilisation_pct'],
            $this->bandLabel($row['band']),
        ])->all();
    }

    protected function bandLabel(string $band): string
    {
        return match ($band) {
            'overallocated' => 'Over allocated',
            'on_track' => 'On track',
            'bench' => 'Bench',
            'unavailable' => 'Unavailable',
            default => $band,
        };
    }
}
