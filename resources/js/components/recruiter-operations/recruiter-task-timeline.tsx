import { formatRecruiterDateTime } from '@/components/recruiter-operations/recruiter-task-status-badge';

export interface RecruiterTaskTimelineEntry {
    id: number;
    event: string;
    event_label: string;
    actor_name: string | null;
    from_status: string | null;
    from_status_label: string | null;
    to_status: string | null;
    to_status_label: string | null;
    from_employee_name: string | null;
    to_employee_name: string | null;
    reason: string | null;
    details: string | null;
    occurred_at: string;
}

export function RecruiterTaskTimeline({ entries }: { entries: RecruiterTaskTimelineEntry[] }) {
    if (entries.length === 0) {
        return <p className="text-muted-foreground text-sm">No history yet.</p>;
    }

    return (
        <ol className="space-y-4">
            {entries.map((entry) => (
                <li key={entry.id} className="border-l-2 border-[rgba(120,115,110,0.2)] pl-4">
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <span className="text-sm font-medium">{entry.event_label}</span>
                        <time className="text-muted-foreground text-xs" dateTime={entry.occurred_at}>
                            {formatRecruiterDateTime(entry.occurred_at)}
                        </time>
                    </div>
                    <p className="text-muted-foreground text-xs">by {entry.actor_name ?? 'a removed user'}</p>

                    {(entry.from_status_label || entry.to_status_label) && (
                        <p className="mt-1 text-sm">
                            Status: {entry.from_status_label ?? '—'} → {entry.to_status_label ?? '—'}
                        </p>
                    )}

                    {(entry.from_employee_name || entry.to_employee_name) && (
                        <p className="mt-1 text-sm">
                            Recruiter: {entry.from_employee_name ?? '—'} → {entry.to_employee_name ?? '—'}
                        </p>
                    )}

                    {entry.details && <p className="mt-1 text-sm">{entry.details}</p>}
                    {entry.reason && <p className="text-muted-foreground mt-1 text-sm italic">“{entry.reason}”</p>}
                </li>
            ))}
        </ol>
    );
}
