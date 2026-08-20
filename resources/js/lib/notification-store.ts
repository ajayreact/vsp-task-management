import { type AppNotification } from '@/types';

type Snapshot = {
    recent: AppNotification[];
    unreadCount: number;
};

const listeners = new Set<() => void>();
let serverRecent: AppNotification[] = [];
let serverUnread = 0;
let liveRecent: AppNotification[] = [];

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

function computeUnread(recent: AppNotification[]): number {
    return recent.filter((item) => !item.read_at).length;
}

function emitChange(): void {
    listeners.forEach((listener) => listener());
}

export function syncNotificationStoreFromServer(recent: AppNotification[], unreadCount: number): void {
    serverRecent = recent;
    serverUnread = unreadCount;
    liveRecent = liveRecent.filter((item) => !serverRecent.some((row) => row.id === item.id));
    emitChange();
}

export function pushLiveNotification(notification: AppNotification): void {
    if (liveRecent.some((item) => item.id === notification.id)) {
        return;
    }

    liveRecent = [notification, ...liveRecent].slice(0, 10);
    emitChange();
}

export function getNotificationStoreSnapshot(): Snapshot {
    const recent = mergeRecent(serverRecent, liveRecent);

    const unreadFromRecent = computeUnread(recent);
    const unreadCount = Math.max(serverUnread, unreadFromRecent);

    return { recent, unreadCount };
}

export function subscribeNotificationStore(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function resetLiveNotifications(): void {
    liveRecent = [];
    emitChange();
}
