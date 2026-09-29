import { ConfirmDelete } from '@/components/admin/confirm-delete';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Link, router, useForm } from '@inertiajs/react';
import { type FormEvent, useEffect, useState } from 'react';

export interface RecruiterTaskActionFlags {
    accept: boolean;
    decline: boolean;
    complete: boolean;
    hold: boolean;
    resume: boolean;
    reopen: boolean;
    cancel: boolean;
    reassign: boolean;
    edit: boolean;
    delete: boolean;
}

type ReasonAction = 'decline' | 'hold' | 'reopen' | 'cancel';

const REASON_COPY: Record<ReasonAction, { button: string; title: string; description: string; submit: string; destructive?: boolean }> = {
    decline: {
        button: 'Decline',
        title: 'Decline this task',
        description: 'Tell your recruiter lead why. The task stays with you as declined until they reassign or cancel it.',
        submit: 'Decline task',
        destructive: true,
    },
    hold: {
        button: 'Hold',
        title: 'Put this task on hold',
        description: 'Say why work is paused. It can be resumed later.',
        submit: 'Put on hold',
    },
    reopen: {
        button: 'Reopen',
        title: 'Reopen this task',
        description: 'The task goes back to in progress. Explain what still needs doing.',
        submit: 'Reopen task',
    },
    cancel: {
        button: 'Cancel task',
        title: 'Cancel this task',
        description: 'A cancelled task cannot be changed again. Its history is kept.',
        submit: 'Cancel task',
        destructive: true,
    },
};

