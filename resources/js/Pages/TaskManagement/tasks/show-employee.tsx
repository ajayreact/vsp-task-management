import { PageHeader } from '@/components/admin/page-header';
import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { TaskAttachments, type TaskAttachmentRow } from '@/components/tasks/task-attachments';
import { TaskChecklist, type TaskChecklistPayload } from '@/components/tasks/task-checklist';
import { TaskComments, type TaskCommentRow } from '@/components/tasks/task-comments';
import { DueDate, PriorityBadge, StatusBadge } from '@/components/tasks/task-badges';
import { TaskReview, type DeliverableRow, type SubmitReviewContext } from '@/components/tasks/task-review';
import { TaskTimer, type TimerState } from '@/components/tasks/task-timer';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, ClipboardList, Clock3, FolderUp, Trash2, X, Zap } from 'lucide-react';
import { useState } from 'react';

interface TaskDetail {
    id: number;
    title: string;
    description: string | null;
    priority: string;
    priority_label: string;
    status: string;
    status_label: string;
    project: { id: number; name: string };
    company_name: string;
    assignee_name: string | null;
    due_at: string | null;
}

interface Props {
    task: TaskDetail;
    timer: TimerState;
    timeEntries: {
        id: number;
        employee_name: string;
        started_at: string;
        ended_at: string | null;
        hours: number;
        source: string;
        note: string | null;
        can_delete: boolean;
    }[];
    attachments: TaskAttachmentRow[];
    deliverables: DeliverableRow[];
    comments: TaskCommentRow[];
    checklist: TaskChecklistPayload;
    submitReview: SubmitReviewContext;
    can: {
        claim: boolean;
        respond: boolean;
        logTime: boolean;
        attachFiles: boolean;
        comment: boolean;
        completeChecklist: boolean;
        submitProof: boolean;
    };
}

const formatted = (value: string | null) =>
    value ? new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '—';

function TaskDetailsGrid({ children, className }: { children: React.ReactNode; className?: string }) {
    return <div className={cn('grid grid-cols-1 items-stretch gap-6 md:grid-cols-2', className)}>{children}</div>;
}

function StretchCell({ children, className }: { children: React.ReactNode; className?: string }) {
    return <div className={cn('flex h-full min-w-0 flex-col', className)}>{children}</div>;
}

