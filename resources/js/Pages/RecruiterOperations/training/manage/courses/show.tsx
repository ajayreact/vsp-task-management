import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import {
    ConfirmPost,
    ContentStatusBadge,
    RequiredBadge,
    TrainingSubNav,
    formatMinutes,
} from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Eye, LoaderCircle, Pencil, Plus, Rocket, Trash2, UserPlus } from 'lucide-react';
import { type FormEvent } from 'react';

interface LessonRow {
    id: number;
    module: string | null;
    title: string;
    content_type: string;
    content_type_label: string;
    duration_minutes: number | null;
    is_required: boolean;
    has_file: boolean;
    has_body: boolean;
    translations: string[];
}

interface SelectedVersion {
    id: number;
    description: string | null;
    estimated_minutes: number | null;
    duration_minutes: number | null;
    open_assignments_count: number;
    lessons: LessonRow[];
    quizzes: LinkedQuiz[];
    can: { update: boolean };
}

interface LinkedQuiz {
    id: number;
    assessment_id: number;
    title: string;
    label: string;
    status_label: string;
}

interface Props {
    course: {
        id: number;
        title: string;
        description: string | null;
        category: string | null;
        track: { name: string; slug: string } | null;
        status: string;
        status_label: string;
    };
    selectedVersion: SelectedVersion | null;
    draft: { id: number; label: string; lessons_count: number; is_shown: boolean; can_publish: boolean } | null;
    can: { update: boolean; archive: boolean; restore: boolean; assign: boolean };
    quizOptions: { value: string; label: string }[];
}

