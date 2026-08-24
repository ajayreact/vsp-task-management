import { AttendanceTableScroll, attendanceTableClassName } from '@/components/attendance/attendance-table-scroll';
import { DataTableCard } from '@/components/admin/data-table-card';
import { buildExportQuery } from '@/components/admin/data-table-export';
import { KpiStatCard, type KpiTone } from '@/components/admin/kpi-stat-card';
import { DailyAttendanceTable, type DailyAttendanceRecord } from '@/components/attendance/daily-attendance-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDuration, formatTimeLabel } from '@/lib/attendance/format';
import { REPORT_STATUS_STYLES, reportCodeClass } from '@/lib/attendance/report-status';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, Download, CalendarDays, Clock, Coffee, LogOut, UserCheck, Users, UserX } from 'lucide-react';
import { useState } from 'react';

interface FilterOption {
    id: number;
    name: string;
    employee_code?: string;
}

interface MonthlyDay {
    date: string;
    day: number;
    weekday: string;
    is_weekend: boolean;
    is_future: boolean;
}

interface MonthlyRow {
    employee_id: number;
    employee: string;
    employee_code: string;
    department: string;
    office: string;
    days: Array<{
        date: string;
        code: string;
        label: string;
        is_weekend: boolean;
        is_future: boolean;
    }>;
    totals: {
        present: number;
        absent: number;
        late: number;
        week_off: number;
        net_seconds: number;
        average_hours: number;
    };
}

interface MonthlySummary {
    total_employees: number;
    working_days: number;
    present: number;
    absent: number;
    late: number;
    week_off: number;
    average_working_hours: number;
}

export interface MonthlyReport {
    month: number;
    year: number;
    label: string;
    days: MonthlyDay[];
    rows: MonthlyRow[];
    summary: MonthlySummary;
    filter: {
        month: number;
        year: number;
        employee_id: number | null;
        department_id: number | null;
        office_id: number | null;
    };
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
    records: DailyAttendanceRecord[];
}

interface MonthlyReportPanelProps {
    report: MonthlyReport;
    employeeDetail: EmployeeDetail | null;
    filterOptions: {
        employees: FilterOption[];
        departments: FilterOption[];
        offices: FilterOption[];
    };
    onFilterChange: (changes: Record<string, string | number | null | undefined>) => void;
}

const SUMMARY_ICONS: Record<string, typeof Users> = {
    total_employees: Users,
    working_days: CalendarDays,
    present: UserCheck,
    absent: UserX,
    late: Clock,
    week_off: Coffee,
    average_working_hours: LogOut,
};

const SUMMARY_TONES: Record<string, KpiTone> = {
    total_employees: 'indigo',
    working_days: 'sky',
    present: 'emerald',
    absent: 'amber',
    late: 'sky',
    week_off: 'teal',
    average_working_hours: 'fuchsia',
};

const LEGEND_ITEMS = [
    { label: 'Present', className: `${REPORT_STATUS_STYLES.P.cell} ${REPORT_STATUS_STYLES.P.text}` },
    { label: 'Absent', className: `${REPORT_STATUS_STYLES.A.cell} ${REPORT_STATUS_STYLES.A.text}` },
    { label: 'Late', className: `${REPORT_STATUS_STYLES.L.cell} ${REPORT_STATUS_STYLES.L.text}` },
    { label: 'Week Off', className: `${REPORT_STATUS_STYLES.OFF.cell} ${REPORT_STATUS_STYLES.OFF.text}` },
];

export function MonthYearControls({
    month,
    year,
    onNavigate,
}: {
    month: number;
    year: number;
    onNavigate: (month: number, year: number) => void;
}) {
    const previous = () => {
        const date = new Date(year, month - 2, 1);
        onNavigate(date.getMonth() + 1, date.getFullYear());
    };

    const next = () => {
        const date = new Date(year, month, 1);
        const now = new Date();
        if (date > now) {
            return;
        }
        onNavigate(date.getMonth() + 1, date.getFullYear());
    };

    const atCurrentMonth = year === new Date().getFullYear() && month === new Date().getMonth() + 1;

    return (
        <div className="flex w-full max-w-full min-w-0 items-center justify-center gap-2 sm:justify-start">
            <Button type="button" variant="outline" size="icon" className="shrink-0" onClick={previous} aria-label="Previous month">
                <ChevronLeft className="h-4 w-4" />
            </Button>
            <div className="min-w-0 max-w-[min(100%,12rem)] truncate text-center text-sm font-medium sm:max-w-none sm:min-w-36 sm:overflow-visible">
                {new Date(year, month - 1, 1).toLocaleDateString(undefined, {
                    month: 'long',
                    year: 'numeric',
                })}
            </div>
            <Button
                type="button"
                variant="outline"
                size="icon"
                className="shrink-0"
                onClick={next}
                disabled={atCurrentMonth}
                aria-label="Next month"
            >
                <ChevronRight className="h-4 w-4" />
            </Button>
        </div>
    );
}

