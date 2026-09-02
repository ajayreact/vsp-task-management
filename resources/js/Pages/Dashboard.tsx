import { DashboardPanel, PanelEmpty, PanelRow } from '@/components/admin/dashboard-panel';
import { KpiStatCard, type KpiTone } from '@/components/admin/kpi-stat-card';
import { PageHeader } from '@/components/admin/page-header';
import { MyTodoWidget } from '@/components/todos/my-todo-widget';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDashboardRealtime } from '@/hooks/use-dashboard-realtime';
import { type MyTodoSnapshot } from '@/lib/todos';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    ClipboardCheck,
    Clock,
    Gauge,
    Inbox,
    ListChecks,
    Pause,
    Plus,
    RotateCcw,
    Square,
    Timer,
    Users,
} from 'lucide-react';
import { useEffect, useState, type ComponentType } from 'react';

interface OverviewStat {
    key: string;
    label: string;
    count: number;
    display: string;
    href: string;
    hint?: string | null;
}

interface PendingStat {
    count: number;
    href: string;
}

interface TeamSnapshot {
    availability: { available: number; working: number; overloaded: number; href: string } | null;
    timers: {
        count: number;
        href: string;
        entries: { id: number; employee: string; task: string; href: string; started_at: string }[];
    };
    pending: {
        need_review: PendingStat;
        overdue: PendingStat;
        unassigned: PendingStat;
    };
}

interface AttentionSnapshot {
    overdue: { id: number; title: string; assignee: string; due_at: string | null; href: string }[];
    unassigned: { id: number; title: string; project: string; href: string }[];
    overdue_href: string;
    unassigned_href: string;
}

interface ActivityItem {
    id: number;
    message: string;
    at: string;
    href: string;
}

interface ActionItem {
    id: number;
    title: string;
    meta: string;
    project: string;
    href: string;
    kind_label: string;
}

interface ApprovalItem {
    id: string;
    title: string;
    meta: string;
    href: string;
    kind_label: string;
}

interface Timer {
    task_id: number;
    task_title: string;
    started_at: string;
    running: boolean;
    yours: boolean;
}

