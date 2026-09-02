import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { TaskAttachments, type TaskAttachmentRow } from '@/components/tasks/task-attachments';
import { TaskChecklist, type TaskChecklistPayload } from '@/components/tasks/task-checklist';
import { TaskSubtasks, type TaskSubtasksPayload } from '@/components/tasks/task-subtasks';
import { TaskRecurrence, type TaskRecurrencePayload } from '@/components/tasks/task-recurrence';
import { TaskReminders, type TaskReminderRow } from '@/components/tasks/task-reminders';
import { TaskComments, type TaskCommentRow } from '@/components/tasks/task-comments';
import { DueDate, PriorityBadge, StatusBadge } from '@/components/tasks/task-badges';
import { TaskReview, type DeliverableRow } from '@/components/tasks/task-review';
import { TaskTimer, type TimerState } from '@/components/tasks/task-timer';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, Pencil, Trash2, X } from 'lucide-react';
import { useState } from 'react';

interface TaskDetail {
    id: number;
    title: string;
    description: string | null;
    requirement: string | null;
    type: string;
    priority: string;
    priority_label: string;
    status: string;
    status_label: string;
    assignment_mode: string;
    project: { id: number; name: string };
    company_name: string;
    department_name: string | null;
    assignee_name: string | null;
    created_by: string;
    estimated_hours: string | null;
    due_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    assigned_user_id: number | null;
}

interface Props {
    task: TaskDetail;
    history: { id: number; from: string | null; to: string; by: string | null; at: string }[];
    assignments: {
        id: number;
        employee_name: string;
        mode: string;
        status: string;
        assigned_by: string | null;
        responded_at: string | null;
        decline_reason: string | null;
    }[];
    allowedTransitions: Option[];
    assignableEmployees: { id: number; label: string }[];
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
    subtaskAssignableEmployees: { id: number; label: string }[];
    reminders: TaskReminderRow[];
    reminderRecipients: { user_id: number; label: string }[];
    recurrence: TaskRecurrencePayload;
    can: {
        update: boolean;
        delete: boolean;
        can_accept: boolean;
        can_decline: boolean;
        can_claim: boolean;
        can_reassign: boolean;
        can_move_to_open_board: boolean;
        logTime: boolean;
        attachFiles: boolean;
        comment: boolean;
        manageChecklist: boolean;
        manageSubtasks: boolean;
        manageReminders: boolean;
        manageRecurrence: boolean;
        submitProof: boolean;
        reviewProof: boolean;
    };
}

const formatted = (value: string | null) => (value ? new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '—');

