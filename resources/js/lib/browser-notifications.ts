/**
 * Browser Notification API helpers. Safe when the API is missing.
 * Does not request permission on load — callers must use a user gesture.
 */

export type BrowserNotificationPermission = NotificationPermission | 'unsupported';

type WorkNotification = {
    id: string;
    event: string | null;
    title: string;
    body: string;
    url: string | null;
};

type ShowOptions = {
    onNavigate?: (url: string) => void;
};

function isBrowserNotificationSupported(): boolean {
    return typeof window !== 'undefined' && typeof Notification !== 'undefined';
}

export function getBrowserNotificationPermission(): BrowserNotificationPermission {
    if (!isBrowserNotificationSupported()) {
        return 'unsupported';
    }

    try {
        return Notification.permission;
    } catch {
        return 'unsupported';
    }
}

export async function requestBrowserNotificationPermission(): Promise<BrowserNotificationPermission> {
    if (!isBrowserNotificationSupported()) {
        return 'unsupported';
    }

    try {
        const result = await Promise.resolve(Notification.requestPermission());

        if (result === 'granted' || result === 'denied' || result === 'default') {
            return result;
        }

        return getBrowserNotificationPermission();
    } catch {
        return getBrowserNotificationPermission();
    }
}

export function subscribeToBrowserNotificationPermission(
    listener: (permission: BrowserNotificationPermission) => void,
): () => void {
    if (typeof navigator === 'undefined' || navigator.permissions?.query === undefined) {
        return () => undefined;
    }

    let cancelled = false;
    let status: PermissionStatus | undefined;

    void navigator.permissions
        .query({ name: 'notifications' })
        .then((permissionStatus) => {
            if (cancelled) {
                return;
            }

            status = permissionStatus;
            permissionStatus.onchange = () => {
                listener(getBrowserNotificationPermission());
            };
        })
        .catch(() => {
            // Safari and some browsers reject this query.
        });

    return () => {
        cancelled = true;

        if (status) {
            status.onchange = null;
        }
    };
}

export function isImportantWorkNotification(event: string | null): boolean {
    if (event === null || event === '') {
        return true;
    }

    return event.startsWith('task.') || event.startsWith('timesheet.');
}

export function isSafeAppPath(url: string): boolean {
    if (!url.startsWith('/') || url.startsWith('//') || url.includes('\\')) {
        return false;
    }

    try {
        const parsed = new URL(url, window.location.origin);

        return parsed.origin === window.location.origin && parsed.pathname.startsWith('/');
    } catch {
        return false;
    }
}

function notificationIcon(): string | undefined {
    if (typeof window === 'undefined') {
        return undefined;
    }

    const link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');

    return link?.href || `${window.location.origin}/favicon.ico`;
}

/**
 * Show a system notification for an already-authorized realtime payload.
 */
export function showIncomingBrowserNotification(notification: WorkNotification, options?: ShowOptions): boolean {
    try {
        if (!isBrowserNotificationSupported()) {
            return false;
        }

        if (Notification.permission !== 'granted') {
            return false;
        }

        if (!isImportantWorkNotification(notification.event)) {
            return false;
        }

        const systemNotification = new Notification(notification.title, {
            body: notification.body,
            tag: `vsp-crm-${notification.id}`,
            icon: notificationIcon(),
        });

        systemNotification.onclick = () => {
            try {
                window.focus();
                systemNotification.close();

                const url = notification.url;

                if (url && isSafeAppPath(url)) {
                    options?.onNavigate?.(url);
                }
            } catch {
                // Never throw from a notification click into the UI.
            }
        };

        return true;
    } catch {
        return false;
    }
}
