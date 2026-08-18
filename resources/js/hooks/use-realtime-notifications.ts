import {
    rememberNotificationIds,
    seedNotificationBaseline,
    startRealtimeNotifications,
    subscribeToRealtimeNotifications,
} from '@/lib/notification-realtime';
import { useNotificationPolling } from '@/hooks/use-notification-polling';
import { type AppNotification, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function mergeRecent(server: AppNotification[], live: AppNotification[]): AppNotification[] {
    const map = new Map<string, AppNotification>();

    [...live, ...server].forEach((item) => {
        const existing = map.get(item.id);

        if (!existing) {
            map.set(item.id, item);

            return;
        }

        if (!existing.read_at && item.read_at) {
            map.set(item.id, { ...existing, ...item });
        }
    });

    return Array.from(map.values())
        .sort((a, b) => {
            const aTime = a.created_at ? new Date(a.created_at).getTime() : 0;
            const bTime = b.created_at ? new Date(b.created_at).getTime() : 0;

            return bTime - aTime;
        })
        .slice(0, 10);
}

/**
 * Keeps bell state in sync with Inertia shared props, live Echo events, and polling.
 */
export function useRealtimeNotifications() {
    const { auth, notifications } = usePage<SharedData>().props;
    const serverRecent = useMemo(() => notifications?.recent ?? [], [notifications?.recent]);
    const serverUnread = notifications?.unread_count ?? 0;

    const [live, setLive] = useState<AppNotification[]>([]);
    const [liveUnreadBump, setLiveUnreadBump] = useState(0);

    useNotificationPolling(Boolean(auth.user?.id));

    useEffect(() => {
        seedNotificationBaseline(serverRecent.map((item) => item.id));
        setLive((current) => current.filter((item) => !serverRecent.some((row) => row.id === item.id)));
        setLiveUnreadBump(0);
    }, [serverRecent]);

    useEffect(() => {
        const userId = auth.user?.id;

        if (!userId) {
            return;
        }

        const stopEcho = startRealtimeNotifications(userId);
        const unsubscribe = subscribeToRealtimeNotifications((notification) => {
            setLive((current) => {
                if (current.some((item) => item.id === notification.id)) {
                    return current;
                }

                return [notification, ...current].slice(0, 10);
            });
            setLiveUnreadBump((count) => count + 1);
        });

        return () => {
            unsubscribe();
            stopEcho();
        };
    }, [auth.user?.id]);

    const recent = useMemo(() => mergeRecent(serverRecent, live), [serverRecent, live]);
    const unreadCount = Math.max(0, serverUnread + liveUnreadBump);

    return { recent, unreadCount };
}

/**
 * Prepend live notifications onto a history page list without duplicating IDs.
 */
export function useRealtimeNotificationFeed(initial: AppNotification[]) {
    const { auth } = usePage<SharedData>().props;
    const [items, setItems] = useState(initial);

    useNotificationPolling(Boolean(auth.user?.id));

    useEffect(() => {
        setItems(initial);
        seedNotificationBaseline(initial.map((item) => item.id));
    }, [initial]);

    useEffect(() => {
        const userId = auth.user?.id;

        if (!userId) {
            return;
        }

        const stopEcho = startRealtimeNotifications(userId);
        const unsubscribe = subscribeToRealtimeNotifications((notification) => {
            setItems((current) => {
                if (current.some((item) => item.id === notification.id)) {
                    return current;
                }

                return [notification, ...current];
            });
        });

        return () => {
            unsubscribe();
            stopEcho();
        };
    }, [auth.user?.id]);

    return items;
}
