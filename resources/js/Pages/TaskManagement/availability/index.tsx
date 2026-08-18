import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { DashboardPanel, PanelEmpty } from '@/components/admin/dashboard-panel';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight, Clock3, Plane } from 'lucide-react';

interface Props {
    employee: { id: number; name: string } | null;
    week: { start: string; end: string };
    capacity: { weekly_hours: string | number; working_days: number[]; effective_from: string | null; available_hours: number };
    days: { date: string; weekday: string; is_working_day: boolean; hours: number }[];
    exceptions: { id: number; date: string; status: string; status_label: string; capacity_hours: string | null; notes: string | null }[];
    employees: { id: number; label: string }[];
    statuses: Option[];
    can: { manage: boolean; capacity: boolean };
}

const DAY_LABELS: Record<number, string> = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };

const DAY_TONES = [
    'from-white to-indigo-100/80',
    'from-white to-sky-100/80',
    'from-white to-emerald-100/80',
    'from-white to-teal-100/80',
    'from-white to-violet-100/80',
    'from-white to-slate-100/90',
    'from-white to-rose-100/70',
] as const;

const DAY_ACCENTS = [
    'bg-gradient-to-br from-indigo-600 to-violet-400',
    'bg-gradient-to-br from-sky-500 to-blue-400',
    'bg-gradient-to-br from-emerald-600 to-lime-400',
    'bg-gradient-to-br from-teal-600 to-cyan-400',
    'bg-gradient-to-br from-violet-600 to-fuchsia-400',
    'bg-gradient-to-br from-slate-500 to-slate-400',
    'bg-gradient-to-br from-rose-600 to-orange-400',
] as const;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Availability', href: '/tasks/availability' },
];

function shiftWeek(start: string, days: number): string {
    const date = new Date(start);
    date.setDate(date.getDate() + days);

    return date.toISOString().slice(0, 10);
}

function formatWeekLabel(start: string, end: string): string {
    const format = (value: string) =>
        new Date(`${value}T12:00:00`).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });

    return `${format(start)} – ${format(end)}`;
}

