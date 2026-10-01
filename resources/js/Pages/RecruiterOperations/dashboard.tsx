import { DashboardPanel, PanelEmpty, PanelRow } from '@/components/admin/dashboard-panel';
import { PageHeader } from '@/components/admin/page-header';
import { formatActivityDuration, formatActivityTimeRange } from '@/components/recruiter-operations/recruiter-activity-format';
import { RecruiterTaskStatusBadge, formatRecruiterDateTime } from '@/components/recruiter-operations/recruiter-task-status-badge';
import { Button } from '@/components/ui/button';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarClock, ClipboardCheck, GraduationCap, ListChecks, UserCheck, Users } from 'lucide-react';

interface UpcomingTask {
    id: number;
    title: string;
    status: string;
    status_label: string;
    work_type_label: string;
    due_at: string | null;
}

interface MyTasks {
    assigned_today: number;
    in_progress: number;
    pending: number;
    completed_today: number;
    due_today: number;
    upcoming: UpcomingTask[];
}

interface TeamTasks {
    assigned: number;
    in_progress: number;
    on_hold: number;
    declined: number;
    due_today: number;
    overdue: number;
    completed_today: number;
}

interface TodayActivities {
    count: number;
    reported_quantity: number | null;
    recorded_minutes: number | null;
    recent: {
        id: number;
        title: string;
        activity_type_label: string;
        start_time: string | null;
        end_time: string | null;
        duration_minutes: number | null;
        quantity: number | null;
        task_title: string | null;
    }[];
}

interface TrainingCounts {
    assigned: number;
    not_started: number;
    in_progress: number;
    completed: number;
    overdue: number;
    overall_percent: number;
}

interface AssessmentCounts {
    assigned: number;
    open: number;
    overdue: number;
    pending_review: number;
    passed: number;
    failed: number;
}

interface Props {
    hasEmployeeProfile: boolean;
    myTasks: MyTasks | null;
    teamTasks: TeamTasks | null;
    todayActivities: TodayActivities | null;
    trainingLearner: boolean;
    training: TrainingCounts | null;
    assessments: AssessmentCounts | null;
    awaitingReview: number | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Recruiter Operations', href: '/recruiter' }];

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-3.5 py-3">
            <div className="text-foreground text-2xl font-semibold tabular-nums">{value}</div>
            <div className="text-muted-foreground text-xs">{label}</div>
        </div>
    );
}

function CountGrid({ items }: { items: { label: string; value: number; href: string }[] }) {
    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {items.map((item) => (
                <Link
                    key={item.label}
                    href={item.href}
                    className="rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-3.5 py-3 transition-colors hover:border-[rgba(120,115,110,0.28)]"
                >
                    <div className="text-foreground text-2xl font-semibold tabular-nums">{item.value}</div>
                    <div className="text-muted-foreground text-xs">{item.label}</div>
                </Link>
            ))}
        </div>
    );
}

