import AppLayoutTemplate, { type AppSidebarLayoutProps } from '@/layouts/app/app-sidebar-layout';
import { staffNavGroups } from '@/lib/navigation';

/**
 * Recruiter Operations layout. Same staff shell and sidebar as the rest of the
 * app; the Recruiter Operations section appears for users with recruiter.access.
 */
export default function RecruiterLayout({ children, breadcrumbs, ...props }: AppSidebarLayoutProps) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} groups={staffNavGroups()} footerItems={[]} {...props}>
            {children}
        </AppLayoutTemplate>
    );
}
