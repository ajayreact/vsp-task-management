import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { staffNavGroups } from '@/lib/navigation';
import { type NavGroup, type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import AppLogo from './app-logo';

export interface AppSidebarProps {
    /** Navigation sections, rendered top to bottom. */
    groups?: NavGroup[];
    /** Logo link destination. Defaults to the Command Center. */
    homeUrl?: string;
    /** Cross-module shortcuts. Pass an empty array to hide them entirely. */
    footerItems?: NavItem[];
}

export function AppSidebar({ groups = staffNavGroups(), homeUrl = '/dashboard', footerItems = [] }: AppSidebarProps) {
    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-6 px-2 py-4">
                {groups.map((group) => (
                    <NavMain key={group.title} items={group.items} label={group.title} anyPermission={group.anyPermission} />
                ))}
            </SidebarContent>

            {footerItems.length > 0 && (
                <SidebarFooter>
                    <NavFooter items={footerItems} className="mt-auto" />
                </SidebarFooter>
            )}
        </Sidebar>
    );
}
