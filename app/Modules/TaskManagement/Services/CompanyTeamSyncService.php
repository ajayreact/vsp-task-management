<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyTeamSyncService
{
    /**
     * @param  list<int>  $supportingEmployeeIds
     */
    public function sync(Company $company, ?int $primaryEmployeeId, array $supportingEmployeeIds): Company
    {
        return DB::transaction(function () use ($company, $primaryEmployeeId, $supportingEmployeeIds) {
            $supporting = collect($supportingEmployeeIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn (int $id) => $primaryEmployeeId !== null && $id === $primaryEmployeeId)
                ->values()
                ->all();

            $company->forceFill([
                'primary_responsible_employee_id' => $primaryEmployeeId,
            ])->save();

            $company->supportingEmployees()->sync($supporting);

            return $company->fresh([
                'primaryResponsible.user',
                'supportingEmployees.user',
            ]);
        });
    }
}
