import {
    seedNotificationBaseline,
    startRealtimeNotifications,
    subscribeToRealtimeNotifications,
} from '@/lib/notification-realtime';
import { configureNotificationPreferences } from '@/lib/notification-preference-store';
import { pushLiveNotification, syncNotificationStoreFromServer } from '@/lib/notification-store';
import { useNotificationPolling } from '@/hooks/use-notification-polling';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Central notification manager: Echo subscription, polling fallback, and store sync.
 * Mounted once from the authenticated app layout.
 */
export function NotificationProvider(): null {
    const { auth, notifications, notificationPreferences } = usePage<SharedData>().props;
    const serverRecent = notifications?.recent ?? [];
    const serverUnread = notifications?.unread_count ?? 0;

    useEffect(() => {
        configureNotificationPreferences(notificationPreferences);
    }, [notificationPreferences]);

    useEffect(() => {
        syncNotificationStoreFromServer(serverRecent, serverUnread);
        seedNotificationBaseline(serverRecent.map((item) => item.id));
    }, [serverRecent, serverUnread]);

    useEffect(() => {
        const userId = auth.user?.id;

        if (!userId) {
            return;
        }

        const stopEcho = startRealtimeNotifications(userId);
        const unsubscribe = subscribeToRealtimeNotifications((notification) => {
            pushLiveNotification(notification);
        });

        return () => {
            unsubscribe();
            stopEcho();
        };
    }, [auth.user?.id]);

    useNotificationPolling(true);

    return null;
}
