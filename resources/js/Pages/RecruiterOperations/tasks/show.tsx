import { PageHeader } from '@/components/admin/page-header';
import { RecruiterTaskActions, type RecruiterTaskActionFlags } from '@/components/recruiter-operations/recruiter-task-actions';
import {
    RecruiterTaskPriorityBadge,
    RecruiterTaskStatusBadge,
    formatRecruiterDateTime,
} from '@/components/recruiter-operations/recruiter-task-status-badge';
import {
    formatActivityDate,
    formatActivityDuration,
    formatActivityTimeRange,
} from '@/components/recruiter-operations/recruiter-activity-format';
import { RecruiterTaskTimeline, type RecruiterTaskTimelineEntry } from '@/components/recruiter-operations/recruiter-task-timeline';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { type ReactNode } from 'react';

interface TaskDetail {
    id: number;
    title: string;
    work_type_label: string;
    priority: string;
    priority_label: string;
    status: string;
    status_label: string;
    assignee_name: string | null;
    due_at: string | null;
    target_count: number | null;
    achieved_count: number | null;
    completed_at: string | null;
    description: string | null;
    instructions: string | null;
    created_by_name: string | null;
    completion_note: string | null;
    accepted_at: string | null;
    started_at: string | null;
    cancelled_at: string | null;
    created_at: string;
}

interface LinkedActivity {
    id: number;
    activity_date: string;
    activity_type_label: string;
    title: string;
    start_time: string | null;
    end_time: string | null;
    duration_minutes: number | null;
    quantity: number | null;
    recruiter_name: string | null;
}

interface Props {
    task: TaskDetail;
    timeline: RecruiterTaskTimelineEntry[];
    actions: RecruiterTaskActionFlags;
    recruiters: { id: number; label: string }[];
    dailyActivities: { items: LinkedActivity[]; reported_quantity: number | null };
    canLogActivity: boolean;
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="space-y-1">
            <dt className="text-muted-foreground text-xs font-medium uppercase tracking-wide">{label}</dt>
            <dd className="text-sm">{children}</dd>
        </div>
    );
}

export default function ShowRecruiterTask({ task, timeline, actions, recruiters, dailyActivities, canLogActivity }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Recruiter Tasks', href: '/recruiter/tasks' },
        { title: task.title, href: `/recruiter/tasks/${task.id}` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={task.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title={task.title} description={task.work_type_label} />

                <RecruiterTaskActions taskId={task.id} targetCount={task.target_count} actions={actions} recruiters={recruiters} />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <Detail label="Status">
                                        <RecruiterTaskStatusBadge status={task.status} label={task.status_label} />
                                    </Detail>
                                    <Detail label="Priority">
                                        <RecruiterTaskPriorityBadge priority={task.priority} label={task.priority_label} />
                                    </Detail>
                                    <Detail label="Assigned recruiter">{task.assignee_name ?? '—'}</Detail>
                                    <Detail label="Created by">{task.created_by_name ?? '—'}</Detail>
                                    <Detail label="Work type">{task.work_type_label}</Detail>
                                    <Detail label="Due">{formatRecruiterDateTime(task.due_at)}</Detail>
                                    <Detail label="Target count">{task.target_count ?? '—'}</Detail>
                                    <Detail label="Achieved count">{task.achieved_count ?? '—'}</Detail>
                                    <Detail label="Accepted">{formatRecruiterDateTime(task.accepted_at)}</Detail>
                                    <Detail label="Completed">{formatRecruiterDateTime(task.completed_at)}</Detail>
                                    {task.cancelled_at && <Detail label="Cancelled">{formatRecruiterDateTime(task.cancelled_at)}</Detail>}
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Description</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="whitespace-pre-line text-sm">{task.description || 'No description.'}</p>
                                {task.instructions && (
                                    <div className="space-y-1">
                                        <h4 className="text-sm font-medium">Instructions</h4>
                                        <p className="whitespace-pre-line text-sm">{task.instructions}</p>
                                    </div>
                                )}
                                {task.completion_note && (
                                    <div className="space-y-1">
                                        <h4 className="text-sm font-medium">Completion note</h4>
                                        <p className="whitespace-pre-line text-sm">{task.completion_note}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-start justify-between gap-3 space-y-0">
                                <div className="space-y-1">
                                    <CardTitle>Daily Activities</CardTitle>
                                    <p className="text-muted-foreground text-sm">
                                        Target: {task.target_count ?? '—'} · Reported quantity: {dailyActivities.reported_quantity ?? '—'}
                                    </p>
                                </div>
                                {canLogActivity && (
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={`/recruiter/activities/create?task=${task.id}`}>
                                            <Plus /> Record activity
                                        </Link>
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                {dailyActivities.items.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">No activities have been logged against this task.</p>
                                ) : (
                                    <ul className="divide-y">
                                        {dailyActivities.items.map((activity) => (
                                            <li key={activity.id} className="flex items-start justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0">
                                                <div className="min-w-0 space-y-0.5">
                                                    <Link href={`/recruiter/activities/${activity.id}`} className="font-medium hover:underline">
                                                        {activity.title}
                                                    </Link>
                                                    <div className="text-muted-foreground text-xs">
                                                        {formatActivityDate(activity.activity_date)}
                                                        {activity.start_time && ` · ${formatActivityTimeRange(activity.start_time, activity.end_time)}`}
                                                        {` · ${activity.activity_type_label}`}
                                                        {activity.recruiter_name && ` · ${activity.recruiter_name}`}
                                                    </div>
                                                </div>
                                                <div className="text-right text-xs whitespace-nowrap">
                                                    {activity.quantity !== null && <div>Quantity: {activity.quantity}</div>}
                                                    {activity.duration_minutes !== null && (
                                                        <div className="text-muted-foreground">{formatActivityDuration(activity.duration_minutes)}</div>
                                                    )}
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Timeline</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <RecruiterTaskTimeline entries={timeline} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </RecruiterLayout>
    );
}