function ReasonDialog({ taskId, action, open, onOpenChange }: { taskId: number; action: ReasonAction; open: boolean; onOpenChange: (open: boolean) => void }) {
    const form = useForm({ reason: '' });
    const copy = REASON_COPY[action];

    useEffect(() => {
        if (!open) {
            form.reset();
            form.clearErrors();
        }
    }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/recruiter/tasks/${taskId}/${action}`, { preserveScroll: true, onSuccess: () => onOpenChange(false) });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{copy.title}</DialogTitle>
                    <DialogDescription>{copy.description}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor={`${action}-reason`}>Reason</Label>
                        <Textarea
                            id={`${action}-reason`}
                            value={form.data.reason}
                            onChange={(event) => form.setData('reason', event.target.value)}
                            rows={3}
                            required
                        />
                        <InputError message={form.errors.reason} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Back
                        </Button>
                        <Button type="submit" variant={copy.destructive ? 'destructive' : 'default'} disabled={form.processing}>
                            {copy.submit}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CompleteDialog({ taskId, targetCount, open, onOpenChange }: { taskId: number; targetCount: number | null; open: boolean; onOpenChange: (open: boolean) => void }) {
    const form = useForm({ achieved_count: '', completion_note: '' });

    useEffect(() => {
        if (!open) {
            form.reset();
            form.clearErrors();
        }
    }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/recruiter/tasks/${taskId}/complete`, { preserveScroll: true, onSuccess: () => onOpenChange(false) });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Complete this task</DialogTitle>
                    <DialogDescription>
                        {targetCount !== null ? `The target was ${targetCount}. ` : ''}Record what you achieved and anything your lead should know.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="achieved_count">Achieved count</Label>
                        <Input
                            id="achieved_count"
                            type="number"
                            min="0"
                            step="1"
                            value={form.data.achieved_count}
                            onChange={(event) => form.setData('achieved_count', event.target.value)}
                        />
                        <InputError message={form.errors.achieved_count} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="completion_note">Completion note</Label>
                        <Textarea
                            id="completion_note"
                            value={form.data.completion_note}
                            onChange={(event) => form.setData('completion_note', event.target.value)}
                            rows={3}
                        />
                        <InputError message={form.errors.completion_note} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Back
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Complete task
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ReassignDialog({
    taskId,
    recruiters,
    open,
    onOpenChange,
}: {
    taskId: number;
    recruiters: { id: number; label: string }[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const form = useForm({ assigned_employee_id: '', reason: '' });

    useEffect(() => {
        if (!open) {
            form.reset();
            form.clearErrors();
        }
    }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/recruiter/tasks/${taskId}/reassign`, { preserveScroll: true, onSuccess: () => onOpenChange(false) });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Reassign this task</DialogTitle>
                    <DialogDescription>The recruiter is notified and must accept before work starts.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="reassign-recruiter">Recruiter</Label>
                        <Select value={form.data.assigned_employee_id} onValueChange={(value) => form.setData('assigned_employee_id', value)}>
                            <SelectTrigger id="reassign-recruiter">
                                <SelectValue placeholder="Choose a recruiter" />
                            </SelectTrigger>
                            <SelectContent>
                                {recruiters.map((recruiter) => (
                                    <SelectItem key={recruiter.id} value={String(recruiter.id)}>
                                        {recruiter.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.assigned_employee_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="reassign-reason">Reason (optional)</Label>
                        <Textarea
                            id="reassign-reason"
                            value={form.data.reason}
                            onChange={(event) => form.setData('reason', event.target.value)}
                            rows={3}
                        />
                        <InputError message={form.errors.reason} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Back
                        </Button>
                        <Button type="submit" disabled={form.processing || form.data.assigned_employee_id === ''}>
                            Reassign
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function RecruiterTaskActions({
    taskId,
    targetCount,
    actions,
    recruiters,
}: {
    taskId: number;
    targetCount: number | null;
    actions: RecruiterTaskActionFlags;
    recruiters: { id: number; label: string }[];
}) {
    const [reasonAction, setReasonAction] = useState<ReasonAction | null>(null);
    const [completing, setCompleting] = useState(false);
    const [reassigning, setReassigning] = useState(false);

    const post = (action: 'accept' | 'resume') => router.post(`/recruiter/tasks/${taskId}/${action}`, {}, { preserveScroll: true });

    const hasAny = Object.values(actions).some(Boolean);

    if (!hasAny) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {actions.accept && <Button onClick={() => post('accept')}>Accept</Button>}
            {actions.decline && (
                <Button variant="outline" onClick={() => setReasonAction('decline')}>
                    {REASON_COPY.decline.button}
                </Button>
            )}
            {actions.complete && <Button onClick={() => setCompleting(true)}>Complete</Button>}
            {actions.resume && <Button onClick={() => post('resume')}>Resume</Button>}
            {actions.hold && (
                <Button variant="outline" onClick={() => setReasonAction('hold')}>
                    {REASON_COPY.hold.button}
                </Button>
            )}
            {actions.reopen && (
                <Button variant="outline" onClick={() => setReasonAction('reopen')}>
                    {REASON_COPY.reopen.button}
                </Button>
            )}
            {actions.reassign && (
                <Button variant="outline" onClick={() => setReassigning(true)}>
                    Reassign
                </Button>
            )}
            {actions.edit && (
                <Button variant="outline" asChild>
                    <Link href={`/recruiter/tasks/${taskId}/edit`}>Edit</Link>
                </Button>
            )}
            {actions.cancel && (
                <Button variant="outline" className="text-destructive" onClick={() => setReasonAction('cancel')}>
                    {REASON_COPY.cancel.button}
                </Button>
            )}
            {actions.delete && (
                <ConfirmDelete
                    trigger={
                        <Button variant="outline" className="text-destructive">
                            Delete
                        </Button>
                    }
                    title="Delete this task?"
                    description="Only possible because nothing has happened on it yet. Once work starts, cancel instead."
                    url={`/recruiter/tasks/${taskId}`}
                />
            )}

            {reasonAction && (
                <ReasonDialog taskId={taskId} action={reasonAction} open onOpenChange={(open) => !open && setReasonAction(null)} />
            )}
            <CompleteDialog taskId={taskId} targetCount={targetCount} open={completing} onOpenChange={setCompleting} />
            <ReassignDialog taskId={taskId} recruiters={recruiters} open={reassigning} onOpenChange={setReassigning} />
        </div>
    );
}
