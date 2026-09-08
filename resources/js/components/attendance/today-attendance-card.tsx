import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAttendanceActions } from '@/hooks/use-attendance-actions';
import { useAttendanceBreakActions } from '@/hooks/use-attendance-break-actions';
import { useBreakDuration, useNetWorkingDuration } from '@/hooks/use-working-duration';
import { formatDuration, formatTimeLabel } from '@/lib/attendance/format';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Clock3, Coffee, Home, LoaderCircle, LogIn, LogOut, MapPin, Play } from 'lucide-react';
import { useEffect } from 'react';

export interface AttendanceOfficeSummary {
    id: number;
    name: string;
    address: string;
    allowed_gps_radius_meters: number;
    network_verification_enabled: boolean;
    is_active: boolean;
}

export interface AttendanceTodaySnapshot {
    status: string;
    status_label: string;
    work_mode?: string | null;
    work_mode_label?: string | null;
    is_wfh?: boolean;
    check_in_at: string | null;
    check_out_at: string | null;
    total_break_seconds: number;
    net_working_seconds: number | null;
    active_break_started_at: string | null;
    break_count: number;
    can_check_in: boolean;
    can_check_in_wfh?: boolean;
    can_check_out: boolean;
    can_start_break: boolean;
    can_resume_work: boolean;
    wfh_request?: {
        id: number;
        date: string;
        date_range_label?: string;
        source_label?: string;
        status: string;
        status_label: string;
    } | null;
}

export interface AttendanceMarkData {
    office: AttendanceOfficeSummary | null;
    can_mark_attendance: boolean;
    location_bypass_enabled: boolean;
    location_fallback: { latitude: number; longitude: number } | null;
    today: AttendanceTodaySnapshot;
}

const STATUS_TONE: Record<string, 'success' | 'warning' | 'neutral' | 'info'> = {
    not_checked_in: 'neutral',
    present: 'success',
    late: 'warning',
    on_break: 'info',
    checked_out: 'neutral',
};

/**
 * Shared “Today's attendance” card used by /attendance/mark and the Dashboard.
 * Actions reuse the same hooks and endpoints as the Attendance page.
 */
