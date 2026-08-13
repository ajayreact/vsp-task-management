import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { coreNavItems, moduleSwitcherItems } from '@/lib/navigation';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import AppLogo from './app-logo';

export interface AppSidebarProps {
    /** Primary navigation for the module currently being viewed. */
    items?: NavItem[];
    /** Group heading above the primary navigation. */
    label?: string;
    /** Where the logo links to within the current module. */
    homeUrl?: string;
    /** Cross-module shortcuts. Pass an empty array to hide them entirely. */
    footerItems?: NavItem[];
}

export function AppSidebar({ items = coreNavItems, label = 'Platform', homeUrl = '/dashboard', footerItems = moduleSwitcherItems }: AppSidebarProps) {
    return (
        <Sidebar collapsible="icon" variant="inset">
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

            <SidebarContent>
                <NavMain items={items} label={label} />
            </SidebarContent>

            <SidebarFooter>
                {footerItems.length > 0 && <NavFooter items={footerItems} className="mt-auto" />}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
