import { DataTableCard } from '@/components/admin/data-table-card';
import { KpiStatCard } from '@/components/admin/kpi-stat-card';
import { RowActions, type RowActionItem } from '@/components/admin/row-actions';
import { SearchInput } from '@/components/admin/search-input';
import InputError from '@/components/input-error';
import { Badge, type BadgeProps } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
    Eye,
    FileText,
    Image,
    Layers,
    Plus,
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

interface ContentItemRow {
    id: number;
    scheduled_date: string;
    scheduled_day: string;
    scheduled_time: string | null;
    content_type: string;
    content_type_label: string;
    platform: string;
    platform_label: string;
    description: string | null;
    status: string;
    status_label: string;
    internal_notes: string | null;
    uploaded_by: string;
    attachments: AttachmentRow[];
    can: { update: boolean; delete: boolean; share: boolean };
}

interface Period {
    start: string;
    end: string;
    label: string;
    previous_start: string;
    next_start: string;
}

interface Props {
    items: ContentItemRow[];
    clients: { id: number; name: string }[];
    contentTypes: Option[];
    platforms: Option[];
    statuses: Option[];
    period: Period;
    filters: {
        client: number | null;
        search: string | null;
        content_type: string | null;
        platform: string | null;
        status: string | null;
    };
    can: { manage: boolean; share: boolean };
}

type ContentFormValues = {
    tm_company_id: string;
    scheduled_date: string;
    scheduled_time: string;
    content_type: string;
    platform: string;
    description: string;
    status: string;
    internal_notes: string;
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
            return 'success';
        case 'in_progress':
        case 'under_review':
            return 'warning';
        case 'changes_requested':
            return 'danger';
        case 'draft':
        default:
            return 'neutral';
    }
};

