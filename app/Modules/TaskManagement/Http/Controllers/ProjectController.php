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
use App\Support\Pagination;
use App\Support\TabularExporter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $filters = $this->listFilters($request);

        $projects = $this->filteredProjectsQuery($filters)
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (Project $project) => $this->summarise($project));

        return Inertia::render('TaskManagement/projects/index', [
            'projects' => $projects,
            'filters' => $filters,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => ProjectStatus::options(),
            'can' => [
                'manage' => $request->user()->can('create', Project::class),
            ],
        ]);
    }

    public function exportExcel(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = $this->filteredProjectsQuery($this->listFilters($request))->get();

        return $exporter->excel(
            'Projects',
            $this->exportHeaders(),
            $this->exportRows($projects),
            'projects-'.now()->format('Y-m-d-His'),
        );
    }

    public function exportPdf(Request $request, TabularExporter $exporter)
    {
        $this->authorize('viewAny', Project::class);

        $projects = $this->filteredProjectsQuery($this->listFilters($request))->get();

        return $exporter->pdf(
            'Projects',
            $this->exportHeaders(),
            $this->exportRows($projects),
            'projects-'.now()->format('Y-m-d-His'),
        );
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
     * @return array{client: int|null, status: string}
     */
    protected function listFilters(Request $request): array
    {
        return [
            'client' => $request->integer('client') ?: $request->integer('company') ?: null,
            'status' => $request->string('status')->value(),
        ];
    }

    /**
     * @param  array{client: int|null, status: string}  $filters
     */
    protected function filteredProjectsQuery(array $filters): Builder
    {
        return Project::query()
            ->with(['company:id,name', 'manager:id,user_id', 'manager.user:id,name'])
            ->withCount(['tasks', 'members'])
            ->when($filters['client'], fn (Builder $query, int $id) => $query->where('tm_company_id', $id))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name');
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Project $project): array
    {
        return [
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
        ];
    }

    /**
     * @return list<string>
     */
    protected function exportHeaders(): array
    {
        return ['Project', 'Code', 'Client', 'Manager', 'Team', 'Tasks', 'Due', 'Status'];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return list<list<string|int|null>>
     */
    protected function exportRows($projects): array
    {
        return $projects->map(fn (Project $project) => [
            $project->name,
            $project->code,
            $project->company->name,
            $project->manager?->user->name ?? '',
            $project->members_count,
            $project->tasks_count,
            $project->due_date?->toDateString() ?? '',
            $project->status->label(),
        ])->all();
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
