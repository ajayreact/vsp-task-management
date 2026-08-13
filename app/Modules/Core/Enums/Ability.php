<?php

namespace App\Modules\Core\Enums;

/**
 * Every permission name in the system, so that a typo in a middleware string
 * is a fatal error rather than a silently failing check.
 *
 * Module entry abilities (`crm.access`, `tasks.access`, `portal.access`) live
 * here rather than in the modules themselves: permissions are seeded as one
 * catalogue, and Core cannot reference a module.
 */
enum Ability: string
{
    case ViewEmployees = 'employees.view';
    case ManageEmployees = 'employees.manage';

    case ViewDepartments = 'departments.view';
    case ManageDepartments = 'departments.manage';

    case ViewRoles = 'roles.view';
    case ManageRoles = 'roles.manage';

    case ViewActivityLog = 'activity.view';

    case AccessCrm = 'crm.access';
    case AccessTasks = 'tasks.access';
    case AccessPortal = 'portal.access';

    case ViewCompanies = 'companies.view';
    case ManageCompanies = 'companies.manage';

    case ViewProjects = 'projects.view';
    case ManageProjects = 'projects.manage';

    case ViewAllTasks = 'tasks.view_all';
    case ManageTasks = 'tasks.manage';
    case AssignTasks = 'tasks.assign';

    public function label(): string
    {
        return match ($this) {
            self::ViewEmployees => 'View employees',
            self::ManageEmployees => 'Create, edit and remove employees',
            self::ViewDepartments => 'View departments',
            self::ManageDepartments => 'Create, edit and remove departments',
            self::ViewRoles => 'View roles and permissions',
            self::ManageRoles => 'Create and edit roles',
            self::ViewActivityLog => 'View the activity log',
            self::AccessCrm => 'Open the CRM module',
            self::AccessTasks => 'Open the Task Management module',
            self::AccessPortal => 'Open the client portal',
            self::ViewCompanies => 'View work companies',
            self::ManageCompanies => 'Create, edit and remove work companies',
            self::ViewProjects => 'View projects',
            self::ManageProjects => 'Create, edit and remove projects',
            self::ViewAllTasks => "View everyone's tasks, not just your own",
            self::ManageTasks => 'Create, edit and remove tasks',
            self::AssignTasks => 'Assign tasks to other people',
        };
    }

    /**
     * Grouping for the role editor, which lists permissions by area.
     */
    public function group(): string
    {
        return match ($this) {
            self::ViewEmployees, self::ManageEmployees => 'Employees',
            self::ViewDepartments, self::ManageDepartments => 'Departments',
            self::ViewRoles, self::ManageRoles => 'Roles',
            self::ViewActivityLog => 'Audit',
            self::AccessCrm, self::AccessTasks, self::AccessPortal => 'Module access',
            self::ViewCompanies, self::ManageCompanies => 'Work companies',
            self::ViewProjects, self::ManageProjects => 'Projects',
            self::ViewAllTasks, self::ManageTasks, self::AssignTasks => 'Tasks',
        };
    }
}
