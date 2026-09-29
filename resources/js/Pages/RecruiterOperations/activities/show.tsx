import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import {
    formatActivityDate,
    formatActivityDuration,
    formatActivityTimeRange,
} from '@/components/recruiter-operations/recruiter-activity-format';
import { formatRecruiterDateTime } from '@/components/recruiter-operations/recruiter-task-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { type ReactNode } from 'react';

interface ActivityDetail {
    id: number;
    activity_date: string;
    activity_type_label: string;
    title: string;
    description: string | null;
    start_time: string | null;
    end_time: string | null;
    duration_minutes: number | null;
    quantity: number | null;
    task: { id: number; title: string } | null;
    recruiter_name: string | null;
    remarks: string | null;
    created_by_name: string | null;
    updated_by_name: string | null;
    created_at: string;
    updated_at: string;
    can: { update: boolean; delete: boolean };
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="space-y-1">
            <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{label}</dt>
            <dd className="text-sm">{children}</dd>
        </div>
    );
}

export default function ShowRecruiterDailyActivity({ activity }: { activity: ActivityDetail }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Daily Activities', href: '/recruiter/activities' },
        { title: activity.title, href: `/recruiter/activities/${activity.id}` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={activity.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={activity.title}
                    description={`${activity.activity_type_label} · ${formatActivityDate(activity.activity_date)}`}
                    action={
                        <div className="flex gap-2">
                            {activity.can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={`/recruiter/activities/${activity.id}/edit`}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>
                            )}
                            {activity.can.delete && (
                                <ConfirmDelete
                                    trigger={
                                        <Button variant="outline" className="text-destructive">
                                            <Trash2 /> Delete
                                        </Button>
                                    }
                                    title="Delete this activity?"
                                    description={`"${activity.title}" will be removed from the activity log.`}
                                    url={`/recruiter/activities/${activity.id}`}
                                />
                            )}
                        </div>
                    }
                />

                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Detail label="Recruiter">{activity.recruiter_name ?? '—'}</Detail>
                            <Detail label="Date">{formatActivityDate(activity.activity_date)}</Detail>
                            <Detail label="Activity type">{activity.activity_type_label}</Detail>
                            <Detail label="Time">{formatActivityTimeRange(activity.start_time, activity.end_time)}</Detail>
                            <Detail label="Duration">{formatActivityDuration(activity.duration_minutes)}</Detail>
                            <Detail label="Quantity">{activity.quantity ?? '—'}</Detail>
                            <Detail label="Related task">
                                {activity.task ? (
                                    <Link href={`/recruiter/tasks/${activity.task.id}`} className="hover:underline">
                                        {activity.task.title}
                                    </Link>
                                ) : (
                                    '—'
                                )}
                            </Detail>
                            <Detail label="Recorded by">{activity.created_by_name ?? '—'}</Detail>
                            <Detail label="Recorded">{formatRecruiterDateTime(activity.created_at)}</Detail>
                            {activity.updated_at !== activity.created_at && (
                                <Detail label="Last changed">
                                    {formatRecruiterDateTime(activity.updated_at)}
                                    {activity.updated_by_name ? ` by ${activity.updated_by_name}` : ''}
                                </Detail>
                            )}
                        </dl>

                        <div className="space-y-1">
                            <h4 className="text-sm font-medium">Description</h4>
                            <p className="text-sm whitespace-pre-line">{activity.description || 'No description.'}</p>
                        </div>

                        {activity.remarks && (
                            <div className="space-y-1">
                                <h4 className="text-sm font-medium">Remarks</h4>
                                <p className="text-sm whitespace-pre-line">{activity.remarks}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </RecruiterLayout>
    );
}
