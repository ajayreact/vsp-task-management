import AppLayoutTemplate, { type AppSidebarLayoutProps } from '@/layouts/app/app-sidebar-layout';
import { taskNavItems } from '@/lib/navigation';

/**
 * Internal task management layout for employees and managers.
 */
export default function TaskLayout({ children, breadcrumbs, ...props }: AppSidebarLayoutProps) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} items={taskNavItems} label="Task Management" homeUrl="/tasks" {...props}>
            {children}
        </AppLayoutTemplate>
    );
}
