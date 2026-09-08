<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\CompanyStatus;
use App\Modules\TaskManagement\Http\Requests\CompanyRequest;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Services\CompanyTeamSyncService;
use App\Support\Pagination;
use App\Support\TabularExporter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public function __construct(protected CompanyTeamSyncService $teamSync) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);

        $filters = $this->listFilters($request);

        $clients = $this->filteredClientsQuery($filters)
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (Company $company) => $this->summarise($company, $request));

        return Inertia::render('TaskManagement/clients/index', [
            'clients' => $clients,
            'filters' => $filters,
            'statuses' => CompanyStatus::options(),
            'employees' => $this->employeeOptions(),
            'can' => [
                'manage' => $request->user()->can('create', Company::class),
            ],
        ]);
    }

    public function exportExcel(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Company::class);

        $clients = $this->filteredClientsQuery($this->listFilters($request))->get();

        return $exporter->excel(
            'Clients',
            $this->exportHeaders(),
            $this->exportRows($clients),
            'clients-'.now()->format('Y-m-d-His'),
        );
    }

    public function exportPdf(Request $request, TabularExporter $exporter)
    {
        $this->authorize('viewAny', Company::class);

        $clients = $this->filteredClientsQuery($this->listFilters($request))->get();

        return $exporter->pdf(
            'Clients',
            $this->exportHeaders(),
            $this->exportRows($clients),
            'clients-'.now()->format('Y-m-d-His'),
        );
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $validated = $request->validated();
        $primaryId = isset($validated['primary_responsible_employee_id'])
            ? (int) $validated['primary_responsible_employee_id']
            : null;
        $supportingIds = $validated['supporting_employee_ids'] ?? [];

        unset($validated['primary_responsible_employee_id'], $validated['supporting_employee_ids']);

        $company = Company::create($validated);
        $this->teamSync->sync($company, $primaryId, $supportingIds);

        return back()->with('success', 'Client created.');
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validated();
        $primaryId = array_key_exists('primary_responsible_employee_id', $validated)
            ? ($validated['primary_responsible_employee_id'] !== null ? (int) $validated['primary_responsible_employee_id'] : null)
            : $company->primary_responsible_employee_id;
        $supportingIds = $validated['supporting_employee_ids'] ?? [];

        unset($validated['primary_responsible_employee_id'], $validated['supporting_employee_ids']);

        $company->update($validated);
        $this->teamSync->sync($company, $primaryId, $supportingIds);

        return back()->with('success', 'Client updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return back()->with('success', 'Client deleted.');
    }

    /**
     * @return array{search: string, responsible: int|null}
     */
    protected function listFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value(),
            'responsible' => $request->integer('responsible') ?: null,
        ];
    }

    /**
     * @param  array{search: string, responsible: int|null}  $filters
     */
    protected function filteredClientsQuery(array $filters): Builder
    {
        return Company::query()
            ->with([
                'primaryResponsible.user:id,name',
                'primaryResponsible.media',
                'supportingEmployees.user:id,name',
                'supportingEmployees.media',
            ])
            ->withCount('projects')
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('primary_contact_name', 'like', "%{$search}%")
                        ->orWhereHas('primaryResponsible.user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supportingEmployees.user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['responsible'], function (Builder $query, int $employeeId) {
                $query->where(function (Builder $inner) use ($employeeId) {
                    $inner->where('primary_responsible_employee_id', $employeeId)
                        ->orWhereHas('supportingEmployees', fn (Builder $members) => $members->where('employees.id', $employeeId));
                });
            })
            ->orderBy('name');
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Company $company, Request $request): array
    {
        $primary = $company->primaryResponsible;
        $supporting = $company->supportingEmployees
            ->sortBy(fn (Employee $employee) => $employee->user?->name ?? '')
            ->values();

        return [
            'id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'status' => $company->status->value,
            'primary_responsible_employee_id' => $company->primary_responsible_employee_id,
            'primary_responsible' => $primary ? $this->employeeSummary($primary) : null,
            'supporting_employees' => $supporting->map(fn (Employee $employee) => $this->employeeSummary($employee))->all(),
            'supporting_employee_ids' => $supporting->pluck('id')->all(),
            'primary_contact_name' => $company->primary_contact_name,
            'primary_contact_email' => $company->primary_contact_email,
            'primary_contact_phone' => $company->primary_contact_phone,
            'notes' => $company->notes,
            'monthly_post_target' => $company->monthly_post_target,
            'holiday_india_enabled' => (bool) $company->holiday_india_enabled,
            'holiday_usa_enabled' => (bool) $company->holiday_usa_enabled,
            'projects_count' => $company->projects_count,
            'can_delete' => $request->user()->can('delete', $company),
        ];
    }

    /**
     * @return array{id: int, name: string, avatar: string|null}
     */
    protected function employeeSummary(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->user?->name ?? 'Unknown',
            'avatar' => $employee->getFirstMediaUrl('avatar', 'thumb') ?: null,
        ];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    protected function employeeOptions(): array
    {
        return Employee::query()
            ->with('user:id,name')
            ->assignable()
            ->orderBy('employee_code')
            ->get(['id', 'user_id', 'employee_code'])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'label' => ($employee->user?->name ?? 'Unknown').' · '.$employee->employee_code,
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function exportHeaders(): array
    {
        return ['Client', 'Code', 'Responsible', 'Supporting', 'Contact', 'Email', 'Phone', 'Projects', 'Status'];
    }

    /**
     * @param  Collection<int, Company>  $clients
     * @return list<list<string|int|null>>
     */
    protected function exportRows($clients): array
    {
        return $clients->map(fn (Company $company) => [
            $company->name,
            $company->code,
            $company->primaryResponsible?->user?->name ?? '',
            $company->supportingEmployees->map(fn (Employee $employee) => $employee->user?->name)->filter()->implode(', '),
            $company->primary_contact_name ?? '',
            $company->primary_contact_email ?? '',
            $company->primary_contact_phone ?? '',
            $company->projects_count,
            $company->status->label(),
        ])->all();
    }
}
