import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [], label = 'Platform', anyPermission }: { items: NavItem[]; label?: string; anyPermission?: string[] }) {
    const page = usePage();
    const { can, canAny, hasRole } = usePermissions();
    const isAdmin = hasRole('super-admin') || hasRole('admin');

    if (!isAdmin && !canAny(anyPermission)) {
        return null;
    }

    const visible = items.filter((item) => {
        if (item.role && !hasRole(item.role)) {
            return false;
        }

        return can(item.permission);
    });

    // A heading with nothing under it is worse than no heading.
    if (visible.length === 0) {
        return null;
    }

    return (
        <SidebarGroup className="px-1 py-0">
            <SidebarGroupLabel className="text-muted-foreground mb-2 text-[11px] font-semibold tracking-[0.14em] uppercase">
                {label}
            </SidebarGroupLabel>
            <SidebarMenu className="gap-1">
                {visible.map((item) => {
                    const label =
                        item.contributorTitle && !can('tasks.view_all') ? item.contributorTitle : item.title;

                    return (
                    <SidebarMenuItem key={item.url}>
                        <SidebarMenuButton asChild isActive={isCurrent(page.url, item.url)} tooltip={label}>
                            <Link href={item.url} prefetch>
                                {item.icon && <item.icon strokeWidth={1.75} />}
                                <span>{label}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}

/**
 * Keeps the parent item highlighted on nested screens, so /admin/employees/3/edit
 * still lights up "Employees". Sibling prefixes are not treated as children:
 * /tasks/board must not light up the "Tasks" item whose url is /tasks.
 */
function isCurrent(currentUrl: string, itemUrl: string): boolean {
    const path = currentUrl.split('?')[0];

    if (path === itemUrl) {
        return true;
    }

    // The task list lives at the module root. Only a numeric task id (and its
    // edit screen) counts as "still on that item".
    if (itemUrl === '/tasks') {
        return /^\/tasks\/\d+(\/|$)/.test(path);
    }

    return path.startsWith(`${itemUrl}/`);
}
