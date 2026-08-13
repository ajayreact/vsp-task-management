<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\ProjectRole;
use App\Modules\TaskManagement\Enums\ProjectStatus;
use App\Modules\TaskManagement\Http\Requests\ProjectRequest;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->with(['company:id,name', 'manager:id,user_id', 'manager.user:id,name'])
            ->withCount(['tasks', 'members'])
            ->when($request->integer('company'), fn ($query, int $id) => $query->where('tm_company_id', $id))
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'company_name' => $project->company->name,
                'manager_name' => $project->manager?->user->name,
                'due_date' => $project->due_date?->toDateString(),
                'tasks_count' => $project->tasks_count,
                'members_count' => $project->members_count,
            ]);

        return Inertia::render('TaskManagement/projects/index', [
            'projects' => $projects,
            'filters' => [
                'company' => $request->integer('company') ?: null,
                'status' => $request->string('status')->value(),
            ],
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => ProjectStatus::options(),
            'can' => [
                'manage' => $request->user()->can('create', Project::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('TaskManagement/projects/create', $this->formOptions());
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = DB::transaction(function () use ($request) {
            $project = Project::create($request->safe()->except('members'));

            $project->members()->sync($this->memberPivot($request));

            return $project;
        });

        return to_route('tasks.projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load([
            'company:id,name,code',
            'manager:id,user_id,employee_code',
            'manager.user:id,name',
            'memberships.employee:id,user_id,employee_code',
            'memberships.employee.user:id,name',
        ]);

        return Inertia::render('TaskManagement/projects/show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'description' => $project->description,
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'start_date' => $project->start_date?->toDateString(),
                'due_date' => $project->due_date?->toDateString(),
                'budget_hours' => $project->budget_hours,
                'company' => ['id' => $project->company->id, 'name' => $project->company->name],
                'manager_name' => $project->manager?->user->name,
                'members' => $project->memberships->map(fn (ProjectMember $membership) => [
                    'id' => $membership->employee->id,
                    'name' => $membership->employee->user->name,
                    'employee_code' => $membership->employee->employee_code,
                    'project_role' => $membership->project_role->label(),
                ]),
            ],
            'taskCounts' => $project->tasks()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'can' => [
                'manage' => $request->user()->can('update', $project),
            ],
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        $project->load('memberships');

        return Inertia::render('TaskManagement/projects/edit', [
            ...$this->formOptions(),
            'project' => [
                'id' => $project->id,
                'tm_company_id' => $project->tm_company_id,
                'name' => $project->name,
                'code' => $project->code,
                'description' => $project->description,
                'status' => $project->status->value,
                'start_date' => $project->start_date?->toDateString(),
                'due_date' => $project->due_date?->toDateString(),
                'manager_employee_id' => $project->manager_employee_id,
                'budget_hours' => $project->budget_hours,
                'members' => $project->memberships->map(fn (ProjectMember $membership) => [
                    'employee_id' => $membership->employee_id,
                    'project_role' => $membership->project_role->value,
                ]),
            ],
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        DB::transaction(function () use ($request, $project) {
            $project->update($request->safe()->except('members'));

            $project->members()->sync($this->memberPivot($request));
        });

        return to_route('tasks.projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return to_route('tasks.projects.index')->with('success', 'Project deleted.');
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function memberPivot(ProjectRequest $request): array
    {
        $members = [];

        /** @var array<int, array{employee_id: int, project_role: string}> $submitted */
        $submitted = $request->validated('members', []);

        foreach ($submitted as $member) {
            $members[$member['employee_id']] = ['project_role' => $member['project_role']];
        }

        return $members;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()
                ->with('user:id,name')
                ->assignable()
                ->orderBy('employee_code')
                ->get(['id', 'user_id', 'employee_code'])
                ->map(fn (Employee $employee) => [
                    'id' => $employee->id,
                    'label' => $employee->user->name.' · '.$employee->employee_code,
                ]),
            'statuses' => ProjectStatus::options(),
            'projectRoles' => ProjectRole::options(),
        ];
    }
}
