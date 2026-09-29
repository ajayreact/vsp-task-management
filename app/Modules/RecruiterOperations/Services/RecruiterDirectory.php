<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Illuminate\Support\Collection;

/**
 * Who counts as a recruiter: an active employee whose active internal login
 * holds recruiter.access, through a role or directly. Never decided by
 * department, so moving someone between departments changes nothing here.
 */
class RecruiterDirectory
{
    /**
     * @return Collection<int, Employee>
     */
    public function assignableRecruiters(): Collection
    {
        return Employee::query()
            ->with('user:id,name')
            ->assignable()
            ->whereIn('user_id', $this->recruiterUserIds())
            ->orderBy('employee_code')
            ->get(['id', 'user_id', 'employee_code']);
    }

    public function isAssignable(Employee $employee): bool
    {
        if (! $employee->status->isAssignable()) {
            return false;
        }

        return $this->recruiterUserIds()->contains($employee->user_id);
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function options(): array
    {
        return $this->assignableRecruiters()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'label' => $employee->user->name.' · '.$employee->employee_code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, int>
     */
    protected function recruiterUserIds(): Collection
    {
        return User::query()
            ->internal()
            ->where('is_active', true)
            ->permission(Ability::RecruiterAccess->value)
            ->pluck('id');
    }
}
