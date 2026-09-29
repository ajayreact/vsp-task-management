import InputError from '@/components/input-error';
import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { ConfirmPost, ContentStatusBadge, RequiredBadge, TrainingSubNav, formatMinutes, formatTrainingDate } from '@/components/recruiter-operations/training/training-ui';
import { Badge } from '@/components/ui/badge';
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
import { ArrowDown, ArrowUp, Eye, LoaderCircle, Pencil, Plus, Trash2, UserPlus } from 'lucide-react';
import { type FormEvent } from 'react';

interface VersionRow {
    id: number;
    label: string;
    status: string;
    status_label: string;
    published_at: string | null;
    lessons_count: number;
    open_assignments_count: number;
    completed_assignments_count: number;
    is_current: boolean;
}

interface LessonRow {
    id: number;
    title: string;
    content_type: string;
    content_type_label: string;
    duration_minutes: number | null;
    is_required: boolean;
    has_file: boolean;
    has_body: boolean;
}

interface SelectedVersion {
    id: number;
    label: string;
    status: string;
    status_label: string;
    description: string | null;
    estimated_minutes: number | null;
    published_at: string | null;
    created_by: string | null;
    is_current: boolean;
    lessons: LessonRow[];
    quizzes: LinkedQuiz[];
    can: { update: boolean; publish: boolean; archive: boolean; delete: boolean };
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
        status: string;
        status_label: string;
        current_version_id: number | null;
    };
    versions: VersionRow[];
    selectedVersion: SelectedVersion | null;
    can: { update: boolean; archive: boolean; restore: boolean; createVersion: boolean; assign: boolean };
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
                    Recruiters assigned this version also receive these quizzes. The quiz version is fixed when linked; change it by creating a new course version.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                {version.quizzes.length === 0 && <p className="text-muted-foreground text-sm">No quizzes linked.</p>}
                {version.quizzes.map((quiz) => (
                    <div key={quiz.id} className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2">
                        <div className="min-w-0">
                            <Link href={`/recruiter/assessments/manage/${quiz.assessment_id}?version=${quiz.id}`} className="text-sm font-medium hover:underline">
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
                                description="Recruiters assigned this course version later will not receive it. Existing quiz assignments are not changed."
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
                <Label htmlFor="version-description">Version notes</Label>
                <Textarea
                    id="version-description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    maxLength={5000}
                    placeholder="What changed in this version, or what reviewers should check."
                />
                <InputError message={errors.description} />
            </div>
            <div className="grid content-start gap-2">
                <Label htmlFor="version-minutes">Estimated minutes</Label>
                <Input
                    id="version-minutes"
                    type="number"
                    min="1"
                    max="6000"
                    value={data.estimated_minutes}
                    onChange={(e) => setData('estimated_minutes', e.target.value)}
                    placeholder="From lessons"
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

export default function ManageTrainingCourse({ course, versions, selectedVersion, can, quizOptions }: Props) {
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

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />
                <PageHeader
                    title={course.title}
                    description={[course.category, course.description].filter(Boolean).join(' · ') || undefined}
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
                                    description="It becomes assignable again if it has a published version."
                                    url={`/recruiter/training/manage/courses/${course.id}/restore`}
                                    confirmLabel="Restore"
                                />
                            )}
                        </div>
                    }
                />

                <div className="grid gap-6 xl:grid-cols-[18rem_1fr]">
                    <Card className="h-fit">
                        <CardHeader>
                            <CardTitle className="text-base">Versions</CardTitle>
                            <CardDescription>Published versions are frozen. Assignments stay on the version they were given.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {versions.map((row) => (
                                <Link
                                    key={row.id}
                                    href={`/recruiter/training/manage/courses/${course.id}?version=${row.id}`}
                                    preserveScroll
                                    className={cn(
                                        'block rounded-lg border px-3 py-2 text-sm transition-colors',
                                        row.id === version?.id ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20' : 'hover:bg-muted/50',
                                    )}
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-medium">{row.label}</span>
                                        <div className="flex gap-1">
                                            {row.is_current && <Badge variant="info">Live</Badge>}
                                            <ContentStatusBadge status={row.status} label={row.status_label} />
                                        </div>
                                    </div>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {row.lessons_count} lessons · {row.open_assignments_count} open · {row.completed_assignments_count} completed
                                        {row.published_at ? ` · published ${formatTrainingDate(row.published_at)}` : ''}
                                    </p>
                                </Link>
                            ))}

                            {can.createVersion && (
                                <ConfirmPost
                                    trigger={
                                        <Button variant="outline" size="sm" className="w-full">
                                            <Plus /> New version
                                        </Button>
                                    }
                                    title="Create a new draft version?"
                                    description="The latest version's lessons and files are copied into a new draft. Published versions and current assignments are not changed."
                                    url={`/recruiter/training/manage/courses/${course.id}/versions`}
                                    confirmLabel="Create draft"
                                />
                            )}
                        </CardContent>
                    </Card>

                    {version ? (
                        <div className="min-w-0 space-y-6">
                            <Card>
                                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-1">
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            {version.label}
                                            <ContentStatusBadge status={version.status} label={version.status_label} />
                                        </CardTitle>
                                        <CardDescription>
                                            {version.created_by ? `Created by ${version.created_by}. ` : ''}
                                            {version.published_at ? `Published ${formatTrainingDate(version.published_at)}. ` : ''}
                                            {version.estimated_minutes ? `Estimated ${formatMinutes(version.estimated_minutes)}.` : ''}
                                        </CardDescription>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {version.can.publish && (
                                            <ConfirmPost
                                                trigger={<Button size="sm">Publish {version.label}</Button>}
                                                title={`Publish ${version.label}?`}
                                                description="Once published this version cannot be edited. It becomes the version given to new assignments, and any older published version is archived. Existing assignments keep their version."
                                                url={`/recruiter/training/manage/versions/${version.id}/publish`}
                                                confirmLabel="Publish"
                                            />
                                        )}
                                        {version.can.archive && (
                                            <ConfirmPost
                                                trigger={
                                                    <Button size="sm" variant="outline">
                                                        Archive version
                                                    </Button>
                                                }
                                                title={`Archive ${version.label}?`}
                                                description="New assignments will not use it. Recruiters already assigned this version can still finish it."
                                                url={`/recruiter/training/manage/versions/${version.id}/archive`}
                                                confirmLabel="Archive"
                                                destructive
                                            />
                                        )}
                                        {version.can.delete && (
                                            <ConfirmDelete
                                                trigger={
                                                    <Button size="sm" variant="ghost" className="text-destructive">
                                                        <Trash2 /> Discard draft
                                                    </Button>
                                                }
                                                title={`Discard ${version.label}?`}
                                                description="The draft and its lessons and files are deleted. Published versions are not affected."
                                                url={`/recruiter/training/manage/versions/${version.id}`}
                                                confirmLabel="Discard"
                                            />
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {editable ? (
                                        <VersionDetailsForm key={version.id} version={version} />
                                    ) : (
                                        <p className="text-muted-foreground text-sm whitespace-pre-line">
                                            {version.description || 'No version notes.'}
                                            {'\n'}This version is read-only. Create a new version to change its content.
                                        </p>
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
                                <CardContent className="overflow-x-auto">
                                    <Table className="min-w-max">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-12">#</TableHead>
                                                <TableHead>Lesson</TableHead>
                                                <TableHead>Material</TableHead>
                                                <TableHead>Duration</TableHead>
                                                <TableHead>Required</TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {lessons.length === 0 && (
                                                <TableRow>
                                                    <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                                        No lessons yet.{editable ? ' Add at least one lesson before publishing.' : ''}
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                            {lessons.map((lesson, index) => (
                                                <TableRow key={lesson.id}>
                                                    <TableCell className="text-muted-foreground text-sm tabular-nums">{index + 1}</TableCell>
                                                    <TableCell>
                                                        <Link href={`/recruiter/training/manage/lessons/${lesson.id}`} className="font-medium hover:underline">
                                                            {lesson.title}
                                                        </Link>
                                                        {!lesson.has_body && <p className="text-muted-foreground text-xs">No written content yet</p>}
                                                    </TableCell>
                                                    <TableCell className="text-sm">
                                                        {lesson.content_type_label}
                                                        {lesson.has_file ? ' · file attached' : ''}
                                                    </TableCell>
                                                    <TableCell className="text-sm">{formatMinutes(lesson.duration_minutes)}</TableCell>
                                                    <TableCell>
                                                        <RequiredBadge required={lesson.is_required} />
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-1">
                                                            {editable && (
                                                                <>
                                                                    <Button
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        aria-label="Move up"
                                                                        disabled={index === 0}
                                                                        onClick={() => move(lesson.id, 'up')}
                                                                    >
                                                                        <ArrowUp />
                                                                    </Button>
                                                                    <Button
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        aria-label="Move down"
                                                                        disabled={index === lessons.length - 1}
                                                                        onClick={() => move(lesson.id, 'down')}
                                                                    >
                                                                        <ArrowDown />
                                                                    </Button>
                                                                </>
                                                            )}
                                                            <Button asChild size="icon" variant="ghost" aria-label="Preview">
                                                                <Link href={`/recruiter/training/manage/lessons/${lesson.id}`}>
                                                                    <Eye />
                                                                </Link>
                                                            </Button>
                                                            {editable && (
                                                                <>
                                                                    <Button asChild size="icon" variant="ghost" aria-label="Edit">
                                                                        <Link href={`/recruiter/training/manage/lessons/${lesson.id}/edit`}>
                                                                            <Pencil />
                                                                        </Link>
                                                                    </Button>
                                                                    <ConfirmDelete
                                                                        trigger={
                                                                            <Button size="icon" variant="ghost" aria-label="Delete" className="text-destructive">
                                                                                <Trash2 />
                                                                            </Button>
                                                                        }
                                                                        title="Delete this lesson?"
                                                                        description="The lesson and its file are removed from this draft."
                                                                        url={`/recruiter/training/manage/lessons/${lesson.id}`}
                                                                    />
                                                                </>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>

                            <LinkedQuizzes version={version} options={quizOptions} />
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="text-muted-foreground py-10 text-center text-sm">This course has no versions.</CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </RecruiterLayout>
    );
}
