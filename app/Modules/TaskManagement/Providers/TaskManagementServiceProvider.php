<?php

namespace App\Modules\TaskManagement\Providers;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\EmployeeAvailability;
use App\Modules\TaskManagement\Models\PersonalTodo;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use App\Modules\TaskManagement\Models\TaskComment;
use App\Modules\TaskManagement\Models\TaskReminder;
use App\Modules\TaskManagement\Models\TaskSubtask;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Models\Timesheet;
use App\Modules\TaskManagement\Policies\CompanyDocumentPolicy;
use App\Modules\TaskManagement\Policies\CompanyPolicy;
use App\Modules\TaskManagement\Policies\ContentCalendarItemPolicy;
use App\Modules\TaskManagement\Policies\DeliverablePolicy;
use App\Modules\TaskManagement\Policies\EmployeeAvailabilityPolicy;
use App\Modules\TaskManagement\Policies\PersonalTodoPolicy;
use App\Modules\TaskManagement\Policies\ProjectPolicy;
use App\Modules\TaskManagement\Policies\TaskChecklistItemPolicy;
use App\Modules\TaskManagement\Policies\TaskCommentPolicy;
use App\Modules\TaskManagement\Policies\TaskPolicy;
use App\Modules\TaskManagement\Policies\TaskReminderPolicy;
use App\Modules\TaskManagement\Policies\TaskSubtaskPolicy;
use App\Modules\TaskManagement\Policies\TimeEntryPolicy;
use App\Modules\TaskManagement\Policies\TimesheetPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Internal task management: companies, projects, tasks, assignment and
 * acceptance, availability, workload, the work timer, timesheets and
 * creative review.
 *
 * Owns every `tm_*` table. Must never reference the CRM module.
 */
class TaskManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/tasks'));

        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(CompanyDocument::class, CompanyDocumentPolicy::class);
        Gate::policy(ContentCalendarItem::class, ContentCalendarItemPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskComment::class, TaskCommentPolicy::class);
        Gate::policy(TaskChecklistItem::class, TaskChecklistItemPolicy::class);
        Gate::policy(TaskSubtask::class, TaskSubtaskPolicy::class);
        Gate::policy(TaskReminder::class, TaskReminderPolicy::class);
        Gate::policy(Deliverable::class, DeliverablePolicy::class);
        Gate::policy(Timesheet::class, TimesheetPolicy::class);
        Gate::policy(TimeEntry::class, TimeEntryPolicy::class);
        Gate::policy(EmployeeAvailability::class, EmployeeAvailabilityPolicy::class);
        Gate::policy(PersonalTodo::class, PersonalTodoPolicy::class);

        Gate::define('manageTaskManagementSettings', function (User $user): bool {
            return $user->hasRole(SystemRole::SuperAdmin->value);
        });

        Route::bind('document', fn (string $value) => CompanyDocument::query()->findOrFail($value));
        Route::bind('calendarItem', fn (string $value) => ContentCalendarItem::query()->findOrFail($value));
        Route::bind('personalTodo', fn (string $value) => PersonalTodo::query()->findOrFail($value));
        Route::bind('media', function (string $value): Media {
            if (ctype_digit($value)) {
                return Media::query()->findOrFail((int) $value);
            }

            return Media::query()->where('uuid', $value)->firstOrFail();
        });

        Route::middleware(['web', 'auth', 'internal', 'permission:'.Ability::AccessTasks->value])
            ->prefix('tasks')
            ->name('tasks.')
            ->group(base_path('routes/tasks.php'));

        Route::middleware('web')
            ->group(base_path('routes/share.php'));
    }
}
