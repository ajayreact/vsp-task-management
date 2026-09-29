import { TrainingAudioPlayer, type TrainingAudioConfig } from '@/components/recruiter-operations/training/training-audio-player';
import { postJson } from '@/components/recruiter-operations/training/training-http';
import { TrainingLessonContent, type TrainingLessonData } from '@/components/recruiter-operations/training/training-lesson-content';
import { RequiredBadge, TrainingProgressBar } from '@/components/recruiter-operations/training/training-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, ChevronLeft, ChevronRight, Circle, LoaderCircle } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface Props {
    course: { id: number; title: string };
    version: string;
    lesson: TrainingLessonData;
    position: { index: number; total: number };
    completion: { completed: boolean; completed_at: string | null; audio_progress_seconds: number; time_spent_seconds: number };
    previousLesson: { id: number; title: string } | null;
    nextLesson: { id: number; title: string } | null;
    outline: { id: number; title: string; is_required: boolean; completed: boolean }[];
    progress: { percent: number; completed: number; counted: number };
    courseCompleted: boolean;
    audio: TrainingAudioConfig;
    urls: { complete: string; progress: string };
}

const HEARTBEAT_MS = 60000;

export default function TrainingLesson({
    course,
    version,
    lesson,
    position,
    completion,
    previousLesson,
    nextLesson,
    outline,
    progress,
    courseCompleted,
    audio,
    urls,
}: Props) {
    const [completing, setCompleting] = useState(false);
    const lastBeatRef = useRef(Date.now());

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'My Training', href: '/recruiter/training/my-training' },
        { title: course.title, href: `/recruiter/training/courses/${course.id}` },
        { title: lesson.title, href: `/recruiter/training/courses/${course.id}/lessons/${lesson.id}` },
    ];

    const lessonUrl = (id: number) => `/recruiter/training/courses/${course.id}/lessons/${id}`;

    // Time on the page and audio position. Informational only: the server
    // never completes a lesson from these reports.
    const send = useCallback(
        (extra: { audio_seconds?: number }, keepalive = false) => {
            const now = Date.now();
            const spent = Math.round((now - lastBeatRef.current) / 1000);
            lastBeatRef.current = now;
            const visibleSpent = document.visibilityState === 'visible' ? Math.min(spent, 300) : 0;

            postJson(urls.progress, { spent_seconds: visibleSpent, ...extra }, keepalive).catch(() => {
                // Progress reporting is best effort.
            });
        },
        [urls.progress],
    );

    useEffect(() => {
        lastBeatRef.current = Date.now();
        const timer = window.setInterval(() => send({}), HEARTBEAT_MS);

        return () => {
            window.clearInterval(timer);
            send({}, true);
        };
    }, [send]);

    const reportAudio = useCallback((seconds: number) => send({ audio_seconds: seconds }), [send]);

    const markComplete = () => {
        router.post(
            urls.complete,
            {},
            {
                onStart: () => setCompleting(true),
                onFinish: () => setCompleting(false),
            },
        );
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={lesson.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
                    <article className="min-w-0 space-y-5">
                        <header className="space-y-2">
                            <div className="text-muted-foreground flex flex-wrap items-center gap-2 text-xs">
                                <Link href={`/recruiter/training/courses/${course.id}`} className="hover:underline">
                                    {course.title}
                                </Link>
                                <span>·</span>
                                <span>
                                    Lesson {position.index} of {position.total}
                                </span>
                                <span>·</span>
                                <span>{lesson.content_type_label}</span>
                                {lesson.duration_minutes && (
                                    <>
                                        <span>·</span>
                                        <span>{lesson.duration_minutes} min</span>
                                    </>
                                )}
                            </div>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <h1 className="text-2xl font-semibold tracking-tight">{lesson.title}</h1>
                                <div className="flex items-center gap-2">
                                    <RequiredBadge required={lesson.is_required} />
                                    {completion.completed && (
                                        <Badge variant="success">
                                            <CheckCircle2 className="size-3.5" /> Completed
                                        </Badge>
                                    )}
                                </div>
                            </div>
                        </header>

                        <TrainingAudioPlayer
                            key={lesson.id}
                            audio={audio}
                            initialSeconds={completion.audio_progress_seconds}
                            onPositionReport={reportAudio}
                        />

                        <TrainingLessonContent lesson={lesson} />

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-5">
                            {previousLesson ? (
                                <Button asChild variant="outline">
                                    <Link href={lessonUrl(previousLesson.id)}>
                                        <ChevronLeft /> Previous lesson
                                    </Link>
                                </Button>
                            ) : (
                                <span />
                            )}

                            <div className="flex flex-wrap items-center gap-2">
                                {completion.completed ? (
                                    nextLesson ? (
                                        <Button asChild>
                                            <Link href={lessonUrl(nextLesson.id)}>
                                                Next lesson <ChevronRight />
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button asChild variant="outline">
                                            <Link href={`/recruiter/training/courses/${course.id}`}>Back to course</Link>
                                        </Button>
                                    )
                                ) : (
                                    <>
                                        {nextLesson && (
                                            <Button asChild variant="ghost">
                                                <Link href={lessonUrl(nextLesson.id)}>
                                                    Skip for now <ChevronRight />
                                                </Link>
                                            </Button>
                                        )}
                                        <Button onClick={markComplete} disabled={completing} className="bg-emerald-600 text-white hover:bg-emerald-700">
                                            {completing ? <LoaderCircle className="animate-spin" /> : <CheckCircle2 />}
                                            Mark lesson complete
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                        <p className="text-muted-foreground text-xs">
                            Listening to the audio does not complete a lesson. Press “Mark lesson complete” once you have read or listened to it.
                        </p>
                    </article>

                    <aside className="space-y-4">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm">{courseCompleted ? 'Course completed' : 'Course progress'}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <TrainingProgressBar percent={progress.percent} label="Course progress" />
                                <p className="text-muted-foreground text-xs">
                                    {progress.completed} of {progress.counted} lessons · {version}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm">Lessons</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ol className="space-y-0.5">
                                    {outline.map((item, index) => (
                                        <li key={item.id}>
                                            <Link
                                                href={lessonUrl(item.id)}
                                                className={cn(
                                                    'flex items-start gap-2 rounded-md px-2 py-1.5 text-sm',
                                                    item.id === lesson.id ? 'bg-emerald-600/10 font-medium' : 'hover:bg-muted/60',
                                                )}
                                                aria-current={item.id === lesson.id ? 'page' : undefined}
                                            >
                                                {item.completed ? (
                                                    <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                                                ) : (
                                                    <Circle className="text-muted-foreground mt-0.5 size-4 shrink-0" />
                                                )}
                                                <span className="min-w-0">
                                                    {index + 1}. {item.title}
                                                    {!item.is_required && <span className="text-muted-foreground"> (optional)</span>}
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ol>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </div>
        </RecruiterLayout>
    );
}
