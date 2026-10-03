import { Badge } from '@/components/ui/badge';

type Tone = 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'outline';

const REVIEW_TONE: Record<string, Tone> = {
    needs_review: 'warning',
    in_review: 'info',
    changes_requested: 'danger',
    approved: 'success',
    outdated: 'danger',
};

const COMPLIANCE_TONE: Record<string, Tone> = {
    not_required: 'outline',
    pending: 'warning',
    changes_requested: 'danger',
    approved: 'success',
};

export function ReviewStatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={REVIEW_TONE[status] ?? 'neutral'}>{label}</Badge>;
}

export function ComplianceBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={COMPLIANCE_TONE[status] ?? 'neutral'}>{label}</Badge>;
}

export function WordCount({ words, overTarget, target }: { words: number; overTarget: boolean; target: number }) {
    return (
        <span className="inline-flex items-center gap-1.5 text-sm tabular-nums">
            {words.toLocaleString()}
            {overTarget && (
                <Badge variant="warning" title={`Longer than the ${target}-word target. Shorten it by hand if needed; nothing is cut automatically.`}>
                    Over target
                </Badge>
            )}
        </span>
    );
}