export function TodayAttendanceCard({
    attendance,
    compact = false,
    variant = 'full',
}: {
    attendance: AttendanceMarkData;
    compact?: boolean;
    /** session = Attendance page column (no WFH banner / office line / WFH buttons). */
    variant?: 'full' | 'session';
}) {
    const { office, can_mark_attendance, location_bypass_enabled, location_fallback, today } = attendance;
    const isFull = variant === 'full';
    const { flash } = usePage<SharedData>().props;
    const { perform, performWfh, reset, isBusy: isAttendanceBusy, phase, action, error } = useAttendanceActions({
        locationBypassEnabled: location_bypass_enabled,
        fallbackCoordinates: location_fallback,
    });
    const {
        perform: performBreak,
        reset: resetBreak,
        isBusy: isBreakBusy,
        action: breakAction,
    } = useAttendanceBreakActions();

    const isSessionOpen = today.check_in_at !== null && today.check_out_at === null;
    const netWorkingSeconds = useNetWorkingDuration({
        checkInAt: today.check_in_at,
        totalBreakSeconds: today.total_break_seconds,
        activeBreakStartedAt: today.active_break_started_at,
        isActive: isSessionOpen,
        frozenNetSeconds: today.check_out_at !== null ? today.net_working_seconds : null,
    });
    const currentBreakSeconds = useBreakDuration(today.active_break_started_at, today.can_resume_work);

    useEffect(() => {
        if (flash?.error) {
            reset();
            resetBreak();
        }
    }, [flash?.error, reset, resetBreak]);

    const handleAction = async (nextAction: 'check_in' | 'check_out') => {
        reset();
        resetBreak();

        try {
            await perform(nextAction);
        } catch {
            // Error state is stored in the hook; flash may also carry server errors.
        }
    };

    const handleBreakAction = async (nextAction: 'start' | 'resume') => {
        reset();
        resetBreak();

        await performBreak(nextAction);
    };

    const handleWfhAction = async (nextAction: 'check_in' | 'check_out') => {
        reset();
        resetBreak();

        try {
            await performWfh(nextAction);
        } catch {
            // Error state is stored in the hook.
        }
    };

    const canCheckInOffice =
        today.can_check_in && (location_bypass_enabled || (office !== null && office.is_active));

    const isBusy = isAttendanceBusy || isBreakBusy;
    const busyLabel =
        phase === 'locating'
            ? location_bypass_enabled
                ? 'Saving attendance…'
                : 'Getting location…'
            : 'Verifying location and saving…';
    const displayError = error ?? flash?.error ?? null;
    const displaySuccess = flash?.success ?? null;

    const totalBreakDisplaySeconds = today.can_resume_work
        ? today.total_break_seconds + currentBreakSeconds
        : today.total_break_seconds;

    return (
        <Card className="h-full">
            <CardHeader>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1.5">
                        <CardTitle className="flex items-center gap-2">
                            <Clock3 className="size-5" strokeWidth={1.75} />
                            Today&apos;s attendance
                        </CardTitle>
                        <CardDescription>
                            {today.can_check_in &&
                                (location_bypass_enabled
                                    ? 'Check in when you are ready to start your day.'
                                    : 'Check in when you arrive at the office.')}
                            {today.can_check_out && 'You are currently working. Start a break when you step away.'}
                            {today.status === 'late' && today.can_check_out && ' You checked in late today.'}
                            {today.can_resume_work && 'You are on a break. Resume work when you return.'}
                            {!today.can_check_in &&
                                !today.can_check_out &&
                                !today.can_start_break &&
                                !today.can_resume_work &&
                                today.check_out_at &&
                                'Your attendance for today is complete.'}
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant={STATUS_TONE[today.status] ?? 'neutral'}>{today.status_label}</Badge>
                        {compact && (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/attendance/mark">Open Attendance</Link>
                            </Button>
                        )}
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {isFull && (office || location_bypass_enabled) && (
                    <div className="text-muted-foreground flex flex-wrap items-center gap-2 text-sm">
                        <MapPin className="size-4 shrink-0" strokeWidth={1.75} />
                        {office ? (
                            <span>
                                {office.name}
                                {office.is_active ? '' : ' (inactive)'}
                            </span>
                        ) : (
                            <span>Location restrictions bypassed</span>
                        )}
                    </div>
                )}

                {isFull && today.wfh_request && (
                    <div className="rounded-lg border border-sky-200 bg-sky-50/70 px-4 py-3 text-sm text-sky-950">
                        <div className="flex flex-wrap items-center gap-2">
                            <Home className="size-4" strokeWidth={1.75} />
                            <span className="font-medium">{today.is_wfh ? 'Working From Home' : 'Work From Home'}</span>
                            <Badge variant="info">{today.wfh_request.status_label}</Badge>
                        </div>
                        {today.wfh_request.source_label && (
                            <p className="text-sky-900/80 mt-1 text-xs">{today.wfh_request.source_label}</p>
                        )}
                    </div>
                )}

                {today.check_in_at && (
                    <div className="rounded-lg border border-[rgba(120,115,110,0.12)] bg-white px-4 py-3 text-sm">
                        <p className="text-muted-foreground">Check-in time</p>
                        <p className="text-foreground font-medium tabular-nums">{formatTimeLabel(today.check_in_at)}</p>
                    </div>
                )}

                {isSessionOpen && (
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div
                            className={`rounded-lg border px-4 py-3 text-sm ${
                                today.can_resume_work
                                    ? 'border-[rgba(120,115,110,0.12)] bg-white'
                                    : today.status === 'late'
                                      ? 'border-amber-200 bg-amber-50 text-amber-900'
                                      : 'border-emerald-200 bg-emerald-50 text-emerald-900'
                            }`}
                        >
                            <p
                                className={
                                    today.can_resume_work
                                        ? 'text-muted-foreground'
                                        : today.status === 'late'
                                          ? 'text-muted-foreground text-amber-900/80'
                                          : 'text-muted-foreground text-emerald-900/80'
                                }
                            >
                                Net working duration
                            </p>
                            <p className="text-2xl font-semibold tabular-nums">{formatDuration(netWorkingSeconds)}</p>
                        </div>

                        {(today.break_count > 0 || today.can_resume_work) && (
                            <div
                                className={`rounded-lg border px-4 py-3 text-sm ${
                                    today.can_resume_work
                                        ? 'border-sky-200 bg-sky-50 text-sky-900'
                                        : 'border-[rgba(120,115,110,0.12)] bg-white'
                                }`}
                            >
                                <p
                                    className={
                                        today.can_resume_work
                                            ? 'text-muted-foreground text-sky-900/80'
                                            : 'text-muted-foreground'
                                    }
                                >
                                    {today.can_resume_work ? 'Current break' : 'Total break time'}
                                </p>
                                <p className="text-2xl font-semibold tabular-nums">
                                    {formatDuration(today.can_resume_work ? currentBreakSeconds : totalBreakDisplaySeconds)}
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {today.check_out_at && (
                    <>
                        <div className="rounded-lg border border-[rgba(120,115,110,0.12)] bg-white px-4 py-3 text-sm">
                            <p className="text-muted-foreground">Check-out time</p>
                            <p className="text-foreground font-medium tabular-nums">{formatTimeLabel(today.check_out_at)}</p>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-lg border border-[rgba(120,115,110,0.12)] bg-white px-4 py-3 text-sm">
                                <p className="text-muted-foreground">Total break time</p>
                                <p className="text-foreground text-lg font-semibold tabular-nums">
                                    {formatDuration(today.total_break_seconds)}
                                </p>
                                {today.break_count > 0 && (
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {today.break_count} break{today.break_count === 1 ? '' : 's'} taken
                                    </p>
                                )}
                            </div>
                            <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                <p className="text-muted-foreground text-emerald-900/80">Net working hours</p>
                                <p className="text-lg font-semibold tabular-nums">
                                    {formatDuration(today.net_working_seconds ?? 0)}
                                </p>
                            </div>
                        </div>
                    </>
                )}

                <div className="flex flex-wrap gap-3">
                    {isFull && today.can_check_in && today.can_check_in_wfh && (
                        <Button type="button" disabled={!can_mark_attendance || isBusy} onClick={() => handleWfhAction('check_in')}>
                            {isAttendanceBusy && action === 'check_in' ? <LoaderCircle className="animate-spin" /> : <Home />}
                            Check In (WFH)
                        </Button>
                    )}

                    {canCheckInOffice && (
                        <Button type="button" disabled={!can_mark_attendance || isBusy} onClick={() => handleAction('check_in')}>
                            {isAttendanceBusy && action === 'check_in' ? <LoaderCircle className="animate-spin" /> : <LogIn />}
                            Check In
                        </Button>
                    )}

                    {today.can_start_break && (
                        <Button
                            type="button"
                            variant="secondary"
                            disabled={!can_mark_attendance || isBusy}
                            onClick={() => handleBreakAction('start')}
                        >
                            {isBreakBusy && breakAction === 'start' ? <LoaderCircle className="animate-spin" /> : <Coffee />}
                            Start Break
                        </Button>
                    )}

                    {today.can_resume_work && (
                        <Button type="button" disabled={!can_mark_attendance || isBusy} onClick={() => handleBreakAction('resume')}>
                            {isBreakBusy && breakAction === 'resume' ? <LoaderCircle className="animate-spin" /> : <Play />}
                            Resume Work
                        </Button>
                    )}

                    {isFull && today.can_check_out && today.is_wfh && (
                        <Button
                            type="button"
                            variant="outline"
                            disabled={!can_mark_attendance || isBusy}
                            onClick={() => handleWfhAction('check_out')}
                        >
                            {isAttendanceBusy && action === 'check_out' ? <LoaderCircle className="animate-spin" /> : <LogOut />}
                            Check Out
                        </Button>
                    )}

                    {today.can_check_out && !today.is_wfh && (
                        <Button
                            type="button"
                            variant="outline"
                            disabled={!can_mark_attendance || isBusy}
                            onClick={() => handleAction('check_out')}
                        >
                            {isAttendanceBusy && action === 'check_out' ? <LoaderCircle className="animate-spin" /> : <LogOut />}
                            Check Out
                        </Button>
                    )}
                </div>

                {isAttendanceBusy && <p className="text-muted-foreground text-sm">{busyLabel}</p>}

                {displayError && (
                    <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{displayError}</div>
                )}

                {displaySuccess && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {displaySuccess}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
