import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { LessonSections, type LessonSection } from '@/components/recruiter-operations/training/training-lesson-sections';
import { ComplianceBadge, ReviewStatusBadge, WordCount } from '@/components/recruiter-operations/training/training-review';
import { TrainingSubNav, formatTrainingDate } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, ChevronLeft, ChevronRight, Eye, Info, Lock, Pencil, ShieldCheck, Undo2 } from 'lucide-react';
import { useState } from 'react';

interface LanguageState {
    status: string;
    label: string;
    outdated: boolean;
    words: number;
    over_target: boolean;
    reviewer: string | null;
    reviewed_at: string | null;
    note: string | null;
    updated_at: string | null;
    sections: LessonSection[] | null;
}

interface Props {
    language: string;
    lesson: {
        id: number;
        title: string;
        course: { id: number; title: string };
        level: number | null;
        version: { id: number; label: string };
        english: LanguageState | null;
        translation: LanguageState | null;
        translation_label: string | null;
        compliance: { status: string; label: string; required: boolean; reviewer: string | null; reviewed_at: string | null; note: string | null };
    };
    navigation: { position: number; total: number; previous: { id: number; title: string } | null; next: { id: number; title: string } | null };
    overTargetWords: number;
    can: { review: boolean };
}

const COMPLIANCE_CHOICES = [
    { value: 'not_required', label: 'Not required' },
    { value: 'pending', label: 'Required: pending review' },
    { value: 'approved', label: 'Approved' },
    { value: 'changes_requested', label: 'Changes requested' },
];

function ReviewedBy({ state }: { state: { reviewer: string | null; reviewed_at: string | null; note: string | null } }) {
    if (!state.reviewer && !state.note) {
        return null;
    }

    return (
        <div className="text-muted-foreground space-y-1 text-xs">
            {state.reviewer && (
                <p>
                    By {state.reviewer}
                    {state.reviewed_at ? ` · ${formatTrainingDate(state.reviewed_at)}` : ''}
                </p>
            )}
            {state.note && <p className="text-foreground rounded-md border bg-amber-50/60 p-2 whitespace-pre-line dark:bg-amber-950/20">{state.note}</p>}
        </div>
    );
}

/**
 * English and translation side by side, section by section in the same order.
 */
