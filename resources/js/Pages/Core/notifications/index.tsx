import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useRealtimeNotificationFeed } from '@/hooks/use-realtime-notifications';
import AppLayout from '@/layouts/app-layout';
import { type AppNotification, type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Props {
    notifications: Paginated<AppNotification>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notifications', href: '/notifications' },
];

function openNotification(notification: AppNotification) {
    if (notification.read_at) {
        if (notification.url) {
            router.visit(notification.url);
        }

        return;
    }

    router.post(`/notifications/${notification.id}/read`, { redirect: true }, { preserveScroll: true });
}

export default function NotificationsIndex({ notifications }: Props) {
    const items = useRealtimeNotificationFeed(notifications.data);
    const hasUnread = items.some((item) => !item.read_at);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Notifications"
                    description="Task assignments, reviews, and timesheet updates."
                    action={
                        hasUnread ? (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.post('/notifications/read-all', {}, { preserveScroll: true })}
                            >
                                Mark all as read
                            </Button>
                        ) : undefined
                    }
                />

                <Card className="vsp-card">
                    <CardContent className="divide-border divide-y p-0">
                        {items.length === 0 && (
                            <p className="text-muted-foreground px-5 py-10 text-center text-sm">No notifications yet.</p>
                        )}

                        {items.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => openNotification(item)}
                                className="hover:bg-muted/40 flex w-full items-start gap-3 px-5 py-4 text-left transition-colors"
                            >
                                <span
                                    className={`mt-1.5 size-2.5 shrink-0 rounded-full ${item.read_at ? 'bg-border' : 'bg-primary'}`}
                                    aria-hidden
                                />
                                <span className="min-w-0 flex-1">
                                    <span className={`block text-sm ${item.read_at ? 'font-medium' : 'font-semibold'}`}>{item.title}</span>
                                    <span className="text-muted-foreground mt-0.5 block text-sm">{item.body}</span>
                                    <span className="text-muted-foreground mt-1.5 block text-xs">
                                        {item.created_at ? new Date(item.created_at).toLocaleString() : ''}
                                    </span>
                                </span>
                            </button>
                        ))}
                    </CardContent>
                </Card>

                {notifications.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {notifications.links.map((link, index) =>
                            link.url ? (
                                <Button key={index} asChild variant={link.active ? 'default' : 'outline'} size="sm">
                                    <Link href={link.url} preserveState>
                                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                    </Link>
                                </Button>
                            ) : (
                                <Button key={index} variant="outline" size="sm" disabled>
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                </Button>
                            ),
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
