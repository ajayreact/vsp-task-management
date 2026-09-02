<?php

namespace App\Modules\Core\Enums;

/**
 * The roles the seeder guarantees exist. Roles beyond these can be created at
 * runtime; these are the ones code is allowed to name directly.
 */
enum SystemRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case TeamLead = 'team-lead';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Operations Head',
            self::Admin => 'Admin',
            self::TeamLead => 'Team Lead',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }

    /**
     * Abilities granted on seed. Super admin is absent by design: it passes
     * through the Gate::before check in CoreServiceProvider instead of
     * carrying every permission as a row.
     *
     * @return array<int, Ability>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::SuperAdmin => [],
            self::Admin => Ability::cases(),
            // Team Leads run the TM board: create/assign/publish/review, view
            // capacity and timesheets. They do not administer org structure,
            // work clients, or projects (Operations Head owns those).
            self::TeamLead => [
                Ability::ViewEmployees,
                Ability::ViewDepartments,
                Ability::ViewActivityLog,
                Ability::AccessTasks,
                Ability::ViewCompanies,
                Ability::ViewCompanyLogos,
                Ability::ManageCompanyLogos,
                Ability::ViewProjects,
                Ability::ViewAllTasks,
                Ability::ManageTasks,
                Ability::AssignTasks,
                Ability::ManageCapacity,
                Ability::ViewWorkload,
                Ability::ApproveTimesheets,
                Ability::ReviewDeliverables,
            ],
            self::Manager => [
                Ability::ViewEmployees,
                Ability::ViewDepartments,
                Ability::ViewActivityLog,
                Ability::AccessTasks,
                Ability::ViewCompanies,
                Ability::ViewCompanyLogos,
                Ability::ManageCompanyLogos,
                Ability::ViewProjects,
                Ability::ViewAllTasks,
                Ability::ManageTasks,
                Ability::AssignTasks,
                Ability::ManageCapacity,
                Ability::ViewWorkload,
                Ability::ApproveTimesheets,
                Ability::ReviewDeliverables,
            ],
            // Individual contributors: work assigned / open-board tasks only.
            // Cannot create, assign, publish, manage org structure, or open admin screens.
            self::Employee => [
                Ability::AccessTasks,
            ],
        };
    }
}