export default function TaskShow({
    task,
    history,
    assignments,
    allowedTransitions,
    assignableEmployees,
    timer,
    timeEntries,
    attachments,
    deliverables,
    comments,
    checklist,
    subtasks,
    subtaskStatuses,
    subtaskAssignableEmployees,
    reminders,
    reminderRecipients,
    recurrence,
    can,
}: Props) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [declineOpen, setDeclineOpen] = useState(false);

    const assignForm = useForm<{ employee_id: string }>({ employee_id: '' });
    const declineForm = useForm<{ reason: string }>({ reason: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: task.title, href: `/tasks/${task.id}` },
    ];

    const post = (url: string) => router.post(url, {}, { preserveScroll: true });

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={task.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={task.title}
                    description={`${task.company_name} · ${task.project.name}`}
                    action={
                        <div className="flex gap-2">
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={`/tasks/${task.id}/edit`}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>
                            )}
                            {can.delete && (
                                <ConfirmDelete
                                    url={`/tasks/${task.id}`}
                                    title={`Delete "${task.title}"?`}
                                    description="Nobody has started work on this task, so it can be removed."
                                    trigger={
                                        <Button variant="outline" className="text-destructive hover:text-destructive">
                                            <Trash2 /> Delete
                                        </Button>
                                    }
                                />
                            )}
                        </div>
                    }
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm whitespace-pre-wrap">
                                    {task.description || <span className="text-muted-foreground">No description.</span>}
                                </p>

                                <Separator />

                                <dl className="grid gap-4 text-sm sm:grid-cols-3">
                                    <Field label="Status">
                                        <StatusBadge status={task.status} label={task.status_label} />
                                    </Field>
                                    <Field label="Priority">
                                        <PriorityBadge priority={task.priority} label={task.priority_label} />
                                    </Field>
                                    <Field label="Type">{task.type}</Field>
                                    <Field label="Assignee">{task.assignee_name ?? 'Unassigned'}</Field>
                                    <Field label="Department">{task.department_name ?? '—'}</Field>
                                    <Field label="Estimated">{task.estimated_hours ? `${task.estimated_hours} h` : '—'}</Field>
                                    <Field label="Due">
                                        <DueDate value={task.due_at} />
                                    </Field>
                                    <Field label="Started">{formatted(task.started_at)}</Field>
                                    <Field label="Completed">{formatted(task.completed_at)}</Field>
                                </dl>
                            </CardContent>
                        </Card>

                        {task.requirement && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Task Requirement</CardTitle>
                                    <CardDescription>Full brief, script, or client instructions for this task.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-sm whitespace-pre-wrap break-words [overflow-wrap:anywhere]">
                                        {task.requirement}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <TaskChecklist taskId={task.id} checklist={checklist} canManage={can.manageChecklist} />

                        <TaskSubtasks
                            taskId={task.id}
                            subtasks={subtasks}
                            statuses={subtaskStatuses}
                            employees={subtaskAssignableEmployees}
                            canManage={can.manageSubtasks}
                        />

                        <TaskComments taskId={task.id} comments={comments} canComment={can.comment} />

                        <Card>
                            <CardHeader>
                                <CardTitle>Assignment history</CardTitle>
                                <CardDescription>Everyone this task has been offered to, and what they said.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {assignments.length === 0 && <p className="text-muted-foreground text-sm">Nobody has held this task yet.</p>}

                                {assignments.map((assignment) => (
                                    <div key={assignment.id} className="rounded-lg border p-3 text-sm">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <span className="font-medium">{assignment.employee_name}</span>
                                            <span className="text-muted-foreground text-xs">
                                                {assignment.mode}
                                                {assignment.assigned_by && ` by ${assignment.assigned_by}`} · {assignment.status}
                                            </span>
                                        </div>
                                        {assignment.decline_reason && (
                                            <p className="text-muted-foreground mt-1 text-xs">Reason: {assignment.decline_reason}</p>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <TaskTimer taskId={task.id} timer={timer} canLog={can.logTime} />

                        {timeEntries.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Time logged</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    {timeEntries.map((entry) => (
                                        <div key={entry.id} className="flex items-start justify-between gap-2 rounded-lg border p-3">
                                            <div>
                                                <div className="font-medium">
                                                    {entry.hours} h · {entry.source}
                                                </div>
                                                <div className="text-muted-foreground text-xs">
                                                    {entry.employee_name} · {formatted(entry.started_at)}
                                                </div>
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

                        <TaskReview taskId={task.id} deliverables={deliverables} canSubmit={can.submitProof} canReview={can.reviewProof} />
                    </div>

                    <div className="space-y-6">
                        <TaskReminders
                            taskId={task.id}
                            dueAt={task.due_at}
                            defaultRecipientUserId={task.assigned_user_id}
                            reminders={reminders}
                            recipients={reminderRecipients}
                            canManage={can.manageReminders}
                        />

                        <TaskRecurrence taskId={task.id} recurrence={recurrence} />

                        <Card>
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                                <CardDescription>Only the moves this task can legally make right now are shown.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {(can.can_accept || can.can_decline) && (
                                    <div className="flex gap-2">
                                        {can.can_accept && (
                                            <Button className="flex-1" onClick={() => post(`/tasks/${task.id}/accept`)}>
                                                <Check /> Accept
                                            </Button>
                                        )}
                                        {can.can_decline && (
                                            <Button variant="outline" className="flex-1" onClick={() => setDeclineOpen(true)}>
                                                <X /> Decline
                                            </Button>
                                        )}
                                    </div>
                                )}

                                {can.can_claim && (
                                    <Button className="w-full" onClick={() => post(`/tasks/${task.id}/claim`)}>
                                        Claim this task
                                    </Button>
                                )}

                                {can.can_reassign && (
                                    <Button variant="outline" className="w-full" onClick={() => setAssignOpen(true)}>
                                        {task.assignee_name ? 'Reassign' : 'Assign to someone'}
                                    </Button>
                                )}

                                {can.can_move_to_open_board && (
                                    <Button variant="outline" className="w-full" onClick={() => post(`/tasks/${task.id}/publish`)}>
                                        Put on the open board
                                    </Button>
                                )}

                                {allowedTransitions.length > 0 && (
                                    <div className="space-y-2 pt-2">
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

                                {!can.can_accept &&
                                    !can.can_decline &&
                                    !can.can_claim &&
                                    !can.can_reassign &&
                                    !can.can_move_to_open_board &&
                                    allowedTransitions.length === 0 && (
                                    <p className="text-muted-foreground text-sm">Nothing for you to do on this task.</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Timeline</CardTitle>
                                <CardDescription>Created by {task.created_by}.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {history.map((entry) => (
                                    <div key={entry.id} className="border-l-2 pl-3">
                                        <div className="font-medium">{entry.from ? `${entry.from} → ${entry.to}` : `Created as ${entry.to}`}</div>
                                        <div className="text-muted-foreground text-xs">
                                            {entry.by ?? 'System'} · {formatted(entry.at)}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <Dialog open={assignOpen} onOpenChange={setAssignOpen}>
                <DialogContent>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            assignForm.post(`/tasks/${task.id}/assign`, {
                                preserveScroll: true,
                                onSuccess: () => setAssignOpen(false),
                            });
                        }}
                        className="space-y-4"
                    >
                        <DialogHeader>
                            <DialogTitle>Assign this task</DialogTitle>
                            <DialogDescription>They will be asked to accept before work starts.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-2">
                            <Label htmlFor="employee_id">Employee</Label>
                            <Select value={assignForm.data.employee_id} onValueChange={(value) => assignForm.setData('employee_id', value)}>
                                <SelectTrigger id="employee_id">
                                    <SelectValue placeholder="Pick someone" />
                                </SelectTrigger>
                                <SelectContent>
                                    {assignableEmployees.map((employee) => (
                                        <SelectItem key={employee.id} value={String(employee.id)}>
                                            {employee.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAssignOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={assignForm.processing || !assignForm.data.employee_id}>
                                Assign
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

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
