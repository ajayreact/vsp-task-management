import { PageHeader } from '@/components/admin/page-header';
import { TaskAttachments, type TaskAttachmentRow } from '@/components/tasks/task-attachments';
import { TaskChecklist, type TaskChecklistPayload } from '@/components/tasks/task-checklist';
import { TaskSubtasks, type TaskSubtasksPayload } from '@/components/tasks/task-subtasks';
import { TaskComments, type TaskCommentRow } from '@/components/tasks/task-comments';
import { DueDate, PriorityBadge, StatusBadge } from '@/components/tasks/task-badges';
import { TaskReview, type DeliverableRow } from '@/components/tasks/task-review';
import { TaskTimer, type TimerState } from '@/components/tasks/task-timer';
import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, Trash2, X } from 'lucide-react';
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
    allowedTransitions: Option[];
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
    subtasks: TaskSubtasksPayload;
    subtaskStatuses: Option[];
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

export default function EmployeeTaskShow({
    task,
    allowedTransitions,
    timer,
    timeEntries,
    attachments,
    deliverables,
    comments,
    checklist,
    subtasks,
    subtaskStatuses,
    can,
}: Props) {
    const [declineOpen, setDeclineOpen] = useState(false);
    const declineForm = useForm<{ reason: string }>({ reason: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'My Tasks', href: '/tasks' },
        { title: task.title, href: `/tasks/${task.id}` },
    ];

    const post = (url: string) => router.post(url, {}, { preserveScroll: true });

    const hasStatusActions = can.respond || can.claim || allowedTransitions.length > 0;

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={task.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={task.title}
                    description={`${task.company_name} · ${task.project.name}`}
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Task summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm whitespace-pre-wrap">
                                    {task.description || <span className="text-muted-foreground">No description.</span>}
                                </p>

                                <Separator />

                                <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                    <Field label="Status">
                                        <StatusBadge status={task.status} label={task.status_label} />
                                    </Field>
                                    <Field label="Priority">
                                        <PriorityBadge priority={task.priority} label={task.priority_label} />
                                    </Field>
                                    <Field label="Project">{task.project.name}</Field>
                                    <Field label="Client">{task.company_name}</Field>
                                    <Field label="Due">
                                        <DueDate value={task.due_at} />
                                    </Field>
                                    <Field label="Assigned to">{task.assignee_name ?? 'Unassigned'}</Field>
                                </dl>
                            </CardContent>
                        </Card>

                        {hasStatusActions && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>My task status</CardTitle>
                                    <CardDescription>Accept, start, and move this task through your workflow.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {can.respond && (
                                        <div className="flex gap-2">
                                            <Button className="flex-1" onClick={() => post(`/tasks/${task.id}/accept`)}>
                                                <Check /> Accept
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

                                    {allowedTransitions.length > 0 && (
                                        <div className="space-y-2">
                                            <Label className="text-muted-foreground text-xs">Move to</Label>
                                            <div className="flex flex-wrap gap-2">
                                                {allowedTransitions.map((transition) => (
                                                    <Button
                                                        key={transition.value}
                                                        variant="secondary"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                `/tasks/${task.id}/status`,
                                                                { status: transition.value },
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                    >
                                                        {transition.label}
                                                    </Button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <TaskChecklist
                            taskId={task.id}
                            checklist={checklist}
                            canManage={false}
                            canComplete={can.completeChecklist}
                        />

                        {subtasks.total > 0 && (
                            <TaskSubtasks
                                taskId={task.id}
                                subtasks={subtasks}
                                statuses={subtaskStatuses}
                                employees={[]}
                                canManage={false}
                                contributorMode
                            />
                        )}

                        <TaskComments taskId={task.id} comments={comments} canComment={can.comment} />

                        <TaskTimer taskId={task.id} timer={timer} canLog={can.logTime} />

                        {timeEntries.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>My time logged</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    {timeEntries.map((entry) => (
                                        <div key={entry.id} className="flex items-start justify-between gap-2 rounded-lg border p-3">
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
                                </CardContent>
                            </Card>
                        )}

                        <TaskAttachments taskId={task.id} attachments={attachments} canUpload={can.attachFiles} />

                        <TaskReview
                            taskId={task.id}
                            deliverables={deliverables}
                            canSubmit={can.submitProof}
                            canReview={false}
                            employeeMode
                        />
                    </div>
                </div>
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
