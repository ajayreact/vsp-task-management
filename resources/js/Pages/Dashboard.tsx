import { DashboardPanel, PanelEmpty, PanelRow } from '@/components/admin/dashboard-panel';
import { KpiStatCard, type KpiTone } from '@/components/admin/kpi-stat-card';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDownRight,
    ArrowUpRight,
    ClipboardCheck,
    Clock,
    Gauge,
    Inbox,
    ListChecks,
    Minus,
    Pause,
    Plus,
    Square,
    TrendingUp,
} from 'lucide-react';
import { useEffect, useState, type ComponentType } from 'react';

interface Trend {
    direction: 'up' | 'down' | 'flat';
    label: string;
}

interface Kpi {
    key: string;
    label: string;
    value: number;
    display: string;
    href: string;
    trend: Trend | null;
    hint: string | null;
}

interface ActionItem {
    id: number;
    title: string;
    meta: string;
    project: string;
    href: string;
    kind: string;
    kind_label: string;
}

interface ApprovalItem {
    id: string;
    title: string;
    meta: string;
    href: string;
    kind: string;
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
    kpis: Kpi[];
    actions: ActionItem[];
    approvals: ApprovalItem[];
    timer: Timer | null;
    can: {
        create_task: boolean;
        open_board: boolean;
        view_workload: boolean;
        approve_timesheets: boolean;
        review_proofs: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const KPI_ICONS: Record<string, ComponentType<{ className?: string; strokeWidth?: number }>> = {
    in_progress: ListChecks,
    in_review: ClipboardCheck,
    open_board: Inbox,
    workload: Gauge,
};

const KPI_TONES: Record<string, KpiTone> = {
    in_progress: 'indigo',
    in_review: 'amber',
    open_board: 'sky',
    workload: 'teal',
};

const TM_KPI_KEYS = ['in_progress', 'in_review', 'open_board', 'workload'];

export default function Dashboard({ snapshot }: { snapshot: Snapshot }) {
    const showTasks = snapshot.modules.tasks;
    const tmKpis = snapshot.kpis.filter((kpi) => TM_KPI_KEYS.includes(kpi.key));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Command Center" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title="Command Center"
                    description={dashboardDescription(snapshot)}
                    action={
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
                                    <Link href="/tasks/board">Go to Open Board</Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                {showTasks && (
                    <section className="space-y-4">
                        <SectionHeading title="Task Management overview" />
                        {tmKpis.length > 0 && (
                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                {tmKpis.map((kpi) => (
                                    <KpiCard key={kpi.key} kpi={kpi} />
                                ))}
                            </div>
                        )}
                        <div className="grid gap-4 lg:grid-cols-3">
                            <ActionList items={snapshot.actions} />
                            <TimerWidget timer={snapshot.timer} />
                            <ApprovalsList items={snapshot.approvals} />
                        </div>
                    </section>
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

function KpiCard({ kpi }: { kpi: Kpi }) {
    const Icon = KPI_ICONS[kpi.key] ?? TrendingUp;
    const tone = KPI_TONES[kpi.key] ?? 'indigo';
    const trendTone =
        kpi.key === 'workload'
            ? kpi.trend?.direction === 'up'
                ? 'text-destructive'
                : kpi.trend?.direction === 'down'
                  ? 'text-amber-600 dark:text-amber-400'
                  : 'text-muted-foreground'
            : kpi.trend?.direction === 'up'
              ? 'text-emerald-600 dark:text-emerald-400'
              : kpi.trend?.direction === 'down'
                ? 'text-destructive'
                : 'text-muted-foreground';

    return (
        <KpiStatCard
            href={kpi.href}
            label={kpi.label}
            value={kpi.display}
            icon={Icon}
            tone={tone}
            footer={
                (kpi.trend || kpi.hint) && (
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                        {kpi.trend && (
                            <span className={`inline-flex items-center gap-1 font-medium ${trendTone}`}>
                                <TrendIcon direction={kpi.trend.direction} />
                                {kpi.trend.label}
                            </span>
                        )}
                        {kpi.hint && <span className="text-muted-foreground">{kpi.hint}</span>}
                    </div>
                )
            }
        />
    );
}

function TrendIcon({ direction }: { direction: Trend['direction'] }) {
    if (direction === 'up') {
        return <ArrowUpRight className="size-3.5" />;
    }
    if (direction === 'down') {
        return <ArrowDownRight className="size-3.5" />;
    }

    return <Minus className="size-3.5" />;
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
