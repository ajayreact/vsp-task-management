import { DataTableCard } from '@/components/admin/data-table-card';
import { KpiStatCard, type KpiTone } from '@/components/admin/kpi-stat-card';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { DailyAttendanceTable } from '@/components/attendance/daily-attendance-table';
import { MonthlyReportPanel, type MonthlyReport } from '@/components/attendance/monthly-report-panel';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useAttendanceDashboardRealtime } from '@/hooks/use-attendance-dashboard-realtime';
import AppLayout from '@/layouts/app-layout';
import { FILTER_LABELS } from '@/lib/attendance/report-status';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Clock, Coffee, LogOut, UserCheck, Users, UserX } from 'lucide-react';
import { type ComponentType, useEffect, useState } from 'react';

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
    is_today: boolean;
    filter: {
        status: string | null;
        date: string;
    };
    overview: OverviewStat[];
    records: unknown[];
}

interface FilterOption {
    id: number;
    name: string;
    employee_code?: string;
}

interface FilterOptions {
    employees: FilterOption[];
    departments: FilterOption[];
    offices: FilterOption[];
}

interface EmployeeDetail {
    employee: {
        id: number;
        name: string;
        employee_code: string;
        department: string;
    };
    month: number;
    year: number;
    label: string;
    records: Parameters<typeof DailyAttendanceTable>[0]['records'];
}

interface Props {
    snapshot: Snapshot;
    dailyTable: {
        date: string;
        day: string;
        is_today: boolean;
        filter: {
            status: string | null;
            date: string;
            employee_id: number | null;
            search: string | null;
        };
        records: Parameters<typeof DailyAttendanceTable>[0]['records'];
    };
    monthlyReport: MonthlyReport;
    employeeDetail: EmployeeDetail | null;
    filterOptions: FilterOptions;
}

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