function SideBySide({ english, translation, translationLabel, code }: { english: LessonSection[]; translation: LessonSection[]; translationLabel: string; code: string }) {
    const rows = Math.max(english.length, translation.length);

    return (
        <div className="space-y-4">
            <div className="text-muted-foreground hidden grid-cols-2 gap-4 text-xs font-semibold tracking-wide uppercase lg:grid">
                <span>English (source)</span>
                <span>{translationLabel}</span>
            </div>
            {Array.from({ length: rows }, (_, index) => (
                <div key={index} className="grid gap-4 lg:grid-cols-2">
                    <div className="min-w-0">
                        <p className="text-muted-foreground mb-1 text-xs lg:hidden">English</p>
                        {english[index] ? (
                            <LessonSections sections={[english[index]]} lang="en" />
                        ) : (
                            <p className="text-muted-foreground rounded-xl border border-dashed p-4 text-sm">No matching English section.</p>
                        )}
                    </div>
                    <div className="min-w-0">
                        <p className="text-muted-foreground mb-1 text-xs lg:hidden">{translationLabel}</p>
                        {translation[index] ? (
                            <LessonSections sections={[translation[index]]} lang={code} />
                        ) : (
                            <p className="text-muted-foreground rounded-xl border border-dashed p-4 text-sm">No matching {translationLabel} section.</p>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function TrainingContentReviewShow({ language, lesson, navigation, overTargetWords, can }: Props) {
    const { errors } = usePage<{ errors: Record<string, string | undefined> }>().props;
    const [note, setNote] = useState('');
    const [processing, setProcessing] = useState(false);
    const [complianceStatus, setComplianceStatus] = useState(lesson.compliance.status);
    const [complianceNote, setComplianceNote] = useState(lesson.compliance.note ?? '');

    const canonical = language === 'en';
    const reviewed = canonical ? lesson.english : lesson.translation;
    const languageLabel = canonical ? 'English' : (lesson.translation_label ?? language);
    const englishApproved = lesson.english?.status === 'approved';
    const base = `/recruiter/training/manage/review/lessons/${lesson.id}`;
    const contentUrl = `/recruiter/training/manage/lessons/${lesson.id}/content?language=${language}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: 'Content Review', href: `/recruiter/training/manage/review?language=${language}` },
        { title: lesson.title, href: `${base}/${language}` },
    ];

    const decide = (status: string, next = false) => {
        router.post(
            `${base}/${language}`,
            { status, note: note.trim() || null, next },
            {
                preserveScroll: !next,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => setNote(''),
            },
        );
    };

    const saveCompliance = () => {
        router.post(
            `${base}/compliance`,
            { status: complianceStatus, note: complianceNote.trim() || null },
            { preserveScroll: true, onStart: () => setProcessing(true), onFinish: () => setProcessing(false) },
        );
    };

    const go = (target: { id: number } | null) => target && router.get(`/recruiter/training/manage/review/lessons/${target.id}/${language}`);

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Review: ${lesson.title}`} />

            <div className="flex max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />
                <PageHeader
                    title={lesson.title}
                    description={[lesson.level ? `Level ${lesson.level}` : null, lesson.course.title, `${lesson.version.label} draft`, `${languageLabel} review`]
                        .filter(Boolean)
                        .join(' · ')}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <Button asChild size="sm" variant="outline">
                                <Link href={`/recruiter/training/manage/review?language=${language}`}>
                                    <ArrowLeft /> All lessons
                                </Link>
                            </Button>
                            <Button asChild size="sm" variant="outline">
                                <Link href={`/recruiter/training/manage/lessons/${lesson.id}`}>
                                    <Eye /> Preview
                                </Link>
                            </Button>
                            {can.review && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={contentUrl}>
                                        <Pencil /> Edit content
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                <nav className="flex flex-wrap items-center justify-between gap-2 rounded-xl border px-3 py-2 text-sm" aria-label="Review navigation">
                    <Button size="sm" variant="ghost" disabled={!navigation.previous} onClick={() => go(navigation.previous)}>
                        <ChevronLeft /> <span className="max-w-48 truncate">{navigation.previous ? navigation.previous.title : 'Previous lesson'}</span>
                    </Button>
                    <span className="text-muted-foreground tabular-nums">
                        Lesson {navigation.position} of {navigation.total}
                    </span>
                    <Button size="sm" variant="ghost" disabled={!navigation.next} onClick={() => go(navigation.next)}>
                        <span className="max-w-48 truncate">{navigation.next ? navigation.next.title : 'Next lesson'}</span> <ChevronRight />
                    </Button>
                </nav>

                <div className="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="min-w-0 space-y-4">
                        {reviewed?.outdated && (
                            <p className="flex items-start gap-2 rounded-lg border border-red-500/30 bg-red-50/60 p-3 text-sm text-red-800 dark:bg-red-950/20 dark:text-red-200">
                                <Info className="mt-0.5 size-4 shrink-0" />
                                The English lesson changed after this translation was written. Compare both sides, update the translation if needed, and
                                review it again.
                            </p>
                        )}

                        {!lesson.english?.sections ? (
                            <p className="text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">This lesson has no written content yet.</p>
                        ) : canonical ? (
                            <LessonSections sections={lesson.english.sections} lang="en" />
                        ) : (
                            <SideBySide
                                english={lesson.english.sections}
                                translation={lesson.translation?.sections ?? []}
                                translationLabel={languageLabel}
                                code={language}
                            />
                        )}
                    </div>

                    <aside className="min-w-0 space-y-4 2xl:sticky 2xl:top-4 2xl:self-start">
                        <Card className="gap-4">
                            <CardHeader>
                                <CardTitle className="text-base">{languageLabel} review</CardTitle>
                                <CardDescription>Approving never publishes. The version is published from the course page once reviews are done.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {reviewed ? (
                                    <>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <ReviewStatusBadge status={reviewed.status} label={reviewed.label} />
                                        </div>
                                        <div className="text-muted-foreground flex items-center justify-between text-sm">
                                            <span>Words</span>
                                            <WordCount words={reviewed.words} overTarget={reviewed.over_target} target={overTargetWords} />
                                        </div>
                                        {!canonical && lesson.english && (
                                            <div className="text-muted-foreground flex items-center justify-between gap-2 text-sm">
                                                <span>English</span>
                                                <ReviewStatusBadge status={lesson.english.status} label={lesson.english.label} />
                                            </div>
                                        )}
                                        <ReviewedBy state={reviewed} />
                                    </>
                                ) : (
                                    <p className="text-muted-foreground text-sm">Nothing to review in this language.</p>
                                )}

                                {can.review && reviewed ? (
                                    <div className="space-y-3 border-t pt-4">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="review-note" className="text-xs">
                                                Note (required when requesting changes)
                                            </Label>
                                            <Textarea
                                                id="review-note"
                                                value={note}
                                                maxLength={2000}
                                                rows={3}
                                                onChange={(event) => setNote(event.target.value)}
                                                placeholder="What should change, and where?"
                                            />
                                            <InputError message={errors.note ?? errors.status ?? errors.version} />
                                        </div>
                                        {!canonical && !englishApproved && (
                                            <p className="text-muted-foreground flex items-start gap-2 text-xs">
                                                <Info className="mt-0.5 size-3.5 shrink-0" /> Approve the English lesson first. The translation is approved
                                                against approved English.
                                            </p>
                                        )}
                                        <div className="grid grid-cols-2 gap-2">
                                            <Button disabled={processing || (!canonical && !englishApproved)} onClick={() => decide('approved')}>
                                                <Check /> Approve
                                            </Button>
                                            <Button
                                                variant="outline"
                                                disabled={processing || !navigation.next || (!canonical && !englishApproved)}
                                                onClick={() => decide('approved', true)}
                                            >
                                                Approve &amp; next <ArrowRight />
                                            </Button>
                                            <Button variant="outline" className="text-destructive" disabled={processing} onClick={() => decide('changes_requested')}>
                                                <Undo2 /> Request changes
                                            </Button>
                                            {reviewed.status === 'needs_review' || reviewed.status === 'outdated' ? (
                                                <Button variant="ghost" disabled={processing} onClick={() => decide('in_review')}>
                                                    Start review
                                                </Button>
                                            ) : (
                                                <Button variant="ghost" disabled={processing} onClick={() => decide('needs_review')}>
                                                    Reopen
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    !can.review && (
                                        <p className="text-muted-foreground flex items-start gap-2 text-sm">
                                            <Lock className="mt-0.5 size-4 shrink-0" /> You can view this lesson but not review it.
                                        </p>
                                    )
                                )}
                            </CardContent>
                        </Card>

                        <Card className="gap-4">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ShieldCheck className="size-4" /> Compliance
                                </CardTitle>
                                <CardDescription>For lessons about immigration, work authorization or other legal ground. Never approved automatically.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <ComplianceBadge status={lesson.compliance.status} label={lesson.compliance.label} />
                                <ReviewedBy state={lesson.compliance} />
                                {can.review && (
                                    <div className="space-y-3 border-t pt-3">
                                        <Select value={complianceStatus} onValueChange={setComplianceStatus}>
                                            <SelectTrigger aria-label="Compliance review">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {COMPLIANCE_CHOICES.map((choice) => (
                                                    <SelectItem key={choice.value} value={choice.value}>
                                                        {choice.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Textarea
                                            value={complianceNote}
                                            maxLength={2000}
                                            rows={2}
                                            onChange={(event) => setComplianceNote(event.target.value)}
                                            placeholder="Compliance note (required when requesting changes)"
                                            aria-label="Compliance note"
                                        />
                                        <Button variant="outline" className="w-full" disabled={processing} onClick={saveCompliance}>
                                            Save compliance decision
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </div>
        </RecruiterLayout>
    );
}
