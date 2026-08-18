import { Badge } from '@/components/ui/badge';

/**
 * Status and priority read as colour on every task screen, so the mapping lives
 * in one place rather than being repeated per list.
 */

const STATUS_TONE: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'outline'> = {
    draft: 'neutral',
    open: 'info',
    assigned: 'warning',
    accepted: 'info',
    in_progress: 'info',
    in_review: 'warning',
    revision: 'danger',
    approved: 'success',
    completed: 'success',
};

const PRIORITY_CLASS: Record<string, string> = {
    urgent: 'border-transparent bg-red-500/10 text-red-700 dark:text-red-400',
    high: 'border-transparent bg-amber-500/10 text-amber-700 dark:text-amber-400',
    normal: '',
    low: 'text-muted-foreground',
};

export function StatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={STATUS_TONE[status] ?? 'neutral'}>{label}</Badge>;
}

export function PriorityBadge({ priority, label }: { priority: string; label: string }) {
    return (
        <Badge variant={priority === 'normal' ? 'neutral' : 'outline'} className={PRIORITY_CLASS[priority] ?? ''}>
            {label}
        </Badge>
    );
}

export function DueDate({ value }: { value: string | null }) {
    if (!value) {
        return <span className="text-muted-foreground">—</span>;
    }

    const due = new Date(value);
    const overdue = due.getTime() < Date.now();

    return (
        <span className={overdue ? 'text-destructive font-medium' : undefined}>
            {due.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })}
        </span>
    );
}
