import { PageHeader } from '@/components/admin/page-header';
import { ResultBadge } from '@/components/recruiter-operations/assessments/assessment-ui';
import {
    RequiredBadge,
    TrainingProgressBar,
    TrainingStatusBadge,
    formatMinutes,
    formatTrainingDate,
} from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, Circle, CircleDot, Play } from 'lucide-react';

interface LessonRow {
    id: number;
    title: string;
    description: string | null;
    content_type: string;
    content_type_label: string;
    duration_minutes: number | null;
    is_required: boolean;
    started: boolean;
    completed: boolean;
}

interface Props {
    course: { id: number; title: string; description: string | null; category: string | null };
    version: { label: string; description: string | null; estimated_minutes: number | null };
    assignment: {
        status: string;
        status_label: string;
        assigned_at: string;
        due_at: string | null;
        started_at: string | null;
        completed_at: string | null;
    };
    progress: { percent: number; completed: number; counted: number; total: number };
    lessons: LessonRow[];
    resumeLessonId: number | null;
    history: { version: string; completed_at: string | null }[];
    quizzes: { id: number; title: string; status_label: string; result: string | null; result_label: string | null }[];
}

export default function TrainingCourse({ course, version, assignment, progress, lessons, resumeLessonId, history, quizzes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'My Training', href: '/recruiter/training/my-training' },
        { title: course.title, href: `/recruiter/training/courses/${course.id}` },
    ];

    const completed = assignment.status === 'completed';
    const started = assignment.started_at !== null || progress.completed > 0;

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={course.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={course.title}
                    description={course.description ?? undefined}
                    action={
                        resumeLessonId ? (
                            <Button asChild>
                                <Link href={`/recruiter/training/courses/${course.id}/lessons/${resumeLessonId}`}>
                                    <Play /> {completed ? 'Review lessons' : started ? 'Continue where you stopped' : 'Start course'}
                                </Link>
                            </Button>
                        ) : undefined
                    }
                />

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Lessons</CardTitle>
                            <CardDescription>
                                {progress.counted === progress.total
                                    ? 'Complete every lesson to finish the course.'
                                    : 'Complete the required lessons to finish the course. Optional lessons are extra reading.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {lessons.length === 0 ? (
                                <p className="text-muted-foreground text-sm">This course has no lessons.</p>
                            ) : (
                                <ol className="divide-y">
                                    {lessons.map((lesson, index) => (
                                        <li key={lesson.id}>
                                            <Link
                                                href={`/recruiter/training/courses/${course.id}/lessons/${lesson.id}`}
                                                className="hover:bg-muted/50 -mx-2 flex items-start gap-3 rounded-lg px-2 py-3"
                                            >
                                                <span className="mt-0.5" aria-hidden>
                                                    {lesson.completed ? (
                                                        <CheckCircle2 className="size-5 text-emerald-600" />
                                                    ) : lesson.started ? (
                                                        <CircleDot className="size-5 text-sky-600" />
                                                    ) : (
                                                        <Circle className="text-muted-foreground size-5" />
                                                    )}
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm font-medium">
                                                        {index + 1}. {lesson.title}
                                                    </span>
                                                    <span className="text-muted-foreground block text-xs">
                                                        {[lesson.content_type_label, lesson.duration_minutes ? `${lesson.duration_minutes} min` : null]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                        {lesson.completed ? ' · Completed' : lesson.started ? ' · Started' : ''}
                                                    </span>
                                                </span>
                                                <RequiredBadge required={lesson.is_required} />
                                            </Link>
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between gap-2">
                                    <CardTitle>Your progress</CardTitle>
                                    <TrainingStatusBadge status={assignment.status} label={assignment.status_label} />
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <TrainingProgressBar percent={progress.percent} label="Course progress" />
                                <dl className="grid grid-cols-2 gap-x-3 gap-y-1.5 text-sm">
                                    <dt className="text-muted-foreground">Lessons completed</dt>
                                    <dd className="text-right">
                                        {progress.completed}/{progress.counted}
                                    </dd>
                                    <dt className="text-muted-foreground">Estimated time</dt>
                                    <dd className="text-right">{formatMinutes(version.estimated_minutes)}</dd>
                                    <dt className="text-muted-foreground">Assigned</dt>
                                    <dd className="text-right">{formatTrainingDate(assignment.assigned_at)}</dd>
                                    <dt className="text-muted-foreground">Due</dt>
                                    <dd className={assignment.status === 'overdue' ? 'text-destructive text-right font-medium' : 'text-right'}>
                                        {formatTrainingDate(assignment.due_at)}
                                    </dd>
                                    {assignment.completed_at && (
                                        <>
                                            <dt className="text-muted-foreground">Completed</dt>
                                            <dd className="text-right">{formatTrainingDate(assignment.completed_at)}</dd>
                                        </>
                                    )}
                                    <dt className="text-muted-foreground">Version</dt>
                                    <dd className="text-right">{version.label}</dd>
                                </dl>
                            </CardContent>
                        </Card>

                        {quizzes.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Quizzes</CardTitle>
                                    <CardDescription>Quizzes that go with this course.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    {quizzes.map((quiz) => (
                                        <Link
                                            key={quiz.id}
                                            href={`/recruiter/assessments/${quiz.id}`}
                                            className="hover:bg-muted/50 flex items-center justify-between gap-2 rounded-lg border px-3 py-2"
                                        >
                                            <span className="min-w-0">
                                                <span className="block text-sm font-medium">{quiz.title}</span>
                                                <span className="text-muted-foreground block text-xs">{quiz.status_label}</span>
                                            </span>
                                            <ResultBadge result={quiz.result} label={quiz.result_label} />
                                        </Link>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {(course.category || version.description) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>About this course</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground space-y-2 text-sm">
                                    {course.category && <p>{course.category}</p>}
                                    {version.description && <p className="whitespace-pre-line">{version.description}</p>}
                                </CardContent>
                            </Card>
                        )}

                        {history.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Earlier versions completed</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-1 text-sm">
                                        {history.map((entry) => (
                                            <li key={entry.version} className="flex justify-between">
                                                <span>{entry.version}</span>
                                                <span className="text-muted-foreground">{formatTrainingDate(entry.completed_at)}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </RecruiterLayout>
    );
}
