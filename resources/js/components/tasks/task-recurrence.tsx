import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { type Option } from '@/types';
import { useForm } from '@inertiajs/react';
import { Repeat } from 'lucide-react';

export interface TaskRecurrenceRulePayload {
    frequency: string;
    interval: number;
    start_date: string;
    end_date: string | null;
    max_occurrences: number | null;
    occurrences_generated: number;
    is_active: boolean;
}

export interface TaskRecurrencePayload {
    can_manage: boolean;
    frequencies: Option[];
    rule: TaskRecurrenceRulePayload | null;
}

export function TaskRecurrence({ taskId, recurrence }: { taskId: number; recurrence: TaskRecurrencePayload }) {
    const rule = recurrence.rule;
    const today = new Date().toISOString().slice(0, 10);

    const form = useForm({
        frequency: rule?.frequency ?? 'weekly',
        interval: rule?.interval ?? 1,
        start_date: rule?.start_date ?? today,
        end_date: rule?.end_date ?? '',
        max_occurrences: rule?.max_occurrences ?? '',
        is_active: rule?.is_active ?? true,
    });

    if (!recurrence.can_manage && recurrence.rule === null) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Repeat className="size-4" /> Recurring task
                </CardTitle>
                <CardDescription>When this task is completed, the next occurrence is created automatically.</CardDescription>
            </CardHeader>
            <CardContent>
                {recurrence.can_manage ? (
                    <form
                        className="space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.transform((data) => ({
                                frequency: data.frequency,
                                interval: Number(data.interval),
                                start_date: data.start_date,
                                end_date: data.end_date === '' ? null : data.end_date,
                                max_occurrences: data.max_occurrences === '' ? null : Number(data.max_occurrences),
                                is_active: data.is_active,
                            }));
                            form.put(`/tasks/${taskId}/recurrence`, { preserveScroll: true });
                        }}
                    >
                        <div className="flex items-center justify-between rounded-lg border p-3">
                            <div>
                                <Label htmlFor="recurrence_active">Active</Label>
                                <p className="text-muted-foreground text-xs">Turn off to stop generating new occurrences.</p>
                            </div>
                            <Switch
                                id="recurrence_active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) => form.setData('is_active', checked)}
                            />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="frequency">Frequency</Label>
                                <Select value={form.data.frequency} onValueChange={(value) => form.setData('frequency', value)}>
                                    <SelectTrigger id="frequency">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {recurrence.frequencies.map((frequency) => (
                                            <SelectItem key={frequency.value} value={frequency.value}>
                                                {frequency.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.frequency} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="interval">Interval</Label>
                                <Input
                                    id="interval"
                                    type="number"
                                    min={1}
                                    value={form.data.interval}
                                    onChange={(event) => form.setData('interval', Number(event.target.value))}
                                />
                                <InputError message={form.errors.interval} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="start_date">Start date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(event) => form.setData('start_date', event.target.value)}
                                />
                                <InputError message={form.errors.start_date} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="end_date">End date (optional)</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(event) => form.setData('end_date', event.target.value)}
                                />
                                <InputError message={form.errors.end_date} />
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="max_occurrences">Maximum occurrences (optional)</Label>
                                <Input
                                    id="max_occurrences"
                                    type="number"
                                    min={1}
                                    value={form.data.max_occurrences}
                                    onChange={(event) => form.setData('max_occurrences', event.target.value)}
                                />
                                <InputError message={form.errors.max_occurrences} />
                            </div>
                        </div>

                        {rule && (
                            <p className="text-muted-foreground text-sm">
                                {rule.occurrences_generated} occurrence{rule.occurrences_generated === 1 ? '' : 's'} generated so far.
                            </p>
                        )}

                        <Button type="submit" disabled={form.processing}>
                            Save recurrence
                        </Button>
                    </form>
                ) : rule ? (
                    <dl className="grid gap-3 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Frequency</dt>
                            <dd>{recurrence.frequencies.find((item) => item.value === rule.frequency)?.label ?? rule.frequency}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd>{rule.is_active ? 'Active' : 'Inactive'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Generated</dt>
                            <dd>{rule.occurrences_generated}</dd>
                        </div>
                    </dl>
                ) : null}
            </CardContent>
        </Card>
    );
}
