import { getNotificationStoreSnapshot, subscribeNotificationStore } from '@/lib/notification-store';
import { subscribeToRealtimeNotifications } from '@/lib/notification-realtime';
import { type AppNotification, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Reads bell state from the centralized notification store.
 * Echo subscription and polling are managed by NotificationProvider.
 */
export function useRealtimeNotifications() {
    const [snapshot, setSnapshot] = useState(getNotificationStoreSnapshot);

    useEffect(() => subscribeNotificationStore(() => setSnapshot(getNotificationStoreSnapshot())), []);

    return { recent: snapshot.recent, unreadCount: snapshot.unreadCount };
}

/**
 * Prepend live notifications onto a history page list without duplicating IDs.
 */
export function useRealtimeNotificationFeed(initial: AppNotification[]) {
    const { auth } = usePage<SharedData>().props;
    const [items, setItems] = useState(initial);

    useEffect(() => {
        setItems(initial);
    }, [initial]);

    useEffect(() => {
        if (!auth.user?.id) {
            return;
        }

        return subscribeToRealtimeNotifications((notification) => {
            setItems((current) => {
                if (current.some((item) => item.id === notification.id)) {
                    return current;
                }

                return [notification, ...current];
            });
        });
    }, [auth.user?.id]);

    return items;
}
