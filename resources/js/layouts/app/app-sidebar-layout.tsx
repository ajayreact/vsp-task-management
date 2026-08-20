import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar, type AppSidebarProps } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashToaster } from '@/components/flash-toaster';
import { DesktopNotificationPrompt } from '@/components/notifications/desktop-notification-prompt';
import { NotificationProvider } from '@/components/notifications/notification-provider';
import { NotificationToastHost } from '@/components/notifications/notification-toast-host';
import { useNotificationSoundConfig } from '@/hooks/use-notification-sound-config';
import { type BreadcrumbItem } from '@/types';

export interface AppSidebarLayoutProps extends AppSidebarProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default function AppSidebarLayout({ children, breadcrumbs = [], ...sidebar }: AppSidebarLayoutProps) {
    useNotificationSoundConfig();

    return (
        <AppShell variant="sidebar">
            <NotificationProvider />
            <AppSidebar {...sidebar} />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <DesktopNotificationPrompt />
                {children}
            </AppContent>
            <FlashToaster />
            <NotificationToastHost />
        </AppShell>
    );
}
