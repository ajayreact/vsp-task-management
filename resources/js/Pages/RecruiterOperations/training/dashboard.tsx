import { DashboardPanel, PanelEmpty, PanelRow } from '@/components/admin/dashboard-panel';
import { PageHeader } from '@/components/admin/page-header';
import { TrainingProgressBar, TrainingStatusBadge, TrainingSubNav, formatTrainingDate } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BookOpen, GraduationCap, Settings2, UserPlus, Users } from 'lucide-react';

export interface TrainingCard {
    id: number;
    course: { id: number; title: string; description: string | null };
    category: string | null;
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
    continueLearning: TrainingCard[];
    can: { manage: boolean; assign: boolean; viewTeam: boolean };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
];

export default function TrainingDashboard({ isLearner, hasEmployeeProfile, counts, continueLearning, can }: Props) {
    const tiles = counts
        ? [
              { label: 'Assigned courses', value: counts.assigned, href: '/recruiter/training/my-training' },
              { label: 'Not started', value: counts.not_started, href: '/recruiter/training/my-training?status=assigned' },
              { label: 'In progress', value: counts.in_progress, href: '/recruiter/training/my-training?status=in_progress' },
              { label: 'Completed', value: counts.completed, href: '/recruiter/training/my-training?status=completed' },
              { label: 'Overdue', value: counts.overdue, href: '/recruiter/training/my-training?status=overdue' },
          ]
        : [];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Training" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Training"
                    description={
                        isLearner
                            ? 'Your recruiter courses. Read or listen to each lesson, then mark it complete.'
                            : 'Manage recruiter courses, assign training and follow team progress.'
                    }
                    action={
                        isLearner ? (
                            <Button asChild>
                                <Link href="/recruiter/training/my-training">
                                    <BookOpen /> My training
                                </Link>
                            </Button>
                        ) : can.manage ? (
                            <Button asChild>
                                <Link href="/recruiter/training/manage">
                                    <Settings2 /> Manage courses
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

                {counts && (
                    <>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            {tiles.map((tile) => (
                                <Link
                                    key={tile.label}
                                    href={tile.href}
                                    className="rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-4 py-3 transition-colors hover:border-[rgba(120,115,110,0.28)]"
                                >
                                    <div className="text-foreground text-2xl font-semibold tabular-nums">{tile.value}</div>
                                    <div className="text-muted-foreground text-xs">{tile.label}</div>
                                </Link>
                            ))}
                        </div>

                        <div className="rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 p-4">
                            <div className="mb-2 flex items-center justify-between text-sm">
                                <span className="font-medium">Overall training progress</span>
                                <span className="text-muted-foreground text-xs">Required lessons completed across all your courses</span>
                            </div>
                            <TrainingProgressBar percent={counts.overall_percent} label="Overall training progress" />
                        </div>
                    </>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    {isLearner && (
                        <DashboardPanel title="Continue learning" description="Courses you have not finished." icon={GraduationCap} tone="emerald">
                            {continueLearning.length > 0 ? (
                                <div className="space-y-2">
                                    {continueLearning.map((card) => (
                                        <PanelRow
                                            key={card.id}
                                            href={`/recruiter/training/courses/${card.course.id}`}
                                            title={card.course.title}
                                            meta={[
                                                `${card.progress_percent}% · ${card.lessons_completed}/${card.lessons_counted} lessons`,
                                                card.due_at ? `Due ${formatTrainingDate(card.due_at)}` : null,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                            badge={<TrainingStatusBadge status={card.status} label={card.status_label} />}
                                        />
                                    ))}
                                </div>
                            ) : (
                                <PanelEmpty>
                                    {hasEmployeeProfile ? 'Nothing waiting. New courses appear here when assigned.' : 'No training.'}
                                </PanelEmpty>
                            )}
                        </DashboardPanel>
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
            </div>
        </RecruiterLayout>
    );
}
