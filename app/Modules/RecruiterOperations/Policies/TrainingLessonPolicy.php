<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingLesson;

/**
 * Lessons are edited only inside a draft version. Their audio and files are
 * open to training managers and to recruiters assigned that exact version;
 * nobody else, whatever the URL.
 */
class TrainingLessonPolicy
{
    public function view(User $user, TrainingLesson $lesson): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, TrainingLesson $lesson): bool
    {
        return $this->manages($user) && $lesson->version->isDraft();
    }

    public function delete(User $user, TrainingLesson $lesson): bool
    {
        return $this->manages($user) && $lesson->version->isDraft();
    }

    public function move(User $user, TrainingLesson $lesson): bool
    {
        return $this->manages($user) && $lesson->version->isDraft();
    }

    public function listen(User $user, TrainingLesson $lesson): bool
    {
        return $this->manages($user) || $this->isAssigned($user, $lesson);
    }

    public function viewFile(User $user, TrainingLesson $lesson): bool
    {
        return $this->manages($user) || $this->isAssigned($user, $lesson);
    }

    protected function manages(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ManageRecruiterTraining->value);
    }

    protected function isAssigned(User $user, TrainingLesson $lesson): bool
    {
        if (! $user->can(Ability::RecruiterAccess->value)) {
            return false;
        }

        $employeeId = Employee::query()->where('user_id', $user->id)->value('id');

        return $employeeId !== null && TrainingAssignment::query()
            ->where('course_version_id', $lesson->course_version_id)
            ->where('employee_id', $employeeId)
            ->exists();
    }
}
