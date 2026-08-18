import { type AppNotification } from '@/types';

export type NotificationToastItem = AppNotification & {
    visibleAt: number;
};

const AUTO_DISMISS_MS = 6000;
const MAX_VISIBLE = 3;

const queue: AppNotification[] = [];
const visible: NotificationToastItem[] = [];
const dismissTimers = new Map<string, ReturnType<typeof setTimeout>>();
const listeners = new Set<() => void>();

function emitChange(): void {
    listeners.forEach((listener) => listener());
}

function clearDismissTimer(id: string): void {
    const timer = dismissTimers.get(id);

    if (timer !== undefined) {
        clearTimeout(timer);
        dismissTimers.delete(id);
    }
}

function scheduleDismiss(id: string): void {
    clearDismissTimer(id);

    dismissTimers.set(
        id,
        setTimeout(() => {
            dismissNotificationToast(id);
        }, AUTO_DISMISS_MS),
    );
}

function promoteFromQueue(): void {
    while (visible.length < MAX_VISIBLE && queue.length > 0) {
        const next = queue.shift();

        if (!next) {
            break;
        }

        visible.push({ ...next, visibleAt: Date.now() });
        scheduleDismiss(next.id);
    }
}

/**
 * Enqueue a toast for a genuinely new notification. Respects the visible cap
 * and keeps overflow in a FIFO queue.
 */
export function showNotificationToast(notification: AppNotification): void {
    if (visible.some((item) => item.id === notification.id) || queue.some((item) => item.id === notification.id)) {
        return;
    }

    if (visible.length < MAX_VISIBLE) {
        visible.push({ ...notification, visibleAt: Date.now() });
        scheduleDismiss(notification.id);
    } else {
        queue.push(notification);
    }

    emitChange();
}

export function dismissNotificationToast(id: string): void {
    clearDismissTimer(id);

    const visibleIndex = visible.findIndex((item) => item.id === id);

    if (visibleIndex !== -1) {
        visible.splice(visibleIndex, 1);
        promoteFromQueue();
        emitChange();
    }
}

export function getNotificationToastSnapshot(): readonly NotificationToastItem[] {
    return visible;
}

export function subscribeNotificationToasts(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}
