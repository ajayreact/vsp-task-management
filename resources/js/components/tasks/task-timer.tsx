import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { router, useForm } from '@inertiajs/react';
import { Pause, Play, Square } from 'lucide-react';
import { useEffect, useState } from 'react';

export interface TimerState {
    running: boolean;
    started_at: string | null;
    yours: boolean;
    running_employee_name?: string | null;
}

function ManualTimeForm({
    taskId,
    submitLabel,
    description,
    compact = false,
}: {
    taskId: number;
    submitLabel: string;
    description?: string;
    compact?: boolean;
}) {
    const form = useForm<{ started_at: string; ended_at: string; note: string; is_billable: boolean }>({
        started_at: '',
        ended_at: '',
        note: '',
        is_billable: true,
    });

    return (
        <form
            className={compact ? 'grid gap-3' : 'grid gap-3 sm:grid-cols-2'}
            onSubmit={(event) => {
                event.preventDefault();
                form.post(`/tasks/${taskId}/time-entries`, { preserveScroll: true, onSuccess: () => form.reset() });
            }}
        >
            {description && <p className={`text-muted-foreground text-sm ${compact ? '' : 'sm:col-span-2'}`}>{description}</p>}

            <div className="grid gap-2">
                <Label htmlFor="started_at">Started</Label>
                <Input
                    id="started_at"
                    type="datetime-local"
                    value={form.data.started_at}
                    onChange={(event) => form.setData('started_at', event.target.value)}
                    required
                />
                <InputError message={form.errors.started_at} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="ended_at">Ended</Label>
                <Input
                    id="ended_at"
                    type="datetime-local"
                    value={form.data.ended_at}
                    onChange={(event) => form.setData('ended_at', event.target.value)}
                    required
                />
                <InputError message={form.errors.ended_at} />
            </div>
            <div className={`grid gap-2 ${compact ? '' : 'sm:col-span-2'}`}>
                <Label htmlFor="note">Note</Label>
                <Textarea id="note" value={form.data.note} onChange={(event) => form.setData('note', event.target.value)} />
            </div>
            <div className={`flex items-center gap-2 ${compact ? '' : 'sm:col-span-2'}`}>
                <Checkbox
                    id="is_billable"
                    checked={form.data.is_billable}
                    onCheckedChange={(checked) => form.setData('is_billable', checked === true)}
                />
                <Label htmlFor="is_billable">Billable</Label>
            </div>
            <Button type="submit" variant="outline" disabled={form.processing} className={compact ? 'w-full' : 'sm:col-span-2'}>
                {submitLabel}
            </Button>
        </form>
    );
}

export function TaskTimer({
    taskId,
    timer,
    canStartTimer,
    canLogManualTime,
    manualTimeTargetName,
    embedded = false,
    compact = false,
}: {
    taskId: number;
    timer: TimerState;
    canStartTimer: boolean;
    canLogManualTime: boolean;
    manualTimeTargetName?: string | null;
    embedded?: boolean;
    compact?: boolean;
}) {
    const [elapsed, setElapsed] = useState('00:00:00');
    const isMonitoring = !canStartTimer;

    useEffect(() => {
        if (!timer.running || !timer.started_at) {
            setElapsed('00:00:00');
            return;
        }

        const tick = () => {
            const seconds = Math.max(0, Math.floor((Date.now() - new Date(timer.started_at as string).getTime()) / 1000));
            const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const rest = String(seconds % 60).padStart(2, '0');
            setElapsed(`${hours}:${minutes}:${rest}`);
        };

        tick();
        const id = window.setInterval(tick, 1000);

        return () => window.clearInterval(id);
    }, [timer.running, timer.started_at]);

    const post = (action: 'start' | 'pause' | 'stop') => router.post(`/tasks/${taskId}/timer/${action}`, {}, { preserveScroll: true });

    const statusMessage = (() => {
        if (timer.running && timer.running_employee_name) {
            return `${timer.running_employee_name} has the timer running on this task.`;
        }

        if (timer.running && !timer.yours) {
            return 'Someone else has the timer running on this task.';
        }

        if (isMonitoring && !timer.running) {
            return manualTimeTargetName
                ? `${manualTimeTargetName} is not currently tracking time on this task.`
                : 'No timer is currently running on this task.';
        }

        return null;
    })();

    const body = (
        <>
            <div className="flex items-center justify-between gap-3">
                <div>
                    <div className={`font-mono tabular-nums ${compact ? 'text-xl' : 'text-2xl'}`}>{elapsed}</div>
                    {isMonitoring && <p className="text-muted-foreground mt-1 text-xs uppercase tracking-wide">Live timer</p>}
                </div>
                {canStartTimer && (
                    <div className="flex gap-2">
                        {!timer.running && (
                            <Button size="sm" onClick={() => post('start')}>
                                <Play /> Start
                            </Button>
                        )}
                        {timer.running && timer.yours && (
                            <>
                                <Button size="sm" variant="outline" onClick={() => post('pause')}>
                                    <Pause /> Pause
                                </Button>
                                <Button size="sm" variant="outline" onClick={() => post('stop')}>
                                    <Square /> Stop
                                </Button>
                            </>
                        )}
                    </div>
                )}
            </div>

            {statusMessage && <p className="text-muted-foreground text-sm">{statusMessage}</p>}

            {canStartTimer && canLogManualTime && (
                <ManualTimeForm taskId={taskId} submitLabel="Log time" compact={compact} />
            )}

            {canLogManualTime && !canStartTimer && (
                <>
                    <Separator />
                    <div className="space-y-3">
                        <div>
                            <p className="text-sm font-medium">Add time manually</p>
                            <p className="text-muted-foreground text-sm">
                                Record an interval on behalf of {manualTimeTargetName ?? 'the assignee'}. This does not start their live timer.
                            </p>
                        </div>
                        <ManualTimeForm
                            taskId={taskId}
                            submitLabel="Add time manually"
                            compact={compact}
                            description={
                                manualTimeTargetName
                                    ? `This entry will be added to ${manualTimeTargetName}'s timesheet.`
                                    : undefined
                            }
                        />
                    </div>
                </>
            )}
        </>
    );

    if (embedded) {
        return <div className="space-y-4">{body}</div>;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Timer</CardTitle>
                <CardDescription>
                    {canStartTimer
                        ? 'Start a live clock, or type an interval after the fact. Both land on the same timesheet.'
                        : 'Monitor the assignee’s live timer and logged time. Use Add time manually only when you need to record time on their behalf.'}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">{body}</CardContent>
        </Card>
    );
}
