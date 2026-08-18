import { showIncomingBrowserNotification } from '@/lib/browser-notifications';
import { readNotificationPreferences } from '@/lib/notification-preferences';
import { showNotificationToast } from '@/lib/notification-toast-store';
import { playNotificationSound } from '@/lib/notification-sound';
import { type AppNotification } from '@/types';

type NavigateHandler = (url: string) => void;

/**
 * Handle a genuinely new notification: in-app toast, sound, and browser alert.
 * Called only from dispatchIncomingNotification after duplicate filtering.
 */
export function handleIncomingNotification(notification: AppNotification, options?: { onNavigate?: NavigateHandler }): void {
    const preferences = readNotificationPreferences();

    if (preferences.inApp) {
        showNotificationToast(notification);
    }

    if (preferences.sound) {
        playNotificationSound();
    }

    if (preferences.browser) {
        showIncomingBrowserNotification(notification, {
            onNavigate: options?.onNavigate,
        });
    }
}
