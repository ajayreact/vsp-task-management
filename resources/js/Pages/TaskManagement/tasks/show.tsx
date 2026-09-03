import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { KpiStatCard } from '@/components/admin/kpi-stat-card';
import { TaskAttachments, type TaskAttachmentRow } from '@/components/tasks/task-attachments';
import { TaskChecklist, type TaskChecklistPayload } from '@/components/tasks/task-checklist';
import { TaskSubtasks, type TaskSubtasksPayload } from '@/components/tasks/task-subtasks';
import { TaskRecurrence, type TaskRecurrencePayload } from '@/components/tasks/task-recurrence';
import { TaskReminders, type TaskReminderRow } from '@/components/tasks/task-reminders';
import { TaskComments, type TaskCommentRow } from '@/components/tasks/task-comments';
import { DueDate, PriorityBadge, StatusBadge } from '@/components/tasks/task-badges';
import { TaskReview, type DeliverableRow } from '@/components/tasks/task-review';
import { TaskTimer, type TimerState } from '@/components/tasks/task-timer';
import { Badge } from '@/components/ui/badge';
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
import {
    Building2,
    Check,
    CheckSquare,
    ClipboardList,
    Clock3,
    FolderOpen,
    History,
    Layers,
    Pencil,
    Sparkles,
    Trash2,
    User,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

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
        startTimer: boolean;
        logManualTime: boolean;
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

    const stats = useMemo(
        () => ({
            hoursLogged: timeEntries.reduce((sum, entry) => sum + entry.hours, 0),
            checklistDone: checklist.total === 0 ? '—' : `${checklist.completed}/${checklist.total}`,
            subtasksDone: subtasks.total === 0 ? '—' : `${subtasks.completed}/${subtasks.total}`,
            files: attachments.length,
            proofs: deliverables.length,
        }),
        [attachments.length, checklist.completed, checklist.total, deliverables.length, subtasks.completed, subtasks.total, timeEntries],
    );

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={task.title} />

            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="relative overflow-hidden rounded-[1.25rem] border border-indigo-500/20 bg-gradient-to-br from-violet-600 via-indigo-600 to-fuchsia-600 px-6 py-8 text-white shadow-lg">
                    <div className="pointer-events-none absolute -right-8 -top-10 size-40 rounded-full bg-white/10 blur-2xl" />
                    <div className="pointer-events-none absolute -bottom-12 left-1/3 size-52 rounded-full bg-fuchsia-400/20 blur-3xl" />

                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="min-w-0 space-y-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge className="border-white/20 bg-white/10 text-white hover:bg-white/10">
                                    <Sparkles className="size-3" />
                                    Task #{task.id}
                                </Badge>
                                <StatusBadge status={task.status} label={task.status_label} />
                                <PriorityBadge priority={task.priority} label={task.priority_label} />
                            </div>

                            <div className="space-y-2">
                                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{task.title}</h1>
                                <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-indigo-100">
                                    <span className="inline-flex items-center gap-1.5">
                                        <Building2 className="size-3.5" />
                                        {task.company_name}
                                    </span>
                                    <span className="hidden sm:inline">·</span>
                                    <span>{task.project.name}</span>
                                    {task.assignee_name && (
                                        <>
                                            <span className="hidden sm:inline">·</span>
                                            <span className="inline-flex items-center gap-1.5">
                                                <User className="size-3.5" />
                                                {task.assignee_name}
                                            </span>
                                        </>
                                    )}
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {can.update && (
                                <Button asChild className="border-0 bg-white text-indigo-700 shadow-sm hover:bg-white/90">
                                    <Link href={`/tasks/${task.id}/edit`}>
                                        <Pencil /> Edit task
                                    </Link>
                                </Button>
                            )}
                            {can.delete && (
                                <ConfirmDelete
                                    url={`/tasks/${task.id}`}
                                    title={`Delete "${task.title}"?`}
                                    description="Nobody has started work on this task, so it can be removed."
                                    trigger={
                                        <Button
                                            variant="outline"
                                            className="border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                                        >
                                            <Trash2 /> Delete
                                        </Button>
                                    }
                                />
                            )}
                        </div>
                    </div>
                </section>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ClipboardList className="text-primary size-4" strokeWidth={1.75} />
                            Task details
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

                        <dl className="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                            <Field label="Type">{task.type}</Field>
                            <Field label="Department">{task.department_name ?? '—'}</Field>
                            <Field label="Assignment mode">{task.assignment_mode}</Field>
                            <Field label="Estimated">{task.estimated_hours ? `${task.estimated_hours} h` : '—'}</Field>
                            <Field label="Started">{formatted(task.started_at)}</Field>
                            <Field label="Completed">{formatted(task.completed_at)}</Field>
                            <Field label="Created by">{task.created_by}</Field>
                        </dl>
                    </CardContent>
                </Card>

                {task.requirement && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ClipboardList className="text-primary size-4" strokeWidth={1.75} />
                                Task requirement
                            </CardTitle>
                            <CardDescription>Full brief, script, or client instructions for this task.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="max-h-64 overflow-y-auto rounded-xl border bg-muted/30 p-4 text-sm whitespace-pre-wrap break-words [overflow-wrap:anywhere]">
                                {task.requirement}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
                    <KpiStatCard tone="indigo" label="Time logged" value={`${stats.hoursLogged.toFixed(1)} h`} icon={Clock3} />
                    <KpiStatCard tone="emerald" label="Checklist" value={stats.checklistDone} icon={CheckSquare} />
                    <KpiStatCard tone="sky" label="Subtasks" value={stats.subtasksDone} icon={Layers} />
                    <KpiStatCard tone="teal" label="Working files" value={stats.files} icon={FolderOpen} />
                    <KpiStatCard tone="fuchsia" label="Creative review" value={stats.proofs} icon={Sparkles} className="col-span-2 lg:col-span-1" />
                </div>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(17.5rem,34%)] xl:grid-cols-[minmax(0,1fr)_22.5rem]">
                    <div className="min-w-0 space-y-5">
                        <TaskChecklist taskId={task.id} checklist={checklist} canManage={can.manageChecklist} />

                        <TaskSubtasks
                            taskId={task.id}
                            subtasks={subtasks}
                            statuses={subtaskStatuses}
                            employees={subtaskAssignableEmployees}
                            canManage={can.manageSubtasks}
                        />

                        <TaskComments taskId={task.id} comments={comments} canComment={can.comment} />

                        <TaskAttachments taskId={task.id} attachments={attachments} canUpload={can.attachFiles} />

                        <TaskReview taskId={task.id} deliverables={deliverables} canSubmit={can.submitProof} canReview={can.reviewProof} />
                    </div>

                    <aside className="min-w-0 space-y-5">
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Assignment &amp; status</CardTitle>
                                <CardDescription>Current ownership and assignment history.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <dl className="grid gap-3 text-sm">
                                    <Field label="Assignee">{task.assignee_name ?? 'Unassigned'}</Field>
                                    <Field label="Status">
                                        <StatusBadge status={task.status} label={task.status_label} />
                                    </Field>
                                    <Field label="Due">
                                        <DueDate value={task.due_at} />
                                    </Field>
                                </dl>

                                {assignments.length > 0 && (
                                    <>
                                        <Separator />
                                        <div className="space-y-2">
                                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">History</p>
                                            {assignments.map((assignment) => (
                                                <div key={assignment.id} className="rounded-lg border bg-muted/20 p-2.5 text-sm">
                                                    <div className="font-medium">{assignment.employee_name}</div>
                                                    <div className="text-muted-foreground text-xs">
                                                        {assignment.mode}
                                                        {assignment.assigned_by && ` by ${assignment.assigned_by}`} · {assignment.status}
                                                    </div>
                                                    {assignment.decline_reason && (
                                                        <p className="text-muted-foreground mt-1 text-xs">Reason: {assignment.decline_reason}</p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

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
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Actions</CardTitle>
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
                                    <div className="space-y-2 pt-1">
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
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <History className="text-primary size-4" strokeWidth={1.75} />
                                    Timeline
                                </CardTitle>
                                <CardDescription>Created by {task.created_by}.</CardDescription>
                            </CardHeader>
                            <CardContent className="max-h-72 space-y-3 overflow-y-auto text-sm">
                                {history.map((entry) => (
                                    <div key={entry.id} className="border-l-2 border-indigo-200 pl-3">
                                        <div className="font-medium">{entry.from ? `${entry.from} → ${entry.to}` : `Created as ${entry.to}`}</div>
                                        <div className="text-muted-foreground text-xs">
                                            {entry.by ?? 'System'} · {formatted(entry.at)}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Clock3 className="text-primary size-4" strokeWidth={1.75} />
                                    Time tracking
                                </CardTitle>
                                <CardDescription>
                                    {can.startTimer
                                        ? 'Start a live clock, or type an interval after the fact.'
                                        : 'Monitor the assignee’s timer and logged time.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <TaskTimer
                                    taskId={task.id}
                                    timer={timer}
                                    canStartTimer={can.startTimer}
                                    canLogManualTime={can.logManualTime}
                                    manualTimeTargetName={task.assignee_name}
                                    embedded
                                    compact
                                />

                                {timeEntries.length > 0 && (
                                    <>
                                        <Separator />
                                        <div className="space-y-2">
                                            <p className="text-sm font-medium">Time logged</p>
                                            {timeEntries.map((entry) => (
                                                <div
                                                    key={entry.id}
                                                    className="flex items-start justify-between gap-2 rounded-lg border bg-muted/20 p-2.5 text-sm"
                                                >
                                                    <div className="min-w-0">
                                                        <div className="font-medium">
                                                            {entry.hours} h · {entry.source}
                                                        </div>
                                                        <div className="text-muted-foreground text-xs">
                                                            {entry.employee_name} · {formatted(entry.started_at)}
                                                        </div>
                                                        {entry.note && <p className="mt-1 text-xs">{entry.note}</p>}
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
                    </aside>
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
