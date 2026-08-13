import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [], label = 'Platform' }: { items: NavItem[]; label?: string }) {
    const page = usePage();
    const { can } = usePermissions();

    const visible = items.filter((item) => can(item.permission));

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarMenu>
                {visible.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton asChild isActive={isCurrent(page.url, item.url)}>
                            <Link href={item.url} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}

/**
 * Keeps the parent item highlighted on nested screens, so /admin/employees/3/edit
 * still lights up "Employees".
 */
function isCurrent(currentUrl: string, itemUrl: string): boolean {
    const path = currentUrl.split('?')[0];

    return path === itemUrl || path.startsWith(`${itemUrl}/`);
}
