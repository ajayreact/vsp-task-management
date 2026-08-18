import { type NavGroup, type NavItem } from '@/types';
import {
    Briefcase,
    Building2,
    CalendarDays,
    Clock,
    FolderKanban,
    Gauge,
    Inbox,
    ListChecks,
    UsersRound,
    SlidersHorizontal,
} from 'lucide-react';

/**
 * Staff navigation is permission-gated. Empty sections are not rendered.
 *
 * "Clients" under Task Management are work clients (`tm_companies`).
 */

export const adminNavItems: NavItem[] = [
    {
        title: 'Employees',
        url: '/admin/employees',
        icon: UsersRound,
        permission: 'employees.view',
    },
    {
        title: 'Departments',
        url: '/admin/departments',
        icon: Building2,
        permission: 'departments.view',
    },
];

export const taskNavItems: NavItem[] = [
    {
        title: 'Clients',
        url: '/tasks/clients',
        icon: Briefcase,
        permission: 'companies.view',
    },
    {
        title: 'Projects',
        url: '/tasks/projects',
        icon: FolderKanban,
        permission: 'projects.view',
    },
    {
        title: 'Tasks',
        url: '/tasks',
        icon: ListChecks,
        permission: 'tasks.access',
    },
    {
        title: 'Open Board',
        url: '/tasks/board',
        icon: Inbox,
        permission: 'tasks.access',
    },
    {
        title: 'Availability',
        url: '/tasks/availability',
        icon: CalendarDays,
        permission: 'tasks.access',
    },
    {
        title: 'Workload',
        url: '/tasks/workload',
        icon: Gauge,
        permission: 'workload.view',
    },
    {
        title: 'Timesheets',
        url: '/tasks/timesheets',
        icon: Clock,
        permission: 'tasks.access',
    },
    {
        title: 'Task Management Settings',
        url: '/tasks/settings',
        icon: SlidersHorizontal,
        role: 'super-admin',
    },
];

/** @deprecated Use adminNavItems + taskNavItems. Kept so older imports keep working. */
export const staffMenuItems: NavItem[] = [...adminNavItems, ...taskNavItems];

export function staffNavGroups(): NavGroup[] {
    return [
        {
            title: 'Administration',
            anyPermission: ['employees.view', 'departments.view'],
            items: adminNavItems,
        },
        {
            title: 'Task Management',
            anyPermission: ['tasks.access'],
            items: taskNavItems,
        },
    ];
}
