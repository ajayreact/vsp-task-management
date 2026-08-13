import AppLayoutTemplate, { type AppSidebarLayoutProps } from '@/layouts/app/app-sidebar-layout';
import { crmNavItems } from '@/lib/navigation';

/**
 * Staff-facing CRM layout. The client portal uses PortalLayout instead — the
 * two must not share navigation.
 */
export default function CrmLayout({ children, breadcrumbs, ...props }: AppSidebarLayoutProps) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} items={crmNavItems} label="CRM & Campaigns" homeUrl="/crm" {...props}>
            {children}
        </AppLayoutTemplate>
    );
}
