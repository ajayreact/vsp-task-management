import { DataTableCard } from '@/components/admin/data-table-card';
import { KpiStatCard } from '@/components/admin/kpi-stat-card';
import { RowActions, type RowActionItem } from '@/components/admin/row-actions';
import { SearchInput } from '@/components/admin/search-input';
import InputError from '@/components/input-error';
import { Badge, type BadgeProps } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    CalendarOff,
    CalendarRange,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clapperboard,
    Copy,
    Download,
    Eye,
    FileSpreadsheet,
    FileText,
    Image,
    Layers,
    PartyPopper,
    Plus,
    Send,
    Share2,
    Sparkles,
    Video,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface AttachmentRow {
    uuid: string;
    name: string;
    mime: string;
    preview_url: string;
    download_url: string;
    can_preview: boolean;
}

interface StatusHistoryRow {
    from_status: string | null;
    from_status_label: string | null;
    to_status: string;
    to_status_label: string;
    note: string | null;
    created_by: string | null;
    created_at: string | null;
}

interface ContentItemRow {
    id: number;
    scheduled_date: string;
    scheduled_day: string;
    is_weekend: boolean;
    scheduled_time: string | null;
    post_number: number | null;
    content_type: string;
    content_type_label: string;
    topic: string;
    topic_label: string;
    platforms: { value: string; label: string }[];
    description: string | null;
    caption: string | null;
    hashtags: string | null;
    status: string;
    status_label: string;
    internal_notes: string | null;
    client_feedback: string | null;
    published_url: string | null;
    published_at: string | null;
    uploaded_by: string;
    updated_by: string | null;
    created_at: string | null;
    updated_at: string | null;
    attachments: AttachmentRow[];
    status_history: StatusHistoryRow[];
    can: { update: boolean; delete: boolean; share: boolean; send_for_review: boolean };
}

interface HolidayRow {
    id: number;
    name: string;
    date: string;
    day: string;
    country: string;
    country_label: string;
    flag: string;
    is_weekend: boolean;
    has_planned_post: boolean;
}

interface Period {
    start: string;
    end: string;
    label: string;
    month: string;
    previous_start: string;
    next_start: string;
    previous_month: string;
    next_month: string;
}

interface ImportPreview {
    client_id: number;
    client_name: string;
    summary: { total: number; valid: number; invalid: number; duplicates: number; importable: number };
    rows: Array<{
        excel_row: number;
        valid: boolean;
        errors: string[];
        is_duplicate: boolean;
        scheduled_date: string | null;
        post_number: number | null;
        topic_label: string | null;
        description: string | null;
    }>;
}

interface Props {
    items: ContentItemRow[];
    holidays: HolidayRow[];
    upcoming_holidays: HolidayRow[];
    clients: {
        id: number;
        name: string;
        monthly_post_target: number | null;
        holiday_india_enabled: boolean;
        holiday_usa_enabled: boolean;
    }[];
    selected_client: {
        id: number;
        name: string;
        monthly_post_target: number | null;
        holiday_india_enabled: boolean;
        holiday_usa_enabled: boolean;
    } | null;
    contentTypes: Option[];
    topics: Option[];
    platforms: Option[];
    platformDefaults: Record<string, string[]>;
    statuses: Option[];
    period: Period;
    kpis: {
        monthly_target: number;
        planned: number;
        remaining: number;
        ready: number;
        under_review: number;
        published: number;
        approved: number;
        changes_requested: number;
        not_ready: number;
    };
    filters: {
        client: number | null;
        search: string | null;
        content_type: string | null;
        topic: string | null;
        platform: string | null;
        status: string | null;
        holiday: string | null;
        sort: string | null;
        direction: string | null;
        view: string | null;
    };
    import_preview: ImportPreview | null;
    can: { manage: boolean; share: boolean };
}

