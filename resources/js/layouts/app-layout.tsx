import AppLayoutTemplate, { type AppSidebarLayoutProps } from '@/layouts/app/app-sidebar-layout';

/**
 * Shared kernel layout — dashboard, settings and anything that belongs to no
 * single module. CRM and Task Management have their own layouts.
 */
export default function AppLayout({ children, breadcrumbs, ...props }: AppSidebarLayoutProps) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            {children}
        </AppLayoutTemplate>
    );
}