interface Snapshot {
    scope: 'agency' | 'personal';
    modules: { tasks: boolean };
    overview: OverviewStat[];
    team: TeamSnapshot | null;
    attention: AttentionSnapshot | null;
    activity: ActivityItem[];
    actions: ActionItem[];
    approvals: ApprovalItem[];
    timer: Timer | null;
    my_todo: MyTodoSnapshot | null;
    can: {
        create_task: boolean;
        open_board: boolean;
        view_workload: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const OVERVIEW_ICONS: Record<string, ComponentType<{ className?: string; strokeWidth?: number }>> = {
    total: ListChecks,
    in_progress: ListChecks,
    pending_acceptance: Clock,
    in_review: ClipboardCheck,
    changes_requested: RotateCcw,
    open_board: Inbox,
    completed_today: CheckCircle2,
    overdue: AlertTriangle,
    team_workload: Gauge,
};

const OVERVIEW_TONES: Record<string, KpiTone> = {
    total: 'indigo',
    in_progress: 'indigo',
    pending_acceptance: 'sky',
    in_review: 'amber',
    changes_requested: 'fuchsia',
    open_board: 'sky',
    completed_today: 'emerald',
    overdue: 'amber',
    team_workload: 'teal',
};

const PRIMARY_OVERVIEW_KEYS = ['in_progress', 'in_review', 'changes_requested', 'open_board', 'completed_today'];

export default function Dashboard({ snapshot }: { snapshot: Snapshot }) {
    useDashboardRealtime();

    const showTasks = snapshot.modules.tasks;
    const isAgency = snapshot.scope === 'agency';
    const primaryOverview = snapshot.overview.filter((stat) => PRIMARY_OVERVIEW_KEYS.includes(stat.key));
    const extendedOverview = snapshot.overview.filter((stat) => !PRIMARY_OVERVIEW_KEYS.includes(stat.key));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Command Center" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title="Command Center"
                    description={dashboardDescription(snapshot)}
                    action={
                        showTasks ? (
                            <div className="flex flex-wrap gap-2">
                                {snapshot.can.create_task && (
                                    <Button asChild>
                                        <Link href="/tasks/create">
                                            <Plus /> New Task
                                        </Link>
                                    </Button>
                                )}
                                {snapshot.can.open_board && (
                                    <Button variant="outline" asChild>
                                        <Link href="/tasks/board">Open Board</Link>
                                    </Button>
                                )}
                            </div>
                        ) : undefined
                    }
                />

                {showTasks && (
                    <>
                        {snapshot.my_todo && (
                            <section className="space-y-4">
                                <SectionHeading title="My productivity" />
                                <div className="max-w-2xl">
                                    <MyTodoWidget snapshot={snapshot.my_todo} />
                                </div>
                            </section>
                        )}

                        <section className="space-y-4">
                            <SectionHeading title="Task overview" />
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                {primaryOverview.map((stat) => (
                                    <OverviewCard key={stat.key} stat={stat} />
                                ))}
                            </div>
                            {extendedOverview.length > 0 && (
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    {extendedOverview.map((stat) => (
                                        <OverviewCard key={stat.key} stat={stat} />
                                    ))}
                                </div>
                            )}
                        </section>

                        {isAgency && snapshot.team && (
                            <section className="space-y-4">
                                <SectionHeading title="Team & work" />
                                <div className="grid gap-4 lg:grid-cols-3">
                                    <TeamAvailabilityPanel availability={snapshot.team.availability} />
                                    <ActiveTimersPanel timers={snapshot.team.timers} />
                                    <PendingActionsPanel pending={snapshot.team.pending} />
                                </div>
                            </section>
                        )}

                        {isAgency && snapshot.attention && (
                            <section className="space-y-4">
                                <SectionHeading title="Needs attention" />
                                <div className="grid gap-4 lg:grid-cols-2">
                                    <AttentionList
                                        title="Overdue tasks"
                                        icon={AlertTriangle}
                                        tone="amber"
                                        empty="Nothing overdue right now."
                                        href={snapshot.attention.overdue_href}
                                        items={snapshot.attention.overdue.map((task) => ({
                                            id: task.id,
                                            href: task.href,
                                            title: task.title,
                                            meta: `${task.assignee} · Due ${formatDue(task.due_at)}`,
                                        }))}
                                    />
                                    <AttentionList
                                        title="Unassigned tasks"
                                        icon={Inbox}
                                        tone="sky"
                                        empty="Every draft task has an owner."
                                        href={snapshot.attention.unassigned_href}
                                        items={snapshot.attention.unassigned.map((task) => ({
                                            id: task.id,
                                            href: task.href,
                                            title: task.title,
                                            meta: task.project,
                                        }))}
                                    />
                                </div>
                            </section>
                        )}

                        <section className="space-y-4">
                            <SectionHeading title="Recent activity" />
                            <DashboardPanel title="Studio feed" description="Latest task moves across the studio." icon={ListChecks} tone="indigo">
                                {snapshot.activity.length === 0 ? (
                                    <PanelEmpty>No recent activity yet.</PanelEmpty>
                                ) : (
                                    <div className="space-y-2.5">
                                        {snapshot.activity.map((item) => (
                                            <PanelRow
                                                key={item.id}
                                                href={item.href}
                                                title={item.message}
                                                meta={formatActivityTime(item.at)}
                                            />
                                        ))}
                                    </div>
                                )}
                            </DashboardPanel>
                        </section>

                        <section className="space-y-4">
                            <SectionHeading title="Your workspace" />
                            <div className="grid gap-4 lg:grid-cols-3">
                                <ActionList items={snapshot.actions} />
                                <TimerWidget timer={snapshot.timer} />
                                <ApprovalsList items={snapshot.approvals} />
                            </div>
                        </section>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function SectionHeading({ title }: { title: string }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="bg-primary h-4 w-1 shrink-0 rounded-full" aria-hidden />
            <h2 className="text-muted-foreground text-xs font-semibold tracking-[0.14em] uppercase">{title}</h2>
        </div>
    );
}

function dashboardDescription(snapshot: Snapshot): string {
    if (snapshot.modules.tasks) {
        return snapshot.scope === 'agency' ? 'Delivery and capacity across the studio.' : 'Your tasks, timer and reviews.';
    }

    return 'Ask an admin for Task Management access to see work here.';
}

function OverviewCard({ stat }: { stat: OverviewStat }) {
    const Icon = OVERVIEW_ICONS[stat.key] ?? ListChecks;
    const tone = OVERVIEW_TONES[stat.key] ?? 'indigo';

    return (
        <KpiStatCard
            href={stat.href}
            label={stat.label}
            value={stat.display}
            icon={Icon}
            tone={tone}
            footer={stat.hint ? <span className="text-muted-foreground text-xs">{stat.hint}</span> : undefined}
        />
    );
}

function TeamAvailabilityPanel({
    availability,
}: {
    availability: TeamSnapshot['availability'];
}) {
    return (
        <DashboardPanel title="Team availability" description="Capacity bands for this week." icon={Users} tone="teal">
            {availability === null ? (
                <PanelEmpty>Workload access required to see availability.</PanelEmpty>
            ) : (
                <div className="grid gap-3">
                    <AvailabilityRow label="Available" count={availability.available} href={availability.href} />
                    <AvailabilityRow label="Working" count={availability.working} href={availability.href} />
                    <AvailabilityRow label="Overloaded" count={availability.overloaded} href={availability.href} />
                </div>
            )}
        </DashboardPanel>
    );
}

function AvailabilityRow({ label, count, href }: { label: string; count: number; href: string }) {
    return (
        <Link
            href={href}
            className="hover:border-primary/30 flex items-center justify-between rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-4 py-3 transition-colors"
        >
            <span className="text-sm font-medium">{label}</span>
            <span className="text-foreground text-2xl font-bold tabular-nums">{count}</span>
        </Link>
    );
}

function ActiveTimersPanel({ timers }: { timers: TeamSnapshot['timers'] }) {
    return (
        <DashboardPanel
            title="Active timers"
            description="Who is on the clock right now."
            icon={Timer}
            tone="sky"
            action={
                <Button variant="ghost" size="sm" asChild>
                    <Link href={timers.href}>View details</Link>
                </Button>
            }
        >
            <Link href={timers.href} className="hover:text-primary mb-4 block text-3xl font-bold tabular-nums transition-colors">
                {timers.count}
                <span className="text-muted-foreground ml-2 text-sm font-normal">employees working</span>
            </Link>
            {timers.entries.length === 0 ? (
                <PanelEmpty>No active timers.</PanelEmpty>
            ) : (
                <div className="space-y-2">
                    {timers.entries.map((entry) => (
                        <PanelRow key={entry.id} href={entry.href} title={entry.task} meta={entry.employee} />
                    ))}
                </div>
            )}
        </DashboardPanel>
    );
}

function PendingActionsPanel({ pending }: { pending: TeamSnapshot['pending'] }) {
    return (
        <DashboardPanel title="Pending actions" description="Work that needs a decision." icon={ClipboardCheck} tone="amber">
            <div className="grid gap-3">
                <PendingRow label="Need review" stat={pending.need_review} />
                <PendingRow label="Overdue" stat={pending.overdue} />
                <PendingRow label="Unassigned" stat={pending.unassigned} />
            </div>
        </DashboardPanel>
    );
}

function PendingRow({ label, stat }: { label: string; stat: PendingStat }) {
    return (
        <Link
            href={stat.href}
            className="hover:border-primary/30 flex items-center justify-between rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-4 py-3 transition-colors"
        >
            <span className="text-sm font-medium">{label}</span>
            <span className="text-foreground text-2xl font-bold tabular-nums">{stat.count}</span>
        </Link>
    );
}

function AttentionList({
    title,
    icon,
    tone,
    empty,
    href,
    items,
}: {
    title: string;
    icon: ComponentType<{ className?: string; strokeWidth?: number }>;
    tone: 'amber' | 'sky';
    empty: string;
    href: string;
    items: { id: number; href: string; title: string; meta: string }[];
}) {
    return (
        <DashboardPanel
            title={title}
            icon={icon}
            tone={tone}
            action={
                <Button variant="ghost" size="sm" asChild>
                    <Link href={href}>View all</Link>
                </Button>
            }
        >
            {items.length === 0 ? (
                <PanelEmpty>{empty}</PanelEmpty>
            ) : (
                <div className="space-y-2.5">
                    {items.map((item) => (
                        <PanelRow key={item.id} href={item.href} title={item.title} meta={item.meta} />
                    ))}
                </div>
            )}
        </DashboardPanel>
    );
}

function ActionList({ items }: { items: ActionItem[] }) {
    return (
        <DashboardPanel title="My action items" description="Tasks waiting on acceptance." icon={ListChecks} tone="indigo">
            {items.length === 0 ? (
                <PanelEmpty>Nothing waiting on you.</PanelEmpty>
            ) : (
                <div className="space-y-2.5">
                    {items.map((item) => (
                        <PanelRow
                            key={item.id}
                            href={item.href}
                            title={item.title}
                            meta={`${item.project} · ${item.meta}`}
                            badge={<Badge variant="secondary">{item.kind_label}</Badge>}
                        />
                    ))}
                </div>
            )}
        </DashboardPanel>
    );
}

function TimerWidget({ timer }: { timer: Timer | null }) {
    const [elapsed, setElapsed] = useState('00:00:00');

    useEffect(() => {
        if (!timer?.running || !timer.started_at) {
            setElapsed('00:00:00');
            return;
        }

        const tick = () => {
            const seconds = Math.max(0, Math.floor((Date.now() - new Date(timer.started_at).getTime()) / 1000));
            const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const rest = String(seconds % 60).padStart(2, '0');
            setElapsed(`${hours}:${minutes}:${rest}`);
        };

        tick();
        const id = window.setInterval(tick, 1000);

        return () => window.clearInterval(id);
    }, [timer?.running, timer?.started_at]);

    const post = (action: 'pause' | 'stop') => {
        if (!timer) {
            return;
        }
        router.post(`/tasks/${timer.task_id}/timer/${action}`, {}, { preserveScroll: true });
    };

    return (
        <DashboardPanel title="Live timer" description="The clock running on your current task." icon={Clock} tone="sky">
            {timer === null ? (
                <PanelEmpty>No timer running. Start one from a task.</PanelEmpty>
            ) : (
                <div className="space-y-4 rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-4 py-4 shadow-[0_0.0625rem_0.25rem_0_rgba(38,43,67,0.06)]">
                    <Link href={`/tasks/${timer.task_id}`} className="text-foreground hover:text-primary font-medium transition-colors">
                        {timer.task_title}
                    </Link>
                    <div className="text-foreground font-mono text-3xl font-semibold tracking-tight tabular-nums">{elapsed}</div>
                    {timer.yours && (
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" onClick={() => post('pause')}>
                                <Pause /> Pause
                            </Button>
                            <Button size="sm" variant="outline" onClick={() => post('stop')}>
                                <Square /> Stop
                            </Button>
                        </div>
                    )}
                </div>
            )}
        </DashboardPanel>
    );
}

function ApprovalsList({ items }: { items: ApprovalItem[] }) {
    return (
        <DashboardPanel title="Pending approvals" description="Timesheets and creative review rounds." icon={ClipboardCheck} tone="amber">
            {items.length === 0 ? (
                <PanelEmpty>No approvals waiting.</PanelEmpty>
            ) : (
                <div className="space-y-2.5">
                    {items.map((item) => (
                        <PanelRow
                            key={item.id}
                            href={item.href}
                            title={item.title}
                            meta={item.meta}
                            badge={<Badge variant="outline">{item.kind_label}</Badge>}
                        />
                    ))}
                </div>
            )}
        </DashboardPanel>
    );
}

function formatDue(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function formatActivityTime(value: string): string {
    return new Date(value).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}
