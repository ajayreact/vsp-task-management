import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { AssessmentSubNav, ManagerQuestionView, type ManagerQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { VersionSettingsFields, type VersionSettings } from '@/components/recruiter-operations/assessments/version-settings-fields';
import { ConfirmPost, ContentStatusBadge } from '@/components/recruiter-operations/training/training-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, BarChart3, Eye, Library, LoaderCircle, Pencil, Plus, Send, Trash2, UserPlus } from 'lucide-react';
import { type FormEvent } from 'react';

interface AssessmentData {
    id: number;
    title: string;
    description: string | null;
    type_label: string;
    status: string;
    status_label: string;
    current_version_id: number | null;
    is_assignable: boolean;
}

interface VersionRow {
    id: number;
    label: string;
    status: string;
    status_label: string;
    questions_count: number;
    assignments_count: number;
    published_at: string | null;
    is_current: boolean;
}

interface SelectedVersion {
    id: number;
    label: string;
    status: string;
    status_label: string;
    instructions: string | null;
    passing_percentage: number;
    time_limit_minutes: number | null;
    max_attempts: number;
    randomize_questions: boolean;
    randomize_options: boolean;
    show_result: boolean;
    allow_review: boolean;
    published_at: string | null;
    total_points: number;
    is_current: boolean;
    training_links: string[];
}

interface Props {
    assessment: AssessmentData;
    versions: VersionRow[];
    version: SelectedVersion | null;
    questions: ManagerQuestion[];
    can: {
        update: boolean;
        createVersion: boolean;
        archive: boolean;
        restore: boolean;
        assign: boolean;
        editVersion: boolean;
        publish: boolean;
        archiveVersion: boolean;
        discard: boolean;
        results: boolean;
    };
}

export default function AssessmentBuilder({ assessment, versions, version, questions, can }: Props) {
    const base = `/recruiter/assessments/manage/${assessment.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: 'Manage', href: '/recruiter/assessments/manage' },
        { title: assessment.title, href: base },
    ];
    const hasDraft = versions.some((row) => row.status === 'draft');

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={assessment.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={assessment.title}
                    description={assessment.description ?? assessment.type_label}
                    action={
                        <div className="flex flex-wrap gap-2">
                            {can.assign && (
                                <Button asChild>
                                    <Link href={`/recruiter/assessments/assignments/create?assessment=${assessment.id}`}>
                                        <UserPlus /> Assign
                                    </Link>
                                </Button>
                            )}
                            {can.results && (
                                <Button asChild variant="outline">
                                    <Link href={`/recruiter/assessments/results?assessment=${assessment.id}`}>
                                        <BarChart3 /> Results
                                    </Link>
                                </Button>
                            )}
                            {can.update && (
                                <Button asChild variant="outline">
                                    <Link href={`${base}/edit`}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>
                            )}
                            {can.archive && (
                                <ConfirmPost
                                    trigger={<Button variant="outline">Archive</Button>}
                                    title="Archive this quiz?"
                                    description="It can no longer be assigned. Recruiters who already have it can still finish it."
                                    url={`${base}/archive`}
                                    confirmLabel="Archive"
                                    destructive
                                />
                            )}
                            {can.restore && (
                                <ConfirmPost
                                    trigger={<Button variant="outline">Restore</Button>}
                                    title="Restore this quiz?"
                                    description="It becomes assignable again if it has a published version."
                                    url={`${base}/restore`}
                                    confirmLabel="Restore"
                                />
                            )}
                        </div>
                    }
                />
                <AssessmentSubNav />

                <div className="flex flex-wrap items-center gap-2">
                    <ContentStatusBadge status={assessment.status} label={assessment.status_label} />
                    {!assessment.is_assignable && assessment.status !== 'archived' && (
                        <span className="text-muted-foreground text-sm">Publish a version to start assigning this quiz.</span>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <Card className="h-fit">
                        <CardHeader>
                            <CardTitle className="text-base">Versions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {versions.map((row) => (
                                <Link
                                    key={row.id}
                                    href={`${base}?version=${row.id}`}
                                    preserveScroll
                                    className={cn(
                                        'block rounded-lg border px-3 py-2 text-sm transition-colors',
                                        version?.id === row.id ? 'border-fuchsia-300 bg-fuchsia-50' : 'hover:bg-muted/50',
                                    )}
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-medium">{row.label}</span>
                                        <ContentStatusBadge status={row.status} label={row.status_label} />
                                    </div>
                                    <div className="text-muted-foreground mt-1 text-xs">
                                        {row.questions_count} questions · {row.assignments_count} assigned
                                        {row.is_current && ' · live'}
                                    </div>
                                </Link>
                            ))}
                            {can.createVersion && !hasDraft && version && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                    onClick={() => router.post(`${base}/versions`, { source_version_id: version.id }, { preserveScroll: true })}
                                >
                                    <Plus /> New draft from {version.label}
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    {version ? (
                        <div className="min-w-0 space-y-6">
                            <VersionPanel key={version.id} version={version} can={can} />
                            <QuestionsPanel version={version} questions={questions} editable={can.editVersion} />
                        </div>
                    ) : (
                        <p className="text-muted-foreground text-sm">This quiz has no versions.</p>
                    )}
                </div>
            </div>
        </RecruiterLayout>
    );
}

function VersionPanel({ version, can }: { version: SelectedVersion; can: Props['can'] }) {
    const versionUrl = `/recruiter/assessments/manage/versions/${version.id}`;
    const { data, setData, put, processing, errors, isDirty } = useForm<VersionSettings>({
        instructions: version.instructions ?? '',
        passing_percentage: version.passing_percentage,
        time_limit_minutes: version.time_limit_minutes ?? '',
        max_attempts: version.max_attempts,
        randomize_questions: version.randomize_questions,
        randomize_options: version.randomize_options,
        show_result: version.show_result,
        allow_review: version.allow_review,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(versionUrl, { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <CardTitle className="flex items-center gap-2">
                        {version.label}
                        <ContentStatusBadge status={version.status} label={version.status_label} />
                        {version.is_current && <Badge variant="info">Live</Badge>}
                    </CardTitle>
                    <CardDescription>
                        {version.status === 'draft'
                            ? 'Drafts can be edited. Publishing freezes this version and makes it the one new assignments use.'
                            : 'This version is frozen. Create a new draft to change questions or settings.'}
                    </CardDescription>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline" size="sm">
                        <Link href={`${versionUrl}/preview`}>
                            <Eye /> Preview
                        </Link>
                    </Button>
                    {can.publish && (
                        <ConfirmPost
                            trigger={
                                <Button size="sm">
                                    <Send /> Publish
                                </Button>
                            }
                            title={`Publish ${version.label}?`}
                            description="Questions and settings become read-only. The previously published version is archived; recruiters already on it keep it."
                            url={`${versionUrl}/publish`}
                            confirmLabel="Publish"
                        />
                    )}
                    {can.archiveVersion && (
                        <ConfirmPost
                            trigger={
                                <Button size="sm" variant="outline">
                                    Archive version
                                </Button>
                            }
                            title={`Archive ${version.label}?`}
                            description="New assignments stop using it. Recruiters already assigned to it can still finish."
                            url={`${versionUrl}/archive`}
                            confirmLabel="Archive"
                            destructive
                        />
                    )}
                    {can.discard && (
                        <ConfirmDelete
                            trigger={
                                <Button size="sm" variant="outline">
                                    <Trash2 /> Discard draft
                                </Button>
                            }
                            title="Discard this draft?"
                            description="The draft and its questions are deleted. Published versions are not affected."
                            url={versionUrl}
                            confirmLabel="Discard"
                        />
                    )}
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {version.training_links.length > 0 && (
                    <p className="text-muted-foreground text-sm">Linked to training: {version.training_links.join(', ')}</p>
                )}
                <form onSubmit={submit} className="space-y-4">
                    <VersionSettingsFields data={data} errors={errors} onChange={(key, value) => setData((current) => ({ ...current, [key]: value }))} disabled={!can.editVersion} />
                    {can.editVersion && (
                        <Button type="submit" disabled={processing || !isDirty}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Save settings
                        </Button>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}

function QuestionsPanel({ version, questions, editable }: { version: SelectedVersion; questions: ManagerQuestion[]; editable: boolean }) {
    const versionUrl = `/recruiter/assessments/manage/versions/${version.id}`;
    const move = (question: ManagerQuestion, direction: 'up' | 'down') =>
        router.post(`/recruiter/assessments/manage/questions/${question.id}/move`, { direction }, { preserveScroll: true });

    return (
        <Card>
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <CardTitle>Questions</CardTitle>
                    <CardDescription>
                        {questions.length} {questions.length === 1 ? 'question' : 'questions'} · {version.total_points} points in total
                    </CardDescription>
                </div>
                {editable && (
                    <div className="flex flex-wrap gap-2">
                        <Button asChild size="sm" variant="outline">
                            <Link href={`${versionUrl}/bank`}>
                                <Library /> Add from bank
                            </Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href={`${versionUrl}/questions/create`}>
                                <Plus /> New question
                            </Link>
                        </Button>
                    </div>
                )}
            </CardHeader>
            <CardContent className="space-y-3">
                {questions.length === 0 && <p className="text-muted-foreground py-6 text-center text-sm">No questions yet. A version needs at least one to publish.</p>}
                {questions.map((question, index) => (
                    <div key={question.id} className="flex gap-3 rounded-xl border p-4">
                        <div className="min-w-0 flex-1">
                            <ManagerQuestionView question={question} number={index + 1} />
                        </div>
                        {editable && (
                            <div className="flex shrink-0 flex-col gap-1">
                                <Button size="icon" variant="ghost" aria-label="Move up" disabled={index === 0} onClick={() => move(question, 'up')}>
                                    <ArrowUp />
                                </Button>
                                <Button size="icon" variant="ghost" aria-label="Move down" disabled={index === questions.length - 1} onClick={() => move(question, 'down')}>
                                    <ArrowDown />
                                </Button>
                                <Button size="icon" variant="ghost" aria-label="Edit question" asChild>
                                    <Link href={`/recruiter/assessments/questions/${question.id}/edit`}>
                                        <Pencil />
                                    </Link>
                                </Button>
                                <ConfirmDelete
                                    trigger={
                                        <Button size="icon" variant="ghost" aria-label="Remove question">
                                            <Trash2 />
                                        </Button>
                                    }
                                    title="Remove this question?"
                                    description="It is removed from this draft only. The Question Bank is not changed."
                                    url={`/recruiter/assessments/questions/${question.id}`}
                                    confirmLabel="Remove"
                                />
                            </div>
                        )}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