const platformTone = (platform: string) => {
    switch (platform) {
        case 'instagram':
            return 'border-fuchsia-200 bg-gradient-to-r from-fuchsia-50 to-pink-50 text-fuchsia-700 dark:border-fuchsia-900/40 dark:from-fuchsia-950/40 dark:to-pink-950/40 dark:text-fuchsia-300';
        case 'facebook':
            return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-300';
        case 'linkedin':
            return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-300';
        case 'youtube':
            return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300';
        case 'whatsapp':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-300';
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

export default function ContentCalendarIndex({ items, clients, contentTypes, platforms, statuses, period, filters, can }: Props) {
    const [editing, setEditing] = useState<ContentItemRow | null>(null);
    const [open, setOpen] = useState(false);
    const [viewItem, setViewItem] = useState<ContentItemRow | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const { flash } = usePage<{ flash: { share_url?: string } }>().props;

    const form = useForm<ContentFormValues>({
        tm_company_id: filters.client ? String(filters.client) : '',
        scheduled_date: period.start,
        scheduled_time: '',
        content_type: 'poster',
        platform: 'instagram',
        description: '',
        status: 'draft',
        internal_notes: '',
        files: [],
    });

    useEffect(() => {
        if (flash.share_url) {
            void navigator.clipboard.writeText(flash.share_url);
        }
    }, [flash.share_url]);

    const queryParams = (changes: Record<string, string | number | null> = {}) => ({
        client: filters.client,
        period_start: period.start,
        search: filters.search,
        content_type: filters.content_type,
        platform: filters.platform,
        status: filters.status,
        ...changes,
    });

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/tasks/content-calendar', queryParams(changes), { preserveState: true, replace: true });
    };

    const start = (item: ContentItemRow | null) => {
        setEditing(item);
        form.clearErrors();
        form.setData(
            item
                ? {
                      tm_company_id: String(filters.client ?? ''),
                      scheduled_date: item.scheduled_date,
                      scheduled_time: item.scheduled_time ?? '',
                      content_type: item.content_type,
                      platform: item.platform,
                      description: item.description ?? '',
                      status: item.status,
                      internal_notes: item.internal_notes ?? '',
                      files: [],
                  }
                : {
                      tm_company_id: filters.client ? String(filters.client) : '',
                      scheduled_date: period.start,
                      scheduled_time: '',
                      content_type: 'poster',
                      platform: 'instagram',
                      description: '',
                      status: 'draft',
                      internal_notes: '',
                      files: [],
                  },
        );
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            forceFormData: form.data.files.length > 0,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.post(`/tasks/content-calendar/${editing.id}`, { ...options, method: 'put' });
        } else {
            form.post('/tasks/content-calendar', options);
        }
    };

    const rowActions = (item: ContentItemRow): RowActionItem[] => {
        const actions: RowActionItem[] = [
            { key: 'view', label: 'View', onSelect: () => setViewItem(item) },
        ];

        if (item.can.update) {
            actions.push({ key: 'edit', label: 'Edit', onSelect: () => start(item) });
        }

        if (item.attachments[0]) {
            actions.push({
                key: 'download',
                label: 'Download',
                href: item.attachments[0].download_url,
            });
        }

        if (item.can.share) {
            actions.push({
                key: 'share',
                label: 'Share',
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

    const selectedClient = clients.find((client) => client.id === filters.client);

    const stats = useMemo(
        () => ({
            total: items.length,
            ready: items.filter((item) => ['ready', 'approved'].includes(item.status)).length,
            published: items.filter((item) => item.status === 'published').length,
            inProgress: items.filter((item) => ['in_progress', 'under_review'].includes(item.status)).length,
        }),
        [items],
    );

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Content Calendar" />

            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="relative overflow-hidden rounded-[1.25rem] border border-indigo-500/20 bg-gradient-to-br from-violet-600 via-indigo-600 to-fuchsia-600 px-6 py-8 text-white shadow-lg">
                    <div className="pointer-events-none absolute -right-8 -top-10 size-40 rounded-full bg-white/10 blur-2xl" />
                    <div className="pointer-events-none absolute -bottom-12 left-1/3 size-52 rounded-full bg-fuchsia-400/20 blur-3xl" />

                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="min-w-0 space-y-3">
                            <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium tracking-wide uppercase">
                                <CalendarDays className="size-3.5" />
                                Social planning
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Content Calendar</h1>
                                <p className="max-w-2xl text-sm leading-relaxed text-indigo-100">
                                    Plan, review, and share upcoming social content client by client across a rolling 15-day schedule.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {can.manage && (
                                <Button
                                    onClick={() => start(null)}
                                    className="border-0 bg-white text-indigo-700 shadow-sm hover:bg-white/90"
                                >
                                    <Plus /> Add content
                                </Button>
                            )}
                            {can.share && filters.client && (
                                <Button
                                    variant="outline"
                                    className="border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                                    onClick={() =>
                                        router.post(
                                            '/tasks/content-calendar/share-schedule',
                                            { client: filters.client, period_start: period.start },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <Share2 /> Share 15-day schedule
                                </Button>
                            )}
                        </div>
                    </div>
                </section>

                {filters.client && (
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <KpiStatCard tone="indigo" label="Scheduled in period" value={stats.total} icon={CalendarRange} />
                        <KpiStatCard tone="emerald" label="Ready / approved" value={stats.ready} icon={CheckCircle2} />
                        <KpiStatCard tone="sky" label="Published" value={stats.published} icon={Sparkles} />
                        <KpiStatCard tone="amber" label="In progress" value={stats.inProgress} icon={Clapperboard} />
                    </div>
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
                            <Button variant="outline" size="sm" className="bg-white/80" onClick={() => apply({ period_start: period.previous_start })}>
                                <ChevronLeft className="size-4" /> Previous
                            </Button>
                            <div className="min-w-0 px-2 text-center">
                                <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">Current period</p>
                                <p className="truncate text-sm font-semibold text-indigo-950">
                                    {selectedClient ? `${selectedClient.name} · ` : ''}
                                    {period.label}
                                </p>
                            </div>
                            <Button variant="outline" size="sm" className="bg-white/80" onClick={() => apply({ period_start: period.next_start })}>
                                Next <ChevronRight className="size-4" />
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => apply({ period_start: null })}>
                                Today
                            </Button>
                        </div>
                    </div>
                </section>

                <DataTableCard
                    title={selectedClient?.name ?? 'Client content'}
                    description={`Scheduled content for ${period.label}.`}
                    toolbar={
                        <div className="flex min-w-0 w-full flex-col gap-3 lg:flex-row lg:flex-wrap">
                            <SearchInput
                                value={filters.search ?? ''}
                                onChange={(search) => apply({ search: search || null })}
                                placeholder="Search description..."
                                className="w-full lg:max-w-xs"
                            />
                            <Select value={filters.content_type ?? 'all'} onValueChange={(value) => apply({ content_type: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-full lg:w-40">
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
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
                                <SelectTrigger className="w-full lg:w-40">
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
                        </div>
                    }
                >
                    <div className="overflow-x-auto">
                        <Table className="min-w-max">
                            <TableHeader>
                                <TableRow className="bg-muted/30 hover:bg-muted/30">
                                    <TableHead>Date</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Platform</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Attachment</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Uploaded By</TableHead>
                                    <TableHead className="w-16 text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 && (
                                    <TableRow className="hover:bg-transparent">
                                        <TableCell colSpan={8} className="py-16">
                                            <div className="mx-auto flex max-w-md flex-col items-center gap-4 text-center">
                                                <span className="flex size-16 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                                    <CalendarOff className="size-8" strokeWidth={1.5} />
                                                </span>
                                                <div className="space-y-2">
                                                    <p className="text-foreground text-base font-semibold">No content scheduled in this period</p>
                                                    <p className="text-muted-foreground text-sm">
                                                        {filters.client
                                                            ? 'Start planning by adding the first post for this client and date range.'
                                                            : 'Select a client above to view and manage their content calendar.'}
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

                                {items.map((item) => {
                                    const TypeIcon = contentTypeIcon(item.content_type);

                                    return (
                                        <TableRow key={item.id} className="hover:bg-indigo-50/40">
                                            <TableCell className="whitespace-nowrap">
                                                <div>
                                                    <p className="font-semibold tabular-nums">{formatShortDate(item.scheduled_date)}</p>
                                                    <p className="text-muted-foreground text-xs">{item.scheduled_day}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <span className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                    <TypeIcon className="size-3.5" />
                                                    {item.content_type_label}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <span className={cn('inline-flex rounded-full border px-2.5 py-1 text-xs font-medium', platformTone(item.platform))}>
                                                    {item.platform_label}
                                                </span>
                                            </TableCell>
                                            <TableCell className="max-w-xs">
                                                <button
                                                    type="button"
                                                    className="text-left leading-snug transition-colors hover:text-indigo-700 hover:underline"
                                                    onClick={() => setViewItem(item)}
                                                >
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
                                            <TableCell className="text-sm">{item.uploaded_by}</TableCell>
                                            <TableCell className="text-right">
                                                <RowActions label={`Actions for ${item.scheduled_date}`} items={rowActions(item)} />
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
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

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_date">Date</Label>
                                <Input
                                    id="scheduled_date"
                                    type="date"
                                    value={form.data.scheduled_date}
                                    onChange={(e) => form.setData('scheduled_date', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.scheduled_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_time">Time</Label>
                                <Input
                                    id="scheduled_time"
                                    type="time"
                                    value={form.data.scheduled_time}
                                    onChange={(e) => form.setData('scheduled_time', e.target.value)}
                                />
                                <InputError message={form.errors.scheduled_time} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Content type</Label>
                                <Select value={form.data.content_type} onValueChange={(value) => form.setData('content_type', value)}>
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
                            <div className="grid gap-2">
                                <Label>Platform</Label>
                                <Select value={form.data.platform} onValueChange={(value) => form.setData('platform', value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {platforms.map((platform) => (
                                            <SelectItem key={platform.value} value={platform.value}>
                                                {platform.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Post description</Label>
                            <Textarea
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                rows={5}
                                className="min-h-[8rem] resize-y break-words"
                            />
                            <InputError message={form.errors.description} />
                        </div>

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

                        <div className="grid gap-2">
                            <Label htmlFor="internal_notes">Internal notes</Label>
                            <Textarea
                                id="internal_notes"
                                value={form.data.internal_notes}
                                onChange={(e) => form.setData('internal_notes', e.target.value)}
                                rows={3}
                            />
                            <InputError message={form.errors.internal_notes} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="files">{editing ? 'Add attachments (optional)' : 'Attachments'}</Label>
                            <Input
                                id="files"
                                type="file"
                                multiple
                                accept="image/*,video/*,.pdf,.doc,.docx,.zip"
                                onChange={(e) => form.setData('files', Array.from(e.target.files ?? []))}
                            />
                            <InputError message={form.errors.files as string | undefined} />
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
                                <Detail label="Type" value={viewItem.content_type_label} />
                                <Detail label="Platform" value={viewItem.platform_label} />
                                <Detail label="Status" value={viewItem.status_label} />
                            </div>
                            <div>
                                <p className="text-muted-foreground mb-1 text-xs font-medium uppercase">Description</p>
                                <p className="whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{viewItem.description || '—'}</p>
                            </div>
                            {viewItem.attachments.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-muted-foreground text-xs font-medium uppercase">Attachments</p>
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
                                        </div>
                                    ))}
                                </div>
                            )}
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
