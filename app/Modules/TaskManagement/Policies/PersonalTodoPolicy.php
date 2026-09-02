<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\PersonalTodo;

class PersonalTodoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::AccessTasks->value);
    }

    public function view(User $user, PersonalTodo $todo): bool
    {
        return $this->owns($user, $todo) || $user->can(Ability::ViewAllTasks->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::AccessTasks->value);
    }

    public function update(User $user, PersonalTodo $todo): bool
    {
        return $this->owns($user, $todo);
    }

    public function delete(User $user, PersonalTodo $todo): bool
    {
        return $this->owns($user, $todo);
    }

    protected function owns(User $user, PersonalTodo $todo): bool
    {
        return $todo->user_id === $user->id;
    }
}
