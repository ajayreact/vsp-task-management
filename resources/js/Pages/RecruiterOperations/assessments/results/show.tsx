import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { AttemptSummary, ReviewedQuestionList } from '@/components/recruiter-operations/assessments/attempt-review';
import { formatDateTime, type AttemptResult, type ReviewedQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

interface Props {
    assessment: { id: number; title: string };
    version: string;
    recruiter: string;
    result: AttemptResult;
    canReview: boolean;
}

export default function AttemptResultForReviewer({ assessment, version, recruiter, result, canReview }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: 'Results', href: '/recruiter/assessments/results' },
        { title: `${recruiter} · Attempt ${result.attempt_number}`, href: `/recruiter/assessments/results/attempts/${result.id}` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`${assessment.title} · ${recruiter}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title={`${assessment.title} · ${version}`} description={`${recruiter}'s attempt ${result.attempt_number}`} />

                <AttemptSummary result={result} />

                <ReviewedQuestionList
                    questions={result.questions}
                    renderReview={(question) => (question.answer.needs_review ? <ReviewForm attemptId={result.id} question={question} canReview={canReview} /> : null)}
                />
            </div>
        </RecruiterLayout>
    );
}

function ReviewForm({ attemptId, question, canReview }: { attemptId: number; question: ReviewedQuestion; canReview: boolean }) {
    const { data, setData, post, processing, errors } = useForm<{ points: number | ''; feedback: string }>({
        points: question.answer.awarded_points ?? '',
        feedback: question.answer.reviewer_feedback ?? '',
    });

    if (question.answer.id === null) {
        return null;
    }

    const reviewed = question.answer.reviewed_at !== null;

    if (!canReview) {
        return reviewed ? (
            <p className="text-muted-foreground text-xs">
                Scored by {question.answer.reviewer ?? 'a reviewer'} on {formatDateTime(question.answer.reviewed_at)}.
            </p>
        ) : null;
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(`/recruiter/assessments/results/attempts/${attemptId}/answers/${question.answer.id}/review`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="bg-muted/30 space-y-3 rounded-lg border p-3">
            <div className="flex flex-wrap items-end gap-3">
                <div className="grid gap-1.5">
                    <Label htmlFor={`points-${question.id}`}>Points (0–{question.points})</Label>
                    <Input
                        id={`points-${question.id}`}
                        type="number"
                        min={0}
                        max={question.points}
                        className="w-28"
                        value={data.points}
                        onChange={(event) => setData('points', event.target.value === '' ? '' : Number(event.target.value))}
                        required
                    />
                </div>
                <Button type="submit" size="sm" disabled={processing || data.points === ''}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    {reviewed ? 'Update score' : 'Save score'}
                </Button>
                {reviewed && (
                    <span className="text-muted-foreground text-xs">
                        Last scored by {question.answer.reviewer ?? 'a reviewer'} on {formatDateTime(question.answer.reviewed_at)}
                    </span>
                )}
            </div>
            <InputError message={errors.points} />
            <div className="grid gap-1.5">
                <Label htmlFor={`feedback-${question.id}`}>Feedback for the recruiter</Label>
                <Textarea id={`feedback-${question.id}`} rows={2} value={data.feedback} onChange={(event) => setData('feedback', event.target.value)} />
                <InputError message={errors.feedback} />
            </div>
        </form>
    );
}
