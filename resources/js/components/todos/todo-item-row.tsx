import { PriorityBadge } from '@/components/tasks/task-badges';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { formatTodoDue, priorityClass, type TodoItem } from '@/lib/todos';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { ArrowRight, CalendarClock } from 'lucide-react';
import { useState } from 'react';

interface TodoItemRowProps {
    item: TodoItem;
    compact?: boolean;
    showKind?: boolean;
    onToggle?: (item: TodoItem, completed: boolean) => void;
}

export function TodoItemRow({ item, compact = false, showKind = true, onToggle }: TodoItemRowProps) {
    const [completed, setCompleted] = useState(item.is_completed);
    const [busy, setBusy] = useState(false);

    const dueLabel = formatTodoDue(item);
    const isTaskLike = item.source !== 'personal';

    const handleToggle = (checked: boolean) => {
        if (!item.can_complete || busy) {
            return;
        }

        const previous = completed;
        setCompleted(checked);
        setBusy(true);
        onToggle?.(item, checked);

        router.patch(
            checked ? `/tasks/personal-todos/${item.id}/complete` : `/tasks/personal-todos/${item.id}/reopen`,
            {},
            {
                preserveScroll: true,
                onError: () => setCompleted(previous),
                onFinish: () => setBusy(false),
            },
        );
    };

    const content = (
        <div className={cn('flex min-w-0 items-start gap-3', compact ? 'py-2' : 'py-2.5')}>
            {item.can_complete ? (
                <Checkbox
                    checked={completed}
                    disabled={busy}
                    onCheckedChange={(value) => handleToggle(value === true)}
                    className="mt-0.5 shrink-0"
                    aria-label={completed ? 'Mark todo pending' : 'Mark todo complete'}
                />
            ) : (
                <span className="mt-1 size-4 shrink-0 rounded border border-transparent" aria-hidden />
            )}

            <div className="min-w-0 flex-1 space-y-1">
                <div className="flex min-w-0 flex-wrap items-center gap-2">
                    <p
                        className={cn(
                            'min-w-0 text-sm font-medium break-words',
                            completed && 'text-muted-foreground line-through',
                            item.priority === 'urgent' && !completed && 'text-red-700 dark:text-red-400',
                        )}
                    >
                        {item.title}
                    </p>
                    {showKind && (
                        <Badge variant={isTaskLike ? 'info' : 'outline'} className="shrink-0 text-[10px] uppercase tracking-wide">
                            {item.kind_label}
                        </Badge>
                    )}
                </div>

                {(item.subtitle || item.note) && (
                    <p className="text-muted-foreground text-xs break-words">{item.subtitle ?? item.note}</p>
                )}

                {item.checklist && (
                    <p className="text-muted-foreground text-xs">
                        {item.checklist.completed}/{item.checklist.total} checklist items completed
                    </p>
                )}

                {item.subtasks.length > 0 && (
                    <ul className="text-muted-foreground space-y-1 border-l pl-3 text-xs">
                        {item.subtasks.map((subtask) => (
                            <li key={subtask.key} className="break-words">
                                ↳ {subtask.title}
                            </li>
                        ))}
                    </ul>
                )}

                <div className="flex flex-wrap items-center gap-2">
                    {!completed && item.priority !== 'normal' && (
                        <PriorityBadge priority={item.priority} label={item.priority_label} />
                    )}
                    {dueLabel && (
                        <span className={cn('inline-flex items-center gap-1 text-xs', item.is_overdue ? 'text-destructive font-medium' : 'text-muted-foreground')}>
                            <CalendarClock className="size-3 shrink-0" />
                            {item.is_overdue ? 'Overdue · ' : 'Due '}
                            {dueLabel}
                        </span>
                    )}
                    {completed && <span className="text-muted-foreground text-xs">Completed</span>}
                </div>

                {item.can_move_to_today && !completed && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-7 px-2 text-xs"
                        onClick={() =>
                            router.patch(`/tasks/personal-todos/${item.id}/move-to-today`, {}, { preserveScroll: true })
                        }
                    >
                        Move to today
                    </Button>
                )}
            </div>

            {isTaskLike && (
                <ArrowRight className="text-muted-foreground mt-1 size-4 shrink-0" aria-hidden />
            )}
        </div>
    );

    if (isTaskLike) {
        return (
            <Link href={item.href} className="hover:bg-muted/40 block rounded-lg px-2 transition-colors">
                {content}
            </Link>
        );
    }

    return <div className="rounded-lg px-2">{content}</div>;
}
