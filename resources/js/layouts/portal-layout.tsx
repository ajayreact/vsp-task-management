import AppLayoutTemplate, { type AppSidebarLayoutProps } from '@/layouts/app/app-sidebar-layout';
import { portalNavItems } from '@/lib/navigation';

/**
 * Client portal layout. Deliberately renders no module switcher and no staff
 * navigation — a client user must never see a route they cannot reach.
 */
export default function PortalLayout({ children, breadcrumbs, ...props }: AppSidebarLayoutProps) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} items={portalNavItems} label="Client Portal" homeUrl="/portal" footerItems={[]} {...props}>
            {children}
        </AppLayoutTemplate>
    );
}
