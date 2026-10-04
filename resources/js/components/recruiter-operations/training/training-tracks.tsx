import { TrainingProgressBar, TrainingStatusBadge, formatMinutes, formatTrainingDate } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';

export interface TrainingCard {
    id: number;
    course: { id: number; title: string; description: string | null };
    category: string | null;
    track: string;
    version: string;
    status: string;
    status_label: string;
    progress_percent: number;
    lessons_completed: number;
    lessons_counted: number;
    lessons_total: number;
    estimated_minutes: number | null;
    assigned_at: string;
    due_at: string | null;
    completed_at: string | null;
}

export interface TrackCardData {
    slug: string;
    name: string;
    description: string | null;
    courses: number;
    lessons: number;
    modules?: number;
    completed?: number;
}

export interface TrackOption {
    value: string;
    label: string;
}

export const BENCH_SALES_EMPTY = 'Bench Sales training is being prepared.';

export function trackHref(slug: string): string {
    return `/recruiter/training/tracks/${slug}`;
}

function plural(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

/**
 * A track on the training landing page. Opening it is the only way to its
 * courses.
 */
export function TrackCard({ track, learner }: { track: TrackCardData; learner: boolean }) {
    const empty = track.courses === 0;

    return (
        <Card className="flex min-w-0 flex-col" data-testid="track-card" data-track={track.slug}>
            <CardHeader className="space-y-2">
                <div className="text-muted-foreground flex items-center gap-2 text-xs font-medium tracking-wide uppercase">
                    <GraduationCap className="size-4 text-emerald-700" aria-hidden />
                    Training track
                </div>
                <CardTitle className="text-lg leading-snug">{track.name}</CardTitle>
                {track.description && <p className="text-muted-foreground text-sm">{track.description}</p>}
            </CardHeader>
            <CardContent className="mt-auto space-y-4">
                {empty ? (
                    <p className="text-muted-foreground text-sm" data-testid="track-card-empty">
                        Courses will be added
                    </p>
                ) : (
                    <dl className="flex flex-wrap gap-x-6 gap-y-1 text-sm" data-testid="track-card-counts">
                        <div>
                            <dt className="sr-only">Courses</dt>
                            <dd className="font-semibold tabular-nums">{plural(track.courses, learner ? 'assigned course' : 'course')}</dd>
                        </div>
                        {track.modules !== undefined && (
                            <div>
                                <dt className="sr-only">Modules</dt>
                                <dd className="tabular-nums">{plural(track.modules, 'module')}</dd>
                            </div>
                        )}
                        <div>
                            <dt className="sr-only">Lessons</dt>
                            <dd className="tabular-nums">{plural(track.lessons, 'lesson')}</dd>
                        </div>
                        {learner && track.completed !== undefined && (
                            <div>
                                <dt className="sr-only">Completed</dt>
                                <dd className="text-muted-foreground tabular-nums">{track.completed} completed</dd>
                            </div>
                        )}
                    </dl>
                )}
                <Button asChild className="w-full sm:w-auto" variant={empty ? 'outline' : 'default'}>
                    <Link href={trackHref(track.slug)}>Open Training</Link>
                </Button>
            </CardContent>
        </Card>
    );
}

/**
 * The "Training Track" dropdown at the top of manager pages.
 */
export function TrackSwitcher({
    tracks,
    value,
    onChange,
    allLabel,
}: {
    tracks: TrackOption[];
    value: string;
    onChange: (value: string) => void;
    allLabel?: string;
}) {
    const ALL = 'all';

    return (
        <div className="flex min-w-0 flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-3">
            <Label htmlFor="training-track" className="shrink-0">
                Training Track
            </Label>
            <Select value={value || ALL} onValueChange={(next) => onChange(next === ALL ? '' : next)}>
                <SelectTrigger id="training-track" className="w-full sm:w-64" data-testid="track-switcher">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {allLabel && <SelectItem value={ALL}>{allLabel}</SelectItem>}
                    {tracks.map((track) => (
                        <SelectItem key={track.value} value={track.value}>
                            {track.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

/**
 * One of the signed-in recruiter's own assigned courses.
 */
export function TrainingAssignmentCard({ card }: { card: TrainingCard }) {
    return (
        <Card className="flex min-w-0 flex-col" data-testid="assignment-card">
            <CardHeader className="space-y-2">
                <div className="flex items-start justify-between gap-3">
                    <span className="text-muted-foreground text-xs">
                        {card.category ?? 'Training'} · {card.version}
                    </span>
                    <TrainingStatusBadge status={card.status} label={card.status_label} />
                </div>
                <CardTitle className="text-base leading-snug">{card.course.title}</CardTitle>
            </CardHeader>
            <CardContent className="mt-auto space-y-3">
                {card.course.description && <p className="text-muted-foreground line-clamp-2 text-sm">{card.course.description}</p>}
                <TrainingProgressBar percent={card.progress_percent} label={`${card.course.title} progress`} />
                <dl className="text-muted-foreground grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                    <dt>Lessons</dt>
                    <dd className="text-foreground text-right">
                        {card.lessons_completed}/{card.lessons_counted}
                    </dd>
                    <dt>Estimated time</dt>
                    <dd className="text-foreground text-right">{formatMinutes(card.estimated_minutes)}</dd>
                    <dt>{card.completed_at ? 'Completed' : 'Due'}</dt>
                    <dd className={card.status === 'overdue' ? 'text-destructive text-right font-medium' : 'text-foreground text-right'}>
                        {formatTrainingDate(card.completed_at ?? card.due_at)}
                    </dd>
                </dl>
                <Button asChild className="w-full" variant={card.status === 'completed' ? 'outline' : 'default'}>
                    <Link href={`/recruiter/training/courses/${card.course.id}`}>
                        {card.status === 'completed'
                            ? 'Review course'
                            : card.progress_percent > 0 || card.status === 'in_progress'
                              ? 'Continue'
                              : 'Start course'}
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}
