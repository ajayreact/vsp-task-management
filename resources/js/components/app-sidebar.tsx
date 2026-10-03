import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { staffNavGroups } from '@/lib/navigation';
import { type NavGroup, type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { useLayoutEffect, useRef } from 'react';
import AppLogo from './app-logo';

export interface AppSidebarProps {
    /** Navigation sections, rendered top to bottom. */
    groups?: NavGroup[];
    /** Logo link destination. Defaults to the Command Center. */
    homeUrl?: string;
    /** Cross-module shortcuts. Pass an empty array to hide them entirely. */
    footerItems?: NavItem[];
}

const SCROLL_KEY = 'app-sidebar-scroll';

/**
 * Each page renders its own layout, so the sidebar is rebuilt on every visit
 * and would start scrolled to the top. Restore where it was, then make sure
 * the highlighted item is on screen.
 */
function useSidebarScroll() {
    const ref = useRef<HTMLDivElement>(null);

    useLayoutEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        element.scrollTop = Number(sessionStorage.getItem(SCROLL_KEY)) || 0;
        element.querySelector<HTMLElement>('[data-active="true"]')?.scrollIntoView({ block: 'nearest' });

        const save = () => sessionStorage.setItem(SCROLL_KEY, String(element.scrollTop));
        element.addEventListener('scroll', save, { passive: true });

        return () => element.removeEventListener('scroll', save);
    }, []);

    return ref;
}

export function AppSidebar({ groups = staffNavGroups(), homeUrl = '/dashboard', footerItems = [] }: AppSidebarProps) {
    const contentRef = useSidebarScroll();

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

            <SidebarContent ref={contentRef} className="gap-6 px-2 py-4">
                {groups.map((group) => (
                    <NavMain
                        key={group.title}
                        items={group.items}
                        label={group.title}
                        anyPermission={group.anyPermission}
                        hideForRecruiterOnly={group.hideForRecruiterOnly}
                    />
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
