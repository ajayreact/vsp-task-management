import { showIncomingBrowserNotification } from '@/lib/browser-notifications';
import { showNotificationToast } from '@/lib/notification-toast-store';
import { playNotificationSound } from '@/lib/notification-sound';
import { type AppNotification } from '@/types';

type NavigateHandler = (url: string) => void;

/**
 * Handle a genuinely new notification: in-app toast, sound, and browser alert.
 * Called only from dispatchIncomingNotification after duplicate filtering.
 */
export function handleIncomingNotification(notification: AppNotification, options?: { onNavigate?: NavigateHandler }): void {
    showNotificationToast(notification);
    playNotificationSound();
    showIncomingBrowserNotification(notification, {
        onNavigate: options?.onNavigate,
    });
}
