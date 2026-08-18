import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { DueDate } from '@/components/tasks/task-badges';
import { cn } from '@/lib/utils';
import { type Option } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

export interface TaskSubtaskRow {
    id: number;
    title: string;
    description: string | null;
    status: string;
    status_label: string;
    assignee_name: string | null;
    assigned_employee_id: number | null;
    due_at: string | null;
    completed_at: string | null;
    sort_order: number;
}

export interface TaskSubtasksPayload {
    items: TaskSubtaskRow[];
    completed: number;
    total: number;
}

type SubtaskFormData = {
    title: string;
    description: string;
    status: string;
    assigned_employee_id: string;
    due_at: string;
};

const emptyForm = (): SubtaskFormData => ({
    title: '',
    description: '',
    status: 'pending',
    assigned_employee_id: '',
    due_at: '',
});

function toForm(row?: TaskSubtaskRow): SubtaskFormData {
    if (!row) {
        return emptyForm();
    }

    return {
        title: row.title,
        description: row.description ?? '',
        status: row.status,
        assigned_employee_id: row.assigned_employee_id ? String(row.assigned_employee_id) : '',
        due_at: row.due_at ? row.due_at.slice(0, 16) : '',
    };
}

export function TaskSubtasks({
    taskId,
    subtasks,
    statuses,
    employees,
    canManage,
}: {
    taskId: number;
    subtasks: TaskSubtasksPayload;
    statuses: Option[];
    employees: { id: number; label: string }[];
    canManage: boolean;
}) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<TaskSubtaskRow | null>(null);
    const form = useForm<SubtaskFormData>(emptyForm());
    const percent = subtasks.total === 0 ? 0 : Math.round((subtasks.completed / subtasks.total) * 100);

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm());
        form.clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (row: TaskSubtaskRow) => {
        setEditing(row);
        form.setData(toForm(row));
        form.clearErrors();
        setDialogOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        form.transform(() => ({
            title: form.data.title,
            description: form.data.description || null,
            status: form.data.status,
            assigned_employee_id: form.data.assigned_employee_id ? Number(form.data.assigned_employee_id) : null,
            due_at: form.data.due_at || null,
        }));

        if (editing) {
            form.put(`/tasks/${taskId}/subtasks/${editing.id}`, {
                preserveScroll: true,
                onSuccess: () => setDialogOpen(false),
            });

            return;
        }

        form.post(`/tasks/${taskId}/subtasks`, {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        });
    };

    const reorder = (order: number[]) => {
        router.post(`/tasks/${taskId}/subtasks/reorder`, { order }, { preserveScroll: true });
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <CardTitle>Subtasks</CardTitle>
                            <CardDescription>
                                Break the parent task into assignable pieces. Completing subtasks does not close the parent task.
                            </CardDescription>
                        </div>
                        <div className="flex items-center gap-2">
                            {subtasks.total > 0 && (
                                <div className="text-right">
                                    <div className={cn('text-sm font-semibold', percent === 100 && 'text-primary')}>
                                        {subtasks.completed} / {subtasks.total} subtasks completed
                                    </div>
                                    <div className="text-muted-foreground text-xs">{percent === 100 ? '100%' : `${percent}%`}</div>
                                </div>
                            )}
                            {canManage && (
                                <Button type="button" size="sm" onClick={openCreate}>
                                    <Plus className="size-4" />
                                    Add subtask
                                </Button>
                            )}
                        </div>
                    </div>
                    {subtasks.total > 0 && (
                        <div className="bg-muted mt-3 h-2 overflow-hidden rounded-full">
                            <div className={cn('bg-primary h-full rounded-full transition-all')} style={{ width: `${percent}%` }} />
                        </div>
                    )}
                </CardHeader>
                <CardContent className="space-y-3">
                    {subtasks.items.length === 0 && <p className="text-muted-foreground text-sm">No subtasks yet.</p>}

                    {subtasks.items.map((item, index) => (
                        <SubtaskRow
                            key={item.id}
                            taskId={taskId}
                            item={item}
                            canManage={canManage}
                            canMoveUp={canManage && index > 0}
                            canMoveDown={canManage && index < subtasks.items.length - 1}
                            onEdit={() => openEdit(item)}
                            onMoveUp={() => {
                                const order = subtasks.items.map((row) => row.id);
                                [order[index - 1], order[index]] = [order[index], order[index - 1]];
                                reorder(order);
                            }}
                            onMoveDown={() => {
                                const order = subtasks.items.map((row) => row.id);
                                [order[index], order[index + 1]] = [order[index + 1], order[index]];
                                reorder(order);
                            }}
                        />
                    ))}
                </CardContent>
            </Card>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-lg">
                    <form onSubmit={submit} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Edit subtask' : 'Add subtask'}</DialogTitle>
                            <DialogDescription>Subtasks belong to this task only and cannot contain nested subtasks.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-2">
                            <Label htmlFor="subtask_title">Title</Label>
                            <Input id="subtask_title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} />
                            <InputError message={form.errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="subtask_description">Description</Label>
                            <Textarea
                                id="subtask_description"
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                                rows={3}
                            />
                            <InputError message={form.errors.description} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="subtask_assignee">Assignee</Label>
                                <Select
                                    value={form.data.assigned_employee_id || 'none'}
                                    onValueChange={(value) => form.setData('assigned_employee_id', value === 'none' ? '' : value)}
                                >
                                    <SelectTrigger id="subtask_assignee">
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
                                <InputError message={form.errors.assigned_employee_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="subtask_status">Status</Label>
                                <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                                    <SelectTrigger id="subtask_status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem key={status.value} value={status.value}>
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.status} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="subtask_due_at">Due date</Label>
                            <Input
                                id="subtask_due_at"
                                type="datetime-local"
                                value={form.data.due_at}
                                onChange={(event) => form.setData('due_at', event.target.value)}
                            />
                            <InputError message={form.errors.due_at} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing || form.data.title.trim() === ''}>
                                {editing ? 'Save changes' : 'Add subtask'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

function SubtaskRow({
    taskId,
    item,
    canManage,
    canMoveUp,
    canMoveDown,
    onEdit,
    onMoveUp,
    onMoveDown,
}: {
    taskId: number;
    item: TaskSubtaskRow;
    canManage: boolean;
    canMoveUp: boolean;
    canMoveDown: boolean;
    onEdit: () => void;
    onMoveDown: () => void;
    onMoveUp: () => void;
}) {
    const deleteForm = useForm({});
    const completed = item.status === 'completed';

    return (
        <div className="flex items-start gap-3 rounded-lg border p-3">
            <Checkbox
                checked={completed}
                disabled={!canManage}
                onCheckedChange={() => {
                    if (canManage) {
                        router.patch(`/tasks/${taskId}/subtasks/${item.id}/toggle`, {}, { preserveScroll: true });
                    }
                }}
                aria-label={`Mark ${item.title} complete`}
                className="mt-0.5"
            />

            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <p className={cn('text-sm font-medium', completed && 'text-muted-foreground line-through')}>{item.title}</p>
                    <Badge variant={completed ? 'default' : 'secondary'}>{item.status_label}</Badge>
                </div>
                {item.description && <p className="text-muted-foreground mt-1 text-xs whitespace-pre-wrap">{item.description}</p>}
                <div className="text-muted-foreground mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                    <span>Assignee: {item.assignee_name ?? 'Unassigned'}</span>
                    <span>
                        Due: <DueDate value={item.due_at} />
                    </span>
                    {item.completed_at && <span>Completed: {new Date(item.completed_at).toLocaleString()}</span>}
                </div>
            </div>

            {canManage && (
                <div className="flex shrink-0 flex-col gap-1">
                    <Button type="button" variant="ghost" size="icon" className="size-7" disabled={!canMoveUp} onClick={onMoveUp} aria-label="Move up">
                        <ChevronUp className="size-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" className="size-7" disabled={!canMoveDown} onClick={onMoveDown} aria-label="Move down">
                        <ChevronDown className="size-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" className="size-7" onClick={onEdit} aria-label="Edit subtask">
                        <Pencil className="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-destructive hover:text-destructive size-7"
                        disabled={deleteForm.processing}
                        onClick={() => {
                            if (window.confirm('Remove this subtask?')) {
                                deleteForm.delete(`/tasks/${taskId}/subtasks/${item.id}`, { preserveScroll: true });
                            }
                        }}
                        aria-label="Delete subtask"
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            )}
        </div>
    );
}
