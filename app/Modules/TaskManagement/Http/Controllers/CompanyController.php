<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Enums\CompanyStatus;
use App\Modules\TaskManagement\Http\Requests\CompanyRequest;
use App\Modules\TaskManagement\Models\Company;
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
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);

        $clients = $this->filteredClientsQuery()
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (Company $company) => $this->summarise($company, $request));

        return Inertia::render('TaskManagement/clients/index', [
            'clients' => $clients,
            'statuses' => CompanyStatus::options(),
            'can' => [
                'manage' => $request->user()->can('create', Company::class),
            ],
        ]);
    }

    public function exportExcel(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', Company::class);

        $clients = $this->filteredClientsQuery()->get();

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

        $clients = $this->filteredClientsQuery()->get();

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

        Company::create($request->validated());

        return back()->with('success', 'Client created.');
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $company->update($request->validated());

        return back()->with('success', 'Client updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return back()->with('success', 'Client deleted.');
    }

    protected function filteredClientsQuery(): Builder
    {
        return Company::query()
            ->withCount('projects')
            ->orderBy('name');
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Company $company, Request $request): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'code' => $company->code,
            'status' => $company->status->value,
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
     * @return list<string>
     */
    protected function exportHeaders(): array
    {
        return ['Client', 'Code', 'Contact', 'Email', 'Phone', 'Projects', 'Status'];
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
            $company->primary_contact_name ?? '',
            $company->primary_contact_email ?? '',
            $company->primary_contact_phone ?? '',
            $company->projects_count,
            $company->status->label(),
        ])->all();
    }
}
