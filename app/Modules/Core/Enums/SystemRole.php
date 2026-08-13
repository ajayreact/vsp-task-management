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
    case Manager = 'manager';
    case Employee = 'employee';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
            self::Client => 'Client',
        };
    }

    /**
     * Client accounts belong to the portal and must never be handed a staff
     * role, so the admin role picker excludes them.
     */
    public function isInternal(): bool
    {
        return $this !== self::Client;
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
            self::Manager => [
                Ability::ViewEmployees,
                Ability::ViewDepartments,
                Ability::ViewActivityLog,
                Ability::AccessCrm,
                Ability::AccessTasks,
                Ability::ViewCompanies,
                Ability::ManageCompanies,
                Ability::ViewProjects,
                Ability::ManageProjects,
                Ability::ViewAllTasks,
                Ability::ManageTasks,
                Ability::AssignTasks,
            ],
            // Enough to work the board: see the projects they are on, raise a
            // task, and claim open work. Not enough to hand work to others.
            self::Employee => [
                Ability::ViewEmployees,
                Ability::ViewDepartments,
                Ability::AccessTasks,
                Ability::ViewCompanies,
                Ability::ViewProjects,
                Ability::ManageTasks,
            ],
            self::Client => [
                Ability::AccessPortal,
            ],
        };
    }
}
