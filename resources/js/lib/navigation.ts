import { type NavGroup, type NavItem } from '@/types';
import {
    Briefcase,
    Building2,
    CalendarDays,
    CalendarRange,
    ClipboardCheck,
    Clock,
    FileText,
    FolderKanban,
    Gauge,
    GraduationCap,
    Home,
    Image,
    Inbox,
    LayoutDashboard,
    ListChecks,
    CalendarCheck2,
    MapPin,
    NotebookPen,
    UserCheck,
    UsersRound,
    SlidersHorizontal,
    ScrollText,
    Wallet,
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
    {
        title: 'My Finance',
        url: '/admin/finance',
        icon: Wallet,
        role: 'super-admin',
    },
];

export const attendanceNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/admin/attendance',
        icon: LayoutDashboard,
        role: 'super-admin',
    },
    {
        title: 'Office Locations',
        url: '/admin/attendance/offices',
        icon: MapPin,
        role: 'super-admin',
    },
    {
        title: 'WFH Management',
        url: '/admin/attendance/wfh',
        icon: Home,
        permission: 'attendance.wfh.manage',
    },
    {
        title: 'Attendance',
        url: '/attendance/mark',
        icon: UserCheck,
        permission: 'tasks.access',
    },
    {
        title: 'WFH Requests',
        url: '/attendance/wfh',
        icon: Home,
        permission: 'tasks.access',
        hideForRoles: ['super-admin'],
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
        title: 'Brand Kit',
        url: '/tasks/brand-kit',
        icon: Image,
        capability: 'brand_kit',
    },
    {
        title: 'Operations Documents',
        url: '/tasks/documents',
        icon: FileText,
        capability: 'document_library',
    },
    {
        title: 'Contracts',
        url: '/tasks/contracts',
        icon: ScrollText,
        capability: 'contracts',
    },
    {
        title: 'Content Calendar',
        url: '/tasks/content-calendar',
        icon: CalendarRange,
        capability: 'content_calendar',
    },
    {
        title: 'Projects',
        url: '/tasks/projects',
        icon: FolderKanban,
        permission: 'projects.view',
    },
    {
        title: 'My Todos',
        contributorTitle: 'My Todos',
        url: '/tasks/todos',
        icon: CalendarCheck2,
        permission: 'tasks.access',
    },
    {
        title: 'Tasks',
        contributorTitle: 'My Tasks',
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

/**
 * US IT Staffing / OPT recruiter team. Separate from Digital Marketing Task
 * Management: nothing here requires tasks.access.
 */
export const recruiterNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/recruiter',
        icon: LayoutDashboard,
        permission: 'recruiter.access',
    },
    {
        title: 'Tasks',
        url: '/recruiter/tasks',
        icon: ListChecks,
        permission: 'recruiter.access',
    },
    {
        title: 'Daily Activities',
        url: '/recruiter/activities',
        icon: NotebookPen,
        permission: 'recruiter.access',
    },
    {
        title: 'Training',
        url: '/recruiter/training',
        icon: GraduationCap,
        permission: 'recruiter.access',
    },
    {
        title: 'Assessments',
        url: '/recruiter/assessments',
        icon: ClipboardCheck,
        permission: 'recruiter.access',
    },
];

/**
 * Shortcuts to the shared Attendance screens for recruiters, who do not see the
 * Attendance section because it is gated on tasks.access. Anyone who already
 * sees that section gets no duplicate links.
 */
export const recruiterAttendanceNavItems: NavItem[] = [
    {
        title: 'My Attendance',
        url: '/attendance/mark',
        icon: UserCheck,
        permission: 'recruiter.access',
        hideWhenPermission: 'tasks.access',
    },
    {
        title: 'WFH Requests',
        url: '/attendance/wfh',
        icon: Home,
        permission: 'recruiter.access',
        hideWhenPermission: 'tasks.access',
        hideForRoles: ['super-admin'],
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
            title: 'Attendance',
            anyPermission: ['tasks.access'],
            items: attendanceNavItems,
        },
        {
            title: 'Task Management',
            anyPermission: ['tasks.access'],
            items: taskNavItems,
        },
        {
            title: 'Recruiter Operations',
            anyPermission: ['recruiter.access'],
            items: [...recruiterNavItems, ...recruiterAttendanceNavItems],
        },
    ];
}
