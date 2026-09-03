<?php

namespace App\Modules\Attendance\Policies;

use App\Modules\Attendance\Enums\WfhRequestType;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Core\Models\User;

class WfhRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->employee !== null || $user->can('manageWfhRequests');
    }

    public function view(User $user, WfhRequest $request): bool
    {
        return $this->owns($user, $request) || $user->can('manageWfhRequests');
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function approve(User $user, WfhRequest $request): bool
    {
        return $user->can('manageWfhRequests')
            && $request->type === WfhRequestType::Request;
    }

    public function reject(User $user, WfhRequest $request): bool
    {
        return $user->can('manageWfhRequests')
            && $request->type === WfhRequestType::Request;
    }

    public function assign(User $user): bool
    {
        return $user->can('manageWfhRequests');
    }

    public function update(User $user, WfhRequest $request): bool
    {
        return $user->can('manageWfhRequests')
            && $request->type === WfhRequestType::Assignment;
    }

    public function cancel(User $user, WfhRequest $request): bool
    {
        return $user->can('manageWfhRequests')
            && $request->type === WfhRequestType::Assignment;
    }

    protected function owns(User $user, WfhRequest $request): bool
    {
        return $user->employee !== null && $request->employee_id === $user->employee->id;
    }
}
