import { PanelEmpty } from '@/components/admin/dashboard-panel';
import { PageHeader } from '@/components/admin/page-header';
import {
    BENCH_SALES_EMPTY,
    TrackSwitcher,
    TrainingAssignmentCard,
    trackHref,
    type TrackOption,
    type TrainingCard,
} from '@/components/recruiter-operations/training/training-tracks';
import { ContentStatusBadge, formatMinutes, TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Settings2, UserPlus } from 'lucide-react';

interface TrackCourse {
    id: number;
    title: string;
    description: string | null;
    status: string;
    status_label: string;
    duration_minutes: number | null;
    modules: number;
    lessons: number;
}

interface Props {
    track: { slug: string; name: string; description: string | null };
    isLearner: boolean;
    hasEmployeeProfile: boolean;
    assignments: TrainingCard[];
    courses: TrackCourse[];
    tracks: TrackOption[];
    can: { manage: boolean; assign: boolean; viewTeam: boolean };
}

const BENCH_SALES = 'bench-sales-recruiter';

export default function TrainingTrack({ track, isLearner, hasEmployeeProfile, assignments, courses, tracks, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: track.name, href: trackHref(track.slug) },
    ];
    const showCourses = tracks.length > 0;
    const emptyText =
        track.slug === BENCH_SALES
            ? BENCH_SALES_EMPTY
            : isLearner && !showCourses
              ? 'No training has been assigned to you in this track yet.'
              : 'No courses in this track yet.';

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`${track.name} Training`} />

            <div className="flex max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={track.name}
                    description={track.description ?? undefined}
                    action={
                        showCourses && can.manage ? (
                            <Button asChild variant="outline">
                                <Link href={`/recruiter/training/manage?track=${track.slug}`}>
                                    <Settings2 /> Manage this track
                                </Link>
                            </Button>
                        ) : undefined
                    }
                />
                <TrainingSubNav />

                {showCourses && <TrackSwitcher tracks={tracks} value={track.slug} onChange={(slug) => slug && router.get(trackHref(slug))} />}

                {isLearner && !hasEmployeeProfile && (
                    <PanelEmpty>Training is assigned to employees. Your account has no employee profile.</PanelEmpty>
                )}

                {isLearner && hasEmployeeProfile && (!showCourses || assignments.length > 0) && (
                    <section aria-label={`Your ${track.name} courses`} className="space-y-3">
                        {showCourses && <h2 className="text-base font-semibold">Your courses</h2>}
                        {assignments.length === 0 ? (
                            <PanelEmpty>
                                <span data-testid="track-empty">{emptyText}</span>
                            </PanelEmpty>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {assignments.map((card) => (
                                    <TrainingAssignmentCard key={card.id} card={card} />
                                ))}
                            </div>
                        )}
                    </section>
                )}

                {showCourses && (
                    <section aria-label={`${track.name} courses`} className="space-y-3">
                        {isLearner && assignments.length > 0 && <h2 className="text-base font-semibold">All courses in this track</h2>}
                        {courses.length === 0 ? (
                            <div className="flex flex-col items-start gap-3 rounded-xl border border-dashed p-6">
                                <p className="text-muted-foreground text-sm" data-testid="track-empty">
                                    {emptyText}
                                </p>
                                {can.manage && (
                                    <Button asChild size="sm">
                                        <Link href={`/recruiter/training/manage/courses/create?track=${track.slug}`}>
                                            <Plus /> Add a course to this track
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {courses.map((course, index) => (
                                    <Card key={course.id} className="flex min-w-0 flex-col" data-testid="track-course">
                                        <CardHeader className="space-y-2">
                                            <div className="flex items-start justify-between gap-3">
                                                <span className="text-muted-foreground text-xs font-medium">Course {index + 1}</span>
                                                <ContentStatusBadge status={course.status} label={course.status_label} />
                                            </div>
                                            <CardTitle className="text-base leading-snug break-words">{course.title}</CardTitle>
                                        </CardHeader>
                                        <CardContent className="mt-auto space-y-3">
                                            <dl className="text-muted-foreground grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                                                <dt>Duration</dt>
                                                <dd className="text-foreground text-right">{formatMinutes(course.duration_minutes)}</dd>
                                                <dt>Modules</dt>
                                                <dd className="text-foreground text-right tabular-nums">{course.modules}</dd>
                                                <dt>Lessons</dt>
                                                <dd className="text-foreground text-right tabular-nums">{course.lessons}</dd>
                                            </dl>
                                            <div className="flex flex-wrap gap-2">
                                                {can.manage && (
                                                    <Button asChild size="sm">
                                                        <Link href={`/recruiter/training/manage/courses/${course.id}`}>Open course</Link>
                                                    </Button>
                                                )}
                                                {can.assign && course.status === 'published' && course.lessons > 0 && (
                                                    <Button asChild size="sm" variant="outline">
                                                        <Link href={`/recruiter/training/assignments/create?course=${course.id}`}>
                                                            <UserPlus /> Assign
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </section>
                )}
            </div>
        </RecruiterLayout>
    );
}
