import { DashboardPanel, PanelEmpty } from '@/components/admin/dashboard-panel';
import { PageHeader } from '@/components/admin/page-header';
import { TrackCard, type TrackCardData } from '@/components/recruiter-operations/training/training-tracks';
import { TrainingProgressBar, TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BookOpen, Settings2, UserPlus, Users } from 'lucide-react';

interface Counts {
    assigned: number;
    not_started: number;
    in_progress: number;
    completed: number;
    overdue: number;
    overall_percent: number;
}

interface Props {
    isLearner: boolean;
    hasEmployeeProfile: boolean;
    counts: Counts | null;
    trackView: 'catalog' | 'learner';
    tracks: TrackCardData[];
    can: { manage: boolean; assign: boolean; viewTeam: boolean };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
];

export default function TrainingDashboard({ isLearner, hasEmployeeProfile, counts, trackView, tracks, can }: Props) {
    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Training" />

            <div className="flex max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Recruiter Training"
                    description={
                        trackView === 'learner'
                            ? 'Choose a training track to see the courses assigned to you.'
                            : 'Choose a training track to see its courses.'
                    }
                    action={
                        isLearner ? (
                            <Button asChild variant="outline">
                                <Link href="/recruiter/training/my-training">
                                    <BookOpen /> My training
                                </Link>
                            </Button>
                        ) : undefined
                    }
                />
                <TrainingSubNav />

                {isLearner && !hasEmployeeProfile && (
                    <PanelEmpty>
                        Training is assigned to employees. Your account has no employee profile, so there is nothing to learn here.
                    </PanelEmpty>
                )}

                {trackView === 'learner' && hasEmployeeProfile && tracks.length === 0 && (
                    <PanelEmpty>No training has been assigned to you yet.</PanelEmpty>
                )}

                {tracks.length > 0 && (
                    <section aria-label="Training tracks" className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {tracks.map((track) => (
                            <TrackCard key={track.slug} track={track} learner={trackView === 'learner'} />
                        ))}
                    </section>
                )}

                {counts && counts.assigned > 0 && (
                    <div className="rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 p-4">
                        <div className="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span className="font-medium">Overall training progress</span>
                            <span className="text-muted-foreground text-xs">
                                {counts.completed} of {counts.assigned} courses completed
                                {counts.overdue > 0 ? ` · ${counts.overdue} overdue` : ''}
                            </span>
                        </div>
                        <TrainingProgressBar percent={counts.overall_percent} label="Overall training progress" />
                    </div>
                )}

                {(can.manage || can.assign || can.viewTeam) && (
                    <DashboardPanel title="Training administration" description="Tools for your role." icon={Settings2} tone="indigo">
                        <div className="flex flex-wrap gap-2">
                            {can.viewTeam && (
                                <Button asChild variant="outline">
                                    <Link href="/recruiter/training/team">
                                        <Users /> Team progress
                                    </Link>
                                </Button>
                            )}
                            {can.assign && (
                                <Button asChild variant="outline">
                                    <Link href="/recruiter/training/assignments/create">
                                        <UserPlus /> Assign training
                                    </Link>
                                </Button>
                            )}
                            {can.manage && (
                                <Button asChild variant="outline">
                                    <Link href="/recruiter/training/manage">
                                        <Settings2 /> Manage courses
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </DashboardPanel>
                )}
            </div>
        </RecruiterLayout>
    );
}
