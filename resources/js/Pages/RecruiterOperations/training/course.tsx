import { ResultBadge } from '@/components/recruiter-operations/assessments/assessment-ui';
import { TrainingAudioPlayer, type TrainingAudioConfig } from '@/components/recruiter-operations/training/training-audio-player';
import {
    BackToModulesButton,
    ModuleTabs,
    scrollToAnchor,
    useScrollSpy,
    type CourseModule,
} from '@/components/recruiter-operations/training/training-course-navigation';
import { postJson } from '@/components/recruiter-operations/training/training-http';
import { resolveLessonLanguage, TrainingLanguageSelect, useTrainingLanguage } from '@/components/recruiter-operations/training/training-language';
import { TrainingLessonContent, type TrainingLessonData } from '@/components/recruiter-operations/training/training-lesson-content';
import { ListenModeToggle, ListenOnlyNotice, useListenMode, type ListenMode } from '@/components/recruiter-operations/training/training-listen-mode';
import {
    formatMinutes,
    formatTrainingDate,
    RequiredBadge,
    TrainingProgressBar,
    TrainingStatusBadge,
} from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    CalendarClock,
    Check,
    CheckCircle2,
    Clock,
    GraduationCap,
    Info,
    Layers,
    LoaderCircle,
    PartyPopper,
    Play,
    RotateCcw,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';

interface CourseLesson extends TrainingLessonData {
    counted: boolean;
    started: boolean;
    completed: boolean;
    completed_at: string | null;
    audio_progress_seconds: number;
    audio: { has_text_by_language: Record<string, boolean>; speech_url: string; has_text: boolean };
    urls: { complete: string; progress: string };
}

interface Progress {
    percent: number;
    completed: number;
    counted: number;
    total: number;
    remaining: number;
}

interface Assignment {
    status: string;
    status_label: string;
    assigned_at: string;
    due_at: string | null;
    started_at: string | null;
    completed_at: string | null;
}

interface CompleteResponse {
    lesson_id: number;
    completed_at: string | null;
    progress: Progress;
    assignment: { status: string; status_label: string; completed_at: string | null };
    course_completed: boolean;
    message: string;
}

interface Props {
    course: { id: number; title: string; description: string | null; category: string | null };
    version: { label: string; description: string | null; estimated_minutes: number | null };
    assignment: Assignment;
    progress: Progress;
    modules: CourseModule[];
    lessons: CourseLesson[];
    audio: Omit<TrainingAudioConfig, 'speech_url' | 'has_text' | 'has_text_by_language'>;
    resumeLessonId: number | null;
    history: { version: string; completed_at: string | null }[];
    quizzes: { id: number; title: string; status_label: string; result: string | null; result_label: string | null }[];
}

const HEARTBEAT_MS = 60000;
const START_AFTER_MS = 4000;
/** Pixels from the top of the window at which a lesson counts as the one being read. */
const READING_LINE = 280;

const lessonAnchor = (id: number) => `lesson-${id}`;
const HERO_CHIP = 'inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 ring-1 ring-white/25 backdrop-blur-sm';

function ProgressRing({ percent, className }: { percent: number; className?: string }) {
    const value = Math.max(0, Math.min(100, Math.round(percent)));
    const radius = 42;
    const circumference = 2 * Math.PI * radius;

    return (
        <div className={cn('relative size-28', className)} aria-hidden>
            <svg viewBox="0 0 100 100" className="size-full -rotate-90">
                <circle cx="50" cy="50" r={radius} fill="none" strokeWidth="8" className="stroke-white/20" />
                <circle
                    cx="50"
                    cy="50"
                    r={radius}
                    fill="none"
                    strokeWidth="8"
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={circumference * (1 - value / 100)}
                    className="stroke-white transition-[stroke-dashoffset] duration-700"
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center leading-none">
                <span className="text-2xl font-bold tabular-nums">{value}%</span>
                <span className="mt-1 text-[11px] font-medium text-white/80">complete</span>
            </div>
        </div>
    );
}

/**
 * Completions saved on this page, by course. Back and Forward remount the page
 * from the props stored in that history entry, which predate them.
 */
const savedCompletions = new Map<
    number,
    { lessons: Record<number, string | null>; progress: Progress; assignment: CompleteResponse['assignment'] }
>();

/**
 * The whole course on one page: every lesson of the assigned version, in
 * order, grouped into modules with sticky tabs. Completing a lesson is an
 * explicit button that saves in the background; the recruiter stays where
 * they are. Listening never completes a lesson.
 */
