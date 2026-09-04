<?php

namespace App\Modules\Core\Enums;

/**
 * Every permission name in the system, so that a typo in a middleware string
 * is a fatal error rather than a silently failing check.
 *
 * Module entry abilities live here rather than in the modules themselves:
 * permissions are seeded as one catalogue, and Core cannot reference a module.
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

    case AccessTasks = 'tasks.access';

    case ViewCompanies = 'companies.view';
    case ManageCompanies = 'companies.manage';

    case ViewCompanyLogos = 'company_logos.view';
    case ManageCompanyLogos = 'company_logos.manage';

    case ViewCompanyDocuments = 'company_documents.view';
    case ManageCompanyDocuments = 'company_documents.manage';
    case ShareCompanyDocuments = 'company_documents.share';

    case ViewContracts = 'contracts.view';
    case ManageContracts = 'contracts.manage';
    case ShareContracts = 'contracts.share';

    case ViewContentCalendar = 'content_calendar.view';
    case ManageContentCalendar = 'content_calendar.manage';
    case ShareContentCalendar = 'content_calendar.share';

    case ViewProjects = 'projects.view';
    case ManageProjects = 'projects.manage';

    case ViewAllTasks = 'tasks.view_all';
    case ManageTasks = 'tasks.manage';
    case AssignTasks = 'tasks.assign';

    case ManageCapacity = 'capacity.manage';
    case ViewWorkload = 'workload.view';
    case ApproveTimesheets = 'timesheets.approve';
    case ReviewDeliverables = 'reviews.decide';

    case ManageWfhRequests = 'attendance.wfh.manage';

    /**
     * Permission names retired with CRM, Portal, and Lead Management. Seeders
     * detach these from roles before deleting the permission rows.
     *
     * @return list<string>
     */
    public static function retired(): array
    {
        return [
            'crm.access',
            'portal.access',
            'crm.clients.view',
            'crm.clients.manage',
            'crm.campaigns.view',
            'crm.campaigns.manage',
            'crm.pipelines.manage',
            'crm.deals.view',
            'crm.deals.manage',
            'crm.reports.view',
            'crm.leads.view',
            'crm.leads.manage',
            'crm.leads.assign',
            'crm.integrations.manage',
        ];
    }

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
            self::AccessTasks => 'Open the Task Management module',
            self::ViewCompanies => 'View clients',
            self::ManageCompanies => 'Create, edit and remove clients',
            self::ViewCompanyLogos => 'View the Brand Kit',
            self::ManageCompanyLogos => 'Upload and manage Brand Kit assets',
            self::ViewCompanyDocuments => 'View the operations document library',
            self::ManageCompanyDocuments => 'Upload and manage client documents',
            self::ShareCompanyDocuments => 'Share client documents externally',
            self::ViewContracts => 'View contracts',
            self::ManageContracts => 'Create, edit and generate contracts',
            self::ShareContracts => 'Share contracts and signing links externally',
            self::ViewContentCalendar => 'View the client content calendar',
            self::ManageContentCalendar => 'Create and edit scheduled content',
            self::ShareContentCalendar => 'Share content calendar links externally',
            self::ViewProjects => 'View projects',
            self::ManageProjects => 'Create, edit and remove projects',
            self::ViewAllTasks => "View everyone's tasks, not just your own",
            self::ManageTasks => 'Create, edit and remove tasks',
            self::AssignTasks => 'Assign tasks to other people',
            self::ManageCapacity => 'Set weekly capacity and log leave for other people',
            self::ViewWorkload => 'See everyone\'s allocated hours against capacity',
            self::ApproveTimesheets => 'Approve or reject submitted timesheets',
            self::ReviewDeliverables => 'Approve, reject or request changes on proofs',
            self::ManageWfhRequests => 'Assign, approve and manage work from home',
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
            self::AccessTasks => 'Module access',
            self::ViewCompanies, self::ManageCompanies => 'Clients',
            self::ViewCompanyLogos, self::ManageCompanyLogos => 'Brand Kit',
            self::ViewCompanyDocuments, self::ManageCompanyDocuments, self::ShareCompanyDocuments => 'Operations documents',
            self::ViewContracts, self::ManageContracts, self::ShareContracts => 'Contracts',
            self::ViewContentCalendar, self::ManageContentCalendar, self::ShareContentCalendar => 'Content calendar',
            self::ViewProjects, self::ManageProjects => 'Projects',
            self::ViewAllTasks, self::ManageTasks, self::AssignTasks => 'Tasks',
            self::ManageCapacity, self::ViewWorkload => 'Capacity',
            self::ApproveTimesheets => 'Timesheets',
            self::ReviewDeliverables => 'Creative review',
            self::ManageWfhRequests => 'Attendance',
        };
    }
}
