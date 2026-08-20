import { handleAttendanceDashboardBroadcast } from '@/lib/attendance-dashboard-realtime';
import { handleCommandCenterBroadcast } from '@/lib/command-center-realtime';
import { handleIncomingNotification } from '@/lib/incoming-notification';
import { handleOpenBoardBroadcast } from '@/lib/open-board-realtime';
import { getEcho } from '@/lib/echo';
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
    actor?: {
        id?: number;
        name?: string;
        avatar?: string | null;
    } | null;
    created_at?: string | null;
    read_at?: string | null;
    type?: string;
};

type Listener = (notification: AppNotification) => void;

const listeners = new Set<Listener>();
const seenIds = new Set<string>();
let baselineSeeded = false;
let subscribedUserId: number | null = null;
let subscriberCount = 0;

export function seedNotificationBaseline(ids: string[]): void {
    rememberNotificationIds(ids);
    baselineSeeded = true;
}

export function isNotificationBaselineSeeded(): boolean {
    return baselineSeeded;
}

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
        actor: payload.actor
            ? {
                  id: payload.actor.id ?? 0,
                  name: payload.actor.name ?? 'Someone',
                  avatar: payload.actor.avatar ?? null,
              }
            : null,
        read_at: payload.read_at ?? null,
        created_at: payload.created_at ?? new Date().toISOString(),
    };
}

export function rememberNotificationIds(ids: string[]): void {
    ids.forEach((id) => seenIds.add(id));
}

export function hasSeenNotification(id: string): boolean {
    return seenIds.has(id);
}

export function subscribeToRealtimeNotifications(listener: Listener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function dispatchIncomingNotification(notification: AppNotification): void {
    if (seenIds.has(notification.id)) {
        return;
    }

    seenIds.add(notification.id);

    if (baselineSeeded) {
        handleIncomingNotification(notification, {
            onNavigate: (url) => {
                router.visit(url);
            },
        });
    }

    listeners.forEach((listener) => listener(notification));
}

/**
 * Reference-counted private channel subscription for the signed-in user.
 * Sound and alerts fire only for new payloads whose IDs were not already known.
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

        echo.private(channelName)
            .notification((payload: RealtimePayload) => {
                const notification = toAppNotification(payload);

                if (!notification) {
                    return;
                }

                dispatchIncomingNotification(notification);
            })
            .listen('.open-board.task-claimed', (payload: { task_id: number }) => {
                handleOpenBoardBroadcast('open-board.task-claimed', payload);
            })
            .listen('.open-board.task-published', (payload: { task: Record<string, unknown> }) => {
                handleOpenBoardBroadcast('open-board.task-published', payload);
            })
            .listen('.command-center.updated', () => {
                handleCommandCenterBroadcast('command-center.updated');
            })
            .listen('.attendance-dashboard.updated', () => {
                handleAttendanceDashboardBroadcast('attendance-dashboard.updated');
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

export function isRealtimeNotificationsAvailable(): boolean {
    return getEcho() !== null;
}