export default function EmployeeTaskShow({
    task,
    timer,
    timeEntries,
    attachments,
    deliverables,
    comments,
    checklist,
    submitReview,
    can,
}: Props) {
    const [declineOpen, setDeclineOpen] = useState(false);
    const declineForm = useForm<{ reason: string }>({ reason: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'My Tasks', href: '/tasks' },
        { title: task.title, href: `/tasks/${task.id}` },
    ];

    const post = (url: string) => router.post(url, {}, { preserveScroll: true });

    const hasStatusActions = can.respond || can.claim;
    const checklistPercent = checklist.total === 0 ? 0 : Math.round((checklist.completed / checklist.total) * 100);

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={task.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title={task.title} description={`${task.company_name} · ${task.project.name}`} />

                <TaskDetailsGrid>
                    {/* Row 1 — Task Details | My Task Status */}
                    <StretchCell>
                        <Card className="h-full">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ClipboardList className="text-primary size-4" strokeWidth={1.75} />
                                    Task Details
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-muted-foreground mb-1 text-xs font-medium tracking-wide uppercase">Description</p>
                                    <p className="text-sm whitespace-pre-wrap">
                                        {task.description || <span className="text-muted-foreground">No description.</span>}
                                    </p>
                                </div>

                                <Separator />

                                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                    <Field label="Project">{task.project.name}</Field>
                                    <Field label="Client">{task.company_name}</Field>
                                    <Field label="Priority">
                                        <PriorityBadge priority={task.priority} label={task.priority_label} />
                                    </Field>
                                    <Field label="Due">
                                        <DueDate value={task.due_at} />
                                    </Field>
                                </dl>
                            </CardContent>
                        </Card>
                    </StretchCell>

                    <StretchCell>
                        <Card className="h-full">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Zap className="text-primary size-4" strokeWidth={1.75} />
                                    My Task Status
                                </CardTitle>
                                <CardDescription>Accept the task, then upload files and submit for review.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <dl className="grid gap-3 text-sm">
                                    <Field label="Current status">
                                        <StatusBadge status={task.status} label={task.status_label} />
                                    </Field>
                                    <Field label="Assigned to">{task.assignee_name ?? 'Unassigned'}</Field>
                                    {checklist.total > 0 && (
                                        <Field label="Checklist progress">
                                            <div className="space-y-1.5">
                                                <span className="font-medium">
                                                    {checklist.completed} / {checklist.total} completed
                                                </span>
                                                <div className="bg-muted h-1.5 overflow-hidden rounded-full">
                                                    <div
                                                        className="bg-primary h-full rounded-full transition-all"
                                                        style={{ width: `${checklistPercent}%` }}
                                                    />
                                                </div>
                                            </div>
                                        </Field>
                                    )}
                                </dl>

                                {hasStatusActions && (
                                    <>
                                        <Separator />
                                        <div className="space-y-3">
                                            {can.respond && (
                                                <div className="flex gap-2">
                                                    <Button className="flex-1" onClick={() => post(`/tasks/${task.id}/accept`)}>
                                                        <Check /> Accept task
                                                    </Button>
                                                    <Button variant="outline" className="flex-1" onClick={() => setDeclineOpen(true)}>
                                                        <X /> Decline
                                                    </Button>
                                                </div>
                                            )}

                                            {can.claim && (
                                                <Button className="w-full" onClick={() => post(`/tasks/${task.id}/claim`)}>
                                                    Claim this task
                                                </Button>
                                            )}
                                        </div>
                                    </>
                                )}

                                {!hasStatusActions && task.status === 'completed' && (
                                    <p className="text-muted-foreground text-sm">This task is completed.</p>
                                )}

                                {!hasStatusActions && task.status !== 'completed' && (
                                    <p className="text-muted-foreground text-sm">
                                        {can.submitProof
                                            ? 'Upload your deliverables below and submit for review when ready.'
                                            : submitReview.blocked_reason ?? 'No actions available right now.'}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </StretchCell>

                    {/* Row 2 — Checklist | Discussion */}
                    <StretchCell className="[&>div]:h-full">
                        <TaskChecklist taskId={task.id} checklist={checklist} canManage={false} canComplete={can.completeChecklist} />
                    </StretchCell>

                    <StretchCell className="[&>div]:h-full">
                        <TaskComments taskId={task.id} comments={comments} canComment={can.comment} />
                    </StretchCell>

                    {/* Row 3 — Files & Submission | Time Tracking */}
                    <StretchCell>
                        <Card className="h-full">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <FolderUp className="text-primary size-4" strokeWidth={1.75} />
                                    Files & Submission
                                </CardTitle>
                                <CardDescription>Upload working files, then submit final deliverables for review.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-8">
                                <TaskAttachments taskId={task.id} attachments={attachments} canUpload={can.attachFiles} embedded />
                                <Separator />
                                <TaskReview
                                    taskId={task.id}
                                    deliverables={deliverables}
                                    canSubmit={can.submitProof}
                                    canReview={false}
                                    employeeMode
                                    submitReview={submitReview}
                                    embedded
                                />
                            </CardContent>
                        </Card>
                    </StretchCell>

                    <StretchCell>
                        <Card className="h-full">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Clock3 className="text-primary size-4" strokeWidth={1.75} />
                                    Time Tracking
                                </CardTitle>
                                <CardDescription>Track time on this task with the timer or manual entries.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <TaskTimer taskId={task.id} timer={timer} canLog={can.logTime} embedded />

                                {timeEntries.length > 0 && (
                                    <>
                                        <Separator />
                                        <div className="space-y-2">
                                            <p className="text-sm font-medium">My time logged</p>
                                            {timeEntries.map((entry) => (
                                                <div
                                                    key={entry.id}
                                                    className="flex items-start justify-between gap-2 rounded-lg border p-3 text-sm"
                                                >
                                                    <div>
                                                        <div className="font-medium">
                                                            {entry.hours} h · {entry.source}
                                                        </div>
                                                        <div className="text-muted-foreground text-xs">{formatted(entry.started_at)}</div>
                                                        {entry.note && <p className="mt-1">{entry.note}</p>}
                                                    </div>
                                                    {entry.can_delete && (
                                                        <ConfirmDelete
                                                            url={`/tasks/time-entries/${entry.id}`}
                                                            title="Remove this time entry?"
                                                            description="It will drop off the weekly timesheet."
                                                            trigger={
                                                                <Button variant="ghost" size="icon" aria-label="Delete time entry">
                                                                    <Trash2 className="text-destructive size-4" />
                                                                </Button>
                                                            }
                                                        />
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </StretchCell>
                </TaskDetailsGrid>
            </div>

            <Dialog open={declineOpen} onOpenChange={setDeclineOpen}>
                <DialogContent>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            declineForm.post(`/tasks/${task.id}/decline`, {
                                preserveScroll: true,
                                onSuccess: () => setDeclineOpen(false),
                            });
                        }}
                        className="space-y-4"
                    >
                        <DialogHeader>
                            <DialogTitle>Decline this task</DialogTitle>
                            <DialogDescription>It goes back on the open board for someone else to pick up.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-2">
                            <Label htmlFor="reason">Reason (optional)</Label>
                            <Textarea
                                id="reason"
                                value={declineForm.data.reason}
                                onChange={(event) => declineForm.setData('reason', event.target.value)}
                                placeholder="Already at capacity this week"
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDeclineOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" variant="destructive" disabled={declineForm.processing}>
                                Decline
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="mt-1">{children}</dd>
        </div>
    );
}
