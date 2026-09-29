import { AssessmentHttpError, putJson } from '@/components/recruiter-operations/assessments/assessment-http';
import { formatClock } from '@/components/recruiter-operations/assessments/assessment-ui';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Clock, LoaderCircle } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

interface LearnerQuestion {
    id: number;
    number: number;
    type: 'single_choice' | 'multiple_choice' | 'true_false' | 'short_answer';
    prompt: string;
    points: number;
    options: { id: number; text: string }[];
    answer: { option_ids: number[]; text: string };
}

interface Props {
    assessment: { id: number; title: string };
    assignmentId: number;
    version: { label: string; instructions: string | null; time_limit_minutes: number | null; passing_percentage: number };
    attempt: { id: number; attempt_number: number; started_at: string; expires_at: string | null; seconds_remaining: number | null };
    questions: LearnerQuestion[];
    urls: { save: string; submit: string };
}

type Answers = Record<number, { option_ids: number[]; text: string }>;
type SaveState = 'idle' | 'saving' | 'saved' | 'error';

const AUTOSAVE_DELAY_MS = 1500;
const AUTOSAVE_INTERVAL_MS = 30000;

function isAnswered(question: LearnerQuestion, answers: Answers): boolean {
    const answer = answers[question.id];

    return question.type === 'short_answer' ? (answer?.text ?? '').trim() !== '' : (answer?.option_ids.length ?? 0) > 0;
}

