<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Policies\Concerns\InteractsWithTaskDepartments;

class ContentCalendarItemPolicy
{
    use InteractsWithTaskDepartments;

    public function viewAny(User $user): bool
    {
        return $this->canAccessCalendar($user);
    }

    public function view(User $user, ContentCalendarItem $item): bool
    {
        return $this->canAccessCalendar($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCalendar($user);
    }

    public function update(User $user, ContentCalendarItem $item): bool
    {
        return $this->canManageCalendar($user);
    }

    public function delete(User $user, ContentCalendarItem $item): bool
    {
        return $user->can(Ability::ManageContentCalendar->value)
            || $user->can(Ability::ManageCompanies->value);
    }

    public function share(User $user, ContentCalendarItem $item): bool
    {
        return $user->can(Ability::ShareContentCalendar->value)
            || $this->canManageCalendar($user);
    }

    protected function canAccessCalendar(User $user): bool
    {
        if ($user->can(Ability::ViewContentCalendar->value) || $user->can(Ability::ManageContentCalendar->value)) {
            return true;
        }

        if ($user->can(Ability::ViewCompanies->value) || $user->can(Ability::ManageCompanies->value)) {
            return true;
        }

        if (! $this->hasTaskAccess($user)) {
            return false;
        }

        return in_array($this->departmentCode($user), ['CRT', 'OPS'], true);
    }

    protected function canManageCalendar(User $user): bool
    {
        if ($user->can(Ability::ManageContentCalendar->value) || $user->can(Ability::ManageCompanies->value)) {
            return true;
        }

        if (! $this->hasTaskAccess($user)) {
            return false;
        }

        return in_array($this->departmentCode($user), ['CRT', 'OPS'], true);
    }
}