export function MonthlyReportPanel({
    report,
    employeeDetail,
    filterOptions,
    onFilterChange,
}: MonthlyReportPanelProps) {
    const [exportError, setExportError] = useState<string | null>(null);
    const selectedEmployeeId = employeeDetail?.employee.id ?? null;

    const exportHref = `/admin/attendance/export/monthly?${buildExportQuery({
        month: report.filter.month,
        year: report.filter.year,
        employee_id: report.filter.employee_id ?? undefined,
        department_id: report.filter.department_id ?? undefined,
        office_id: report.filter.office_id ?? undefined,
    })}`;

    const handleExport = async () => {
        setExportError(null);

        try {
            const response = await fetch(exportHref, {
                credentials: 'same-origin',
                headers: { Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
            });

            if (! response.ok) {
                throw new Error('Unable to download the attendance report. Please try again.');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `VSP_Attendance_${new Date(report.filter.year, report.filter.month - 1, 1).toLocaleDateString('en-US', { month: 'long' })}_${report.filter.year}.xlsx`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch {
            setExportError('Unable to download the attendance report. Please try again.');
        }
    };

    return (
        <section id="monthly-attendance-report" className="min-w-0 max-w-full space-y-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold tracking-tight">Monthly Attendance Report</h2>
                    <p className="text-muted-foreground text-sm">
                        Monthly attendance summary and employee-wise attendance register.
                    </p>
                </div>
                <div className="flex flex-col items-stretch gap-2 sm:items-end">
                    <Button type="button" onClick={handleExport} className="w-full sm:w-auto">
                        <Download className="mr-2 h-4 w-4" />
                        Download Excel
                    </Button>
                    {exportError && <p className="text-destructive text-sm">{exportError}</p>}
                </div>
            </div>

            <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div className="min-w-0 max-w-full">
                    <MonthYearControls
                        month={report.filter.month}
                        year={report.filter.year}
                        onNavigate={(month, year) => onFilterChange({ month, year, detail_employee_id: null })}
                    />
                </div>

                <div className="grid min-w-0 max-w-full gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:max-w-3xl xl:flex-1">
                    <Select
                        value={report.filter.employee_id ? String(report.filter.employee_id) : 'all'}
                        onValueChange={(value) =>
                            onFilterChange({
                                employee_id: value === 'all' ? null : Number(value),
                                detail_employee_id: null,
                            })
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
                        value={report.filter.department_id ? String(report.filter.department_id) : 'all'}
                        onValueChange={(value) =>
                            onFilterChange({
                                department_id: value === 'all' ? null : Number(value),
                                detail_employee_id: null,
                            })
                        }
                    >
                        <SelectTrigger className="w-full min-w-0">
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

                    <Select
                        value={report.filter.office_id ? String(report.filter.office_id) : 'all'}
                        onValueChange={(value) =>
                            onFilterChange({
                                office_id: value === 'all' ? null : Number(value),
                                detail_employee_id: null,
                            })
                        }
                    >
                        <SelectTrigger className="w-full min-w-0">
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

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                {[
                    ['total_employees', 'Total Employees', report.summary.total_employees],
                    ['working_days', 'Working Days', report.summary.working_days],
                    ['present', 'Present', report.summary.present],
                    ['absent', 'Absent', report.summary.absent],
                    ['late', 'Late', report.summary.late],
                    ['week_off', 'Week Off', report.summary.week_off],
                    ['average_working_hours', 'Average Working Hours', report.summary.average_working_hours],
                ].map(([key, label, value]) => {
                    const Icon = SUMMARY_ICONS[key] ?? Users;

                    return (
                        <KpiStatCard
                            key={key}
                            label={label}
                            value={String(value)}
                            icon={Icon}
                            tone={SUMMARY_TONES[key] ?? 'indigo'}
                        />
                    );
                })}
            </div>

            <div className="flex flex-wrap gap-3 text-xs font-medium">
                {LEGEND_ITEMS.map((item) => (
                    <span
                        key={item.label}
                        className={cn('inline-flex items-center rounded-md border px-2.5 py-1', item.className)}
                    >
                        {item.label}
                    </span>
                ))}
            </div>

            <DataTableCard
                title="Monthly attendance matrix"
                description="Click an employee row to inspect daily records for the selected month."
            >
                <AttendanceTableScroll className="monthly-attendance-table-container">
                    <table className={attendanceTableClassName}>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="sticky left-0 z-10 min-w-40 bg-[var(--table-header-bg-color,#f5f5f7)]">
                                    Employee
                                </TableHead>
                                <TableHead className="min-w-28">Employee ID</TableHead>
                                {report.days.map((day) => (
                                    <TableHead
                                        key={day.date}
                                        className={cn(
                                            'min-w-10 text-center',
                                            day.is_weekend && 'bg-yellow-100 text-yellow-900',
                                        )}
                                    >
                                        {day.day}
                                    </TableHead>
                                ))}
                                <TableHead className="min-w-20 text-center">Present</TableHead>
                                <TableHead className="min-w-20 text-center">Absent</TableHead>
                                <TableHead className="min-w-16 text-center">Late</TableHead>
                                <TableHead className="min-w-16 text-center">Off</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.rows.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={report.days.length + 6}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No employees match the selected monthly filters.
                                    </TableCell>
                                </TableRow>
                            )}

                            {report.rows.map((row) => (
                                <TableRow
                                    key={row.employee_id}
                                    className={cn(
                                        'cursor-pointer',
                                        selectedEmployeeId === row.employee_id && 'bg-muted/50',
                                    )}
                                    onClick={() =>
                                        onFilterChange({
                                            detail_employee_id:
                                                selectedEmployeeId === row.employee_id ? null : row.employee_id,
                                        })
                                    }
                                >
                                    <TableCell
                                        className={cn(
                                            'sticky left-0 z-10 bg-card font-medium',
                                            selectedEmployeeId === row.employee_id && 'bg-muted/50',
                                        )}
                                    >
                                        {row.employee}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">{row.employee_code}</TableCell>
                                    {row.days.map((day) => (
                                        <TableCell
                                            key={day.date}
                                            className={cn(
                                                'text-center text-xs font-semibold',
                                                reportCodeClass(day.code, day.is_weekend),
                                            )}
                                        >
                                            {day.code || '—'}
                                        </TableCell>
                                    ))}
                                    <TableCell className="text-center tabular-nums">{row.totals.present}</TableCell>
                                    <TableCell className="text-center tabular-nums">{row.totals.absent}</TableCell>
                                    <TableCell className="text-center tabular-nums">{row.totals.late}</TableCell>
                                    <TableCell className="text-center tabular-nums">{row.totals.week_off}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </table>
                </AttendanceTableScroll>
            </DataTableCard>

            {employeeDetail && (
                <DataTableCard
                    title={`${employeeDetail.employee.name} — ${employeeDetail.label}`}
                    description="Detailed attendance records for the selected employee."
                    action={
                        <Button variant="outline" size="sm" onClick={() => onFilterChange({ detail_employee_id: null })}>
                            Close detail
                        </Button>
                    }
                >
                    <AttendanceTableScroll className="daily-attendance-table-container">
                        <table className={attendanceTableClassName}>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-28">Date</TableHead>
                                    <TableHead className="min-w-24">Day</TableHead>
                                    <TableHead className="min-w-24">Check in</TableHead>
                                    <TableHead className="min-w-24">Check out</TableHead>
                                    <TableHead className="min-w-24">Break</TableHead>
                                    <TableHead className="min-w-28">Net hours</TableHead>
                                    <TableHead className="min-w-28">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {employeeDetail.records.map((record) => (
                                    <TableRow key={`${record.date}-${record.id}`}>
                                        <TableCell className="tabular-nums">{record.date}</TableCell>
                                        <TableCell>{record.day}</TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.check_in_at ? formatTimeLabel(record.check_in_at) : '—'}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.check_out_at ? formatTimeLabel(record.check_out_at) : '—'}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.total_break_seconds > 0
                                                ? formatDuration(record.total_break_seconds)
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {record.net_working_seconds !== null
                                                ? formatDuration(record.net_working_seconds)
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                className={reportCodeClass(
                                                    record.report_code ?? '',
                                                    record.status === 'week_off',
                                                )}
                                            >
                                                {record.report_label || record.status_label}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </table>
                    </AttendanceTableScroll>
                </DataTableCard>
            )}
        </section>
    );
}
