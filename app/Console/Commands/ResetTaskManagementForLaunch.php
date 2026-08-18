<?php

namespace App\Console\Commands;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\PermissionRegistrar;

/**
 * One-shot Option 2 reset: wipe TM demo data, keep Super Admin,
 * delete other staff accounts.
 */
class ResetTaskManagementForLaunch extends Command
{
    protected $signature = 'app:reset-tm-for-launch {--force : Required to run}';

    protected $description = 'Wipe Task Management demo data and non–Super Admin staff';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to run without --force.');

            return self::FAILURE;
        }

        $superAdmin = User::query()
            ->role(SystemRole::SuperAdmin->value)
            ->with('employee')
            ->whereHas('employee', fn ($query) => $query->where('employee_code', 'EMP-0001'))
            ->first();

        if ($superAdmin === null || $superAdmin->employee === null) {
            $this->error('Super Admin ajay@vspcrm.in / EMP-0001 not found. Aborting.');

            return self::FAILURE;
        }

        $keepUserId = (int) $superAdmin->id;
        $keepEmployeeId = (int) $superAdmin->employee->id;

        DB::transaction(function () use ($keepUserId, $superAdmin): void {
            $this->wipeTaskManagement();
            $this->wipeTmMediaAndLogs();
            $this->deleteDemoStaff($keepUserId);
            $this->deleteUnusedCreativeDepartment();
            $this->refreshSuperAdminProfile($superAdmin);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Reset complete.');
        $this->line('Kept user #'.$keepUserId.' employee #'.$keepEmployeeId);

        return self::SUCCESS;
    }

    protected function wipeTaskManagement(): void
    {
        $tables = [
            'tm_review_annotations',
            'tm_deliverable_reviews',
            'tm_deliverables',
            'tm_time_entries',
            'tm_timesheets',
            'tm_task_status_history',
            'tm_task_assignments',
            'tm_tasks',
            'tm_project_members',
            'tm_projects',
            'tm_companies',
            'tm_employee_capacity',
            'tm_employee_availability',
            'tm_workload_snapshots',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    protected function wipeTmMediaAndLogs(): void
    {
        $tmTypes = [
            Task::class,
            Deliverable::class,
            Project::class,
            Company::class,
        ];

        Media::query()
            ->whereIn('model_type', [Task::class, Deliverable::class])
            ->get()
            ->each(fn (Media $media) => $media->delete());

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->whereIn('subject_type', $tmTypes)
                ->delete();
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where(function ($query): void {
                    $query->where('type', 'like', '%TaskManagement%')
                        ->orWhere('type', 'like', '%StaffDatabaseNotification%');
                })
                ->delete();
        }
    }

    protected function deleteDemoStaff(int $keepUserId): void
    {
        $demoEmployees = Employee::query()
            ->where('user_id', '!=', $keepUserId)
            ->get();

        foreach ($demoEmployees as $employee) {
            Media::query()
                ->where('model_type', Employee::class)
                ->where('model_id', $employee->id)
                ->get()
                ->each(fn (Media $media) => $media->delete());

            if (Schema::hasTable('activity_log')) {
                DB::table('activity_log')
                    ->where('subject_type', Employee::class)
                    ->where('subject_id', $employee->id)
                    ->delete();
            }

            $employee->delete();
        }

        $demoUsers = User::query()
            ->where('id', '!=', $keepUserId)
            ->get();

        foreach ($demoUsers as $user) {
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id)
                    ->delete();
            }

            if (Schema::hasTable('activity_log')) {
                DB::table('activity_log')
                    ->where('causer_type', User::class)
                    ->where('causer_id', $user->id)
                    ->delete();
            }

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $user->roles()->detach();
            $user->permissions()->detach();
            $user->delete();
        }
    }

    protected function deleteUnusedCreativeDepartment(): void
    {
        $creative = Department::query()->where('code', 'CRT')->first();

        if ($creative === null) {
            return;
        }

        $inUse = Employee::query()->where('department_id', $creative->id)->exists()
            || (Schema::hasTable('tm_tasks') && DB::table('tm_tasks')->where('department_id', $creative->id)->exists());

        if (! $inUse) {
            $creative->delete();
        }
    }

    protected function refreshSuperAdminProfile(User $superAdmin): void
    {
        $opsHead = Designation::query()->where('code', 'OPS-HEAD')->first();

        $superAdmin->employee?->update(array_filter([
            'designation_id' => $opsHead?->id,
        ]));

        Department::query()
            ->where('code', 'OPS')
            ->whereNull('head_employee_id')
            ->update(['head_employee_id' => $superAdmin->employee?->id]);
    }
}
