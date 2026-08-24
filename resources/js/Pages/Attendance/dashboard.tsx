import { DataTableCard } from '@/components/admin/data-table-card';
import { KpiStatCard, type KpiTone } from '@/components/admin/kpi-stat-card';
import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { DailyAttendanceTable } from '@/components/attendance/daily-attendance-table';
import { MonthYearControls, MonthlyReportPanel } from '@/components/attendance/monthly-report-panel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface Props {
    tab: 'daily' | 'monthly';
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
    monthlyReport: Parameters<typeof MonthlyReportPanel>[0]['report'] | null;
    employeeDetail: Parameters<typeof MonthlyReportPanel>[0]['employeeDetail'];
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

function todayIsoDate(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function shiftDate(isoDate: string, days: number): string {
    const date = new Date(`${isoDate}T12:00:00`);
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

export default function AttendanceDashboard({
    tab,
    snapshot,
    dailyTable,
    monthlyReport,
    employeeDetail,
    filterOptions,
}: Props) {
    useAttendanceDashboardRealtime(tab === 'daily' && snapshot.is_today);

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

            applyDailyFilters({ search: search || undefined });
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [search]);

    const applyDailyFilters = (changes: Record<string, string | number | null | undefined>) => {
        router.get(
            '/admin/attendance',
            {
                tab: 'daily',
                date: dailyTable.filter.date,
                ...(activeStatus ? { status: activeStatus } : {}),
                ...(dailyTable.filter.employee_id ? { employee_id: dailyTable.filter.employee_id } : {}),
                ...(dailyTable.filter.search ? { search: dailyTable.filter.search } : {}),
                ...changes,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const applyMonthlyFilters = (changes: Record<string, string | number | null | undefined>) => {
        router.get(
            '/admin/attendance',
            {
                tab: 'monthly',
                month: monthlyReport?.filter.month ?? new Date().getMonth() + 1,
                year: monthlyReport?.filter.year ?? new Date().getFullYear(),
                ...(monthlyReport?.filter.employee_id ? { employee_id: monthlyReport.filter.employee_id } : {}),
                ...(monthlyReport?.filter.department_id ? { department_id: monthlyReport.filter.department_id } : {}),
                ...(monthlyReport?.filter.office_id ? { office_id: monthlyReport.filter.office_id } : {}),
                ...(employeeDetail ? { detail_employee_id: employeeDetail.employee.id } : {}),
                ...changes,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const switchTab = (nextTab: 'daily' | 'monthly') => {
        router.get(
            '/admin/attendance',
            nextTab === 'daily'
                ? { tab: 'daily', date: snapshot.filter.date, ...(activeStatus ? { status: activeStatus } : {}) }
                : {
                      tab: 'monthly',
                      month: new Date().getMonth() + 1,
                      year: new Date().getFullYear(),
                  },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleDateChange = (nextDate: string) => {
        if (nextDate === '' || nextDate > todayIsoDate()) {
            return;
        }

        applyDailyFilters({ date: nextDate });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title="Attendance"
                    description="Daily attendance register and monthly attendance reports for Super Admin review."
                />

                <div className="flex flex-wrap gap-2">
                    <Button variant={tab === 'daily' ? 'default' : 'outline'} onClick={() => switchTab('daily')}>
                        Daily Attendance
                    </Button>
                    <Button variant={tab === 'monthly' ? 'default' : 'outline'} onClick={() => switchTab('monthly')}>
                        Monthly Report
                    </Button>
                </div>

                {tab === 'daily' ? (
                    <>
                        <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-end">
                            <div>
                                <Label htmlFor="attendance-date">View date</Label>
                                <div className="mt-2 flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={() => handleDateChange(shiftDate(snapshot.filter.date, -1))}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                    </Button>
                                    <Input
                                        id="attendance-date"
                                        type="date"
                                        value={snapshot.filter.date}
                                        max={todayIsoDate()}
                                        onChange={(event) => handleDateChange(event.target.value)}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        disabled={snapshot.filter.date >= todayIsoDate()}
                                        onClick={() => handleDateChange(shiftDate(snapshot.filter.date, 1))}
                                    >
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                                <p className="text-muted-foreground mt-2 text-sm">{formatDateLabel(snapshot.filter.date)}</p>
                            </div>

                            <div>
                                <Label>Employee</Label>
                                <Select
                                    value={dailyTable.filter.employee_id ? String(dailyTable.filter.employee_id) : 'all'}
                                    onValueChange={(value) =>
                                        applyDailyFilters({ employee_id: value === 'all' ? null : Number(value) })
                                    }
                                >
                                    <SelectTrigger className="mt-2 w-56">
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
                            </div>

                            <div>
                                <Label>Status</Label>
                                <Select
                                    value={activeStatus ?? 'all'}
                                    onValueChange={(value) =>
                                        applyDailyFilters({ status: value === 'all' ? null : value })
                                    }
                                >
                                    <SelectTrigger className="mt-2 w-44">
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
                        </div>

                        <SearchInput
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search employee name or ID"
                            containerClassName="max-w-md"
                        />

                        <section className="space-y-4">
                            <SectionHeading title={isToday ? "Today's overview" : 'Daily overview'} />
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
                            <SectionHeading title="Attendance register" />
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
                    </>
                ) : (
                    <>
                        <div className="grid gap-4 lg:grid-cols-[auto_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] lg:items-end">
                            <div>
                                <Label>Month</Label>
                                <div className="mt-2">
                                    {monthlyReport && (
                                        <MonthYearControls
                                            month={monthlyReport.filter.month}
                                            year={monthlyReport.filter.year}
                                            onNavigate={(month, year) => applyMonthlyFilters({ month, year })}
                                        />
                                    )}
                                </div>
                            </div>

                            <div>
                                <Label>Employee</Label>
                                <Select
                                    value={
                                        monthlyReport?.filter.employee_id
                                            ? String(monthlyReport.filter.employee_id)
                                            : 'all'
                                    }
                                    onValueChange={(value) =>
                                        applyMonthlyFilters({
                                            employee_id: value === 'all' ? null : Number(value),
                                            detail_employee_id: null,
                                        })
                                    }
                                >
                                    <SelectTrigger className="mt-2">
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
                            </div>

                            <div>
                                <Label>Department</Label>
                                <Select
                                    value={
                                        monthlyReport?.filter.department_id
                                            ? String(monthlyReport.filter.department_id)
                                            : 'all'
                                    }
                                    onValueChange={(value) =>
                                        applyMonthlyFilters({
                                            department_id: value === 'all' ? null : Number(value),
                                            detail_employee_id: null,
                                        })
                                    }
                                >
                                    <SelectTrigger className="mt-2">
                                        <SelectValue placeholder="All departments" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All departments</SelectItem>
                                        {filterOptions.departments.map((department) => (
                                            <SelectItem key={department.id} value={String(department.id)}>
                                                {department.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label>Office</Label>
                                <Select
                                    value={
                                        monthlyReport?.filter.office_id
                                            ? String(monthlyReport.filter.office_id)
                                            : 'all'
                                    }
                                    onValueChange={(value) =>
                                        applyMonthlyFilters({
                                            office_id: value === 'all' ? null : Number(value),
                                            detail_employee_id: null,
                                        })
                                    }
                                >
                                    <SelectTrigger className="mt-2">
                                        <SelectValue placeholder="All offices" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All offices</SelectItem>
                                        {filterOptions.offices.map((office) => (
                                            <SelectItem key={office.id} value={String(office.id)}>
                                                {office.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {monthlyReport && (
                            <MonthlyReportPanel
                                report={monthlyReport}
                                employeeDetail={employeeDetail}
                                selectedEmployeeId={employeeDetail?.employee.id ?? null}
                                onEmployeeSelect={(employeeId) =>
                                    applyMonthlyFilters({
                                        detail_employee_id: employeeId,
                                    })
                                }
                            />
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