export default function TrainingCourse({
    course,
    version,
    assignment: initialAssignment,
    progress: initialProgress,
    modules,
    lessons: initialLessons,
    audio,
    history,
    quizzes,
}: Props) {
    const saved = savedCompletions.get(course.id);
    const [lessons, setLessons] = useState(() =>
        initialLessons.map((lesson) =>
            saved && lesson.id in saved.lessons && !lesson.completed
                ? { ...lesson, started: true, completed: true, completed_at: saved.lessons[lesson.id] }
                : lesson,
        ),
    );
    const [progress, setProgress] = useState(() =>
        saved && saved.progress.completed > initialProgress.completed ? saved.progress : initialProgress,
    );
    const [assignment, setAssignment] = useState(() =>
        saved && saved.progress.completed > initialProgress.completed ? { ...initialAssignment, ...saved.assignment } : initialAssignment,
    );
    const [completing, setCompleting] = useState<number | null>(null);
    const [chosenLanguage, chooseLanguage] = useTrainingLanguage();
    const [listenMode, chooseListenMode] = useListenMode();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'My Training', href: '/recruiter/training/my-training' },
        { title: course.title, href: `/recruiter/training/courses/${course.id}` },
    ];

    const lessonsById = useMemo(() => new Map(lessons.map((lesson) => [lesson.id, lesson])), [lessons]);
    const positions = useMemo(() => new Map(lessons.map((lesson, index) => [lesson.id, index + 1])), [lessons]);
    const moduleAnchors = useMemo(() => modules.map((module) => module.anchor), [modules]);
    const lessonAnchors = useMemo(() => lessons.map((lesson) => lessonAnchor(lesson.id)), [lessons]);
    const activeModule = useScrollSpy(moduleAnchors);
    const currentLessonAnchor = useScrollSpy(lessonAnchors, READING_LINE);
    const pageLanguages = lessons.find((lesson) => lesson.languages.length > 1)?.languages ?? [];
    const courseCompleted = assignment.status === 'completed';

    const moduleProgress = useMemo(() => {
        const result: Record<string, { completed: number; counted: number }> = {};

        for (const module of modules) {
            const items = module.lesson_ids.map((id) => lessonsById.get(id)).filter((lesson): lesson is CourseLesson => !!lesson);
            const counted = items.some((lesson) => lesson.counted) ? items.filter((lesson) => lesson.counted) : items;
            result[module.anchor] = { completed: counted.filter((lesson) => lesson.completed).length, counted: counted.length };
        }

        return result;
    }, [modules, lessonsById]);

    const firstIncomplete = lessons.find((lesson) => lesson.counted && !lesson.completed) ?? lessons.find((lesson) => !lesson.completed);
    const notStarted = progress.completed === 0 && !assignment.started_at;
    const nextStep: { label: string; lesson: CourseLesson; icon: ReactNode } | null =
        lessons.length === 0
            ? null
            : courseCompleted || !firstIncomplete
              ? { label: 'Review Course', lesson: lessons[0], icon: <RotateCcw /> }
              : notStarted
                ? { label: 'Start Course', lesson: lessons[0], icon: <Play /> }
                : { label: 'Continue Learning', lesson: firstIncomplete, icon: <Play /> };

    // Module navigation. Inertia treats hash-only changes as replacements, so the
    // history entry is pushed natively (carrying Inertia's state) and Inertia's
    // current URL is then updated, otherwise its scroll bookkeeping strips the hash.
    // Inertia throttles scroll saves, so the exact position is recorded on the
    // entry being left before the new one is pushed.
    const selectModule = useCallback((anchor: string) => {
        const position = { top: window.scrollY, left: window.scrollX };
        if (scrollToAnchor(anchor) && window.location.hash !== `#${anchor}`) {
            const url = `${window.location.pathname}${window.location.search}#${anchor}`;
            window.history.replaceState({ ...window.history.state, documentScrollPosition: position }, '', window.location.href);
            window.history.pushState(window.history.state, '', url);
            router.replace({ url, preserveScroll: true, preserveState: true });
        }
    }, []);

    useEffect(() => {
        const anchor = decodeURIComponent(window.location.hash.slice(1));
        const restoring = window.history.state?.documentScrollPosition !== undefined;

        if (anchor && !restoring) {
            scrollToAnchor(anchor, 'auto');
        }
    }, []);

    // Progress reports: which lesson is being read and for how long. Informational only.
    const currentLessonId = currentLessonAnchor ? Number(currentLessonAnchor.replace('lesson-', '')) : null;
    const currentRef = useRef<number | null>(null);
    const lastBeatRef = useRef(Date.now());
    const startedRef = useRef(new Set(initialLessons.filter((lesson) => lesson.started).map((lesson) => lesson.id)));

    const report = useCallback(
        (lessonId: number, extra: { audio_seconds?: number; spent_seconds?: number }, keepalive = false) => {
            const lesson = lessonsById.get(lessonId);

            if (!lesson) {
                return;
            }

            postJson(lesson.urls.progress, extra, keepalive).catch(() => {
                // Progress reporting is best effort.
            });
        },
        [lessonsById],
    );

    const beat = useCallback(
        (keepalive = false) => {
            const now = Date.now();
            const spent = Math.round((now - lastBeatRef.current) / 1000);
            lastBeatRef.current = now;

            if (currentRef.current !== null && document.visibilityState === 'visible' && spent > 0) {
                report(currentRef.current, { spent_seconds: Math.min(spent, 300) }, keepalive);
            }
        },
        [report],
    );

    useEffect(() => {
        if (currentLessonId === null || currentRef.current === currentLessonId) {
            return;
        }

        beat();
        currentRef.current = currentLessonId;

        if (startedRef.current.has(currentLessonId)) {
            return;
        }

        const timer = window.setTimeout(() => {
            if (currentRef.current === currentLessonId && !startedRef.current.has(currentLessonId)) {
                startedRef.current.add(currentLessonId);
                report(currentLessonId, { spent_seconds: 0 });
            }
        }, START_AFTER_MS);

        return () => window.clearTimeout(timer);
    }, [beat, currentLessonId, report]);

    useEffect(() => {
        lastBeatRef.current = Date.now();
        const timer = window.setInterval(() => beat(), HEARTBEAT_MS);

        return () => {
            window.clearInterval(timer);
            beat(true);
        };
    }, [beat]);

    const markComplete = async (lesson: CourseLesson) => {
        setCompleting(lesson.id);

        try {
            const result = await postJson<CompleteResponse>(lesson.urls.complete, {});

            if (result) {
                const previous = savedCompletions.get(course.id);
                savedCompletions.set(course.id, {
                    lessons: { ...(previous?.lessons ?? {}), [lesson.id]: result.completed_at },
                    progress: result.progress,
                    assignment: result.assignment,
                });
                startedRef.current.add(lesson.id);
                setLessons((current) =>
                    current.map((item) =>
                        item.id === lesson.id ? { ...item, started: true, completed: true, completed_at: result.completed_at } : item,
                    ),
                );
                setProgress(result.progress);
                setAssignment((current) => ({ ...current, ...result.assignment, started_at: current.started_at ?? new Date().toISOString() }));
                toast.success(result.message);
            }
        } catch (caught) {
            toast.error(caught instanceof Error ? caught.message : 'The lesson could not be marked complete. Please try again.');
        } finally {
            setCompleting(null);
        }
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs} contentClassName="overflow-x-clip">
            <Head title={course.title} />

            <div className="flex flex-1 flex-col">
                <header className="mx-auto w-full max-w-4xl px-4 pt-3 pb-4 md:px-6" data-testid="course-header">
                    <Link
                        href="/recruiter/training/my-training"
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm font-medium"
                    >
                        <ArrowLeft className="size-4" /> Back to My Training
                    </Link>

                    <div className="mt-3 overflow-hidden rounded-2xl border shadow-sm" data-testid="course-progress">
                        <div className="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-600 to-teal-600 px-5 py-6 text-white sm:px-7">
                            <div aria-hidden className="pointer-events-none absolute -top-20 -right-12 size-60 rounded-full bg-white/10" />
                            <div aria-hidden className="pointer-events-none absolute right-28 -bottom-24 size-44 rounded-full bg-white/5" />
                            <div className="relative flex items-center gap-6">
                                <div className="min-w-0 flex-1 space-y-3">
                                    <div className="flex flex-wrap items-center gap-2 text-xs font-medium">
                                        {course.category && (
                                            <span className={HERO_CHIP}>
                                                <GraduationCap className="size-3.5" aria-hidden /> {course.category}
                                            </span>
                                        )}
                                        <span className={HERO_CHIP} data-testid="course-version">
                                            <Layers className="size-3.5" aria-hidden /> {version.label}
                                        </span>
                                        <span className={HERO_CHIP}>
                                            <BookOpen className="size-3.5" aria-hidden /> {lessons.length}{' '}
                                            {lessons.length === 1 ? 'lesson' : 'lessons'}
                                        </span>
                                        {version.estimated_minutes ? (
                                            <span className={HERO_CHIP}>
                                                <Clock className="size-3.5" aria-hidden /> {formatMinutes(version.estimated_minutes)}
                                            </span>
                                        ) : null}
                                        {assignment.due_at && (
                                            <span className={cn(HERO_CHIP, assignment.status === 'overdue' && 'bg-red-500/90 ring-red-300/50')}>
                                                <CalendarClock className="size-3.5" aria-hidden /> Due {formatTrainingDate(assignment.due_at)}
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <h1 className="text-2xl font-bold tracking-tight text-balance md:text-3xl">{course.title}</h1>
                                        <span className="rounded-full bg-white shadow-sm">
                                            <TrainingStatusBadge status={assignment.status} label={assignment.status_label} />
                                        </span>
                                    </div>
                                    {course.description && (
                                        <p className="line-clamp-2 max-w-2xl text-sm leading-relaxed text-white/85">{course.description}</p>
                                    )}
                                </div>
                                <ProgressRing percent={progress.percent} className="hidden shrink-0 sm:block" />
                            </div>
                        </div>

                        <div className="bg-card flex flex-col gap-4 p-4 sm:px-6 lg:flex-row lg:items-center lg:gap-6">
                            <div className="min-w-0 flex-1 space-y-2">
                                <div className="flex items-baseline justify-between gap-3">
                                    <p className="text-sm font-medium" data-testid="course-progress-count">
                                        {progress.completed} / {progress.counted} lessons completed
                                        <span className="text-muted-foreground font-normal">
                                            {' · '}
                                            {progress.remaining === 0 ? 'nothing left to finish' : `${progress.remaining} remaining`}
                                        </span>
                                    </p>
                                    <span className="text-sm font-semibold text-emerald-700 tabular-nums lg:hidden dark:text-emerald-300">
                                        {Math.round(progress.percent)}%
                                    </span>
                                </div>
                                <TrainingProgressBar percent={progress.percent} label="Course progress" showValue={false} />
                            </div>
                            {nextStep && (
                                <Button
                                    onClick={() => scrollToAnchor(lessonAnchor(nextStep.lesson.id))}
                                    className="h-auto min-h-11 w-full min-w-0 justify-start gap-3 rounded-xl bg-emerald-600 px-4 py-2 text-left text-white shadow-md shadow-emerald-600/25 hover:bg-emerald-700 lg:w-auto lg:max-w-80"
                                    data-testid="course-action"
                                >
                                    {nextStep.icon}
                                    <span className="flex min-w-0 flex-col leading-tight">
                                        <span className="font-semibold">{nextStep.label}</span>
                                        <span className="truncate text-xs font-normal opacity-90">{nextStep.lesson.title}</span>
                                    </span>
                                </Button>
                            )}
                        </div>
                        <div className="bg-card flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t px-4 py-2.5 sm:px-6">
                            <ListenModeToggle value={listenMode} onChange={chooseListenMode} />
                            {pageLanguages.length > 1 && (
                                <TrainingLanguageSelect languages={pageLanguages} value={chosenLanguage} onChange={chooseLanguage} compact />
                            )}
                        </div>
                    </div>
                </header>

                {modules.length > 0 && <ModuleTabs modules={modules} active={activeModule} progress={moduleProgress} onSelect={selectModule} />}

                <div className="mx-auto w-full max-w-4xl space-y-10 px-4 pt-6 pb-24 md:px-6">
                    {lessons.length === 0 && <p className="text-muted-foreground text-sm">This course has no lessons.</p>}

                    {modules.map((module) => {
                        const counts = moduleProgress[module.anchor];
                        const percent = counts && counts.counted > 0 ? (counts.completed * 100) / counts.counted : 0;

                        return (
                            <section key={module.anchor} id={module.anchor} aria-labelledby={`${module.anchor}-title`} data-testid="course-module">
                                <div className="flex flex-col gap-4 rounded-xl border border-emerald-600/15 bg-emerald-50/70 p-4 lg:flex-row lg:items-center lg:justify-between dark:bg-emerald-950/20">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <span
                                            aria-hidden
                                            className="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-base font-semibold text-white tabular-nums"
                                        >
                                            {module.number}
                                        </span>
                                        <div className="min-w-0">
                                            <p className="text-xs font-semibold tracking-wider text-emerald-700 uppercase dark:text-emerald-300">
                                                Module {module.number} of {modules.length}
                                            </p>
                                            <h2 id={`${module.anchor}-title`} className="text-xl font-semibold tracking-tight md:text-2xl">
                                                {module.title}
                                            </h2>
                                        </div>
                                    </div>
                                    <div className="w-full space-y-1.5 lg:w-44 lg:shrink-0">
                                        <p
                                            className="text-muted-foreground flex justify-between text-xs tabular-nums"
                                            data-testid="module-lesson-count"
                                        >
                                            <span>
                                                {counts?.completed ?? 0} / {module.lesson_ids.length}{' '}
                                                {module.lesson_ids.length === 1 ? 'lesson' : 'lessons'}
                                            </span>
                                            <span>{Math.round(percent)}%</span>
                                        </p>
                                        <TrainingProgressBar percent={percent} label={`${module.title} progress`} showValue={false} />
                                    </div>
                                </div>

                                <div className="divide-y">
                                    {module.lesson_ids.map((id) => {
                                        const lesson = lessonsById.get(id);

                                        return lesson ? (
                                            <CourseLessonArticle
                                                key={lesson.id}
                                                lesson={lesson}
                                                position={positions.get(lesson.id) ?? 0}
                                                total={lessons.length}
                                                chosenLanguage={chosenLanguage}
                                                listenMode={listenMode}
                                                audio={audio}
                                                completing={completing === lesson.id}
                                                onComplete={() => markComplete(lesson)}
                                                onAudioPosition={(seconds) => report(lesson.id, { audio_seconds: seconds })}
                                            />
                                        ) : null;
                                    })}
                                </div>
                            </section>
                        );
                    })}

                    {lessons.length > 0 && (
                        <section
                            aria-labelledby="course-completion-title"
                            className="bg-muted/40 space-y-4 rounded-xl p-5"
                            data-testid="course-summary"
                        >
                            <div className="flex items-start gap-3">
                                {courseCompleted ? (
                                    <PartyPopper className="mt-0.5 size-6 shrink-0 text-emerald-600" />
                                ) : (
                                    <CheckCircle2 className="text-muted-foreground mt-0.5 size-6 shrink-0" />
                                )}
                                <div className="space-y-1">
                                    <h2 id="course-completion-title" className="text-lg font-semibold">
                                        {courseCompleted ? 'Course completed' : 'Course completion'}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {courseCompleted
                                            ? `You completed this course${assignment.completed_at ? ` on ${formatTrainingDate(assignment.completed_at)}` : ''}. You can come back to any lesson at any time.`
                                            : `${progress.completed} of ${progress.counted} lessons completed, ${progress.remaining} remaining.${progress.counted < progress.total ? ' Optional lessons are extra reading and do not count.' : ''}`}
                                    </p>
                                </div>
                            </div>
                            <TrainingProgressBar percent={progress.percent} label="Course progress" />
                            {!courseCompleted && firstIncomplete && (
                                <Button variant="outline" size="sm" onClick={() => scrollToAnchor(lessonAnchor(firstIncomplete.id))}>
                                    Go to the next lesson to complete: {firstIncomplete.title}
                                </Button>
                            )}

                            {quizzes.length > 0 && (
                                <div className="space-y-2 pt-2">
                                    <h3 className="text-sm font-semibold">Quizzes for this course</h3>
                                    {quizzes.map((quiz) => (
                                        <Link
                                            key={quiz.id}
                                            href={`/recruiter/assessments/${quiz.id}`}
                                            className="bg-background hover:bg-muted/50 flex items-center justify-between gap-2 rounded-lg px-3 py-2"
                                        >
                                            <span className="min-w-0">
                                                <span className="block text-sm font-medium">{quiz.title}</span>
                                                <span className="text-muted-foreground block text-xs">{quiz.status_label}</span>
                                            </span>
                                            <ResultBadge result={quiz.result} label={quiz.result_label} />
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {history.length > 0 && (
                                <div className="space-y-1 pt-2 text-sm">
                                    <h3 className="font-semibold">Earlier versions completed</h3>
                                    {history.map((entry) => (
                                        <p key={entry.version} className="text-muted-foreground flex justify-between">
                                            <span>{entry.version}</span>
                                            <span>{formatTrainingDate(entry.completed_at)}</span>
                                        </p>
                                    ))}
                                </div>
                            )}
                        </section>
                    )}
                </div>

                <BackToModulesButton />
            </div>
        </RecruiterLayout>
    );
}

function CourseLessonArticle({
    lesson,
    position,
    total,
    chosenLanguage,
    listenMode,
    audio,
    completing,
    onComplete,
    onAudioPosition,
}: {
    lesson: CourseLesson;
    position: number;
    total: number;
    chosenLanguage: string;
    listenMode: ListenMode;
    audio: Props['audio'];
    completing: boolean;
    onComplete: () => void;
    onAudioPosition: (seconds: number) => void;
}) {
    const { shown, fellBack } = resolveLessonLanguage(lesson.languages, chosenLanguage);
    const chosen = lesson.languages.find((item) => item.code === chosenLanguage);
    const language = shown?.code ?? 'en';
    const audioConfig: TrainingAudioConfig = { ...audio, ...lesson.audio };

    return (
        <article
            id={lessonAnchor(lesson.id)}
            aria-labelledby={`${lessonAnchor(lesson.id)}-title`}
            className="space-y-5 py-10 first:pt-6"
            data-testid="course-lesson"
            data-completed={lesson.completed ? 'true' : 'false'}
        >
            <header className="flex items-start gap-4">
                <span
                    aria-hidden
                    className={cn(
                        'hidden size-11 shrink-0 items-center justify-center rounded-full text-base font-semibold tabular-nums ring-4 sm:flex',
                        lesson.completed
                            ? 'bg-emerald-600 text-white ring-emerald-600/15'
                            : 'bg-background text-emerald-700 ring-emerald-600/15 dark:text-emerald-300',
                    )}
                >
                    {lesson.completed ? <Check className="size-5" /> : position}
                </span>
                <div className="min-w-0 flex-1 space-y-1.5">
                    <div className="text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                        <span className="font-medium" data-testid="lesson-number">
                            Lesson {position} of {total}
                        </span>
                        {lesson.duration_minutes && (
                            <>
                                <span aria-hidden>·</span>
                                <span>{lesson.duration_minutes} min</span>
                            </>
                        )}
                        {!lesson.is_required && <RequiredBadge required={false} />}
                        {lesson.completed && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-600/10 px-2 py-0.5 font-medium text-emerald-700 dark:text-emerald-300">
                                <CheckCircle2 className="size-3.5" /> Completed
                            </span>
                        )}
                    </div>
                    <h3 id={`${lessonAnchor(lesson.id)}-title`} className="text-xl font-semibold tracking-tight md:text-2xl">
                        {lesson.title}
                    </h3>
                    {lesson.description && <p className="text-muted-foreground leading-relaxed">{lesson.description}</p>}
                    {fellBack && chosen && (
                        <p className="bg-muted text-muted-foreground inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs" role="status">
                            <Info className="size-3.5 shrink-0" />
                            {chosen.label} not available — showing English
                        </p>
                    )}
                    {!fellBack && shown && !shown.canonical && shown.outdated && (
                        <p className="flex items-start gap-2 pt-1 text-sm text-amber-700 dark:text-amber-300" role="status">
                            <Info className="mt-0.5 size-4 shrink-0" />
                            The English lesson was updated after this translation. Check the English version for the latest content.
                        </p>
                    )}
                </div>
            </header>

            <TrainingAudioPlayer
                key={`${lesson.id}-${language}`}
                audio={audioConfig}
                language={language}
                initialSeconds={lesson.audio_progress_seconds}
                onPositionReport={onAudioPosition}
            />

            {listenMode === 'listen' ? (
                <ListenOnlyNotice />
            ) : (
                <TrainingLessonContent lesson={lesson} language={shown} headingLevel="h4" showDescription={false} variant="flow" />
            )}

            <footer className="flex flex-wrap items-center justify-between gap-3 pt-2">
                {lesson.completed ? (
                    <p className="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-300" role="status">
                        <CheckCircle2 className="size-5" /> Lesson Completed
                        {lesson.completed_at && (
                            <span className="text-muted-foreground font-normal">· {formatTrainingDate(lesson.completed_at)}</span>
                        )}
                    </p>
                ) : (
                    <>
                        <p className="text-muted-foreground text-xs">Listening to the audio does not complete the lesson.</p>
                        <Button onClick={onComplete} disabled={completing} className="bg-emerald-600 text-white hover:bg-emerald-700 max-sm:w-full">
                            {completing ? <LoaderCircle className="animate-spin" /> : <CheckCircle2 />}
                            Mark Lesson Complete
                        </Button>
                    </>
                )}
            </footer>
        </article>
    );
}
