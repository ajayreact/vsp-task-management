import { type NotificationPreferences } from '@/types';

const defaults: NotificationPreferences = {
    browser_notifications: true,
    notification_sound: true,
    in_app_notifications: true,
};

let preferences: NotificationPreferences = { ...defaults };

export function configureNotificationPreferences(next: NotificationPreferences | null | undefined): void {
    preferences = next ? { ...defaults, ...next } : { ...defaults };
}

export function getNotificationPreferences(): NotificationPreferences {
    return preferences;
}

export function shouldShowInAppNotifications(): boolean {
    return preferences.in_app_notifications;
}

export function shouldPlayNotificationSound(): boolean {
    return preferences.notification_sound;
}

export function shouldShowBrowserNotifications(): boolean {
    return preferences.browser_notifications;
}
