import { Badge } from '@/components/ui/badge';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';

type Tone = 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'outline';

const RESULT_TONE: Record<string, Tone> = {
    passed: 'success',
    failed: 'danger',
    pending_review: 'warning',
};

const ASSIGNMENT_TONE: Record<string, Tone> = {
    assigned: 'warning',
    in_progress: 'info',
    completed: 'success',
    overdue: 'danger',
};

export function ResultBadge({ result, label }: { result: string | null; label: string | null }) {
    if (!result || !label) {
        return <span className="text-muted-foreground text-sm">—</span>;
    }

    return <Badge variant={RESULT_TONE[result] ?? 'neutral'}>{label}</Badge>;
}

export function AssignmentStatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={ASSIGNMENT_TONE[status] ?? 'neutral'}>{label}</Badge>;
}

export function QuestionTypeBadge({ label }: { label: string }) {
    return <Badge variant="outline">{label}</Badge>;
}

export interface Eligibility {
    can_start: boolean;
    reason: string | null;
    attempts_used: number;
    attempts_allowed: number;
    attempts_left: number;
    active_attempt_id: number | null;
}

export interface LearnerAssignment {
    id: number;
    assessment: { id: number; title: string; description: string | null };
    version: {
        id: number;
        label: string;
        passing_percentage: number;
        time_limit_minutes: number | null;
        max_attempts: number;
        instructions: string | null;
        question_count: number;
    };
    employee: { id: number; name: string };
    assigned_by: string | null;
    from_training: boolean;
    status: string;
    status_label: string;
    result: string | null;
    result_label: string | null;
    due_at: string | null;
    assigned_at: string;
    completed_at: string | null;
    attempts_count: number | null;
    latest_attempt_id: number | null;
    eligibility: Eligibility | null;
}

/**
 * An attempt's outcome as the server allows it to be shown. For recruiters,
 * scores appear only when the quiz shows results, and the per-question review
 * (with correct options) only when it allows review.
 */
export interface AttemptResult {
    id: number;
    attempt_number: number;
    status: string;
    status_label: string;
    started_at: string;
    submitted_at: string | null;
    auto_submitted: boolean;
    result: string | null;
    result_label: string | null;
    pending_review: boolean;
    show_score: boolean;
    show_review: boolean;
    total_points: number | null;
    awarded_points: number | null;
    percentage: number | null;
    passing_percentage: number;
    questions: ReviewedQuestion[];
}

export interface ReviewedQuestion {
    id: number;
    number: number;
    type: string;
    type_label: string;
    prompt: string;
    points: number;
    explanation: string | null;
    options: { id: number; text: string; is_correct: boolean; selected: boolean }[];
    answer: {
        id: number | null;
        text: string | null;
        is_correct: boolean | null;
        awarded_points: number | null;
        needs_review: boolean;
        awaiting_review: boolean;
        reviewer: string | null;
        reviewed_at: string | null;
        reviewer_feedback: string | null;
    };
}

export interface ManagerQuestion {
    id: number;
    type: string;
    type_label: string;
    prompt: string;
    points: number;
    explanation: string | null;
    category: string | null;
    sort_order: number;
    source: string;
    source_label: string;
    import_batch: string | null;
    import_row: number | null;
    archived: boolean;
    options: { id: number; key: string; text: string; is_correct: boolean }[];
}

/**
 * A question with its answer key, for managers only.
 */
export function ManagerQuestionView({ question, number }: { question: ManagerQuestion; number?: number }) {
    return (
        <div className="space-y-2">
            <div className="flex flex-wrap items-start gap-2">
                {number !== undefined && <span className="text-muted-foreground text-sm font-medium">{number}.</span>}
                <p className="min-w-0 flex-1 text-sm font-medium whitespace-pre-line">{question.prompt}</p>
            </div>
            <div className="flex flex-wrap gap-1.5">
                <QuestionTypeBadge label={question.type_label} />
                <Badge variant="neutral">
                    {question.points} {question.points === 1 ? 'point' : 'points'}
                </Badge>
                {question.category && <Badge variant="outline">{question.category}</Badge>}
            </div>
            {question.options.length > 0 ? (
                <ul className="space-y-1">
                    {question.options.map((option) => (
                        <li
                            key={option.id}
                            className={cn(
                                'flex items-start gap-2 rounded-md border px-2.5 py-1.5 text-sm',
                                option.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : 'border-[rgba(120,115,110,0.14)]',
                            )}
                        >
                            <span className="font-medium">{option.key}.</span>
                            <span className="flex-1">{option.text}</span>
                            {option.is_correct && <span className="text-xs font-medium">Correct</span>}
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="text-muted-foreground text-xs">Short answer. Reviewed manually; never marked automatically.</p>
            )}
            {question.explanation && <p className="text-muted-foreground text-xs whitespace-pre-line">Explanation: {question.explanation}</p>}
        </div>
    );
}

export function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

export function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

export function formatClock(seconds: number): string {
    const safe = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(safe / 3600);
    const minutes = Math.floor((safe % 3600) / 60);
    const rest = safe % 60;
    const pad = (value: number) => String(value).padStart(2, '0');

    return hours > 0 ? `${hours}:${pad(minutes)}:${pad(rest)}` : `${pad(minutes)}:${pad(rest)}`;
}

/**
 * Assessment tabs. Hidden tabs are also refused by the server.
 */
export function AssessmentSubNav() {
    const { can } = usePermissions();
    const { url } = usePage();
    const path = url.split('?')[0];

    const tabs = [
        { label: 'My Quizzes', href: '/recruiter/assessments', exact: true, show: true },
        { label: 'Assignments', href: '/recruiter/assessments/assignments', show: can('recruiter.assessments.invite') },
        { label: 'Results', href: '/recruiter/assessments/results', show: can('recruiter.assessments.review') },
        { label: 'Manage', href: '/recruiter/assessments/manage', show: can('recruiter.assessments.manage') },
        { label: 'Question Bank', href: '/recruiter/assessments/questions', show: can('recruiter.assessments.manage') },
    ].filter((tab) => tab.show);

    if (tabs.length < 2) {
        return null;
    }

    return (
        <nav className="flex flex-wrap gap-1 border-b border-[rgba(120,115,110,0.14)]" aria-label="Assessment sections">
            {tabs.map((tab) => {
                const active = tab.exact ? path === tab.href : path === tab.href || path.startsWith(`${tab.href}/`);

                return (
                    <Link
                        key={tab.href}
                        href={tab.href}
                        className={cn(
                            '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                            active ? 'border-fuchsia-600 text-foreground' : 'text-muted-foreground hover:text-foreground border-transparent',
                        )}
                        aria-current={active ? 'page' : undefined}
                    >
                        {tab.label}
                    </Link>
                );
            })}
        </nav>
    );
}
