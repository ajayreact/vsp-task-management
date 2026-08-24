import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDuration, formatTimeLabel } from '@/lib/attendance/format';
import { FILTER_LABELS, RECORD_STATUS_TONE } from '@/lib/attendance/report-status';

export interface DailyAttendanceRecord {
    id: number;
    employee_id?: number;
    employee: string;
    employee_code: string;
    date: string;
    day: string;
    office: string;
    status: string;
    status_label: string;
    is_late?: boolean;
    check_in_at: string | null;
    check_out_at: string | null;
    total_break_seconds: number;
    break_count: number;
    net_working_seconds: number | null;
    report_code?: string;
    report_label?: string;
}

interface DailyAttendanceTableProps {
    records: DailyAttendanceRecord[];
    isToday: boolean;
    activeStatus: string | null;
}

export function DailyAttendanceTable({ records, isToday, activeStatus }: DailyAttendanceTableProps) {
    const emptyMessage = activeStatus
        ? `No ${FILTER_LABELS[activeStatus].toLowerCase()} employees for this date.`
        : isToday
          ? 'No attendance records recorded yet today.'
          : 'No attendance records for this date.';

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Employee</TableHead>
                    <TableHead>Employee ID</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Day</TableHead>
                    <TableHead>Office</TableHead>
                    <TableHead>Check in</TableHead>
                    <TableHead>Check out</TableHead>
                    <TableHead>Break time</TableHead>
                    <TableHead>Net working hours</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Late</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {records.length === 0 && (
                    <TableRow>
                        <TableCell colSpan={11} className="text-muted-foreground py-10 text-center">
                            {emptyMessage}
                        </TableCell>
                    </TableRow>
                )}

                {records.map((record) => (
                    <TableRow key={record.id}>
                        <TableCell className="font-medium">{record.employee}</TableCell>
                        <TableCell className="font-mono text-xs">{record.employee_code}</TableCell>
                        <TableCell className="tabular-nums">{record.date}</TableCell>
                        <TableCell>{record.day}</TableCell>
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
                        <TableCell>
                            {record.is_late ? (
                                <Badge variant="warning">Late</Badge>
                            ) : record.check_in_at ? (
                                <span className="text-muted-foreground text-sm">On time</span>
                            ) : (
                                '—'
                            )}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