export default function TakeAssessment({ assessment, assignmentId, version, attempt, questions, urls }: Props) {
    const [answers, setAnswers] = useState<Answers>(() =>
        Object.fromEntries(questions.map((question) => [question.id, { option_ids: question.answer.option_ids, text: question.answer.text }])),
    );
    const [saveState, setSaveState] = useState<SaveState>('idle');
    const [saveError, setSaveError] = useState<string | null>(null);
    const [savedAt, setSavedAt] = useState<Date | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [remaining, setRemaining] = useState<number | null>(attempt.seconds_remaining);

    const dirty = useRef(false);
    const answersRef = useRef(answers);
    const deadline = useRef<number | null>(attempt.seconds_remaining !== null ? Date.now() + attempt.seconds_remaining * 1000 : null);
    const submittedRef = useRef(false);

    answersRef.current = answers;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: assessment.title, href: `/recruiter/assessments/${assignmentId}` },
        { title: `Attempt ${attempt.attempt_number}`, href: `/recruiter/assessments/attempts/${attempt.id}` },
    ];

    const submit = useCallback(() => {
        if (submittedRef.current) {
            return;
        }

        submittedRef.current = true;
        setSubmitting(true);
        router.post(
            urls.submit,
            { answers: answersRef.current },
            {
                onError: () => {
                    submittedRef.current = false;
                    setSubmitting(false);
                },
            },
        );
    }, [urls.submit]);

    const save = useCallback(async () => {
        if (!dirty.current || submittedRef.current) {
            return;
        }

        dirty.current = false;
        setSaveState('saving');

        try {
            const response = await putJson<{ saved_at: string; seconds_remaining: number | null }>(urls.save, { answers: answersRef.current });

            if (response.seconds_remaining !== null) {
                deadline.current = Date.now() + response.seconds_remaining * 1000;
            }

            setSavedAt(new Date(response.saved_at));
            setSaveState('saved');
            setSaveError(null);
        } catch (error) {
            dirty.current = true;
            setSaveState('error');
            setSaveError(error instanceof Error ? error.message : 'Your answers could not be saved.');

            if (error instanceof AssessmentHttpError && error.status === 422) {
                submittedRef.current = true;
                router.reload();
            }
        }
    }, [urls.save]);

    useEffect(() => {
        if (!dirty.current) {
            return;
        }

        const timer = window.setTimeout(() => void save(), AUTOSAVE_DELAY_MS);

        return () => window.clearTimeout(timer);
    }, [answers, save]);

    useEffect(() => {
        const interval = window.setInterval(() => void save(), AUTOSAVE_INTERVAL_MS);

        return () => window.clearInterval(interval);
    }, [save]);

    useEffect(() => {
        if (deadline.current === null) {
            return;
        }

        const tick = window.setInterval(() => {
            const left = Math.max(0, Math.round(((deadline.current ?? Date.now()) - Date.now()) / 1000));
            setRemaining(left);

            if (left <= 0) {
                window.clearInterval(tick);
                submit();
            }
        }, 1000);

        return () => window.clearInterval(tick);
    }, [submit]);

    useEffect(() => {
        const warn = (event: BeforeUnloadEvent) => {
            if (dirty.current && !submittedRef.current) {
                event.preventDefault();
            }
        };

        window.addEventListener('beforeunload', warn);

        return () => window.removeEventListener('beforeunload', warn);
    }, []);

    const update = (questionId: number, change: Partial<{ option_ids: number[]; text: string }>) => {
        dirty.current = true;
        setAnswers((current) => ({ ...current, [questionId]: { ...current[questionId], ...change } }));
    };

    const unanswered = useMemo(() => questions.filter((question) => !isAnswered(question, answers)).length, [questions, answers]);
    const lowTime = remaining !== null && remaining <= 60;

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`${assessment.title} · Attempt ${attempt.attempt_number}`} />

            <div className="flex flex-1 flex-col gap-4 p-4 md:p-6">
                <div className="bg-background/95 sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[rgba(120,115,110,0.14)] px-4 py-3 backdrop-blur">
                    <div className="min-w-0">
                        <h1 className="truncate text-lg font-semibold">{assessment.title}</h1>
                        <p className="text-muted-foreground text-xs">
                            Attempt {attempt.attempt_number} · {questions.length - unanswered} of {questions.length} answered · Pass mark {version.passing_percentage}%
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="text-muted-foreground text-xs" aria-live="polite">
                            {saveState === 'saving' && 'Saving…'}
                            {saveState === 'saved' && savedAt && `Saved ${savedAt.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })}`}
                            {saveState === 'error' && <span className="text-destructive">{saveError}</span>}
                        </span>
                        {remaining !== null && (
                            <span
                                className={cn(
                                    'flex items-center gap-1.5 rounded-md border px-2.5 py-1 font-mono text-sm tabular-nums',
                                    lowTime ? 'border-red-300 bg-red-50 text-red-700' : 'border-[rgba(120,115,110,0.2)]',
                                )}
                                role="timer"
                                aria-label="Time remaining"
                            >
                                <Clock className="size-3.5" /> {formatClock(remaining)}
                            </span>
                        )}
                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button disabled={submitting}>
                                    {submitting && <LoaderCircle className="animate-spin" />}
                                    Submit
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Submit your answers?</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        {unanswered > 0
                                            ? `${unanswered} question${unanswered === 1 ? ' is' : 's are'} unanswered and will score 0. `
                                            : 'All questions are answered. '}
                                        You cannot change your answers after submitting.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Keep working</AlertDialogCancel>
                                    <AlertDialogAction onClick={submit}>Submit</AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </div>

                {version.instructions && <div className="bg-muted/40 rounded-lg p-3 text-sm whitespace-pre-line">{version.instructions}</div>}

                <div className="space-y-4">
                    {questions.map((question) => {
                        const answer = answers[question.id];

                        return (
                            <Card key={question.id} id={`question-${question.id}`}>
                                <CardContent className="space-y-3 pt-6">
                                    <div className="flex items-start justify-between gap-3">
                                        <p className="text-sm font-medium whitespace-pre-line">
                                            <span className="text-muted-foreground mr-1.5">{question.number}.</span>
                                            {question.prompt}
                                        </p>
                                        <span className="text-muted-foreground shrink-0 text-xs">
                                            {question.points} {question.points === 1 ? 'pt' : 'pts'}
                                        </span>
                                    </div>

                                    {question.type === 'multiple_choice' && <p className="text-muted-foreground text-xs">Select all that apply.</p>}

                                    {question.type === 'short_answer' ? (
                                        <Textarea
                                            value={answer.text}
                                            onChange={(event) => update(question.id, { text: event.target.value })}
                                            rows={4}
                                            maxLength={5000}
                                            placeholder="Type your answer"
                                            aria-label={`Answer to question ${question.number}`}
                                        />
                                    ) : question.type === 'multiple_choice' ? (
                                        <div className="space-y-1.5">
                                            {question.options.map((option) => (
                                                <label
                                                    key={option.id}
                                                    className={cn(
                                                        'flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2 text-sm transition-colors',
                                                        answer.option_ids.includes(option.id) ? 'border-fuchsia-500 bg-fuchsia-50' : 'hover:bg-muted/50',
                                                    )}
                                                >
                                                    <Checkbox
                                                        checked={answer.option_ids.includes(option.id)}
                                                        onCheckedChange={(checked) =>
                                                            update(question.id, {
                                                                option_ids:
                                                                    checked === true
                                                                        ? [...answer.option_ids, option.id]
                                                                        : answer.option_ids.filter((id) => id !== option.id),
                                                            })
                                                        }
                                                    />
                                                    <span>{option.text}</span>
                                                </label>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="space-y-1.5" role="radiogroup" aria-label={`Question ${question.number}`}>
                                            {question.options.map((option) => (
                                                <label
                                                    key={option.id}
                                                    className={cn(
                                                        'flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2 text-sm transition-colors',
                                                        answer.option_ids.includes(option.id) ? 'border-fuchsia-500 bg-fuchsia-50' : 'hover:bg-muted/50',
                                                    )}
                                                >
                                                    <input
                                                        type="radio"
                                                        name={`question-${question.id}`}
                                                        className="mt-0.5 accent-fuchsia-600"
                                                        checked={answer.option_ids.includes(option.id)}
                                                        onChange={() => update(question.id, { option_ids: [option.id] })}
                                                    />
                                                    <span>{option.text}</span>
                                                </label>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </RecruiterLayout>
    );
}
