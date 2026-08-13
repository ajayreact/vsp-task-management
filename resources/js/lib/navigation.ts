import { type NavItem } from '@/types';
import { Briefcase, LayoutGrid, Users } from 'lucide-react';

/**
 * Each module owns its own navigation. Keeping the three lists apart means a
 * CRM screen can never accidentally surface a Task Management link, and the
 * client portal only ever sees portal routes.
 */

export const coreNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
];

export const crmNavItems: NavItem[] = [
    {
        title: 'Overview',
        url: '/crm',
        icon: LayoutGrid,
    },
];

export const taskNavItems: NavItem[] = [
    {
        title: 'Overview',
        url: '/tasks',
        icon: LayoutGrid,
    },
];

export const portalNavItems: NavItem[] = [
    {
        title: 'Overview',
        url: '/portal',
        icon: LayoutGrid,
    },
];

/**
 * Staff-only shortcuts for moving between the two modules. Never rendered in
 * the client portal.
 */
export const moduleSwitcherItems: NavItem[] = [
    {
        title: 'CRM & Campaigns',
        url: '/crm',
        icon: Users,
    },
    {
        title: 'Task Management',
        url: '/tasks',
        icon: Briefcase,
    },
];
