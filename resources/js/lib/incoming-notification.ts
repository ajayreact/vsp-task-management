import { showIncomingBrowserNotification } from '@/lib/browser-notifications';
import { readNotificationPreferences } from '@/lib/notification-preferences';
import { playNotificationSound } from '@/lib/notification-sound';
import { type AppNotification } from '@/types';
import { toast } from 'sonner';

type NavigateHandler = (url: string) => void;

/**
 * Handle a genuinely new notification: sound, browser alert, and in-app toast.
 */
export function handleIncomingNotification(notification: AppNotification, options?: { onNavigate?: NavigateHandler }): void {
    const preferences = readNotificationPreferences();

    if (preferences.sound) {
        playNotificationSound();
    }

    if (preferences.browser) {
        showIncomingBrowserNotification(notification, {
            onNavigate: options?.onNavigate,
        });
    }

    if (preferences.inApp) {
        toast(notification.title, {
            description: notification.body,
            action: notification.url
                ? {
                      label: 'Open',
                      onClick: () => {
                          if (notification.url) {
                              options?.onNavigate?.(notification.url);
                          }
                      },
                  }
                : undefined,
        });
    }
}
