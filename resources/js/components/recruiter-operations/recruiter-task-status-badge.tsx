import { Badge } from '@/components/ui/badge';

type Tone = 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'outline';

const STATUS_TONE: Record<string, Tone> = {
    assigned: 'warning',
    in_progress: 'info',
    on_hold: 'neutral',
    completed: 'success',
    declined: 'danger',
    cancelled: 'outline',
};

const PRIORITY_CLASS: Record<string, string> = {
    urgent: 'border-transparent bg-red-500/10 text-red-700 dark:text-red-400',
    high: 'border-transparent bg-amber-500/10 text-amber-700 dark:text-amber-400',
    normal: '',
    low: 'text-muted-foreground',
};

export function RecruiterTaskStatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={STATUS_TONE[status] ?? 'neutral'}>{label}</Badge>;
}

export function RecruiterTaskPriorityBadge({ priority, label }: { priority: string; label: string }) {
    return (
        <Badge variant={priority === 'normal' ? 'neutral' : 'outline'} className={PRIORITY_CLASS[priority] ?? ''}>
            {label}
        </Badge>
    );
}

export function formatRecruiterDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

/**
 * Overdue only matters while the recruiter still owes the work.
 */
export function RecruiterDueDate({ value, open }: { value: string | null; open: boolean }) {
    if (!value) {
        return <span className="text-muted-foreground">—</span>;
    }

    const overdue = open && new Date(value).getTime() < Date.now();

    return <span className={overdue ? 'text-destructive font-medium' : undefined}>{formatRecruiterDateTime(value)}</span>;
}
