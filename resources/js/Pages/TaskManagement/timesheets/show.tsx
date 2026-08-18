import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';

interface Props {
    timesheet: {
        id: number;
        employee_name: string;
        period_start: string;
        period_end: string;
        total_hours: string;
        status: string;
        status_label: string;
        review_note: string | null;
        approver_name: string | null;
        submitted_at: string | null;
        approved_at: string | null;
    };
    entries: {
        id: number;
        task_id: number;
        task_title: string;
        started_at: string;
        ended_at: string | null;
        hours: number;
        source: string;
        note: string | null;
        is_billable: boolean;
    }[];
    can: { submit: boolean; review: boolean };
}

export default function TimesheetShow({ timesheet, entries, can }: Props) {
    const reviewForm = useForm({ note: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Timesheets', href: '/tasks/timesheets' },
        { title: timesheet.period_start, href: `/tasks/timesheets/${timesheet.id}` },
    ];

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={`Timesheet ${timesheet.period_start}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={`${timesheet.period_start} – ${timesheet.period_end}`}
                    description={`${timesheet.employee_name} · ${timesheet.total_hours} h`}
                    action={<Badge variant={timesheet.status === 'approved' ? 'default' : 'secondary'}>{timesheet.status_label}</Badge>}
                />

                {timesheet.review_note && (
                    <p className="text-sm">
                        {timesheet.approver_name}: {timesheet.review_note}
                    </p>
                )}

                <div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Task</TableHead>
                                <TableHead>When</TableHead>
                                <TableHead>Hours</TableHead>
                                <TableHead>Source</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell>
                                        <Link href={`/tasks/${entry.task_id}`} className="hover:underline">
                                            {entry.task_title}
                                        </Link>
                                        {entry.note && <div className="text-muted-foreground text-xs">{entry.note}</div>}
                                    </TableCell>
                                    <TableCell className="text-sm">{new Date(entry.started_at).toLocaleString()}</TableCell>
                                    <TableCell>{entry.hours}</TableCell>
                                    <TableCell>{entry.source}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {can.submit && (
                    <Button onClick={() => router.post(`/tasks/timesheets/${timesheet.id}/submit`, {}, { preserveScroll: true })}>
                        Submit for approval
                    </Button>
                )}

                {can.review && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Review</CardTitle>
                            <CardDescription>Approving locks the entries for this week.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="grid gap-2">
                                <Label htmlFor="note">Note</Label>
                                <Textarea
                                    id="note"
                                    value={reviewForm.data.note}
                                    onChange={(event) => reviewForm.setData('note', event.target.value)}
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button onClick={() => reviewForm.post(`/tasks/timesheets/${timesheet.id}/approve`, { preserveScroll: true })}>
                                    Approve
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => reviewForm.post(`/tasks/timesheets/${timesheet.id}/reject`, { preserveScroll: true })}
                                >
                                    Reject
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </TaskLayout>
    );
}
