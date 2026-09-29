import { ResultBadge, formatDateTime, type AttemptResult, type ReviewedQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

export function AttemptSummary({ result }: { result: AttemptResult }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex flex-wrap items-center gap-2">
                    Attempt {result.attempt_number}
                    <ResultBadge result={result.result} label={result.result_label} />
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {result.show_score ? (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <Figure label="Score" value={result.percentage !== null ? `${result.percentage}%` : result.pending_review ? 'Awaiting review' : '—'} />
                        <Figure
                            label="Points"
                            value={result.awarded_points !== null && result.total_points !== null ? `${result.awarded_points} / ${result.total_points}` : '—'}
                        />
                        <Figure label="Pass mark" value={`${result.passing_percentage}%`} />
                        <Figure label="Submitted" value={formatDateTime(result.submitted_at)} />
                    </div>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        Your answers were submitted {formatDateTime(result.submitted_at)}. Scores for this quiz are not shown to recruiters.
                    </p>
                )}
                {result.pending_review && (
                    <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        Some answers are waiting for a reviewer. The final result appears once they are scored.
                    </p>
                )}
                {result.auto_submitted && <p className="text-muted-foreground text-xs">Submitted automatically when the time ran out.</p>}
            </CardContent>
        </Card>
    );
}

function Figure({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-3.5 py-3">
            <div className="text-foreground text-lg font-semibold tabular-nums">{value}</div>
            <div className="text-muted-foreground text-xs">{label}</div>
        </div>
    );
}

/**
 * Question-by-question review. `renderReview` lets reviewers add a scoring
 * form under short answers.
 */
export function ReviewedQuestionList({ questions, renderReview }: { questions: ReviewedQuestion[]; renderReview?: (question: ReviewedQuestion) => ReactNode }) {
    return (
        <div className="space-y-4">
            {questions.map((question) => (
                <Card key={question.id}>
                    <CardContent className="space-y-3 pt-6">
                        <div className="flex items-start justify-between gap-3">
                            <p className="text-sm font-medium whitespace-pre-line">
                                <span className="text-muted-foreground mr-1.5">{question.number}.</span>
                                {question.prompt}
                            </p>
                            <QuestionOutcome question={question} />
                        </div>

                        {question.type === 'short_answer' ? (
                            <div className="space-y-2">
                                <div className="bg-muted/40 rounded-lg p-3 text-sm whitespace-pre-line">
                                    {question.answer.text || <span className="text-muted-foreground">No answer given.</span>}
                                </div>
                                {question.answer.reviewer_feedback && (
                                    <p className="text-sm">
                                        <span className="text-muted-foreground">Reviewer feedback: </span>
                                        {question.answer.reviewer_feedback}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <ul className="space-y-1">
                                {question.options.map((option) => (
                                    <li
                                        key={option.id}
                                        className={cn(
                                            'flex items-start gap-2 rounded-md border px-2.5 py-1.5 text-sm',
                                            option.is_correct && 'border-emerald-300 bg-emerald-50',
                                            option.selected && !option.is_correct && 'border-red-300 bg-red-50',
                                        )}
                                    >
                                        <span className="flex-1">{option.text}</span>
                                        {option.selected && <span className="text-xs font-medium">Your choice</span>}
                                        {option.is_correct && <span className="text-xs font-medium text-emerald-700">Correct</span>}
                                    </li>
                                ))}
                            </ul>
                        )}

                        {question.explanation && <p className="text-muted-foreground text-xs whitespace-pre-line">Explanation: {question.explanation}</p>}
                        {renderReview?.(question)}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function QuestionOutcome({ question }: { question: ReviewedQuestion }) {
    if (question.answer.awaiting_review) {
        return <Badge variant="warning">Awaiting review</Badge>;
    }

    const points = question.answer.awarded_points ?? 0;

    return (
        <Badge variant={points >= question.points ? 'success' : points > 0 ? 'info' : 'danger'}>
            {points} / {question.points}
        </Badge>
    );
}
