import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { DueDate } from '@/components/tasks/task-badges';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

export type DraftSubtask = {
    key: string;
    title: string;
    description: string;
    assigned_employee_id: string;
    due_at: string;
};

type SubtaskFormData = Omit<DraftSubtask, 'key'>;

const emptySubtask = (): SubtaskFormData => ({
    title: '',
    description: '',
    assigned_employee_id: '',
    due_at: '',
});

function newDraftSubtask(data: SubtaskFormData): DraftSubtask {
    return { key: crypto.randomUUID(), ...data };
}

function assigneeLabel(employees: { id: number; label: string }[], employeeId: string): string | null {
    if (!employeeId) {
        return null;
    }

    return employees.find((employee) => String(employee.id) === employeeId)?.label ?? null;
}

export function TaskCreateSubtasks({
    items,
    onChange,
    employees,
    errors,
}: {
    items: DraftSubtask[];
    onChange: (items: DraftSubtask[]) => void;
    employees: { id: number; label: string }[];
    errors?: Record<string, string | undefined>;
}) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingKey, setEditingKey] = useState<string | null>(null);
    const [form, setForm] = useState<SubtaskFormData>(emptySubtask());

    const openCreate = () => {
        setEditingKey(null);
        setForm(emptySubtask());
        setDialogOpen(true);
    };

    const openEdit = (item: DraftSubtask) => {
        setEditingKey(item.key);
        setForm({
            title: item.title,
            description: item.description,
            assigned_employee_id: item.assigned_employee_id,
            due_at: item.due_at,
        });
        setDialogOpen(true);
    };

    const saveSubtask = (event: React.FormEvent) => {
        event.preventDefault();

        const title = form.title.trim();

        if (title === '') {
            return;
        }

        const payload = { ...form, title };

        if (editingKey) {
            onChange(items.map((item) => (item.key === editingKey ? { ...item, ...payload } : item)));
        } else {
            onChange([...items, newDraftSubtask(payload)]);
        }

        setDialogOpen(false);
    };

    const removeSubtask = (key: string) => {
        onChange(items.filter((item) => item.key !== key));
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <CardTitle>Subtasks</CardTitle>
                            <CardDescription>Break the task into smaller pieces before it is created.</CardDescription>
                        </div>
                        <Button type="button" size="sm" onClick={openCreate}>
                            <Plus className="size-4" />
                            Add subtask
                        </Button>
                    </div>
                </CardHeader>
                <CardContent className="space-y-3">
                    {items.length === 0 && <p className="text-muted-foreground text-sm">No subtasks yet.</p>}

                    {items.map((item, index) => (
                        <div key={item.key} className="flex items-start justify-between gap-3 rounded-lg border p-3">
                            <div className="min-w-0 flex-1 space-y-1">
                                <p className="font-medium">{item.title}</p>
                                {item.description && <p className="text-muted-foreground text-sm">{item.description}</p>}
                                <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                    <span>{assigneeLabel(employees, item.assigned_employee_id) ?? 'Unassigned'}</span>
                                    {item.due_at && <DueDate value={item.due_at} />}
                                </div>
                                <InputError message={errors?.[`subtasks.${index}.title`]} />
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <Button type="button" variant="ghost" size="icon" onClick={() => openEdit(item)} aria-label="Edit subtask">
                                    <Pencil className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-destructive hover:text-destructive"
                                    onClick={() => removeSubtask(item.key)}
                                    aria-label="Remove subtask"
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-lg">
                    <form onSubmit={saveSubtask} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{editingKey ? 'Edit subtask' : 'Add subtask'}</DialogTitle>
                            <DialogDescription>Subtasks belong to this task only and cannot contain nested subtasks.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-2">
                            <Label htmlFor="create_subtask_title">Title</Label>
                            <Input
                                id="create_subtask_title"
                                value={form.title}
                                onChange={(event) => setForm((current) => ({ ...current, title: event.target.value }))}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="create_subtask_description">Description</Label>
                            <Textarea
                                id="create_subtask_description"
                                value={form.description}
                                onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))}
                                rows={3}
                            />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="create_subtask_assignee">Assignee</Label>
                                <Select
                                    value={form.assigned_employee_id || 'none'}
                                    onValueChange={(value) =>
                                        setForm((current) => ({
                                            ...current,
                                            assigned_employee_id: value === 'none' ? '' : value,
                                        }))
                                    }
                                >
                                    <SelectTrigger id="create_subtask_assignee">
                                        <SelectValue placeholder="Unassigned" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Unassigned</SelectItem>
                                        {employees.map((employee) => (
                                            <SelectItem key={employee.id} value={String(employee.id)}>
                                                {employee.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="create_subtask_due_at">Due date</Label>
                                <Input
                                    id="create_subtask_due_at"
                                    type="datetime-local"
                                    value={form.due_at}
                                    onChange={(event) => setForm((current) => ({ ...current, due_at: event.target.value }))}
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.title.trim() === ''}>
                                {editingKey ? 'Save changes' : 'Add subtask'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

export function sanitizeSubtasks(items: DraftSubtask[]): Array<{
    title: string;
    description: string | null;
    assigned_employee_id: number | null;
    due_at: string | null;
    status: string;
}> {
    return items
        .map((item) => ({
            title: item.title.trim(),
            description: item.description.trim() || null,
            assigned_employee_id: item.assigned_employee_id ? Number(item.assigned_employee_id) : null,
            due_at: item.due_at || null,
            status: 'pending',
        }))
        .filter((item) => item.title !== '');
}