type ContentFormValues = {
    tm_company_id: string;
    scheduled_date: string;
    scheduled_time: string;
    post_number: string;
    content_type: string;
    topic: string;
    platforms: string[];
    description: string;
    caption: string;
    hashtags: string;
    status: string;
    internal_notes: string;
    published_url: string;
    files: File[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Content Calendar', href: '/tasks/content-calendar' },
];

const truncate = (value: string | null, length = 80) => {
    if (!value) {
        return '—';
    }

    return value.length > length ? `${value.slice(0, length)}…` : value;
};

const formatShortDate = (value: string) => {
    const date = new Date(`${value}T00:00:00`);

    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

const statusVariant = (status: string): BadgeProps['variant'] => {
    switch (status) {
        case 'published':
        case 'approved':
        case 'ready':
        case 'scheduled':
            return 'success';
        case 'in_progress':
        case 'under_review':
            return 'warning';
        case 'changes_requested':
        case 'rejected':
            return 'danger';
        case 'draft':
        default:
            return 'neutral';
    }
};

const platformTone = (platform: string) => {
    switch (platform) {
        case 'instagram':
            return 'border-fuchsia-200 bg-gradient-to-r from-fuchsia-50 to-pink-50 text-fuchsia-700';
        case 'facebook':
            return 'border-blue-200 bg-blue-50 text-blue-700';
        case 'linkedin':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        case 'youtube':
            return 'border-red-200 bg-red-50 text-red-700';
        case 'whatsapp':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'x':
            return 'border-slate-300 bg-slate-100 text-slate-800';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
};

const contentTypeIcon = (type: string) => {
    switch (type) {
        case 'reel':
            return Clapperboard;
        case 'video':
            return Video;
        case 'carousel':
            return Layers;
        case 'article':
            return FileText;
        case 'poster':
            return Image;
        default:
            return Sparkles;
    }
};

export default function ContentCalendarIndex({
    items,
    holidays,
    upcoming_holidays,
    clients,
    selected_client,
    contentTypes,
    topics,
    platforms,
    platformDefaults,
    statuses,
    period,
    kpis,
    filters,
    import_preview,
    can,
}: Props) {
    const [editing, setEditing] = useState<ContentItemRow | null>(null);
    const [open, setOpen] = useState(false);
    const [viewItem, setViewItem] = useState<ContentItemRow | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [importOpen, setImportOpen] = useState(Boolean(import_preview));
    const [importFile, setImportFile] = useState<File | null>(null);
    const [platformsCustomized, setPlatformsCustomized] = useState(false);
    const { flash } = usePage<{ flash: { share_url?: string; success?: string; error?: string } }>().props;
    const viewMode = filters.view === 'calendar' ? 'calendar' : 'table';

    const form = useForm<ContentFormValues>({
        tm_company_id: filters.client ? String(filters.client) : '',
        scheduled_date: period.start,
        scheduled_time: '',
        post_number: '',
        content_type: 'poster',
        topic: 'other',
        platforms: platformDefaults.poster ?? ['facebook', 'instagram', 'linkedin'],
        description: '',
        caption: '',
        hashtags: '',
        status: 'draft',
        internal_notes: '',
        published_url: '',
        files: [],
    });

    useEffect(() => {
        if (flash.share_url) {
            void navigator.clipboard.writeText(flash.share_url);
        }
    }, [flash.share_url]);

    useEffect(() => {
        if (import_preview) {
            setImportOpen(true);
        }
    }, [import_preview]);

    const queryParams = (changes: Record<string, string | number | null> = {}) => ({
        client: filters.client,
        month: period.month,
        search: filters.search,
        content_type: filters.content_type,
        topic: filters.topic,
        platform: filters.platform,
        status: filters.status,
        holiday: filters.holiday,
        sort: filters.sort,
        direction: filters.direction,
        view: filters.view,
        ...changes,
    });

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/tasks/content-calendar', queryParams(changes), { preserveState: true, replace: true });
    };

    const start = (item: ContentItemRow | null, defaults?: Partial<ContentFormValues>) => {
        setEditing(item);
        form.clearErrors();
        setPlatformsCustomized(Boolean(item));
        form.setData(
            item
                ? {
                      tm_company_id: String(filters.client ?? ''),
                      scheduled_date: item.scheduled_date,
                      scheduled_time: item.scheduled_time ?? '',
                      post_number: item.post_number ? String(item.post_number) : '',
                      content_type: item.content_type,
                      topic: item.topic,
                      platforms: item.platforms.map((platform) => platform.value),
                      description: item.description ?? '',
                      caption: item.caption ?? '',
                      hashtags: item.hashtags ?? '',
                      status: item.status,
                      internal_notes: item.internal_notes ?? '',
                      published_url: item.published_url ?? '',
                      files: [],
                  }
                : {
                      tm_company_id: filters.client ? String(filters.client) : '',
                      scheduled_date: period.start,
                      scheduled_time: '',
                      post_number: '',
                      content_type: 'poster',
                      topic: 'other',
                      platforms: platformDefaults.poster ?? ['facebook', 'instagram', 'linkedin'],
                      description: '',
                      caption: '',
                      hashtags: '',
                      status: 'draft',
                      internal_notes: '',
                      published_url: '',
                      files: [],
                      ...defaults,
                  },
        );
        setOpen(true);
    };

    const applyContentTypeDefaults = (contentType: string) => {
        form.setData('content_type', contentType);
        if (!platformsCustomized) {
            form.setData('platforms', platformDefaults[contentType] ?? []);
        }
    };

    const togglePlatform = (value: string) => {
        setPlatformsCustomized(true);
        const current = form.data.platforms;
        form.setData(
            'platforms',
            current.includes(value) ? current.filter((platform) => platform !== value) : [...current, value],
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            post_number: form.data.post_number === '' ? null : Number(form.data.post_number),
        };

        const options = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            router.post(`/tasks/content-calendar/${editing.id}`, { ...payload, _method: 'put' }, options);
        } else {
            router.post('/tasks/content-calendar', payload, options);
        }
    };

    const rowActions = (item: ContentItemRow): RowActionItem[] => {
        const actions: RowActionItem[] = [{ key: 'view', label: 'View', onSelect: () => setViewItem(item) }];

        if (item.can.update) {
            actions.push({ key: 'edit', label: 'Edit', onSelect: () => start(item) });
        }

        if (item.attachments[0]) {
            actions.push({ key: 'download', label: 'Download', href: item.attachments[0].download_url });
        }

        if (item.can.send_for_review) {
            actions.push({
                key: 'review',
                label: 'Send for client review',
                onSelect: () => router.post(`/tasks/content-calendar/${item.id}/send-for-review`, {}, { preserveScroll: true }),
            });
        }

        if (item.can.share) {
            actions.push({
                key: 'share',
                label: 'Share link',
                onSelect: () => router.post(`/tasks/content-calendar/${item.id}/share-link`, {}, { preserveScroll: true }),
            });
        }

        if (item.can.delete) {
            actions.push({
                key: 'delete',
                label: 'Delete',
                destructive: true,
                confirm: {
                    url: `/tasks/content-calendar/${item.id}`,
                    title: 'Remove this scheduled content?',
                    description: 'The content item and its attachments will be deleted.',
                },
            });
        }

        return actions;
    };

    const calendarDays = useMemo(() => {
        const startDate = new Date(`${period.start}T00:00:00`);
        const endDate = new Date(`${period.end}T00:00:00`);
        const days: Array<{
            date: string;
            day: string;
            weekday: number;
            isWeekend: boolean;
            items: ContentItemRow[];
            holidays: HolidayRow[];
            visible: boolean;
        }> = [];

        for (let cursor = new Date(startDate); cursor <= endDate; cursor.setDate(cursor.getDate() + 1)) {
            const iso = cursor.toISOString().slice(0, 10);
            const weekday = cursor.getDay();
            const isWeekend = weekday === 0 || weekday === 6;
            const dayItems = items.filter((item) => item.scheduled_date === iso);
            const dayHolidays = holidays.filter((holiday) => holiday.date === iso);
            const visible = !isWeekend || dayItems.length > 0 || dayHolidays.length > 0;

            days.push({
                date: iso,
                day: cursor.toLocaleDateString(undefined, { weekday: 'short' }),
                weekday,
                isWeekend,
                items: dayItems,
                holidays: dayHolidays,
                visible,
            });
        }

        return days;
    }, [period.start, period.end, items, holidays]);

    const holidayRowsForTable = holidays.filter((holiday) => !holiday.has_planned_post);

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Content Calendar" />

            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="relative overflow-hidden rounded-[1.25rem] border border-indigo-500/20 bg-gradient-to-br from-violet-600 via-indigo-600 to-fuchsia-600 px-6 py-8 text-white shadow-lg">
                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="min-w-0 space-y-3">
                            <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium tracking-wide uppercase">
                                <CalendarDays className="size-3.5" />
                                Social planning
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Content Calendar</h1>
                                <p className="max-w-2xl text-sm leading-relaxed text-indigo-100">
                                    Plan, produce, review, and publish monthly social content client by client.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {can.manage && (
                                <>
                                    <Button onClick={() => start(null)} className="border-0 bg-white text-indigo-700 shadow-sm hover:bg-white/90">
                                        <Plus /> Add content
                                    </Button>
                                    <Button
                                        variant="outline"
                                        className="border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                                        onClick={() => setImportOpen(true)}
                                    >
                                        <FileSpreadsheet /> Import Excel
                                    </Button>
                                    <Button variant="outline" className="border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white" asChild>
                                        <a href="/tasks/content-calendar/import/template">
                                            <Download /> Template
                                        </a>
                                    </Button>
                                </>
                            )}
                            {can.share && filters.client && (
                                <Button
                                    variant="outline"
                                    className="border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                                    onClick={() =>
                                        router.post(
                                            '/tasks/content-calendar/share-schedule',
                                            { client: filters.client, month: period.month },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <Share2 /> Share month
                                </Button>
                            )}
                        </div>
                    </div>
                </section>

                {filters.client && (
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-7">
                        <KpiStatCard tone="indigo" label="Monthly target" value={kpis.monthly_target} icon={CalendarRange} />
                        <KpiStatCard tone="sky" label="Planned" value={kpis.planned} icon={Layers} />
                        <KpiStatCard tone="emerald" label="Post ready" value={kpis.ready} icon={CheckCircle2} />
                        <KpiStatCard tone="amber" label="Client review" value={kpis.under_review} icon={Send} />
                        <KpiStatCard tone="emerald" label="Approved" value={kpis.approved} icon={CheckCircle2} />
                        <KpiStatCard tone="emerald" label="Published" value={kpis.published} icon={CheckCircle2} />
                        <KpiStatCard tone="amber" label="Remaining" value={kpis.remaining} icon={Sparkles} />
                    </div>
                )}

                {upcoming_holidays.length > 0 && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <PartyPopper className="size-4 text-amber-600" /> Upcoming holidays
                            </CardTitle>
                            <CardDescription>Holidays do not auto-create posts. Create one when ready.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap gap-3">
                            {upcoming_holidays.map((holiday) => (
                                <div key={holiday.id} className="flex min-w-[220px] flex-1 items-center justify-between gap-3 rounded-xl border bg-amber-50/60 px-3 py-2">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {holiday.flag} {holiday.name}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {formatShortDate(holiday.date)}
                                            {!holiday.has_planned_post ? ' · No post planned' : ' · Post planned'}
                                        </p>
                                    </div>
                                    {can.manage && !holiday.has_planned_post && filters.client && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                router.post(
                                                    '/tasks/content-calendar/holiday-post',
                                                    { tm_company_id: filters.client, holiday_id: holiday.id },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Create
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <section className="vsp-card mb-0 space-y-4 bg-white p-4 md:p-5">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                            <div className="flex items-center gap-2 text-sm font-medium text-indigo-700">
                                <span className="flex size-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                    <Building2 className="size-4" />
                                </span>
                                Client
                            </div>
                            <Select
                                value={filters.client ? String(filters.client) : 'none'}
                                onValueChange={(value) => apply({ client: value === 'none' ? null : Number(value) })}
                            >
                                <SelectTrigger className="w-full sm:w-64">
                                    <SelectValue placeholder="Select client" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.map((client) => (
                                        <SelectItem key={client.id} value={String(client.id)}>
                                            {client.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50/80 via-white to-fuchsia-50/80 px-3 py-2">
                            <Button variant="outline" size="sm" className="bg-white/80" onClick={() => apply({ month: period.previous_month })}>
                                <ChevronLeft className="size-4" /> Previous
                            </Button>
                            <div className="min-w-0 px-2 text-center">
                                <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">Month</p>
                                <p className="truncate text-sm font-semibold text-indigo-950">
                                    {selected_client ? `${selected_client.name} · ` : ''}
                                    {period.label}
                                </p>
                            </div>
                            <Button variant="outline" size="sm" className="bg-white/80" onClick={() => apply({ month: period.next_month })}>
                                Next <ChevronRight className="size-4" />
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => apply({ month: null })}>
                                Today
                            </Button>
                            <div className="ml-2 flex gap-1">
                                <Button size="sm" variant={viewMode === 'table' ? 'default' : 'outline'} onClick={() => apply({ view: 'table' })}>
                                    Table
                                </Button>
                                <Button size="sm" variant={viewMode === 'calendar' ? 'default' : 'outline'} onClick={() => apply({ view: 'calendar' })}>
                                    Calendar
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>

                <DataTableCard
                    title={selected_client?.name ?? 'Client content'}
                    description={`Scheduled content for ${period.label}. Weekends stay hidden unless they have a holiday or manual post.`}
                    toolbar={
                        <div className="flex min-w-0 w-full flex-col gap-3 lg:flex-row lg:flex-wrap">
                            <SearchInput
                                value={filters.search ?? ''}
                                onChange={(search) => apply({ search: search || null })}
                                placeholder="Search description, caption..."
                                className="w-full lg:max-w-xs"
                            />
                            <Select value={filters.topic ?? 'all'} onValueChange={(value) => apply({ topic: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-full lg:w-40">
                                    <SelectValue placeholder="Topic" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All topics</SelectItem>
                                    {topics.map((topic) => (
                                        <SelectItem key={topic.value} value={topic.value}>
                                            {topic.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.content_type ?? 'all'} onValueChange={(value) => apply({ content_type: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-full lg:w-40">
                                    <SelectValue placeholder="Format" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All formats</SelectItem>
                                    {contentTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.platform ?? 'all'} onValueChange={(value) => apply({ platform: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-full lg:w-40">
                                    <SelectValue placeholder="Platform" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All platforms</SelectItem>
                                    {platforms.map((platform) => (
                                        <SelectItem key={platform.value} value={platform.value}>
                                            {platform.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.status ?? 'all'} onValueChange={(value) => apply({ status: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-full lg:w-44">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    {statuses.map((status) => (
                                        <SelectItem key={status.value} value={status.value}>
                                            {status.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.holiday ?? 'all'} onValueChange={(value) => apply({ holiday: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-full lg:w-40">
                                    <SelectValue placeholder="Holiday" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All rows</SelectItem>
                                    <SelectItem value="only">Holiday posts</SelectItem>
                                    <SelectItem value="exclude">Exclude holidays</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    }
                >
                    {viewMode === 'calendar' ? (
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {calendarDays
                                .filter((day) => day.visible)
                                .map((day) => (
                                    <div key={day.date} className={cn('rounded-xl border p-3', day.isWeekend ? 'border-amber-200 bg-amber-50/50' : 'bg-white')}>
                                        <div className="mb-2 flex items-center justify-between">
                                            <div>
                                                <p className="font-semibold">{formatShortDate(day.date)}</p>
                                                <p className="text-muted-foreground text-xs">{day.day}</p>
                                            </div>
                                            {can.manage && (
                                                <Button size="sm" variant="ghost" onClick={() => start(null, { scheduled_date: day.date })}>
                                                    <Plus className="size-4" />
                                                </Button>
                                            )}
                                        </div>
                                        {day.holidays.map((holiday) => (
                                            <div key={holiday.id} className="mb-2 rounded-lg border border-amber-200 bg-amber-100/70 px-2 py-1.5 text-xs">
                                                {holiday.flag} {holiday.name}
                                            </div>
                                        ))}
                                        {day.items.map((item) => (
                                            <button
                                                key={item.id}
                                                type="button"
                                                className="mb-2 block w-full rounded-lg border px-2 py-1.5 text-left text-xs hover:bg-indigo-50"
                                                onClick={() => setViewItem(item)}
                                            >
                                                <div className="font-medium">{item.topic_label}</div>
                                                <div className="text-muted-foreground">{item.platforms.map((platform) => platform.label).join(', ')}</div>
                                                <Badge variant={statusVariant(item.status)} className="mt-1">
                                                    {item.status_label}
                                                </Badge>
                                            </button>
                                        ))}
                                    </div>
                                ))}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table className="min-w-max">
                                <TableHeader>
                                    <TableRow className="bg-muted/30 hover:bg-muted/30">
                                        <TableHead>Post #</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Day</TableHead>
                                        <TableHead>Topic</TableHead>
                                        <TableHead>Format</TableHead>
                                        <TableHead>Platforms</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Creative</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-16 text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.length === 0 && holidayRowsForTable.length === 0 && (
                                        <TableRow className="hover:bg-transparent">
                                            <TableCell colSpan={10} className="py-16">
                                                <div className="mx-auto flex max-w-md flex-col items-center gap-4 text-center">
                                                    <span className="flex size-16 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                                        <CalendarOff className="size-8" strokeWidth={1.5} />
                                                    </span>
                                                    <div className="space-y-2">
                                                        <p className="text-foreground text-base font-semibold">No content scheduled this month</p>
                                                        <p className="text-muted-foreground text-sm">
                                                            Import an Excel plan or add content manually. Weekday placeholders are not auto-created.
                                                        </p>
                                                    </div>
                                                    {can.manage && filters.client && (
                                                        <Button onClick={() => start(null)}>
                                                            <Plus /> Add first content
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}

                                    {holidayRowsForTable.map((holiday) => (
                                        <TableRow key={`holiday-${holiday.id}`} className="bg-amber-50/70 hover:bg-amber-50">
                                            <TableCell>—</TableCell>
                                            <TableCell className="whitespace-nowrap font-semibold tabular-nums">{formatShortDate(holiday.date)}</TableCell>
                                            <TableCell>{holiday.day}</TableCell>
                                            <TableCell>
                                                <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-900">
                                                    <PartyPopper className="size-3.5" /> Holiday / Festival
                                                </span>
                                            </TableCell>
                                            <TableCell>—</TableCell>
                                            <TableCell>{holiday.flag}</TableCell>
                                            <TableCell>
                                                {holiday.flag} {holiday.name}
                                                {holiday.is_weekend ? ' (weekend)' : ''}
                                            </TableCell>
                                            <TableCell>—</TableCell>
                                            <TableCell>
                                                <Badge variant="neutral">Holiday</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {can.manage && filters.client && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            start(null, {
                                                                scheduled_date: holiday.date,
                                                                topic: 'festival_holiday',
                                                                description: holiday.name,
                                                                content_type: 'poster',
                                                                platforms: platformDefaults.poster ?? ['facebook', 'instagram', 'linkedin'],
                                                            })
                                                        }
                                                    >
                                                        Create post
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}

                                    {items.map((item) => {
                                        const TypeIcon = contentTypeIcon(item.content_type);

                                        return (
                                            <TableRow key={item.id} className={cn('hover:bg-indigo-50/40', item.is_weekend && 'bg-amber-50/30')}>
                                                <TableCell>{item.post_number ? String(item.post_number).padStart(2, '0') : '—'}</TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    <p className="font-semibold tabular-nums">{formatShortDate(item.scheduled_date)}</p>
                                                </TableCell>
                                                <TableCell>{item.scheduled_day}</TableCell>
                                                <TableCell>{item.topic_label}</TableCell>
                                                <TableCell>
                                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                        <TypeIcon className="size-3.5" />
                                                        {item.content_type_label}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap gap-1">
                                                        {item.platforms.map((platform) => (
                                                            <span
                                                                key={platform.value}
                                                                className={cn('inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium', platformTone(platform.value))}
                                                            >
                                                                {platform.label}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="max-w-xs">
                                                    <button type="button" className="text-left hover:text-indigo-700 hover:underline" onClick={() => setViewItem(item)}>
                                                        {truncate(item.description)}
                                                    </button>
                                                </TableCell>
                                                <TableCell>
                                                    {item.attachments[0] ? (
                                                        <Button
                                                            variant="link"
                                                            className="h-auto p-0 text-indigo-700"
                                                            onClick={() =>
                                                                item.attachments[0].can_preview
                                                                    ? setPreviewUrl(item.attachments[0].preview_url)
                                                                    : window.location.assign(item.attachments[0].download_url)
                                                            }
                                                        >
                                                            <Eye className="mr-1 size-4" /> View
                                                        </Button>
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={statusVariant(item.status)}>{item.status_label}</Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <RowActions label={`Actions for ${item.scheduled_date}`} items={rowActions(item)} />
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </DataTableCard>

                {flash.share_url && (
                    <div className="flex flex-col gap-2 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span className="break-all text-emerald-900">Share link copied: {flash.share_url}</span>
                        <Button variant="outline" size="sm" className="border-emerald-300 bg-white" onClick={() => void navigator.clipboard.writeText(flash.share_url!)}>
                            <Copy className="size-4" /> Copy again
                        </Button>
                    </div>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit content' : 'Add content'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label>Client</Label>
                            <Select value={form.data.tm_company_id} onValueChange={(value) => form.setData('tm_company_id', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select client" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.map((client) => (
                                        <SelectItem key={client.id} value={String(client.id)}>
                                            {client.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.tm_company_id} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_date">Date</Label>
                                <Input id="scheduled_date" type="date" value={form.data.scheduled_date} onChange={(e) => form.setData('scheduled_date', e.target.value)} required />
                                <InputError message={form.errors.scheduled_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_time">Time</Label>
                                <Input id="scheduled_time" type="time" value={form.data.scheduled_time} onChange={(e) => form.setData('scheduled_time', e.target.value)} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="post_number">Post #</Label>
                                <Input id="post_number" type="number" min={1} max={999} value={form.data.post_number} onChange={(e) => form.setData('post_number', e.target.value)} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Topic</Label>
                                <Select value={form.data.topic} onValueChange={(value) => form.setData('topic', value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {topics.map((topic) => (
                                            <SelectItem key={topic.value} value={topic.value}>
                                                {topic.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Format</Label>
                                <Select value={form.data.content_type} onValueChange={applyContentTypeDefaults}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {contentTypes.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label>Platforms</Label>
                            <div className="grid grid-cols-2 gap-2 rounded-lg border p-3 sm:grid-cols-3">
                                {platforms.map((platform) => (
                                    <label key={platform.value} className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.data.platforms.includes(platform.value)}
                                            onChange={() => togglePlatform(platform.value)}
                                        />
                                        {platform.label}
                                    </label>
                                ))}
                            </div>
                            <InputError message={form.errors.platforms as string | undefined} />
                            <p className="text-muted-foreground text-xs">Changing Format applies defaults unless you have customized platforms for this item.</p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                                    <SelectTrigger>
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
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description / topic</Label>
                            <Textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} rows={3} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="caption">Caption</Label>
                            <Textarea id="caption" value={form.data.caption} onChange={(e) => form.setData('caption', e.target.value)} rows={3} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="hashtags">Hashtags</Label>
                            <Input id="hashtags" value={form.data.hashtags} onChange={(e) => form.setData('hashtags', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="published_url">Published URL</Label>
                            <Input id="published_url" value={form.data.published_url} onChange={(e) => form.setData('published_url', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="internal_notes">Internal notes</Label>
                            <Textarea id="internal_notes" value={form.data.internal_notes} onChange={(e) => form.setData('internal_notes', e.target.value)} rows={2} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="files">{editing ? 'Upload / add creative' : 'Upload creative'}</Label>
                            <Input id="files" type="file" multiple accept="image/*,video/*,.pdf,.doc,.docx,.zip" onChange={(e) => form.setData('files', Array.from(e.target.files ?? []))} />
                            <p className="text-muted-foreground text-xs">Uploading while Post Not Ready / In Progress moves the item to Post Ready.</p>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {editing ? 'Save changes' : 'Add content'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={viewItem !== null} onOpenChange={() => setViewItem(null)}>
                <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Content details</DialogTitle>
                    </DialogHeader>
                    {viewItem && (
                        <div className="space-y-4 text-sm">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <Detail label="Date" value={viewItem.scheduled_date} />
                                <Detail label="Post #" value={viewItem.post_number ? String(viewItem.post_number).padStart(2, '0') : '—'} />
                                <Detail label="Topic" value={viewItem.topic_label} />
                                <Detail label="Format" value={viewItem.content_type_label} />
                                <Detail label="Platforms" value={viewItem.platforms.map((platform) => platform.label).join(', ') || '—'} />
                                <Detail label="Status" value={viewItem.status_label} />
                                <Detail label="Created by" value={viewItem.uploaded_by} />
                                <Detail label="Updated by" value={viewItem.updated_by ?? '—'} />
                            </div>
                            <div>
                                <p className="text-muted-foreground mb-1 text-xs font-medium uppercase">Description</p>
                                <p className="whitespace-pre-wrap">{viewItem.description || '—'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground mb-1 text-xs font-medium uppercase">Caption</p>
                                <p className="whitespace-pre-wrap">{viewItem.caption || '—'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground mb-1 text-xs font-medium uppercase">Hashtags</p>
                                <p>{viewItem.hashtags || '—'}</p>
                            </div>
                            {viewItem.client_feedback && (
                                <div>
                                    <p className="text-muted-foreground mb-1 text-xs font-medium uppercase">Client feedback</p>
                                    <p className="whitespace-pre-wrap">{viewItem.client_feedback}</p>
                                </div>
                            )}
                            {viewItem.attachments.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Creative</p>
                                    {viewItem.attachments.map((attachment) => (
                                        <div key={attachment.uuid} className="flex flex-wrap items-center gap-2">
                                            <span>{attachment.name}</span>
                                            {attachment.can_preview && (
                                                <Button variant="outline" size="sm" onClick={() => setPreviewUrl(attachment.preview_url)}>
                                                    Preview
                                                </Button>
                                            )}
                                            <Button variant="outline" size="sm" asChild>
                                                <a href={attachment.download_url}>Download</a>
                                            </Button>
                                            {viewItem.can.update && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.delete(`/tasks/content-calendar/${viewItem.id}/attachments/${attachment.uuid}`, {
                                                            preserveScroll: true,
                                                        })
                                                    }
                                                >
                                                    Delete file
                                                </Button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                            <div>
                                <p className="text-muted-foreground mb-2 text-xs font-medium uppercase">Status history</p>
                                <div className="space-y-2">
                                    {viewItem.status_history.length === 0 && <p className="text-muted-foreground">No history yet.</p>}
                                    {viewItem.status_history.map((entry, index) => (
                                        <div key={`${entry.to_status}-${index}`} className="rounded-lg border px-3 py-2">
                                            <p className="font-medium">{entry.to_status_label}</p>
                                            <p className="text-muted-foreground text-xs">
                                                {entry.created_at ? new Date(entry.created_at).toLocaleString() : ''}
                                                {entry.created_by ? ` · ${entry.created_by}` : ''}
                                            </p>
                                            {entry.note && <p className="mt-1 text-xs">{entry.note}</p>}
                                        </div>
                                    ))}
                                </div>
                            </div>
                            {viewItem.can.send_for_review && (
                                <Button
                                    onClick={() =>
                                        router.post(`/tasks/content-calendar/${viewItem.id}/send-for-review`, {}, { preserveScroll: true, onSuccess: () => setViewItem(null) })
                                    }
                                >
                                    <Send className="mr-2 size-4" /> Send for client review
                                </Button>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog open={importOpen} onOpenChange={setImportOpen}>
                <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Import Excel</DialogTitle>
                    </DialogHeader>
                    {!import_preview ? (
                        <div className="space-y-4">
                            <p className="text-muted-foreground text-sm">Upload a plan for the selected client. Preview validates rows before import. Duplicates and creatives are never overwritten.</p>
                            <div className="grid gap-2">
                                <Label>Excel file</Label>
                                <Input type="file" accept=".xlsx,.xls,.csv" onChange={(e) => setImportFile(e.target.files?.[0] ?? null)} />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setImportOpen(false)}>
                                    Cancel
                                </Button>
                                <Button
                                    disabled={!importFile || !filters.client}
                                    onClick={() => {
                                        if (!importFile || !filters.client) {
                                            return;
                                        }
                                        const data = new FormData();
                                        data.append('client', String(filters.client));
                                        data.append('file', importFile);
                                        router.post('/tasks/content-calendar/import/preview', data, { forceFormData: true, preserveScroll: true });
                                    }}
                                >
                                    Preview & validate
                                </Button>
                            </DialogFooter>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                                <Detail label="Total" value={String(import_preview.summary.total)} />
                                <Detail label="Valid" value={String(import_preview.summary.valid)} />
                                <Detail label="Invalid" value={String(import_preview.summary.invalid)} />
                                <Detail label="Duplicates" value={String(import_preview.summary.duplicates)} />
                                <Detail label="Importable" value={String(import_preview.summary.importable)} />
                            </div>
                            <div className="max-h-72 overflow-auto rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Row</TableHead>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Post #</TableHead>
                                            <TableHead>Topic</TableHead>
                                            <TableHead>Result</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {import_preview.rows.map((row) => (
                                            <TableRow key={row.excel_row}>
                                                <TableCell>{row.excel_row}</TableCell>
                                                <TableCell>{row.scheduled_date ?? '—'}</TableCell>
                                                <TableCell>{row.post_number ?? '—'}</TableCell>
                                                <TableCell>{row.topic_label ?? '—'}</TableCell>
                                                <TableCell>
                                                    {!row.valid ? row.errors.join(', ') : row.is_duplicate ? 'Duplicate (skip)' : 'OK'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setImportOpen(false)}>
                                    Close
                                </Button>
                                <Button
                                    disabled={import_preview.summary.importable < 1}
                                    onClick={() =>
                                        router.post('/tasks/content-calendar/import/confirm', {}, { preserveScroll: true, onSuccess: () => setImportOpen(false) })
                                    }
                                >
                                    Confirm import ({import_preview.summary.importable})
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog open={previewUrl !== null} onOpenChange={() => setPreviewUrl(null)}>
                <DialogContent className="max-h-[90vh] max-w-4xl overflow-hidden">
                    <DialogHeader>
                        <DialogTitle>Preview</DialogTitle>
                    </DialogHeader>
                    {previewUrl && (
                        <div className="max-h-[70vh] overflow-auto">
                            {previewUrl.includes('.pdf') || previewUrl.includes('/preview') ? (
                                <iframe src={previewUrl} title="Preview" className="h-[70vh] w-full rounded-lg border" />
                            ) : previewUrl.match(/video|mp4|webm|mov/i) ? (
                                <video src={previewUrl} controls className="mx-auto max-h-[70vh] max-w-full rounded-lg" />
                            ) : (
                                <img src={previewUrl} alt="Preview" className="mx-auto max-h-[70vh] max-w-full object-contain" />
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-muted-foreground text-xs font-medium uppercase">{label}</p>
            <p className="font-medium">{value}</p>
        </div>
    );
}