function SectionHeading({ title, description }: { title: string; description?: string }) {
    return (
        <div className="space-y-1">
            <div className="flex items-center gap-2.5">
                <span className="bg-primary h-4 w-1 shrink-0 rounded-full" aria-hidden />
                <h2 className="text-foreground text-base font-semibold tracking-tight">{title}</h2>
            </div>
            {description && <p className="text-muted-foreground pl-3.5 text-sm">{description}</p>}
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

function todayIsoDate(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function formatShortDateLabel(isoDate: string): string {
    return new Date(`${isoDate}T12:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function shiftDate(isoDate: string, days: number): string {
    const date = new Date(`${isoDate}T12:00:00`);
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

function buildQuery(
    snapshot: Snapshot,
    dailyTable: Props['dailyTable'],
    monthlyReport: MonthlyReport,
    employeeDetail: EmployeeDetail | null,
    changes: Record<string, string | number | null | undefined> = {},
): Record<string, string | number> {
    const query: Record<string, string | number> = {
        date: dailyTable.filter.date,
        month: monthlyReport.filter.month,
        year: monthlyReport.filter.year,
    };

    if (snapshot.filter.status) {
        query.status = snapshot.filter.status;
    }

    if (dailyTable.filter.employee_id) {
        query.employee_id = dailyTable.filter.employee_id;
    }

    if (dailyTable.filter.search) {
        query.search = dailyTable.filter.search;
    }

    if (monthlyReport.filter.department_id) {
        query.department_id = monthlyReport.filter.department_id;
    }

    if (monthlyReport.filter.office_id) {
        query.office_id = monthlyReport.filter.office_id;
    }

    if (employeeDetail) {
        query.detail_employee_id = employeeDetail.employee.id;
    }

    for (const [key, value] of Object.entries(changes)) {
        if (value === null || value === undefined || value === '') {
            delete query[key];
        } else {
            query[key] = value;
        }
    }

    return query;
}

export default function AttendanceDashboard({
    snapshot,
    dailyTable,
    monthlyReport,
    employeeDetail,
    filterOptions,
}: Props) {
    useAttendanceDashboardRealtime(snapshot.is_today);

    const activeStatus = snapshot.filter.status;
    const isToday = snapshot.is_today;
    const [search, setSearch] = useState(dailyTable.filter.search ?? '');

    useEffect(() => {
        setSearch(dailyTable.filter.search ?? '');
    }, [dailyTable.filter.search]);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            if (search === (dailyTable.filter.search ?? '')) {
                return;
            }

            navigate({ search: search || undefined });
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [search]);

    const navigate = (changes: Record<string, string | number | null | undefined>) => {
        router.get('/admin/attendance', buildQuery(snapshot, dailyTable, monthlyReport, employeeDetail, changes), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleDateChange = (nextDate: string) => {
        if (nextDate === '' || nextDate > todayIsoDate()) {
            return;
        }

        navigate({ date: nextDate });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex w-full min-w-0 max-w-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:gap-8 md:p-6">
                <PageHeader
                    title="Attendance"
                    description="Daily attendance register and monthly attendance reports for Super Admin review."
                />

                <section className="min-w-0 max-w-full space-y-4">
                    <SectionHeading title={isToday ? "Today's Overview" : 'Daily Overview'} />
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        {snapshot.overview.map((stat) => (
                            <OverviewCard key={stat.key} stat={stat} active={isStatActive(stat, activeStatus)} />
                        ))}
                    </div>
                </section>

                <section className="min-w-0 max-w-full space-y-4">
                    <div className="flex min-w-0 max-w-full flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="min-w-0">
                            <SectionHeading
                                title="Daily Attendance"
                                description="Employee attendance records for the selected date."
                            />
                        </div>
                        <div className="flex w-full min-w-0 max-w-full items-center justify-center gap-2 sm:w-auto sm:justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="shrink-0"
                                onClick={() => handleDateChange(shiftDate(snapshot.filter.date, -1))}
                                aria-label="Previous day"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <div className="min-w-0 max-w-[min(100%,16rem)] flex-1 rounded-md border bg-background px-2 py-2 text-center text-xs font-medium sm:max-w-none sm:flex-none sm:px-3 sm:text-sm">
                                <span className="block truncate sm:hidden">{formatShortDateLabel(snapshot.filter.date)}</span>
                                <span className="hidden sm:block">{formatDateLabel(snapshot.filter.date)}</span>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="shrink-0"
                                disabled={snapshot.filter.date >= todayIsoDate()}
                                onClick={() => handleDateChange(shiftDate(snapshot.filter.date, 1))}
                                aria-label="Next day"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div className="grid min-w-0 max-w-full gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_minmax(0,0.8fr)]">
                        <SearchInput
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search employee name or ID"
                            containerClassName="w-full min-w-0 md:col-span-2 xl:col-span-1"
                        />
                        <Select
                            value={dailyTable.filter.employee_id ? String(dailyTable.filter.employee_id) : 'all'}
                            onValueChange={(value) =>
                                navigate({ employee_id: value === 'all' ? null : Number(value) })
                            }
                        >
                            <SelectTrigger className="w-full min-w-0">
                                <SelectValue placeholder="All employees" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All employees</SelectItem>
                                {filterOptions.employees.map((employee) => (
                                    <SelectItem key={employee.id} value={String(employee.id)}>
                                        {employee.name} ({employee.employee_code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={activeStatus ?? 'all'}
                            onValueChange={(value) => navigate({ status: value === 'all' ? null : value })}
                        >
                            <SelectTrigger className="w-full min-w-0">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                {Object.entries(FILTER_LABELS).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <DataTableCard
                        title="Daily attendance records"
                        description={
                            activeStatus
                                ? `Showing employees filtered by ${FILTER_LABELS[activeStatus].toLowerCase()} status.`
                                : `All employees for ${formatDateLabel(snapshot.filter.date)}.`
                        }
                    >
                        <DailyAttendanceTable
                            records={dailyTable.records}
                            isToday={isToday}
                            activeStatus={activeStatus}
                        />
                    </DataTableCard>
                </section>

                <MonthlyReportPanel
                    report={monthlyReport}
                    employeeDetail={employeeDetail}
                    filterOptions={filterOptions}
                    onFilterChange={navigate}
                />
            </div>
        </AppLayout>
    );
}
