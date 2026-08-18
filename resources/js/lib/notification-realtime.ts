import { showIncomingBrowserNotification } from '@/lib/browser-notifications';
import { getEcho } from '@/lib/echo';
import { playNotificationSound } from '@/lib/notification-sound';
import { type AppNotification } from '@/types';
import { router } from '@inertiajs/react';

type RealtimePayload = {
    id?: string;
    event?: string | null;
    title?: string;
    body?: string;
    url?: string | null;
    task_id?: number | null;
    timesheet_id?: number | null;
    created_at?: string | null;
    read_at?: string | null;
    type?: string;
};

type Listener = (notification: AppNotification) => void;

const listeners = new Set<Listener>();
const seenIds = new Set<string>();
let subscribedUserId: number | null = null;
let subscriberCount = 0;

function toAppNotification(payload: RealtimePayload): AppNotification | null {
    if (!payload.id) {
        return null;
    }

    return {
        id: String(payload.id),
        event: payload.event ?? null,
        title: payload.title ?? 'Notification',
        body: payload.body ?? '',
        url: payload.url ?? null,
        task_id: payload.task_id ?? null,
        timesheet_id: payload.timesheet_id ?? null,
        read_at: payload.read_at ?? null,
        created_at: payload.created_at ?? new Date().toISOString(),
    };
}

export function rememberNotificationIds(ids: string[]): void {
    ids.forEach((id) => seenIds.add(id));
}

export function subscribeToRealtimeNotifications(listener: Listener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

/**
 * Reference-counted private channel subscription for the signed-in user.
 * Sound plays only for new Echo payloads whose IDs were not already known.
 */
export function startRealtimeNotifications(userId: number): () => void {
    const echo = getEcho();

    if (!echo) {
        return () => undefined;
    }

    subscriberCount += 1;

    if (subscribedUserId !== userId) {
        if (subscribedUserId !== null) {
            echo.leave(`staff.user.${subscribedUserId}`);
        }

        subscribedUserId = userId;
        const channelName = `staff.user.${userId}`;

        echo.private(channelName).notification((payload: RealtimePayload) => {
            const notification = toAppNotification(payload);

            if (!notification || seenIds.has(notification.id)) {
                return;
            }

            seenIds.add(notification.id);
            playNotificationSound();
            showIncomingBrowserNotification(notification, {
                onNavigate: (url) => {
                    router.visit(url);
                },
            });
            listeners.forEach((listener) => listener(notification));
        });
    }

    return () => {
        subscriberCount = Math.max(0, subscriberCount - 1);

        if (subscriberCount === 0 && subscribedUserId !== null) {
            echo.leave(`staff.user.${subscribedUserId}`);
            subscribedUserId = null;
        }
    };
}
