import { DataTableCard } from '@/components/admin/data-table-card';
import { KpiStatCard, type KpiTone } from '@/components/admin/kpi-stat-card';
import { DailyAttendanceTable, type DailyAttendanceRecord } from '@/components/attendance/daily-attendance-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDuration, formatTimeLabel } from '@/lib/attendance/format';
import { cn } from '@/lib/utils';
import { reportCodeClass } from '@/lib/attendance/report-status';
import { ChevronLeft, ChevronRight, Download } from 'lucide-react';
import { buildExportQuery } from '@/components/admin/data-table-export';

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

interface MonthlyReport {
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
    selectedEmployeeId: number | null;
    onEmployeeSelect: (employeeId: number | null) => void;
}

const SUMMARY_TONES: Record<string, KpiTone> = {
    total_employees: 'indigo',
    working_days: 'sky',
    present: 'emerald',
    absent: 'amber',
    late: 'sky',
    week_off: 'teal',
    average_working_hours: 'fuchsia',
};

export function MonthlyReportPanel({
    report,
    employeeDetail,
    selectedEmployeeId,
    onEmployeeSelect,
}: MonthlyReportPanelProps) {
    const exportHref = `/admin/attendance/export/monthly?${buildExportQuery({
        month: report.filter.month,
        year: report.filter.year,
        employee_id: report.filter.employee_id ?? undefined,
        department_id: report.filter.department_id ?? undefined,
        office_id: report.filter.office_id ?? undefined,
    })}`;

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="text-lg font-semibold">{report.label}</h2>
                    <p className="text-muted-foreground text-sm">Monthly attendance matrix and summary totals.</p>
                </div>
                <Button asChild>
                    <a href={exportHref}>
                        <Download className="mr-2 h-4 w-4" />
                        Download Monthly Excel
                    </a>
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                {[
                    ['total_employees', 'Total Employees', report.summary.total_employees],
                    ['working_days', 'Working Days', report.summary.working_days],
                    ['present', 'Present', report.summary.present],
                    ['absent', 'Absent', report.summary.absent],
                    ['late', 'Late', report.summary.late],
                    ['week_off', 'Week Off', report.summary.week_off],
                    ['average_working_hours', 'Average Working Hours', report.summary.average_working_hours],
                ].map(([key, label, value]) => (
                    <KpiStatCard
                        key={key}
                        label={label}
                        value={String(value)}
                        tone={SUMMARY_TONES[key] ?? 'indigo'}
                    />
                ))}
            </div>

            <DataTableCard
                title="Monthly attendance matrix"
                description="Click an employee row to inspect daily records for the selected month."
            >
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="sticky left-0 z-10 bg-background">Employee</TableHead>
                                <TableHead>Employee ID</TableHead>
                                {report.days.map((day) => (
                                    <TableHead
                                        key={day.date}
                                        className={cn(
                                            'min-w-10 text-center',
                                            day.is_weekend && 'bg-yellow-100 text-yellow-900',
                                        )}
                                    >
                                        {String(day.day).padStart(2, '0')}
                                    </TableHead>
                                ))}
                                <TableHead className="text-center">Present</TableHead>
                                <TableHead className="text-center">Absent</TableHead>
                                <TableHead className="text-center">Late</TableHead>
                                <TableHead className="text-center">Off</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.rows.map((row) => (
                                <TableRow
                                    key={row.employee_id}
                                    className={cn(
                                        'cursor-pointer',
                                        selectedEmployeeId === row.employee_id && 'bg-muted/50',
                                    )}
                                    onClick={() =>
                                        onEmployeeSelect(
                                            selectedEmployeeId === row.employee_id ? null : row.employee_id,
                                        )
                                    }
                                >
                                    <TableCell className="sticky left-0 z-10 bg-background font-medium">
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
                    </Table>
                </div>
            </DataTableCard>

            {employeeDetail && (
                <DataTableCard
                    title={`${employeeDetail.employee.name} — ${employeeDetail.label}`}
                    description="Detailed attendance records for the selected employee."
                    action={
                        <Button variant="outline" size="sm" onClick={() => onEmployeeSelect(null)}>
                            Close detail
                        </Button>
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Day</TableHead>
                                <TableHead>Check in</TableHead>
                                <TableHead>Check out</TableHead>
                                <TableHead>Break</TableHead>
                                <TableHead>Net hours</TableHead>
                                <TableHead>Status</TableHead>
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
                    </Table>
                </DataTableCard>
            )}
        </div>
    );
}

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
        <div className="flex items-center gap-2">
            <Button type="button" variant="outline" size="icon" onClick={previous}>
                <ChevronLeft className="h-4 w-4" />
            </Button>
            <div className="min-w-40 text-center text-sm font-medium">
                {new Date(year, month - 1, 1).toLocaleDateString(undefined, {
                    month: 'long',
                    year: 'numeric',
                })}
            </div>
            <Button type="button" variant="outline" size="icon" onClick={next} disabled={atCurrentMonth}>
                <ChevronRight className="h-4 w-4" />
            </Button>
        </div>
    );
}
