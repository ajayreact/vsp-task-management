import {
    dispatchIncomingNotification,
    isNotificationBaselineSeeded,
    seedNotificationBaseline,
} from '@/lib/notification-realtime';
import { type AppNotification } from '@/types';
import { useEffect } from 'react';

const POLL_INTERVAL_MS = 30_000;

type FeedResponse = {
    unread_count: number;
    recent: AppNotification[];
};

/**
 * Polls the notification feed when Echo/Reverb is unavailable or as a backup.
 * Only dispatches alerts for IDs not already seen this session.
 */
export function useNotificationPolling(enabled: boolean): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        let cancelled = false;

        const poll = async () => {
            try {
                const response = await fetch('/notifications/feed', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok || cancelled) {
                    return;
                }

                const payload = (await response.json()) as FeedResponse;

                if (!isNotificationBaselineSeeded()) {
                    seedNotificationBaseline(payload.recent.map((notification) => notification.id));

                    return;
                }

                payload.recent.forEach((notification) => {
                    dispatchIncomingNotification(notification);
                });
            } catch {
                // Network hiccup — next poll will retry.
            }
        };

        void poll();

        const interval = window.setInterval(() => {
            void poll();
        }, POLL_INTERVAL_MS);

        const onFocus = () => {
            void poll();
        };

        window.addEventListener('focus', onFocus);
        document.addEventListener('visibilitychange', onFocus);

        return () => {
            cancelled = true;
            window.clearInterval(interval);
            window.removeEventListener('focus', onFocus);
            document.removeEventListener('visibilitychange', onFocus);
        };
    }, [enabled]);
}
