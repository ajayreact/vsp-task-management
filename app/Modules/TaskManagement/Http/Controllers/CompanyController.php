<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Enums\CompanyStatus;
use App\Modules\TaskManagement\Http\Requests\CompanyRequest;
use App\Modules\TaskManagement\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'status' => $company->status->value,
                'primary_contact_name' => $company->primary_contact_name,
                'primary_contact_email' => $company->primary_contact_email,
                'primary_contact_phone' => $company->primary_contact_phone,
                'notes' => $company->notes,
                'projects_count' => $company->projects_count,
                'can_delete' => $request->user()->can('delete', $company),
            ]);

        return Inertia::render('TaskManagement/companies/index', [
            'companies' => $companies,
            'statuses' => CompanyStatus::options(),
            'can' => [
                'manage' => $request->user()->can('create', Company::class),
            ],
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $this->authorize('create', Company::class);

        Company::create($request->validated());

        return back()->with('success', 'Company created.');
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $company->update($request->validated());

        return back()->with('success', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return back()->with('success', 'Company deleted.');
    }
}
