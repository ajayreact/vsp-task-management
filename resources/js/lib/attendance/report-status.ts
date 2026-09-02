export type AttendanceReportCode = 'P' | 'WFH' | 'A' | 'L' | 'OFF' | '';

export const REPORT_STATUS_STYLES: Record<string, { cell: string; text: string; label: string }> = {
    P: {
        cell: 'bg-emerald-100',
        text: 'text-emerald-800',
        label: 'Present - Office',
    },
    WFH: {
        cell: 'bg-sky-100',
        text: 'text-sky-800',
        label: 'Present - Work From Home',
    },
    A: {
        cell: 'bg-red-100',
        text: 'text-red-800',
        label: 'Absent',
    },
    L: {
        cell: 'bg-orange-100',
        text: 'text-orange-800',
        label: 'Late',
    },
    OFF: {
        cell: 'bg-yellow-100',
        text: 'text-yellow-900',
        label: 'Week off',
    },
};

export const RECORD_STATUS_TONE: Record<string, 'success' | 'warning' | 'neutral' | 'info' | 'danger'> = {
    present: 'success',
    late: 'warning',
    on_break: 'info',
    checked_out: 'neutral',
    absent: 'danger',
    week_off: 'neutral',
};

export const WORK_MODE_TONE: Record<string, 'success' | 'info' | 'neutral'> = {
    office: 'success',
    wfh: 'info',
};

export const FILTER_LABELS: Record<string, string> = {
    present: 'Present',
    absent: 'Absent',
    late: 'Late',
    on_break: 'On break',
    checked_out: 'Checked out',
};

export function reportCodeClass(code: string, isWeekend = false): string {
    if (code === '' && isWeekend) {
        return `${REPORT_STATUS_STYLES.OFF.cell} ${REPORT_STATUS_STYLES.OFF.text}`;
    }

    const style = REPORT_STATUS_STYLES[code];

    if (! style) {
        return 'text-muted-foreground';
    }

    return `${style.cell} ${style.text}`;
}