export default function AvailabilityIndex({ employee, week, capacity, days, exceptions, employees, statuses, can }: Props) {
    const exceptionForm = useForm({
        employee_id: employee ? String(employee.id) : '',
        date: week.start,
        status: 'leave',
        capacity_hours: '',
        notes: '',
    });

    const capacityForm = useForm({
        employee_id: employee ? String(employee.id) : '',
        weekly_hours: String(capacity.weekly_hours),
        working_days: capacity.working_days.map(String),
        effective_from: new Date().toISOString().slice(0, 10),
    });

    const go = (changes: Record<string, string | number | undefined>) => {
        router.get(
            '/tasks/availability',
            { week: week.start, employee: employee?.id, ...changes },
            { preserveState: !('employee' in changes), replace: true },
        );
    };

    const plannedHours = days.reduce((sum, day) => sum + day.hours, 0);

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Availability" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Availability"
                    description={
                        employee
                            ? `${employee.name} · ${capacity.available_hours} h available this week against a ${capacity.weekly_hours} h plan.`
                            : 'No employee profile to show yet.'
                    }
                />

                <DashboardPanel
                    title="This week"
                    description={`${formatWeekLabel(week.start, week.end)} · ${plannedHours} h planned across the calendar.`}
                    icon={CalendarDays}
                    tone="indigo"
                    action={
                        <div className="flex flex-wrap items-center justify-end gap-2">
                            <div className="flex items-center gap-1 rounded-xl border border-[rgba(120,115,110,0.2)] bg-white/90 p-1 shadow-[var(--vsp-card-shadow)]">
                                <Button variant="ghost" size="icon" className="size-8" onClick={() => go({ week: shiftWeek(week.start, -7) })} aria-label="Previous week">
                                    <ChevronLeft className="size-4" />
                                </Button>
                                <div className="text-foreground min-w-[11rem] px-2 text-center text-sm font-medium tabular-nums">
                                    {week.start} – {week.end}
                                </div>
                                <Button variant="ghost" size="icon" className="size-8" onClick={() => go({ week: shiftWeek(week.start, 7) })} aria-label="Next week">
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>

                            {employees.length > 0 && employee && (
                                <Select value={String(employee.id)} onValueChange={(value) => go({ employee: value })}>
                                    <SelectTrigger className="bg-white/90 w-64 shadow-[var(--vsp-card-shadow)]" aria-label="Employee">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {employees.map((person) => (
                                            <SelectItem key={person.id} value={String(person.id)}>
                                                {person.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        </div>
                    }
                >
                    <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-7">
                        {days.map((day, index) => {
                            const tone = DAY_TONES[index % DAY_TONES.length];
                            const accent = DAY_ACCENTS[index % DAY_ACCENTS.length];
                            const off = !day.is_working_day || day.hours <= 0;

                            return (
                                <article
                                    key={day.date}
                                    className={cn(
                                        'vsp-card mb-0 relative overflow-hidden bg-gradient-to-br px-4 py-4 transition-transform duration-300 hover:-translate-y-0.5',
                                        tone,
                                        off && 'opacity-90',
                                    )}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <p className="text-muted-foreground text-[11px] font-semibold tracking-[0.14em] uppercase">{day.weekday}</p>
                                            <p className="text-foreground mt-1 text-2xl font-bold tracking-tight tabular-nums">{day.date.slice(8)}</p>
                                        </div>
                                        <span className={cn('flex size-9 shrink-0 items-center justify-center rounded-full text-white shadow-sm', accent)}>
                                            <Clock3 className="size-4" strokeWidth={1.75} />
                                        </span>
                                    </div>
                                    <div className="mt-4 flex items-end justify-between gap-2">
                                        <p className="text-foreground text-lg font-semibold tabular-nums">{day.hours} h</p>
                                        <span
                                            className={cn(
                                                'rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase',
                                                off ? 'bg-slate-500/10 text-slate-600' : 'bg-emerald-500/10 text-emerald-700',
                                            )}
                                        >
                                            {off ? 'Off' : 'Working'}
                                        </span>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                </DashboardPanel>

                <div className="grid gap-6 lg:grid-cols-2">
                    {can.manage && (
                        <DashboardPanel title="Log leave or an exception" description="Overrides the planned hours for a single day." icon={Plane} tone="amber">
                            <form
                                className="grid gap-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    exceptionForm.post('/tasks/availability', {
                                        preserveScroll: true,
                                        onSuccess: () => exceptionForm.reset('notes'),
                                    });
                                }}
                            >
                                <input type="hidden" value={exceptionForm.data.employee_id} />
                                <div className="grid gap-2">
                                    <Label htmlFor="date">Date</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        className="bg-white/90"
                                        value={exceptionForm.data.date}
                                        onChange={(event) => exceptionForm.setData('date', event.target.value)}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <Select value={exceptionForm.data.status} onValueChange={(value) => exceptionForm.setData('status', value)}>
                                        <SelectTrigger id="status" className="bg-white/90">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statuses.map((status) => (
                                                <SelectItem key={status.value} value={status.value}>
                                                    {status.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="capacity_hours">Hours (optional override)</Label>
                                    <Input
                                        id="capacity_hours"
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        className="bg-white/90"
                                        value={exceptionForm.data.capacity_hours}
                                        onChange={(event) => exceptionForm.setData('capacity_hours', event.target.value)}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="notes">Notes</Label>
                                    <Textarea
                                        id="notes"
                                        className="bg-white/90"
                                        value={exceptionForm.data.notes}
                                        onChange={(event) => exceptionForm.setData('notes', event.target.value)}
                                    />
                                </div>
                                <Button type="submit" disabled={exceptionForm.processing}>
                                    Save exception
                                </Button>
                            </form>
                        </DashboardPanel>
                    )}

                    {can.capacity && employee && (
                        <DashboardPanel title="Weekly capacity" description="The planned baseline. Leave is applied on top of this." icon={Clock3} tone="sky">
                            <form
                                className="grid gap-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    capacityForm.post('/tasks/availability/capacity', { preserveScroll: true });
                                }}
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="weekly_hours">Weekly hours</Label>
                                    <Input
                                        id="weekly_hours"
                                        type="number"
                                        step="0.5"
                                        min="1"
                                        className="bg-white/90"
                                        value={capacityForm.data.weekly_hours}
                                        onChange={(event) => capacityForm.setData('weekly_hours', event.target.value)}
                                        required
                                    />
                                    <InputError message={capacityForm.errors.weekly_hours} />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Working days</Label>
                                    <div className="flex flex-wrap gap-3 rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/70 px-3 py-3">
                                        {[1, 2, 3, 4, 5, 6, 7].map((day) => (
                                            <label key={day} className="flex items-center gap-2 text-sm">
                                                <Checkbox
                                                    checked={capacityForm.data.working_days.includes(String(day))}
                                                    onCheckedChange={(checked) => {
                                                        const value = String(day);
                                                        capacityForm.setData(
                                                            'working_days',
                                                            checked === true
                                                                ? [...capacityForm.data.working_days, value]
                                                                : capacityForm.data.working_days.filter((current) => current !== value),
                                                        );
                                                    }}
                                                />
                                                {DAY_LABELS[day]}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="effective_from">Effective from</Label>
                                    <Input
                                        id="effective_from"
                                        type="date"
                                        className="bg-white/90"
                                        value={capacityForm.data.effective_from}
                                        onChange={(event) => capacityForm.setData('effective_from', event.target.value)}
                                        required
                                    />
                                </div>
                                <Button type="submit" disabled={capacityForm.processing}>
                                    Save capacity
                                </Button>
                            </form>
                        </DashboardPanel>
                    )}
                </div>

                {exceptions.length > 0 ? (
                    <DashboardPanel title="This week’s exceptions" description="Leave and overrides applied on top of the weekly plan." icon={Plane} tone="fuchsia">
                        <div className="space-y-2.5">
                            {exceptions.map((row) => (
                                <div
                                    key={row.id}
                                    className="flex items-center justify-between gap-3 rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-3.5 py-3 text-sm shadow-[0_0.0625rem_0.25rem_0_rgba(38,43,67,0.06)]"
                                >
                                    <div>
                                        <Badge variant="outline">{row.status_label}</Badge>{' '}
                                        <span className="font-medium">{row.date}</span>
                                        {row.notes && <span className="text-muted-foreground"> · {row.notes}</span>}
                                    </div>
                                    {can.manage && (
                                        <ConfirmDelete
                                            url={`/tasks/availability/${row.id}`}
                                            title="Remove this exception?"
                                            description="The planned hours for that day will apply again."
                                            trigger={
                                                <Button variant="outline" size="sm">
                                                    Remove
                                                </Button>
                                            }
                                        />
                                    )}
                                </div>
                            ))}
                        </div>
                    </DashboardPanel>
                ) : (
                    employee && (
                        <DashboardPanel title="This week’s exceptions" description="Leave and overrides applied on top of the weekly plan." icon={Plane} tone="fuchsia">
                            <PanelEmpty>No exceptions logged for this week.</PanelEmpty>
                        </DashboardPanel>
                    )
                )}
            </div>
        </TaskLayout>
    );
}
