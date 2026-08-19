import AppLayoutTemplate, { type AppSidebarLayoutProps } from '@/layouts/app/app-sidebar-layout';
import { staffNavGroups } from '@/lib/navigation';

/**
 * Internal task management layout. Same sidebar groups as the rest of the staff app.
 */
export default function TaskLayout({ children, breadcrumbs, ...props }: AppSidebarLayoutProps) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} groups={staffNavGroups()} footerItems={[]} {...props}>
            {children}
        </AppLayoutTemplate>
    );
}
