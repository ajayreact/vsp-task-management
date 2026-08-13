import { Badge } from '@/components/ui/badge';

/**
 * Status and priority read as colour on every task screen, so the mapping lives
 * in one place rather than being repeated per list.
 */

const STATUS_TONE: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'outline',
    open: 'secondary',
    assigned: 'secondary',
    accepted: 'default',
    in_progress: 'default',
    in_review: 'secondary',
    revision: 'destructive',
    approved: 'default',
    completed: 'outline',
};

const PRIORITY_CLASS: Record<string, string> = {
    urgent: 'border-destructive/50 text-destructive',
    high: 'border-amber-500/50 text-amber-600 dark:text-amber-400',
    normal: '',
    low: 'text-muted-foreground',
};

export function StatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={STATUS_TONE[status] ?? 'outline'}>{label}</Badge>;
}

export function PriorityBadge({ priority, label }: { priority: string; label: string }) {
    return (
        <Badge variant="outline" className={PRIORITY_CLASS[priority] ?? ''}>
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
