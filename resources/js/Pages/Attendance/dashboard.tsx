import { DataTableCard } from '@/components/admin/data-table-card';
import { KpiStatCard, type KpiTone } from '@/components/admin/kpi-stat-card';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDuration, formatTimeLabel } from '@/lib/attendance/format';
import { useAttendanceDashboardRealtime } from '@/hooks/use-attendance-dashboard-realtime';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Clock, Coffee, LogOut, UserCheck, Users, UserX } from 'lucide-react';
import { type ComponentType } from 'react';

interface OverviewStat {
    key: string;
    label: string;
    count: number;
    display: string;
    href: string;
    hint?: string | null;
}

interface Snapshot {
    date: string;
    filter: {
        status: string | null;
    };
    overview: OverviewStat[];
    records: AttendanceRecord[];
}

interface AttendanceRecord {
    id: number;
    employee: string;
    employee_code: string;
    office: string;
    status: string;
    status_label: string;
    check_in_at: string | null;
    check_out_at: string | null;
    total_break_seconds: number;
    break_count: number;
    net_working_seconds: number | null;
}

const RECORD_STATUS_TONE: Record<string, 'success' | 'warning' | 'neutral' | 'info'> = {
    present: 'success',
    late: 'warning',
    on_break: 'info',
    checked_out: 'neutral',
    absent: 'neutral',
};

const FILTER_LABELS: Record<string, string> = {
    present: 'Present',
    absent: 'Absent',
    late: 'Late',
    on_break: 'On break',
    checked_out: 'Checked out',
};

const FILTER_EMPTY_MESSAGES: Record<string, string> = {
    present: 'No present employees recorded yet today.',
    absent: 'No absent employees today.',
    late: 'No late check-ins recorded yet today.',
    on_break: 'No employees are currently on break.',
    checked_out: 'No checked-out employees recorded yet today.',
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Attendance', href: '/admin/attendance' }];

const OVERVIEW_ICONS: Record<string, ComponentType<{ className?: string; strokeWidth?: number }>> = {
    total_employees: Users,
    present_today: UserCheck,
    absent_today: UserX,
    late_today: Clock,
    on_break: Coffee,
    checked_out: LogOut,
};

const OVERVIEW_TONES: Record<string, KpiTone> = {
    total_employees: 'indigo',
    present_today: 'emerald',
    absent_today: 'amber',
    late_today: 'sky',
    on_break: 'teal',
    checked_out: 'fuchsia',
};

const FILTER_KEY_BY_STATUS: Record<string, string> = {
    present: 'present_today',
    absent: 'absent_today',
    late: 'late_today',
    on_break: 'on_break',
    checked_out: 'checked_out',
};

function SectionHeading({ title }: { title: string }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="bg-primary h-4 w-1 shrink-0 rounded-full" aria-hidden />
            <h2 className="text-muted-foreground text-xs font-semibold tracking-[0.14em] uppercase">{title}</h2>
        </div>
    );
}

function isStatActive(stat: OverviewStat, activeStatus: string | null): boolean {
    if (stat.key === 'total_employees') {
        return activeStatus === null;
    }

    const expectedKey = activeStatus ? FILTER_KEY_BY_STATUS[activeStatus] : null;

    return expectedKey === stat.key;
}

function OverviewCard({ stat, active }: { stat: OverviewStat; active: boolean }) {
    const Icon = OVERVIEW_ICONS[stat.key] ?? Users;
    const tone = OVERVIEW_TONES[stat.key] ?? 'indigo';

    return (
        <KpiStatCard
            href={stat.href}
            label={stat.label}
            value={stat.display}
            icon={Icon}
            tone={tone}
            className={cn(active && 'ring-primary rounded-xl ring-2 ring-offset-2')}
            footer={stat.hint ? <span className="text-muted-foreground text-xs">{stat.hint}</span> : undefined}
        />
    );
}

function formatDateLabel(isoDate: string): string {
    return new Date(`${isoDate}T12:00:00`).toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function AttendanceDashboard({ snapshot }: { snapshot: Snapshot }) {
    useAttendanceDashboardRealtime();

    const activeStatus = snapshot.filter.status;
    const recordsTitle = activeStatus ? `${FILTER_LABELS[activeStatus]} employees` : "Today's check-ins";
    const recordsDescription = activeStatus
        ? `Showing employees filtered by ${FILTER_LABELS[activeStatus].toLowerCase()} status.`
        : 'Check-in and check-out events recorded today.';
    const emptyMessage = activeStatus
        ? FILTER_EMPTY_MESSAGES[activeStatus]
        : 'No check-ins recorded yet today.';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title="Attendance"
                    description={`Daily attendance overview for ${formatDateLabel(snapshot.date)}.`}
                />

                <section className="space-y-4">
                    <SectionHeading title="Today's overview" />
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        {snapshot.overview.map((stat) => (
                            <OverviewCard
                                key={stat.key}
                                stat={stat}
                                active={isStatActive(stat, activeStatus)}
                            />
                        ))}
                    </div>
                </section>

                <section className="space-y-4">
                    <SectionHeading title={recordsTitle} />
                    <DataTableCard title="Employee attendance records" description={recordsDescription}>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Employee</TableHead>
                                    <TableHead>Office</TableHead>
                                    <TableHead>Check in</TableHead>
                                    <TableHead>Check out</TableHead>
                                    <TableHead>Break time</TableHead>
                                    <TableHead>Net working</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {snapshot.records.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-muted-foreground py-10 text-center">
                                            {emptyMessage}
                                        </TableCell>
                                    </TableRow>
                                )}

                                {snapshot.records.map((record) => (
                                    <TableRow key={record.id}>
                                        <TableCell>
                                            <div className="font-medium">{record.employee}</div>
                                            <div className="text-muted-foreground font-mono text-xs">{record.employee_code}</div>
                                        </TableCell>
                                        <TableCell>{record.office}</TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.check_in_at ? formatTimeLabel(record.check_in_at) : '—'}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.check_out_at ? formatTimeLabel(record.check_out_at) : '—'}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.total_break_seconds > 0 || record.break_count > 0
                                                ? formatDuration(record.total_break_seconds)
                                                : '—'}
                                            {record.break_count > 0 && (
                                                <div className="text-muted-foreground text-xs">
                                                    {record.break_count} break{record.break_count === 1 ? '' : 's'}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.net_working_seconds !== null
                                                ? formatDuration(record.net_working_seconds)
                                                : record.check_in_at && !record.check_out_at
                                                  ? 'In progress'
                                                  : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={RECORD_STATUS_TONE[record.status] ?? 'neutral'}>
                                                {record.status_label}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </DataTableCard>
                </section>
            </div>
        </AppLayout>
    );
}
