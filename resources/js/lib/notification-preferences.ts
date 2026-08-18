export type NotificationPreferences = {
    inApp: boolean;
    browser: boolean;
    sound: boolean;
};

const STORAGE_KEY = 'vsp-crm.notification-preferences';

const DEFAULTS: NotificationPreferences = {
    inApp: true,
    browser: true,
    sound: true,
};

export function readNotificationPreferences(): NotificationPreferences {
    if (typeof window === 'undefined') {
        return DEFAULTS;
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return DEFAULTS;
        }

        const parsed = JSON.parse(raw) as Partial<NotificationPreferences>;

        return {
            inApp: parsed.inApp ?? DEFAULTS.inApp,
            browser: parsed.browser ?? DEFAULTS.browser,
            sound: parsed.sound ?? DEFAULTS.sound,
        };
    } catch {
        return DEFAULTS;
    }
}

export function writeNotificationPreferences(preferences: NotificationPreferences): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
    } catch {
        // Private mode may block storage.
    }
}

export function updateNotificationPreference<K extends keyof NotificationPreferences>(
    key: K,
    value: NotificationPreferences[K],
): NotificationPreferences {
    const next = { ...readNotificationPreferences(), [key]: value };
    writeNotificationPreferences(next);

    return next;
}
