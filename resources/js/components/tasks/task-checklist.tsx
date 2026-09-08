import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { router, useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export interface TaskChecklistItemRow {
    id: number;
    title: string;
    source?: string;
    template_key?: string | null;
    checklist_group?: string | null;
    is_mandatory?: boolean;
    is_system?: boolean;
    is_completed: boolean;
    completed_by: string | null;
    completed_at: string | null;
    sort_order: number;
}

export interface TaskChecklistPayload {
    items: TaskChecklistItemRow[];
    completed: number;
    total: number;
    required_incomplete?: number;
    ready_blocked?: boolean;
    ready_blocked_message?: string | null;
}

export function TaskChecklist({
    taskId,
    checklist,
    canManage,
    canComplete = canManage,
}: {
    taskId: number;
    checklist: TaskChecklistPayload;
    canManage: boolean;
    canComplete?: boolean;
}) {
    const addForm = useForm<{ title: string }>({ title: '' });
    const percent = checklist.total === 0 ? 0 : Math.round((checklist.completed / checklist.total) * 100);

    const qualityItems = checklist.items.filter((item) => item.checklist_group === 'quality');
    const videoItems = checklist.items.filter((item) => item.checklist_group === 'video');
    const hasGroupedDefaults = qualityItems.length > 0 || videoItems.length > 0;
    const otherItems = checklist.items.filter((item) => item.checklist_group !== 'quality' && item.checklist_group !== 'video');
    const displaySections = hasGroupedDefaults
        ? [
              ...(qualityItems.length > 0 ? [{ title: 'Quality checklist', items: qualityItems }] : []),
              ...(videoItems.length > 0 ? [{ title: 'Video checklist', items: videoItems }] : []),
              ...(otherItems.length > 0 ? [{ title: 'Additional checklist', items: otherItems }] : []),
          ]
        : [{ title: null as string | null, items: checklist.items }];

    const reorder = (order: number[]) => {
        router.post(`/tasks/${taskId}/checklist-items/reorder`, { order }, { preserveScroll: true });
    };

    const moveWithinAll = (itemId: number, direction: 'up' | 'down') => {
        const order = checklist.items.map((row) => row.id);
        const index = order.indexOf(itemId);
        if (index < 0) {
            return;
        }
        const swapWith = direction === 'up' ? index - 1 : index + 1;
        if (swapWith < 0 || swapWith >= order.length) {
            return;
        }
        [order[index], order[swapWith]] = [order[swapWith], order[index]];
        reorder(order);
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle>Checklist</CardTitle>
                        <CardDescription>
                            Break work into smaller steps. Completing the checklist does not close the task.
                            {checklist.ready_blocked ? ' Required quality checks must be done before marking the creative Ready.' : ''}
                        </CardDescription>
                    </div>
                    {checklist.total > 0 && (
                        <div className="text-right">
                            <div className={cn('text-sm font-semibold', percent === 100 && 'text-primary')}>
                                {checklist.completed} / {checklist.total} completed
                            </div>
                            <div className="text-muted-foreground text-xs">{percent === 100 ? '100%' : `${percent}%`}</div>
                        </div>
                    )}
                </div>
                {checklist.total > 0 && (
                    <div className="bg-muted mt-3 h-2 overflow-hidden rounded-full">
                        <div
                            className={cn('bg-primary h-full rounded-full transition-all', percent === 100 && 'w-full')}
                            style={{ width: `${percent}%` }}
                        />
                    </div>
                )}
                {checklist.ready_blocked && checklist.ready_blocked_message && (
                    <p className="text-destructive mt-3 text-sm">{checklist.ready_blocked_message}</p>
                )}
            </CardHeader>
            <CardContent className="space-y-6">
                {checklist.items.length === 0 && <p className="text-muted-foreground text-sm">No checklist items yet.</p>}

                {displaySections.map((section) => (
                    <div key={section.title ?? 'all'} className="space-y-2">
                        {section.title && (
                            <h3 className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">{section.title}</h3>
                        )}
                        {section.items.map((item) => {
                            const index = checklist.items.findIndex((row) => row.id === item.id);
                            return (
                                <ChecklistRow
                                    key={item.id}
                                    taskId={taskId}
                                    item={item}
                                    canManage={canManage}
                                    canComplete={canComplete}
                                    canMoveUp={canManage && index > 0}
                                    canMoveDown={canManage && index < checklist.items.length - 1}
                                    onMoveUp={() => moveWithinAll(item.id, 'up')}
                                    onMoveDown={() => moveWithinAll(item.id, 'down')}
                                />
                            );
                        })}
                    </div>
                ))}

                {canManage && (
                    <form
                        className="flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-end"
                        onSubmit={(event) => {
                            event.preventDefault();
                            addForm.post(`/tasks/${taskId}/checklist-items`, {
                                preserveScroll: true,
                                onSuccess: () => addForm.reset(),
                            });
                        }}
                    >
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="checklist_title">Add item</Label>
                            <Input
                                id="checklist_title"
                                value={addForm.data.title}
                                onChange={(event) => addForm.setData('title', event.target.value)}
                                placeholder="Write copy, export assets, send for review…"
                            />
                            <InputError message={addForm.errors.title} />
                        </div>
                        <Button type="submit" disabled={addForm.processing || addForm.data.title.trim() === ''}>
                            Add
                        </Button>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

function ChecklistRow({
    taskId,
    item,
    canManage,
    canComplete,
    canMoveUp,
    canMoveDown,
    onMoveUp,
    onMoveDown,
}: {
    taskId: number;
    item: TaskChecklistItemRow;
    canManage: boolean;
    canComplete: boolean;
    canMoveUp: boolean;
    canMoveDown: boolean;
    onMoveUp: () => void;
    onMoveDown: () => void;
}) {
    const [editing, setEditing] = useState(false);
    const editForm = useForm<{ title: string }>({ title: item.title });
    const deleteForm = useForm({});
    const isSystem = item.is_system === true;

    const toggle = () => {
        router.patch(`/tasks/${taskId}/checklist-items/${item.id}/toggle`, {}, { preserveScroll: true });
    };

    return (
        <div className="flex items-start gap-2 rounded-lg border p-3">
            <Checkbox
                checked={item.is_completed}
                disabled={!canComplete}
                onCheckedChange={() => {
                    if (canComplete) {
                        toggle();
                    }
                }}
                aria-label={`Mark ${item.title} complete`}
                className="mt-0.5"
            />

            <div className="min-w-0 flex-1">
                {editing && !isSystem ? (
                    <form
                        className="space-y-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            editForm.put(`/tasks/${taskId}/checklist-items/${item.id}`, {
                                preserveScroll: true,
                                onSuccess: () => setEditing(false),
                            });
                        }}
                    >
                        <Input value={editForm.data.title} onChange={(event) => editForm.setData('title', event.target.value)} />
                        <InputError message={editForm.errors.title} />
                        <div className="flex gap-2">
                            <Button type="submit" size="sm" disabled={editForm.processing || editForm.data.title.trim() === ''}>
                                Save
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => {
                                    editForm.setData('title', item.title);
                                    setEditing(false);
                                }}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                ) : (
                    <>
                        <p className={cn('text-sm', item.is_completed && 'text-muted-foreground line-through')}>{item.title}</p>
                        {item.is_mandatory && (
                            <p className="text-muted-foreground mt-1 text-xs">Required before Ready</p>
                        )}
                        {item.is_completed && item.completed_by && (
                            <p className="text-muted-foreground mt-1 text-xs">
                                Completed by {item.completed_by}
                                {item.completed_at && ` · ${new Date(item.completed_at).toLocaleString()}`}
                            </p>
                        )}
                    </>
                )}
            </div>

            {canManage && !editing && (
                <div className="flex shrink-0 flex-col gap-1">
                    <Button type="button" variant="ghost" size="icon" className="size-7" disabled={!canMoveUp} onClick={onMoveUp} aria-label="Move up">
                        <ChevronUp className="size-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" className="size-7" disabled={!canMoveDown} onClick={onMoveDown} aria-label="Move down">
                        <ChevronDown className="size-4" />
                    </Button>
                    {!isSystem && (
                        <Button type="button" variant="ghost" size="icon" className="size-7" onClick={() => setEditing(true)} aria-label="Edit item">
                            <Pencil className="size-4" />
                        </Button>
                    )}
                    {!(isSystem && item.is_mandatory) && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="text-destructive hover:text-destructive size-7"
                            disabled={deleteForm.processing}
                            onClick={() => {
                                if (window.confirm('Remove this checklist item?')) {
                                    deleteForm.delete(`/tasks/${taskId}/checklist-items/${item.id}`, { preserveScroll: true });
                                }
                            }}
                            aria-label="Delete item"
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
