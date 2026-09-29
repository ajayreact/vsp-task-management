import { DataTableCard } from '@/components/admin/data-table-card';
import { buildExportQuery, exportHref } from '@/components/admin/data-table-export';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { RowActions, type RowActionItem } from '@/components/admin/row-actions';
import {
    formatActivityDate,
    formatActivityDuration,
    formatActivityTimeRange,
    localToday,
} from '@/components/recruiter-operations/recruiter-activity-format';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';

interface ActivityRow {
    id: number;
    activity_date: string;
    activity_type: string;
    activity_type_label: string;
    title: string;
    description: string | null;
    start_time: string | null;
    end_time: string | null;
    duration_minutes: number | null;
    quantity: number | null;
    task: { id: number; title: string } | null;
    recruiter_name: string | null;
    can: { update: boolean; delete: boolean };
}

interface Filters {
    from: string;
    to: string;
    type: string;
    task: number | null;
    recruiter: number | null;
}

interface Props {
    activities: Paginated<ActivityRow>;
    filters: Filters;
    activityTypes: Option[];
    tasks: { id: number; label: string }[];
    recruiters: { id: number; label: string }[];
    pageTitle: string;
    can: { create: boolean; viewTeam: boolean };
}

const ALL = 'all';

export default function RecruiterDailyActivityIndex({ activities, filters, activityTypes, tasks, recruiters, pageTitle, can }: Props) {
    const current = {
        from: filters.from,
        to: filters.to,
        type: filters.type || undefined,
        task: filters.task ?? undefined,
        recruiter: filters.recruiter ?? undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/activities', { ...current, per_page: activities.per_page, ...changes }, { preserveState: true, replace: true });
    };

    const today = localToday();
    const isToday = filters.from === today && filters.to === today;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: pageTitle, href: '/recruiter/activities' },
    ];

    const rowActions = (activity: ActivityRow): RowActionItem[] => [
        { key: 'view', label: 'View', href: `/recruiter/activities/${activity.id}` },
        ...(activity.can.update ? [{ key: 'edit', label: 'Edit', href: `/recruiter/activities/${activity.id}/edit` }] : []),
        ...(activity.can.delete
            ? [
                  {
                      key: 'delete',
                      label: 'Delete',
                      confirm: {
                          url: `/recruiter/activities/${activity.id}`,
                          title: 'Delete this activity?',
                          description: `"${activity.title}" will be removed from the activity log.`,
                      },
                  },
              ]
            : []),
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title={pageTitle}
                    description={
                        can.viewTeam
                            ? 'What recruiters logged. An activity log, not a performance measure.'
                            : 'What you logged. Record today and the previous 7 days.'
                    }
                    action={
                        can.create ? (
                            <Button asChild className="w-full shrink-0 sm:w-auto">
                                <Link href="/recruiter/activities/create">
                                    <Plus /> Record activity
                                </Link>
                            </Button>
                        ) : undefined
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
                            <div className="grid gap-1">
                                <Label htmlFor="from" className="text-muted-foreground text-xs">
                                    From
                                </Label>
                                <Input
                                    id="from"
                                    type="date"
                                    value={filters.from}
                                    max={today}
                                    onChange={(event) => event.target.value && apply({ from: event.target.value })}
                                    className="w-full lg:w-40"
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="to" className="text-muted-foreground text-xs">
                                    To
                                </Label>
                                <Input
                                    id="to"
                                    type="date"
                                    value={filters.to}
                                    max={today}
                                    onChange={(event) => event.target.value && apply({ to: event.target.value })}
                                    className="w-full lg:w-40"
                                />
                            </div>

                            {!isToday && (
                                <Button variant="ghost" size="sm" onClick={() => apply({ from: today, to: today })}>
                                    Today
                                </Button>
                            )}

                            {can.viewTeam && (
                                <Select
                                    value={filters.recruiter ? String(filters.recruiter) : ALL}
                                    onValueChange={(value) => apply({ recruiter: value === ALL ? null : value })}
                                >
                                    <SelectTrigger className="w-full lg:w-52" aria-label="Filter by recruiter">
                                        <SelectValue placeholder="All recruiters" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>All recruiters</SelectItem>
                                        {recruiters.map((recruiter) => (
                                            <SelectItem key={recruiter.id} value={String(recruiter.id)}>
                                                {recruiter.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}

                            <Select value={filters.type || ALL} onValueChange={(value) => apply({ type: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-48" aria-label="Filter by activity type">
                                    <SelectValue placeholder="Any activity type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Any activity type</SelectItem>
                                    {activityTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={filters.task ? String(filters.task) : ALL} onValueChange={(value) => apply({ task: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-56" aria-label="Filter by task">
                                    <SelectValue placeholder="Any task" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Any task</SelectItem>
                                    {tasks.map((task) => (
                                        <SelectItem key={task.id} value={String(task.id)}>
                                            {task.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                    footer={
                        <Pagination
                            page={activities}
                            leading={<EntriesSelect value={activities.per_page} onChange={(perPage) => apply({ per_page: perPage })} />}
                            actions={
                                <Button variant="outline" size="sm" asChild>
                                    <a href={exportHref('/recruiter/activities/export', 'excel', buildExportQuery(current))}>Excel</a>
                                </Button>
                            }
                        />
                    }
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                {can.viewTeam && <TableHead>Recruiter</TableHead>}
                                <TableHead>Activity</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Time</TableHead>
                                <TableHead className="text-right">Duration</TableHead>
                                <TableHead className="text-right">Quantity</TableHead>
                                <TableHead>Related task</TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {activities.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={can.viewTeam ? 10 : 9} className="text-muted-foreground py-10 text-center">
                                        No activities recorded {isToday ? 'today' : 'for these dates'}.
                                    </TableCell>
                                </TableRow>
                            )}

                            {activities.data.map((activity) => (
                                <TableRow key={activity.id}>
                                    <TableCell className="text-sm whitespace-nowrap">{formatActivityDate(activity.activity_date)}</TableCell>
                                    {can.viewTeam && <TableCell className="text-sm">{activity.recruiter_name ?? '—'}</TableCell>}
                                    <TableCell>
                                        <Link href={`/recruiter/activities/${activity.id}`} className="font-medium hover:underline">
                                            {activity.title}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="neutral">{activity.activity_type_label}</Badge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground max-w-xs truncate text-sm">{activity.description ?? '—'}</TableCell>
                                    <TableCell className="text-sm whitespace-nowrap">{formatActivityTimeRange(activity.start_time, activity.end_time)}</TableCell>
                                    <TableCell className="text-right text-sm">{formatActivityDuration(activity.duration_minutes)}</TableCell>
                                    <TableCell className="text-right text-sm">{activity.quantity ?? '—'}</TableCell>
                                    <TableCell className="text-sm">
                                        {activity.task ? (
                                            <Link href={`/recruiter/tasks/${activity.task.id}`} className="hover:underline">
                                                {activity.task.title}
                                            </Link>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <RowActions label={`Actions for ${activity.title}`} items={rowActions(activity)} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
