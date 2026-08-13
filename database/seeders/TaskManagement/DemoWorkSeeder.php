<?php

namespace Database\Seeders\TaskManagement;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\AssignmentAction;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\CompanyStatus;
use App\Modules\TaskManagement\Enums\ProjectStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TaskType;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Database\Seeder;

/**
 * Enough data to click through the module locally: one company, one project, a
 * manager and a designer, and tasks sitting at each interesting point of the
 * lifecycle. Only runs in local environments.
 */
class DemoWorkSeeder extends Seeder
{
    public function run(): void
    {
        $creative = Department::firstOrCreate(
            ['code' => 'CRT'],
            ['name' => 'Creative', 'is_active' => true],
        );

        $manager = $this->person('Priya Manager', 'manager@vsp.test', 'EMP-0002', $creative, SystemRole::Manager, 'Creative Lead');
        $designer = $this->person('Dev Designer', 'designer@vsp.test', 'EMP-0003', $creative, SystemRole::Employee, 'Senior Designer');

        $company = Company::firstOrCreate(
            ['code' => 'NORTHWIND'],
            [
                'name' => 'Northwind Traders',
                'status' => CompanyStatus::Active,
                'primary_contact_name' => 'Rhea Kapoor',
                'primary_contact_email' => 'rhea@northwind.test',
            ],
        );

        $project = Project::firstOrCreate(
            ['code' => 'NW-LAUNCH'],
            [
                'tm_company_id' => $company->id,
                'name' => 'Autumn launch campaign',
                'description' => 'Creative and content for the autumn product launch.',
                'status' => ProjectStatus::Active,
                'start_date' => now()->subWeeks(2),
                'due_date' => now()->addMonths(2),
                'manager_employee_id' => $manager->id,
                'budget_hours' => 180,
            ],
        );

        $project->members()->syncWithoutDetaching([
            $manager->id => ['project_role' => 'lead'],
            $designer->id => ['project_role' => 'member'],
        ]);

        if ($project->tasks()->exists()) {
            return;
        }

        $this->task($project, $creative, $manager->user, [
            'title' => 'Key visual for the launch',
            'type' => TaskType::Design,
            'priority' => TaskPriority::High,
            'status' => TaskStatus::Draft,
        ]);

        $this->task($project, $creative, $manager->user, [
            'title' => 'Write the launch email sequence',
            'type' => TaskType::Content,
            'priority' => TaskPriority::Normal,
            'status' => TaskStatus::Open,
            'assignment_mode' => 'open',
        ]);

        $this->task($project, $creative, $manager->user, [
            'title' => 'Cut the 30 second teaser',
            'type' => TaskType::Video,
            'priority' => TaskPriority::Urgent,
            'status' => TaskStatus::Open,
            'assignment_mode' => 'open',
        ]);

        $awaiting = $this->task($project, $creative, $manager->user, [
            'title' => 'Storyboard the product demo',
            'type' => TaskType::Design,
            'priority' => TaskPriority::High,
            'status' => TaskStatus::Assigned,
            'assigned_employee_id' => $designer->id,
        ]);

        $awaiting->assignments()->create([
            'employee_id' => $designer->id,
            'assigned_by_user_id' => $manager->user->id,
            'mode' => AssignmentAction::Direct,
            'status' => AssignmentStatus::Pending,
        ]);

        $underway = $this->task($project, $creative, $manager->user, [
            'title' => 'Social cutdowns for launch week',
            'type' => TaskType::Design,
            'priority' => TaskPriority::Normal,
            'status' => TaskStatus::InProgress,
            'assigned_employee_id' => $designer->id,
            'started_at' => now()->subDay(),
        ]);

        $underway->assignments()->create([
            'employee_id' => $designer->id,
            'assigned_by_user_id' => $manager->user->id,
            'mode' => AssignmentAction::Direct,
            'status' => AssignmentStatus::Accepted,
            'responded_at' => now()->subDays(2),
        ]);
    }

    protected function person(
        string $name,
        string $email,
        string $code,
        Department $department,
        SystemRole $role,
        string $designation,
    ): Employee {
        $user = User::firstOrCreate(
            ['email' => $email],
            User::factory()->raw(['name' => $name, 'email' => $email]),
        );

        $user->syncRoles($role->value);

        return Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'department_id' => $department->id,
                'employee_code' => $code,
                'designation' => $designation,
                'status' => 'active',
                'joined_on' => now()->subMonths(8),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function task(Project $project, Department $department, User $creator, array $attributes): Task
    {
        $task = Task::create([
            'tm_project_id' => $project->id,
            'department_id' => $department->id,
            'created_by_user_id' => $creator->id,
            'estimated_hours' => 8,
            'due_at' => now()->addWeeks(2),
            ...$attributes,
        ]);

        $task->statusHistory()->create([
            'from_status' => null,
            'to_status' => $task->status,
            'changed_by_user_id' => $creator->id,
            'changed_at' => now(),
        ]);

        return $task;
    }
}
