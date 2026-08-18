import AppLayoutTemplate, { type AppSidebarLayoutProps } from '@/layouts/app/app-sidebar-layout';
import { staffNavGroups } from '@/lib/navigation';

/**
 * Shared kernel layout — dashboard, settings and anything that belongs to no
 * single module. Task Management uses the same sidebar groups.
 */
export default function AppLayout({ children, breadcrumbs, groups, homeUrl, footerItems, ...props }: AppSidebarLayoutProps) {
    return (
        <AppLayoutTemplate
            breadcrumbs={breadcrumbs}
            groups={groups ?? staffNavGroups()}
            homeUrl={homeUrl}
            footerItems={footerItems ?? []}
            {...props}
        >
            {children}
        </AppLayoutTemplate>
    );
}
