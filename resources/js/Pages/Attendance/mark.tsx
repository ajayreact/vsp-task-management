import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAttendanceActions } from '@/hooks/use-attendance-actions';
import { useAttendanceBreakActions } from '@/hooks/use-attendance-break-actions';
import { useBreakDuration, useNetWorkingDuration } from '@/hooks/use-working-duration';
import AppLayout from '@/layouts/app-layout';
import { formatDuration, formatTimeLabel } from '@/lib/attendance/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Clock3, Coffee, LoaderCircle, LogIn, LogOut, MapPin, Play } from 'lucide-react';
import { useEffect } from 'react';

interface OfficeSummary {
    id: number;
    name: string;
    address: string;
    allowed_gps_radius_meters: number;
    network_verification_enabled: boolean;
    is_active: boolean;
}

interface TodaySnapshot {
    status: string;
    status_label: string;
    check_in_at: string | null;
    check_out_at: string | null;
    total_break_seconds: number;
    net_working_seconds: number | null;
    active_break_started_at: string | null;
    break_count: number;
    can_check_in: boolean;
    can_check_out: boolean;
    can_start_break: boolean;
    can_resume_work: boolean;
}

interface Props {
    office: OfficeSummary | null;
    today: TodaySnapshot;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Attendance', href: '/attendance/mark' }];

const STATUS_TONE: Record<string, 'success' | 'warning' | 'neutral' | 'info'> = {
    not_checked_in: 'neutral',
    present: 'success',
    late: 'warning',
    on_break: 'info',
    checked_out: 'neutral',
};

export default function AttendanceMark({ office, today }: Props) {
    const { flash } = usePage<SharedData>().props;
    const { perform, reset, isBusy: isAttendanceBusy, phase, action, error } = useAttendanceActions();
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

    const isBusy = isAttendanceBusy || isBreakBusy;
    const busyLabel = phase === 'locating' ? 'Getting location…' : 'Verifying location and saving…';
    const displayError = error ?? flash?.error ?? null;
    const displaySuccess = flash?.success ?? null;

    const totalBreakDisplaySeconds = today.can_resume_work
        ? today.total_break_seconds + currentBreakSeconds
        : today.total_break_seconds;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Attendance"
                    description="Check in and check out from your assigned office. GPS verification runs automatically before each action."
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="size-5" strokeWidth={1.75} />
                                Assigned office
                            </CardTitle>
                            <CardDescription>Your location is compared with this office and its GPS radius.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {office ? (
                                <>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-lg font-semibold">{office.name}</p>
                                        <Badge variant={office.is_active ? 'success' : 'neutral'}>
                                            {office.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground text-sm leading-relaxed">{office.address}</p>
                                    <p className="text-muted-foreground text-sm">
                                        Allowed radius:{' '}
                                        <span className="text-foreground font-medium tabular-nums">
                                            {office.allowed_gps_radius_meters} m
                                        </span>
                                    </p>
                                    {office.network_verification_enabled && (
                                        <p className="text-muted-foreground text-sm">
                                            Office network verification is enabled. Check-in and check-out require office
                                            Wi-Fi in addition to GPS.
                                        </p>
                                    )}
                                </>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    No office is assigned to your profile yet. Ask a Super Admin to assign one before marking
                                    attendance.
                                </p>
                            )}

                            <div className="border-t border-[rgba(120,115,110,0.12)] pt-4">
                                <p className="text-muted-foreground mb-2 text-sm">Today&apos;s status</p>
                                <Badge variant={STATUS_TONE[today.status] ?? 'neutral'}>{today.status_label}</Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock3 className="size-5" strokeWidth={1.75} />
                                Today&apos;s attendance
                            </CardTitle>
                            <CardDescription>
                                {today.can_check_in && 'Check in when you arrive at the office.'}
                                {today.can_check_out && 'You are currently working. Start a break when you step away.'}
                                {today.status === 'late' && today.can_check_out && 'You checked in late today. Start a break when you step away.'}
                                {today.can_resume_work && 'You are on a break. Resume work when you return.'}
                                {!today.can_check_in &&
                                    !today.can_check_out &&
                                    !today.can_start_break &&
                                    !today.can_resume_work &&
                                    today.check_out_at &&
                                    'Your attendance for today is complete.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
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
                                                {formatDuration(
                                                    today.can_resume_work ? currentBreakSeconds : totalBreakDisplaySeconds,
                                                )}
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
                                {today.can_check_in && (
                                    <Button
                                        type="button"
                                        disabled={!office?.is_active || isBusy}
                                        onClick={() => handleAction('check_in')}
                                    >
                                        {isAttendanceBusy && action === 'check_in' ? (
                                            <LoaderCircle className="animate-spin" />
                                        ) : (
                                            <LogIn />
                                        )}
                                        Check In
                                    </Button>
                                )}

                                {today.can_start_break && (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        disabled={!office?.is_active || isBusy}
                                        onClick={() => handleBreakAction('start')}
                                    >
                                        {isBreakBusy && breakAction === 'start' ? (
                                            <LoaderCircle className="animate-spin" />
                                        ) : (
                                            <Coffee />
                                        )}
                                        Start Break
                                    </Button>
                                )}

                                {today.can_resume_work && (
                                    <Button
                                        type="button"
                                        disabled={!office?.is_active || isBusy}
                                        onClick={() => handleBreakAction('resume')}
                                    >
                                        {isBreakBusy && breakAction === 'resume' ? (
                                            <LoaderCircle className="animate-spin" />
                                        ) : (
                                            <Play />
                                        )}
                                        Resume Work
                                    </Button>
                                )}

                                {today.can_check_out && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={!office?.is_active || isBusy}
                                        onClick={() => handleAction('check_out')}
                                    >
                                        {isAttendanceBusy && action === 'check_out' ? (
                                            <LoaderCircle className="animate-spin" />
                                        ) : (
                                            <LogOut />
                                        )}
                                        Check Out
                                    </Button>
                                )}
                            </div>

                            {isAttendanceBusy && <p className="text-muted-foreground text-sm">{busyLabel}</p>}

                            {displayError && (
                                <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                                    {displayError}
                                </div>
                            )}

                            {displaySuccess && (
                                <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                    {displaySuccess}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
