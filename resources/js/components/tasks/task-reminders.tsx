import InputError from '@/components/input-error';
import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { Bell, Trash2 } from 'lucide-react';
import { useMemo } from 'react';

export interface TaskReminderRow {
    id: number;
    remind_at: string;
    message: string | null;
    recipient_name: string;
    recipient_user_id: number;
    created_by: string;
    sent_at: string | null;
    can_delete: boolean;
}

function formatted(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function toLocalInputValue(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export function TaskReminders({
    taskId,
    dueAt,
    defaultRecipientUserId,
    reminders,
    recipients,
    canManage,
}: {
    taskId: number;
    dueAt: string | null;
    defaultRecipientUserId: number | null;
    reminders: TaskReminderRow[];
    recipients: { user_id: number; label: string }[];
    canManage: boolean;
}) {
    const defaultRecipient = defaultRecipientUserId ? String(defaultRecipientUserId) : recipients[0]?.user_id ? String(recipients[0].user_id) : '';

    const form = useForm({
        remind_at: '',
        recipient_user_id: defaultRecipient,
        message: '',
    });

    const dueDate = useMemo(() => (dueAt ? new Date(dueAt) : null), [dueAt]);

    const applyPreset = (hoursBefore: number) => {
        if (!dueDate) {
            return;
        }

        const remindAt = new Date(dueDate.getTime() - hoursBefore * 60 * 60 * 1000);

        if (remindAt.getTime() <= Date.now()) {
            return;
        }

        form.setData('remind_at', toLocalInputValue(remindAt));
    };

    if (!canManage && reminders.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Bell className="size-4" /> Reminders
                </CardTitle>
                <CardDescription>One-time nudges before the deadline or at a custom time.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {reminders.length === 0 && <p className="text-muted-foreground text-sm">No reminders scheduled.</p>}

                <div className="space-y-3">
                    {reminders.map((reminder) => (
                        <div key={reminder.id} className="flex items-start justify-between gap-3 rounded-lg border p-3 text-sm">
                            <div className="space-y-1">
                                <div className="font-medium">{formatted(reminder.remind_at)}</div>
                                <div className="text-muted-foreground text-xs">
                                    To {reminder.recipient_name} · by {reminder.created_by}
                                </div>
                                {reminder.message && <p>{reminder.message}</p>}
                                <div className="text-muted-foreground text-xs">{reminder.sent_at ? `Sent ${formatted(reminder.sent_at)}` : 'Pending'}</div>
                            </div>
                            {reminder.can_delete && (
                                <ConfirmDelete
                                    url={`/tasks/${taskId}/reminders/${reminder.id}`}
                                    title="Cancel this reminder?"
                                    description="It will not be sent."
                                    trigger={
                                        <Button variant="ghost" size="icon" aria-label="Cancel reminder">
                                            <Trash2 className="text-destructive size-4" />
                                        </Button>
                                    }
                                />
                            )}
                        </div>
                    ))}
                </div>

                {canManage && (
                    <form
                        className="space-y-4 rounded-lg border p-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(`/tasks/${taskId}/reminders`, {
                                preserveScroll: true,
                                onSuccess: () => form.reset('remind_at', 'message'),
                            });
                        }}
                    >
                        {dueDate && (
                            <div className="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => applyPreset(1)}>
                                    1 hour before due
                                </Button>
                                <Button type="button" variant="outline" size="sm" onClick={() => applyPreset(24)}>
                                    1 day before due
                                </Button>
                            </div>
                        )}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="remind_at">Remind at</Label>
                                <Input
                                    id="remind_at"
                                    type="datetime-local"
                                    value={form.data.remind_at}
                                    onChange={(event) => form.setData('remind_at', event.target.value)}
                                />
                                <InputError message={form.errors.remind_at} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="recipient_user_id">Recipient</Label>
                                <Select value={form.data.recipient_user_id} onValueChange={(value) => form.setData('recipient_user_id', value)}>
                                    <SelectTrigger id="recipient_user_id">
                                        <SelectValue placeholder="Choose someone" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {recipients.map((recipient) => (
                                            <SelectItem key={recipient.user_id} value={String(recipient.user_id)}>
                                                {recipient.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.recipient_user_id} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="reminder_message">Message (optional)</Label>
                            <Textarea
                                id="reminder_message"
                                value={form.data.message}
                                onChange={(event) => form.setData('message', event.target.value)}
                                placeholder="Add context for the reminder…"
                                rows={2}
                            />
                            <InputError message={form.errors.message} />
                        </div>

                        <Button type="submit" disabled={form.processing || form.data.remind_at === '' || form.data.recipient_user_id === ''}>
                            Schedule reminder
                        </Button>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}
