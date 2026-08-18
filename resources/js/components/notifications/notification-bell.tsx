import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useBrowserNotificationPermission } from '@/hooks/use-browser-notification-permission';
import { useRealtimeNotifications } from '@/hooks/use-realtime-notifications';
import { type AppNotification } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Bell } from 'lucide-react';

function relativeTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));

    if (seconds < 60) {
        return 'Just now';
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    return `${days}d ago`;
}

function openNotification(notification: AppNotification) {
    const visit = () => {
        if (notification.url) {
            router.visit(notification.url);
        }
    };

    if (notification.read_at) {
        visit();

        return;
    }

    router.post(
        `/notifications/${notification.id}/read`,
        { redirect: false },
        {
            preserveScroll: true,
            onFinish: visit,
        },
    );
}

export function NotificationBell() {
    const { recent, unreadCount: unread } = useRealtimeNotifications();
    const { permission, requesting, requestPermission } = useBrowserNotificationPermission();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="text-muted-foreground hover:text-foreground relative"
                    aria-label={unread > 0 ? `Notifications, ${unread} unread` : 'Notifications'}
                >
                    <Bell className="size-4" strokeWidth={1.75} />
                    {unread > 0 && (
                        <span className="bg-primary text-primary-foreground absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold">
                            {unread > 99 ? '99+' : unread}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 rounded-xl p-0">
                <div className="flex items-center justify-between gap-2 px-3 py-2.5">
                    <DropdownMenuLabel className="p-0 text-sm font-semibold">Notifications</DropdownMenuLabel>
                    {unread > 0 && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="text-muted-foreground h-7 px-2 text-xs"
                            onClick={() => router.post('/notifications/read-all', {}, { preserveScroll: true })}
                        >
                            Mark all read
                        </Button>
                    )}
                </div>
                <DropdownMenuSeparator className="m-0" />
                <div className="max-h-80 overflow-y-auto py-1">
                    {recent.length === 0 && (
                        <p className="text-muted-foreground px-3 py-6 text-center text-sm">No notifications yet.</p>
                    )}
                    {recent.map((item) => (
                        <DropdownMenuItem
                            key={item.id}
                            className="focus:bg-muted/60 cursor-pointer items-start gap-2 rounded-none px-3 py-2.5"
                            onSelect={(event) => {
                                event.preventDefault();
                                openNotification(item);
                            }}
                        >
                            <span
                                className={`mt-1.5 size-2 shrink-0 rounded-full ${item.read_at ? 'bg-transparent' : 'bg-primary'}`}
                                aria-hidden
                            />
                            <span className="min-w-0 flex-1">
                                <span className={`block truncate text-sm ${item.read_at ? 'font-medium' : 'font-semibold'}`}>
                                    {item.title}
                                </span>
                                <span className="text-muted-foreground line-clamp-2 text-xs">{item.body}</span>
                                <span className="text-muted-foreground mt-1 block text-[11px]">{relativeTime(item.created_at)}</span>
                            </span>
                        </DropdownMenuItem>
                    ))}
                </div>
                {permission === 'default' && (
                    <>
                        <DropdownMenuSeparator className="m-0" />
                        <div className="px-3 py-2.5">
                            <p className="text-sm font-medium">Enable desktop notifications</p>
                            <p className="text-muted-foreground mt-0.5 text-xs">Get notified when important work updates happen.</p>
                            <Button
                                type="button"
                                size="sm"
                                className="mt-2 w-full"
                                disabled={requesting}
                                onClick={() => void requestPermission()}
                            >
                                Enable Notifications
                            </Button>
                        </div>
                    </>
                )}
                {permission === 'denied' && (
                    <>
                        <DropdownMenuSeparator className="m-0" />
                        <p className="text-muted-foreground px-3 py-2 text-[11px]">Browser notifications are blocked.</p>
                    </>
                )}
                <DropdownMenuSeparator className="m-0" />
                <div className="px-2 py-2">
                    <Button asChild variant="ghost" size="sm" className="w-full justify-center text-xs">
                        <Link href="/notifications">View all notifications</Link>
                    </Button>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
