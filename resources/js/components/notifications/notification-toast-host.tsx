import { Button } from '@/components/ui/button';
import { isSafeAppPath } from '@/lib/browser-notifications';
import {
    dismissNotificationToast,
    getNotificationToastSnapshot,
    subscribeNotificationToasts,
} from '@/lib/notification-toast-store';
import { cn } from '@/lib/utils';
import { type AppNotification } from '@/types';
import { router } from '@inertiajs/react';
import { Bell, X } from 'lucide-react';
import { useSyncExternalStore } from 'react';

function openNotification(notification: AppNotification): void {
    dismissNotificationToast(notification.id);

    if (notification.read_at) {
        if (notification.url && isSafeAppPath(notification.url)) {
            router.visit(notification.url);
        }

        return;
    }

    router.post(
        `/notifications/${notification.id}/read`,
        { redirect: true },
        {
            preserveScroll: true,
            onError: () => {
                if (notification.url && isSafeAppPath(notification.url)) {
                    router.visit(notification.url);
                }
            },
        },
    );
}

function ToastCard({ notification }: { notification: AppNotification }) {
    const hasViewAction = Boolean(notification.url && isSafeAppPath(notification.url));
    const viewLabel = notification.task_id ? 'View task' : 'View';

    return (
        <article
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            tabIndex={hasViewAction ? 0 : undefined}
            onClick={() => {
                if (hasViewAction) {
                    openNotification(notification);
                }
            }}
            onKeyDown={(event) => {
                if (!hasViewAction) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openNotification(notification);
                }
            }}
            className={cn(
                'bg-card text-card-foreground w-full overflow-hidden rounded-xl border shadow-lg',
                'motion-safe:animate-in motion-safe:fade-in-0 motion-safe:slide-in-from-top-2 motion-safe:duration-300',
                'motion-reduce:transition-none motion-reduce:animate-none',
                hasViewAction && 'cursor-pointer',
            )}
        >
            <div className="bg-primary/10 border-primary flex gap-3 border-l-4 p-4">
                <div
                    className="bg-primary text-primary-foreground mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full"
                    aria-hidden
                >
                    <Bell className="size-4" strokeWidth={1.75} />
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <p className="text-sm leading-snug font-semibold">{notification.title}</p>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="text-muted-foreground hover:text-foreground size-7 shrink-0"
                            aria-label="Dismiss notification"
                            onClick={(event) => {
                                event.stopPropagation();
                                dismissNotificationToast(notification.id);
                            }}
                        >
                            <X className="size-4" />
                        </Button>
                    </div>

                    {notification.body && (
                        <p className="text-muted-foreground mt-1 line-clamp-3 text-sm whitespace-pre-wrap">{notification.body}</p>
                    )}

                    {hasViewAction && (
                        <p className="text-primary mt-2 text-sm font-semibold" aria-hidden>
                            {viewLabel} →
                        </p>
                    )}
                </div>
            </div>
        </article>
    );
}

/**
 * Top-right in-app alert stack for genuinely new realtime notifications.
 */
export function NotificationToastHost() {
    const toasts = useSyncExternalStore(
        subscribeNotificationToasts,
        getNotificationToastSnapshot,
        () => [],
    );

    if (toasts.length === 0) {
        return null;
    }

    return (
        <div
            className="pointer-events-none fixed top-4 right-4 z-[100] flex w-[min(calc(100vw-2rem),24rem)] flex-col gap-3"
            aria-label="Notification alerts"
        >
            {toasts.map((notification) => (
                <div key={notification.id} className="pointer-events-auto">
                    <ToastCard notification={notification} />
                </div>
            ))}
        </div>
    );
}