export default function RecruiterDashboard({
    hasEmployeeProfile,
    myTasks,
    teamTasks,
    todayActivities,
    trainingLearner,
    training,
    assessments,
    awaitingReview,
}: Props) {
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Recruiter Operations" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Recruiter Operations" description="Welcome to Recruiter Operations." />

                <div className="grid gap-4 lg:grid-cols-2">
                    <DashboardPanel
                        title="My Tasks"
                        description="Work assigned to you."
                        icon={ListChecks}
                        tone="indigo"
                        action={
                            <Button asChild variant="outline" size="sm">
                                <Link href="/recruiter/tasks?scope=mine">View all</Link>
                            </Button>
                        }
                    >
                        {myTasks ? (
                            <div className="space-y-4">
                                <CountGrid
                                    items={[
                                        { label: 'Assigned today', value: myTasks.assigned_today, href: '/recruiter/tasks?scope=mine' },
                                        { label: 'In progress', value: myTasks.in_progress, href: '/recruiter/tasks?scope=mine&status=in_progress' },
                                        { label: 'Pending', value: myTasks.pending, href: '/recruiter/tasks?scope=mine' },
                                        {
                                            label: 'Completed today',
                                            value: myTasks.completed_today,
                                            href: '/recruiter/tasks?scope=mine&status=completed',
                                        },
                                        { label: 'Due today', value: myTasks.due_today, href: `/recruiter/tasks?scope=mine&due_date=${today}` },
                                    ]}
                                />

                                {myTasks.upcoming.length > 0 ? (
                                    <div className="space-y-2">
                                        {myTasks.upcoming.map((task) => (
                                            <PanelRow
                                                key={task.id}
                                                href={`/recruiter/tasks/${task.id}`}
                                                title={task.title}
                                                meta={`${task.work_type_label} · Due ${formatRecruiterDateTime(task.due_at)}`}
                                                badge={<RecruiterTaskStatusBadge status={task.status} label={task.status_label} />}
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <PanelEmpty>No pending tasks.</PanelEmpty>
                                )}
                            </div>
                        ) : (
                            <PanelEmpty>Tasks are available once your account has an employee profile.</PanelEmpty>
                        )}
                    </DashboardPanel>

                    <DashboardPanel
                        title="Today's Activities"
                        description="What you have logged today."
                        icon={CalendarClock}
                        tone="sky"
                        action={
                            todayActivities ? (
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/recruiter/activities/create">Record activity</Link>
                                </Button>
                            ) : undefined
                        }
                    >
                        {todayActivities ? (
                            <div className="space-y-4">
                                <div className="grid grid-cols-3 gap-3">
                                    <Stat label="Activities Today" value={String(todayActivities.count)} />
                                    <Stat
                                        label="Reported Quantity"
                                        value={todayActivities.reported_quantity !== null ? String(todayActivities.reported_quantity) : '—'}
                                    />
                                    <Stat label="Recorded Duration" value={formatActivityDuration(todayActivities.recorded_minutes)} />
                                </div>

                                {todayActivities.recent.length > 0 ? (
                                    <div className="space-y-2">
                                        {todayActivities.recent.map((activity) => (
                                            <PanelRow
                                                key={activity.id}
                                                href={`/recruiter/activities/${activity.id}`}
                                                title={activity.title}
                                                meta={[
                                                    activity.activity_type_label,
                                                    activity.start_time ? formatActivityTimeRange(activity.start_time, activity.end_time) : null,
                                                    activity.quantity !== null ? `Quantity ${activity.quantity}` : null,
                                                    activity.task_title,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <PanelEmpty>Nothing logged yet today.</PanelEmpty>
                                )}
                            </div>
                        ) : (
                            <PanelEmpty>Daily activities are available once your account has an employee profile.</PanelEmpty>
                        )}
                    </DashboardPanel>

                    <DashboardPanel
                        title="Training"
                        description={
                            trainingLearner ? 'Courses assigned to you and your progress.' : 'Recruiter courses, assignments and team progress.'
                        }
                        icon={GraduationCap}
                        tone="emerald"
                        action={
                            <Button asChild variant="outline" size="sm">
                                {trainingLearner ? (
                                    <Link href="/recruiter/training/my-training">My training</Link>
                                ) : (
                                    <Link href="/recruiter/training">Open training</Link>
                                )}
                            </Button>
                        }
                    >
                        {!trainingLearner ? (
                            <PanelEmpty>
                                You manage recruiter training. Open Training to manage courses, assign training and follow team progress.
                            </PanelEmpty>
                        ) : training ? (
                            <CountGrid
                                items={[
                                    { label: 'Assigned', value: training.assigned, href: '/recruiter/training/my-training' },
                                    { label: 'In progress', value: training.in_progress, href: '/recruiter/training/my-training?status=in_progress' },
                                    { label: 'Completed', value: training.completed, href: '/recruiter/training/my-training?status=completed' },
                                    { label: 'Overdue', value: training.overdue, href: '/recruiter/training/my-training?status=overdue' },
                                ]}
                            />
                        ) : (
                            <PanelEmpty>Training is available once your account has an employee profile.</PanelEmpty>
                        )}
                    </DashboardPanel>

                    {teamTasks && (
                        <DashboardPanel
                            title="Team Tasks"
                            description="Recruiter task counts across the team."
                            icon={Users}
                            tone="amber"
                            action={
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/recruiter/tasks">View all</Link>
                                </Button>
                            }
                        >
                            <CountGrid
                                items={[
                                    { label: 'Awaiting acceptance', value: teamTasks.assigned, href: '/recruiter/tasks?status=assigned' },
                                    { label: 'In progress', value: teamTasks.in_progress, href: '/recruiter/tasks?status=in_progress' },
                                    { label: 'On hold', value: teamTasks.on_hold, href: '/recruiter/tasks?status=on_hold' },
                                    { label: 'Declined', value: teamTasks.declined, href: '/recruiter/tasks?status=declined' },
                                    { label: 'Due today', value: teamTasks.due_today, href: `/recruiter/tasks?due_date=${today}` },
                                    { label: 'Overdue', value: teamTasks.overdue, href: '/recruiter/tasks' },
                                    { label: 'Completed today', value: teamTasks.completed_today, href: '/recruiter/tasks?status=completed' },
                                ]}
                            />
                        </DashboardPanel>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <DashboardPanel
                        title="Quizzes"
                        description="Quizzes assigned to you and your results."
                        icon={ClipboardCheck}
                        tone="fuchsia"
                        action={
                            <Button asChild variant="outline" size="sm">
                                <Link href="/recruiter/assessments">My quizzes</Link>
                            </Button>
                        }
                    >
                        {assessments ? (
                            <div className="space-y-3">
                                <CountGrid
                                    items={[
                                        { label: 'To take', value: assessments.open, href: '/recruiter/assessments' },
                                        { label: 'Overdue', value: assessments.overdue, href: '/recruiter/assessments' },
                                        { label: 'Awaiting review', value: assessments.pending_review, href: '/recruiter/assessments' },
                                        { label: 'Passed', value: assessments.passed, href: '/recruiter/assessments' },
                                        { label: 'Not passed', value: assessments.failed, href: '/recruiter/assessments' },
                                    ]}
                                />
                                {awaitingReview !== null && awaitingReview > 0 && (
                                    <Link
                                        href="/recruiter/assessments/results?result=pending_review"
                                        className="text-sm font-medium text-fuchsia-700 hover:underline"
                                    >
                                        {awaitingReview === 1 ? '1 attempt needs' : `${awaitingReview} attempts need`} manual review
                                    </Link>
                                )}
                            </div>
                        ) : (
                            <PanelEmpty>Quizzes are available once your account has an employee profile.</PanelEmpty>
                        )}
                    </DashboardPanel>

                    <DashboardPanel title="Attendance" description="Check in, breaks and work from home." icon={UserCheck} tone="teal">
                        {hasEmployeeProfile ? (
                            <div className="flex flex-wrap gap-2">
                                <Button asChild>
                                    <Link href="/attendance/mark">Open attendance</Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href="/attendance/wfh">WFH requests</Link>
                                </Button>
                            </div>
                        ) : (
                            <PanelEmpty>Attendance is available once your account has an employee profile.</PanelEmpty>
                        )}
                    </DashboardPanel>
                </div>
            </div>
        </RecruiterLayout>
    );
}
