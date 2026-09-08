import { PageHeader } from '@/components/admin/page-header';
import { TodayAttendanceCard, type AttendanceMarkData } from '@/components/attendance/today-attendance-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAttendanceActions } from '@/hooks/use-attendance-actions';
import { useAttendanceBreakActions } from '@/hooks/use-attendance-break-actions';
import AppLayout from '@/layouts/app-layout';
import { formatTimeLabel } from '@/lib/attendance/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Home, LoaderCircle, LogOut, MapPin } from 'lucide-react';
import { useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Attendance', href: '/attendance/mark' }];

const STATUS_TONE: Record<string, 'success' | 'warning' | 'neutral' | 'info'> = {
    not_checked_in: 'neutral',
    present: 'success',
    late: 'warning',
    on_break: 'info',
    checked_out: 'neutral',
};

export default function AttendanceMark(props: AttendanceMarkData) {
    const { office, can_mark_attendance, location_bypass_enabled, location_fallback, today } = props;
    const { flash } = usePage<SharedData>().props;
    const { performWfh, reset, isBusy: isAttendanceBusy, action } = useAttendanceActions({
        locationBypassEnabled: location_bypass_enabled,
        fallbackCoordinates: location_fallback,
    });
    const { reset: resetBreak, isBusy: isBreakBusy } = useAttendanceBreakActions();

    useEffect(() => {
        if (flash?.error) {
            reset();
            resetBreak();
        }
    }, [flash?.error, reset, resetBreak]);

    const handleWfhAction = async (nextAction: 'check_in' | 'check_out') => {
        reset();
        resetBreak();

        try {
            await performWfh(nextAction);
        } catch {
            // Error state is stored in the hook.
        }
    };

    const isBusy = isAttendanceBusy || isBreakBusy;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Attendance"
                    description={
                        location_bypass_enabled
                            ? 'Check in and check out from any location. Location restrictions are bypassed for Super Admin.'
                            : 'Check in and check out from your assigned office. GPS verification runs automatically before each action.'
                    }
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    {today.wfh_request && (
                        <Card className="border-sky-200 bg-gradient-to-br from-white to-sky-50/70 lg:col-span-2">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Home className="size-5 text-sky-700" strokeWidth={1.75} />
                                    {today.is_wfh ? 'Working From Home' : 'Work From Home'}
                                </CardTitle>
                                <CardDescription>
                                    {today.is_wfh && today.check_in_at
                                        ? `Checked in at ${formatTimeLabel(today.check_in_at)}`
                                        : today.wfh_request.source_label
                                          ? `${today.wfh_request.source_label} for ${today.wfh_request.date_range_label ?? today.wfh_request.date}. Check in without office GPS verification.`
                                          : 'You have approved WFH for today. Check in without office GPS verification.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-wrap items-center gap-3">
                                <Badge variant="info">{today.wfh_request.status_label}</Badge>
                                {today.wfh_request.source_label && (
                                    <span className="text-muted-foreground text-sm">{today.wfh_request.source_label}</span>
                                )}
                                {today.can_check_in && today.can_check_in_wfh && (
                                    <Button type="button" disabled={!can_mark_attendance || isBusy} onClick={() => handleWfhAction('check_in')}>
                                        {isAttendanceBusy && action === 'check_in' ? <LoaderCircle className="animate-spin" /> : <Home />}
                                        Check In
                                    </Button>
                                )}
                                {today.is_wfh && today.can_check_out && (
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
                            </CardContent>
                        </Card>
                    )}

                    {!today.wfh_request && today.can_check_in && !today.can_check_in_wfh && (
                        <Card className="border-dashed lg:col-span-2">
                            <CardContent className="text-muted-foreground py-4 text-sm">
                                Work From Home is not approved for today.
                            </CardContent>
                        </Card>
                    )}

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
                                            Office network verification is enabled. Check-in and check-out require office Wi-Fi
                                            in addition to GPS.
                                        </p>
                                    )}
                                </>
                            ) : location_bypass_enabled ? (
                                <p className="text-muted-foreground text-sm">
                                    As Super Admin, you can mark attendance from any location.
                                </p>
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

                    <TodayAttendanceCard attendance={props} variant="session" />
                </div>
            </div>
        </AppLayout>
    );
}
