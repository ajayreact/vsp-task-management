import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type Option } from '@/types';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

interface TodoQuickAddDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    priorities: Option[];
}

const REMINDER_OPTIONS: Option[] = [
    { value: 'none', label: 'No reminder' },
    { value: '15', label: '15 minutes before' },
    { value: '30', label: '30 minutes before' },
    { value: '60', label: '1 hour before' },
    { value: '1440', label: '1 day before' },
];

export function TodoQuickAddDialog({ open, onOpenChange, priorities }: TodoQuickAddDialogProps) {
    const form = useForm({
        title: '',
        note: '',
        due_date: '',
        due_time: '',
        priority: 'normal',
        reminder_minutes_before: 'none',
    });

    useEffect(() => {
        if (!open) {
            form.reset();
            form.clearErrors();
        }
    }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            reminder_minutes_before: data.reminder_minutes_before && data.reminder_minutes_before !== 'none'
                ? Number(data.reminder_minutes_before)
                : null,
            due_date: data.due_date || null,
            due_time: data.due_time || null,
            note: data.note || null,
        }));

        form.post('/tasks/personal-todos', {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Todo</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="todo-title">Title</Label>
                        <Input
                            id="todo-title"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                            placeholder="Call client regarding approval"
                            required
                        />
                        {form.errors.title && <p className="text-destructive text-sm">{form.errors.title}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="todo-date">Date</Label>
                            <Input
                                id="todo-date"
                                type="date"
                                value={form.data.due_date}
                                onChange={(event) => form.setData('due_date', event.target.value)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="todo-time">Time</Label>
                            <Input
                                id="todo-time"
                                type="time"
                                value={form.data.due_time}
                                onChange={(event) => form.setData('due_time', event.target.value)}
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Priority</Label>
                            <Select value={form.data.priority} onValueChange={(value) => form.setData('priority', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {priorities.map((priority) => (
                                        <SelectItem key={priority.value} value={priority.value}>
                                            {priority.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Reminder</Label>
                            <Select
                                value={form.data.reminder_minutes_before}
                                onValueChange={(value) => form.setData('reminder_minutes_before', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="No reminder" />
                                </SelectTrigger>
                                <SelectContent>
                                    {REMINDER_OPTIONS.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="todo-note">Note</Label>
                        <Textarea
                            id="todo-note"
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            placeholder="Optional context for this reminder"
                            rows={3}
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Add Todo
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