function LinkedQuizzes({ version, options }: { version: SelectedVersion; options: { value: string; label: string }[] }) {
    const editable = version.can.update;
    const available = options.filter((option) => !version.quizzes.some((quiz) => String(quiz.id) === option.value));
    const { data, setData, post, processing, errors, reset } = useForm({ assessment_version_id: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(`/recruiter/training/manage/versions/${version.id}/quizzes`, { preserveScroll: true, onSuccess: () => reset() });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Quizzes</CardTitle>
                <CardDescription>
                    Recruiters assigned this course receive these quizzes, including recruiters assigned before the quiz was linked.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                {version.quizzes.length === 0 && <p className="text-muted-foreground text-sm">No quizzes linked.</p>}
                {version.quizzes.map((quiz) => (
                    <div key={quiz.id} className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2">
                        <div className="min-w-0">
                            <Link
                                href={`/recruiter/assessments/manage/${quiz.assessment_id}?version=${quiz.id}`}
                                className="text-sm font-medium hover:underline"
                            >
                                {quiz.title}
                            </Link>
                            <div className="text-muted-foreground text-xs">
                                {quiz.label} · {quiz.status_label}
                            </div>
                        </div>
                        {editable && (
                            <ConfirmDelete
                                trigger={
                                    <Button size="icon" variant="ghost" aria-label={`Unlink ${quiz.title}`}>
                                        <Trash2 />
                                    </Button>
                                }
                                title="Unlink this quiz?"
                                description="Recruiters who have not started this quiz no longer receive it. Attempts already made are kept."
                                url={`/recruiter/training/manage/versions/${version.id}/quizzes/${quiz.id}`}
                                confirmLabel="Unlink"
                            />
                        )}
                    </div>
                ))}
                {editable && (
                    <form onSubmit={submit} className="flex flex-wrap items-end gap-2">
                        <div className="grid min-w-64 flex-1 gap-2">
                            <Label htmlFor="quiz">Link a published quiz</Label>
                            <Select value={data.assessment_version_id} onValueChange={(value) => setData('assessment_version_id', value)}>
                                <SelectTrigger id="quiz">
                                    <SelectValue placeholder={available.length ? 'Choose a quiz' : 'No published quizzes available'} />
                                </SelectTrigger>
                                <SelectContent>
                                    {available.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <Button type="submit" variant="outline" disabled={processing || data.assessment_version_id === ''}>
                            {processing ? <LoaderCircle className="animate-spin" /> : <Plus />}
                            Link quiz
                        </Button>
                        <InputError message={errors.assessment_version_id} className="w-full" />
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

function VersionDetailsForm({ version }: { version: SelectedVersion }) {
    const { data, setData, put, processing, errors, isDirty } = useForm({
        description: version.description ?? '',
        estimated_minutes: version.estimated_minutes ? String(version.estimated_minutes) : '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(`/recruiter/training/manage/versions/${version.id}`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid gap-4 sm:grid-cols-[1fr_12rem]">
            <div className="grid gap-2">
                <Label htmlFor="version-description">Course notes</Label>
                <Textarea
                    id="version-description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    maxLength={5000}
                    placeholder="Internal notes for training managers."
                />
                <InputError message={errors.description} />
            </div>
            <div className="grid content-start gap-2">
                <Label htmlFor="version-minutes">Duration (minutes)</Label>
                <Input
                    id="version-minutes"
                    type="number"
                    min="1"
                    max="6000"
                    value={data.estimated_minutes}
                    onChange={(e) => setData('estimated_minutes', e.target.value)}
                    placeholder={version.duration_minutes ? `Auto: ${version.duration_minutes}` : 'From lessons'}
                />
                <InputError message={errors.estimated_minutes} />
                <Button type="submit" variant="outline" disabled={processing || !isDirty}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    Save details
                </Button>
            </div>
        </form>
    );
}

function LessonActions({
    lesson,
    index,
    total,
    editable,
    onMove,
    className,
}: {
    lesson: LessonRow;
    index: number;
    total: number;
    editable: boolean;
    onMove: (lessonId: number, direction: 'up' | 'down') => void;
    className?: string;
}) {
    return (
        <div className={cn('flex shrink-0 justify-end gap-1', className)}>
            {editable && (
                <>
                    <Button
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        aria-label="Move up"
                        disabled={index === 0}
                        onClick={() => onMove(lesson.id, 'up')}
                    >
                        <ArrowUp />
                    </Button>
                    <Button
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        aria-label="Move down"
                        disabled={index === total - 1}
                        onClick={() => onMove(lesson.id, 'down')}
                    >
                        <ArrowDown />
                    </Button>
                </>
            )}
            <Button asChild size="icon" variant="ghost" className="size-8" aria-label="Preview">
                <Link href={`/recruiter/training/manage/lessons/${lesson.id}`}>
                    <Eye />
                </Link>
            </Button>
            {editable && (
                <>
                    <Button asChild size="icon" variant="ghost" className="size-8" aria-label="Edit">
                        <Link href={`/recruiter/training/manage/lessons/${lesson.id}/edit`}>
                            <Pencil />
                        </Link>
                    </Button>
                    <ConfirmDelete
                        trigger={
                            <Button size="icon" variant="ghost" aria-label="Delete" className="text-destructive size-8">
                                <Trash2 />
                            </Button>
                        }
                        title="Delete this lesson?"
                        description="The lesson and its file are removed for everyone assigned this course, along with their progress on it."
                        url={`/recruiter/training/manage/lessons/${lesson.id}`}
                    />
                </>
            )}
        </div>
    );
}

export default function ManageTrainingCourse({ course, selectedVersion, draft, can, quizOptions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: 'Manage', href: '/recruiter/training/manage' },
        { title: course.title, href: `/recruiter/training/manage/courses/${course.id}` },
    ];

    const version = selectedVersion;
    const editable = version?.can.update ?? false;
    const lessons = version?.lessons ?? [];

    const move = (lessonId: number, direction: 'up' | 'down') => {
        router.post(`/recruiter/training/manage/lessons/${lessonId}/move`, { direction }, { preserveScroll: true, preserveState: true });
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={course.title} />

            <div className="flex max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />
                <PageHeader
                    title={course.title}
                    description={
                        [course.track?.name ?? 'Not in a track', course.category, course.description].filter(Boolean).join(' · ') || undefined
                    }
                    action={
                        <div className="flex flex-wrap gap-2">
                            <ContentStatusBadge status={course.status} label={course.status_label} />
                            {can.assign && (
                                <Button asChild size="sm">
                                    <Link href={`/recruiter/training/assignments/create?course=${course.id}`}>
                                        <UserPlus /> Assign
                                    </Link>
                                </Button>
                            )}
                            {can.update && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={`/recruiter/training/manage/courses/${course.id}/edit`}>
                                        <Pencil /> Edit course
                                    </Link>
                                </Button>
                            )}
                            {can.archive && (
                                <ConfirmPost
                                    trigger={
                                        <Button size="sm" variant="outline">
                                            Archive course
                                        </Button>
                                    }
                                    title="Archive this course?"
                                    description="It can no longer be assigned. Recruiters who already have it can still finish it, and all history is kept."
                                    url={`/recruiter/training/manage/courses/${course.id}/archive`}
                                    confirmLabel="Archive"
                                    destructive
                                />
                            )}
                            {can.restore && (
                                <ConfirmPost
                                    trigger={
                                        <Button size="sm" variant="outline">
                                            Restore course
                                        </Button>
                                    }
                                    title="Restore this course?"
                                    description="It becomes assignable again."
                                    url={`/recruiter/training/manage/courses/${course.id}/restore`}
                                    confirmLabel="Restore"
                                />
                            )}
                        </div>
                    }
                />

                {draft?.can_publish && (
                    <div
                        className="flex flex-col gap-3 rounded-xl border border-amber-300/60 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-amber-500/30 dark:bg-amber-950/20"
                        data-testid="draft-banner"
                    >
                        <div className="min-w-0 space-y-0.5">
                            <p className="font-medium">
                                {draft.label} is a draft and not live yet
                                <span className="text-muted-foreground font-normal">
                                    {' · '}
                                    {draft.lessons_count} {draft.lessons_count === 1 ? 'lesson' : 'lessons'}
                                </span>
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {draft.is_shown
                                    ? 'This course has never been published, so recruiters cannot be assigned it yet.'
                                    : 'Recruiters still see the live version below. Review the draft in Content review, then publish it.'}
                            </p>
                        </div>
                        <div className="flex shrink-0 flex-wrap gap-2">
                            <Button asChild size="sm" variant="outline">
                                <Link href="/recruiter/training/manage/review">Content review</Link>
                            </Button>
                            {draft.lessons_count > 0 && (
                                <ConfirmPost
                                    trigger={
                                        <Button size="sm" className="bg-emerald-600 text-white hover:bg-emerald-700" data-testid="publish-draft">
                                            <Rocket /> Publish {draft.label}
                                        </Button>
                                    }
                                    title={`Publish ${draft.label}?`}
                                    description="It becomes the live version straight away, and new assignments receive it. Recruiters already assigned this course stay on the version they started."
                                    url={`/recruiter/training/manage/versions/${draft.id}/publish`}
                                    confirmLabel="Publish"
                                />
                            )}
                        </div>
                    </div>
                )}

                <div className="min-w-0">
                    {version ? (
                        <div className="min-w-0 space-y-6">
                            <Card>
                                <CardHeader className="space-y-1">
                                    <CardTitle className="text-base">Course details</CardTitle>
                                    <CardDescription data-testid="course-duration">
                                        Duration {formatMinutes(version.duration_minutes)}
                                        {version.estimated_minutes ? '' : ' (estimated from the lesson text)'}. Changes you save are live straight
                                        away
                                        {version.open_assignments_count > 0
                                            ? ` for the ${version.open_assignments_count} recruiter${version.open_assignments_count === 1 ? '' : 's'} working on this course.`
                                            : '.'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {editable ? (
                                        <VersionDetailsForm key={version.id} version={version} />
                                    ) : (
                                        <p className="text-muted-foreground text-sm">{version.description || 'No course notes.'}</p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between gap-3">
                                    <div className="space-y-1">
                                        <CardTitle className="text-base">Lessons</CardTitle>
                                        <CardDescription>Recruiters complete required lessons to finish the course.</CardDescription>
                                    </div>
                                    {editable && (
                                        <Button asChild size="sm">
                                            <Link href={`/recruiter/training/manage/versions/${version.id}/lessons/create`}>
                                                <Plus /> Add lesson
                                            </Link>
                                        </Button>
                                    )}
                                </CardHeader>
                                <CardContent>
                                    {lessons.length === 0 ? (
                                        <p className="text-muted-foreground py-10 text-center text-sm">
                                            No lessons yet.{editable ? ' Add at least one lesson before assigning this course.' : ''}
                                        </p>
                                    ) : (
                                        <>
                                            <Table className="hidden table-fixed xl:table [&_td]:px-3 [&_th]:px-3">
                                                <colgroup>
                                                    <col className="w-10" />
                                                    <col />
                                                    <col className="w-28" />
                                                    <col className="w-28" />
                                                    <col className="w-28" />
                                                    <col className={editable ? 'w-[12.5rem]' : 'w-24'} />
                                                </colgroup>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>#</TableHead>
                                                        <TableHead>Lesson</TableHead>
                                                        <TableHead>Material</TableHead>
                                                        <TableHead>Duration</TableHead>
                                                        <TableHead>Required</TableHead>
                                                        <TableHead className="text-right">Actions</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {lessons.map((lesson, index) => (
                                                        <TableRow key={lesson.id}>
                                                            <TableCell className="text-muted-foreground text-sm tabular-nums">{index + 1}</TableCell>
                                                            <TableCell className="break-words">
                                                                {lesson.module && <p className="text-muted-foreground text-xs">{lesson.module}</p>}
                                                                <Link
                                                                    href={`/recruiter/training/manage/lessons/${lesson.id}`}
                                                                    className="font-medium hover:underline"
                                                                >
                                                                    {lesson.title}
                                                                </Link>
                                                                {!lesson.has_body && (
                                                                    <p className="text-muted-foreground text-xs">No written content yet</p>
                                                                )}
                                                                {lesson.translations.length > 0 && (
                                                                    <p className="text-muted-foreground text-xs">
                                                                        Also in {lesson.translations.join(', ')}
                                                                    </p>
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="text-sm">
                                                                {lesson.content_type_label}
                                                                {lesson.has_file && <p className="text-muted-foreground text-xs">File attached</p>}
                                                            </TableCell>
                                                            <TableCell className="text-sm">{formatMinutes(lesson.duration_minutes)}</TableCell>
                                                            <TableCell>
                                                                <RequiredBadge required={lesson.is_required} />
                                                            </TableCell>
                                                            <TableCell>
                                                                <LessonActions
                                                                    lesson={lesson}
                                                                    index={index}
                                                                    total={lessons.length}
                                                                    editable={editable}
                                                                    onMove={move}
                                                                />
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>

                                            <ul className="divide-border divide-y xl:hidden">
                                                {lessons.map((lesson, index) => (
                                                    <li key={lesson.id} className="flex gap-3 py-3">
                                                        <span className="text-muted-foreground w-6 shrink-0 text-sm tabular-nums">{index + 1}</span>
                                                        <div className="min-w-0 flex-1 space-y-1.5">
                                                            {lesson.module && <p className="text-muted-foreground text-xs">{lesson.module}</p>}
                                                            <Link
                                                                href={`/recruiter/training/manage/lessons/${lesson.id}`}
                                                                className="block font-medium break-words hover:underline"
                                                            >
                                                                {lesson.title}
                                                            </Link>
                                                            <div className="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                                                <span>
                                                                    {lesson.content_type_label}
                                                                    {lesson.has_file ? ' · file attached' : ''}
                                                                </span>
                                                                <span>{formatMinutes(lesson.duration_minutes)}</span>
                                                                <RequiredBadge required={lesson.is_required} />
                                                            </div>
                                                            {!lesson.has_body && (
                                                                <p className="text-muted-foreground text-xs">No written content yet</p>
                                                            )}
                                                            {lesson.translations.length > 0 && (
                                                                <p className="text-muted-foreground text-xs">
                                                                    Also in {lesson.translations.join(', ')}
                                                                </p>
                                                            )}
                                                            <LessonActions
                                                                lesson={lesson}
                                                                index={index}
                                                                total={lessons.length}
                                                                editable={editable}
                                                                onMove={move}
                                                                className="justify-start"
                                                            />
                                                        </div>
                                                    </li>
                                                ))}
                                            </ul>
                                        </>
                                    )}
                                </CardContent>
                            </Card>

                            <LinkedQuizzes version={version} options={quizOptions} />
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="text-muted-foreground py-10 text-center text-sm">This course has no content yet.</CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </RecruiterLayout>
    );
}
