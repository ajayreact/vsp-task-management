<?php

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\Admin\DepartmentRequest;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->with(['parent:id,name', 'head:id,user_id', 'head.user:id,name'])
            ->withCount('employees')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department) => [
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
            ]);

        return Inertia::render('Core/admin/departments/index', [
            'departments' => $departments,
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
}
