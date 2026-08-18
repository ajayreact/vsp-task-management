<?php

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\Admin\DepartmentRequest;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Support\Pagination;
use App\Support\TabularExporter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $departments = $this->listQuery()
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (Department $department) => $this->summarise($department, $request));

        return Inertia::render('Core/admin/departments/index', [
            'departments' => $departments,
            'parents' => Department::query()->orderBy('name')->get(['id', 'name']),
            'heads' => Employee::query()
                ->with('user:id,name')
                ->assignable()
                ->orderBy('employee_code')
                ->get(['id', 'user_id', 'employee_code'])
                ->map(fn (Employee $employee) => [
                    'id' => $employee->id,
                    'label' => $employee->user->name.' · '.$employee->employee_code,
                ]),
            'can' => [
                'manage' => $request->user()?->can('create', Department::class) ?? false,
            ],
        ]);
    }

    public function exportExcel(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Department::class);

        return $exporter->excel(
            'Departments',
            $this->exportHeaders(),
            $this->exportRows($this->listQuery()->get()),
            'departments-'.now()->format('Y-m-d-His'),
        );
    }

    public function exportPdf(Request $request, TabularExporter $exporter)
    {
        $this->authorize('viewAny', Department::class);

        return $exporter->pdf(
            'Departments',
            $this->exportHeaders(),
            $this->exportRows($this->listQuery()->get()),
            'departments-'.now()->format('Y-m-d-His'),
        );
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        Department::create($request->validated());

        return back()->with('success', 'Department created.');
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $department->update($request->validated());

        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return back()->with('success', 'Department deleted.');
    }

    protected function listQuery(): Builder
    {
        return Department::query()
            ->with(['parent:id,name', 'head:id,user_id', 'head.user:id,name'])
            ->withCount('employees')
            ->orderBy('name');
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Department $department, Request $request): array
    {
        return [
            'id' => $department->id,
            'name' => $department->name,
            'code' => $department->code,
            'description' => $department->description,
            'parent_id' => $department->parent_id,
            'parent_name' => $department->parent?->name,
            'head_employee_id' => $department->head_employee_id,
            'head_name' => $department->head?->user->name,
            'employees_count' => $department->employees_count,
            'is_active' => $department->is_active,
            'can_delete' => $request->user()?->can('delete', $department) ?? false,
        ];
    }

    /**
     * @return list<string>
     */
    protected function exportHeaders(): array
    {
        return ['Department', 'Code', 'Parent', 'Head', 'People', 'Status'];
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @return list<list<string|int|null>>
     */
    protected function exportRows($departments): array
    {
        return $departments->map(fn (Department $department) => [
            $department->name,
            $department->code,
            $department->parent?->name ?? '',
            $department->head?->user?->name ?? '',
            $department->employees_count,
            $department->is_active ? 'Active' : 'Archived',
        ])->all();
    }
}
