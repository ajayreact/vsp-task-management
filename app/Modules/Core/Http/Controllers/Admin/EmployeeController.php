<?php

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Enums\UserType;
use App\Modules\Core\Http\Requests\Admin\EmployeeRequest;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Support\Pagination;
use App\Support\TabularExporter;
use App\Services\EmployeeOfficeAssignmentService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function __construct(protected EmployeeOfficeAssignmentService $officeAssignments) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $filters = $this->listFilters($request);

        $employees = $this->filteredQuery($filters)
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString();

        $officeByEmployee = $this->officeAssignments->summariesFor(
            $employees->getCollection()->pluck('id')->all(),
        );

        $employees->getCollection()->transform(function (Employee $employee) use ($officeByEmployee) {
            $employee->setAttribute('office_location', $officeByEmployee[$employee->id] ?? null);

            return $employee;
        });

        return Inertia::render('Core/admin/employees/index', [
            'employees' => $employees,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => EmployeeStatus::options(),
            'can' => [
                'manage' => $request->user()?->can('create', Employee::class) ?? false,
            ],
        ]);
    }

    public function exportExcel(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        return $exporter->excel(
            'Employees',
            $this->exportHeaders(),
            $this->exportRows($this->filteredQuery($this->listFilters($request))->get()),
            'employees-'.now()->format('Y-m-d-His'),
        );
    }

    public function exportPdf(Request $request, TabularExporter $exporter)
    {
        $this->authorize('viewAny', Employee::class);

        return $exporter->pdf(
            'Employees',
            $this->exportHeaders(),
            $this->exportRows($this->filteredQuery($this->listFilters($request))->get()),
            'employees-'.now()->format('Y-m-d-His'),
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('Core/admin/employees/create', $this->formOptions(request: $request));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'user_type' => UserType::Internal,
                'is_active' => $request->boolean('is_active'),
            ]);

            $user->syncRoles($request->validated('roles', []));

            $employee = $user->employee()->create($this->profileAttributes($request));

            $this->syncOfficeAssignment($request, $employee);
        });

        return to_route('admin.employees.index')->with('success', 'Employee created.');
    }

    public function edit(Request $request, Employee $employee): Response
    {
        $this->authorize('update', $employee);

        $employee->load('user');

        return Inertia::render('Core/admin/employees/edit', [
            ...$this->formOptions($employee, $request),
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'email' => $employee->user->email,
                'is_active' => $employee->user->is_active,
                'employee_code' => $employee->employee_code,
                'department_id' => $employee->department_id,
                'designation_id' => $employee->designation_id,
                'reporting_to_id' => $employee->reporting_to_id,
                'phone' => $employee->phone,
                'joined_on' => $employee->joined_on?->toDateString(),
                'exited_on' => $employee->exited_on?->toDateString(),
                'status' => $employee->status->value,
                'roles' => $employee->user->getRoleNames()->all(),
                'office_location_id' => $this->officeAssignments->officeIdFor($employee->id),
            ],
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        DB::transaction(function () use ($request, $employee) {
            $user = $employee->user;

            $user->fill([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'is_active' => $request->boolean('is_active'),
            ]);

            // Blank means "leave the current password alone".
            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }

            $user->save();
            $user->syncRoles($request->validated('roles', []));

            $employee->update($this->profileAttributes($request));

            $this->syncOfficeAssignment($request, $employee);
        });

        return to_route('admin.employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        // The employees row is removed by the cascade on user_id.
        $employee->user->delete();

        return to_route('admin.employees.index')->with('success', 'Employee removed.');
    }

    /**
     * @return array{search: string, department: int|null, status: string}
     */
    protected function listFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value(),
            'department' => $request->integer('department') ?: null,
            'status' => $request->string('status')->value(),
        ];
    }

    /**
     * @param  array{search: string, department: int|null, status: string}  $filters
     */
    protected function filteredQuery(array $filters): Builder
    {
        return Employee::query()
            ->with([
                'user:id,name,email,is_active',
                'department:id,name',
                'designation:id,name',
                'manager:id,employee_code,user_id',
                'manager.user:id,name',
            ])
            ->when($filters['search'], function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('employee_code', 'like', "%{$search}%")
                        ->orWhereHas('designation', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($filters['department'], fn ($query, int $id) => $query->where('department_id', $id))
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('employee_code');
    }

    /**
     * @return list<string>
     */
    protected function exportHeaders(): array
    {
        return ['Employee', 'Email', 'Code', 'Designation', 'Department', 'Office', 'Reports to', 'Status'];
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<list<string|null>>
     */
    protected function exportRows($employees): array
    {
        $officeByEmployee = $this->officeAssignments->summariesFor(
            $employees->pluck('id')->all(),
        );

        return $employees->map(fn (Employee $employee) => [
            $employee->user?->name ?? '',
            $employee->user?->email ?? '',
            $employee->employee_code,
            $employee->designation?->name ?? '',
            $employee->department?->name ?? '',
            $officeByEmployee[$employee->id]['name'] ?? '',
            $employee->manager?->user?->name ?? '',
            $employee->status->label(),
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function profileAttributes(EmployeeRequest $request): array
    {
        return [
            'department_id' => $request->validated('department_id'),
            'designation_id' => $request->validated('designation_id'),
            'employee_code' => $request->validated('employee_code'),
            'reporting_to_id' => $request->validated('reporting_to_id'),
            'phone' => $request->validated('phone'),
            'joined_on' => $request->validated('joined_on'),
            'exited_on' => $request->validated('exited_on'),
            'status' => $request->validated('status'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(?Employee $employee = null, ?Request $request = null): array
    {
        $options = [
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'designations' => Designation::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'managers' => Employee::query()
                ->with('user:id,name')
                ->when($employee, fn ($query, Employee $current) => $query->whereKeyNot($current->id))
                ->assignable()
                ->orderBy('employee_code')
                ->get(['id', 'user_id', 'employee_code'])
                ->map(fn (Employee $manager) => [
                    'id' => $manager->id,
                    'label' => $manager->user->name.' · '.$manager->employee_code,
                ]),
            'statuses' => EmployeeStatus::options(),
            'roles' => Role::query()
                ->orderBy('name')
                ->pluck('name')
                ->reject(fn (string $name) => $name === SystemRole::SuperAdmin->value)
                ->values(),
        ];

        if ($request?->user()?->can('viewAttendance')) {
            $currentOfficeId = $employee
                ? $this->officeAssignments->officeIdFor($employee->id)
                : null;

            $options['officeLocations'] = $this->officeAssignments
                ->selectableOffices($currentOfficeId)
                ->map(fn ($office) => [
                    'id' => $office->id,
                    'name' => $office->name,
                ])
                ->values()
                ->all();
        }

        return $options;
    }

    protected function syncOfficeAssignment(EmployeeRequest $request, Employee $employee): void
    {
        if (! $request->user()?->can('viewAttendance')) {
            return;
        }

        $officeLocationId = $request->validated('office_location_id');

        $this->officeAssignments->assign(
            $employee,
            is_numeric($officeLocationId) ? (int) $officeLocationId : null,
        );
    }
}
