import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { DataTableCard } from '@/components/admin/data-table-card';
import { RowActions, type RowActionItem } from '@/components/admin/row-actions';
import { SearchInput } from '@/components/admin/search-input';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Copy, Eye, Plus, Share2 } from 'lucide-react';
import { useEffect, useState } from 'react';

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

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Content Calendar" />

            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="min-w-0 space-y-2">
                        <h1 className="text-foreground text-xl font-semibold tracking-tight">Content Calendar</h1>
                        <p className="text-muted-foreground text-sm">Plan and share upcoming social content client by client.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {can.manage && (
                            <Button onClick={() => start(null)}>
                                <Plus /> Add content
                            </Button>
                        )}
                        {can.share && filters.client && (
                            <Button
                                variant="outline"
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

                <div className="flex min-w-0 flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
                    <Select
                        value={filters.client ? String(filters.client) : 'none'}
                        onValueChange={(value) => apply({ client: value === 'none' ? null : Number(value) })}
                    >
                        <SelectTrigger className="w-full lg:w-56">
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

                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" onClick={() => apply({ period_start: period.previous_start })}>
                            <ChevronLeft className="size-4" /> Previous 15 days
                        </Button>
                        <span className="text-sm font-medium">{selectedClient ? `${selectedClient.name} · ` : ''}{period.label}</span>
                        <Button variant="outline" size="sm" onClick={() => apply({ period_start: period.next_start })}>
                            Next 15 days <ChevronRight className="size-4" />
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => apply({ period_start: null })}>
                            Today
                        </Button>
                    </div>
                </div>

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
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Day</TableHead>
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
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-muted-foreground py-10 text-center">
                                            No content scheduled in this period.
                                        </TableCell>
                                    </TableRow>
                                )}

                                {items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="whitespace-nowrap">{item.scheduled_date}</TableCell>
                                        <TableCell>{item.scheduled_day}</TableCell>
                                        <TableCell>{item.content_type_label}</TableCell>
                                        <TableCell>{item.platform_label}</TableCell>
                                        <TableCell className="max-w-xs">
                                            <button type="button" className="text-left hover:underline" onClick={() => setViewItem(item)}>
                                                {truncate(item.description)}
                                            </button>
                                        </TableCell>
                                        <TableCell>
                                            {item.attachments[0] ? (
                                                <Button
                                                    variant="link"
                                                    className="h-auto p-0"
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
                                            <Badge variant="neutral">{item.status_label}</Badge>
                                        </TableCell>
                                        <TableCell>{item.uploaded_by}</TableCell>
                                        <TableCell className="text-right">
                                            <RowActions label={`Actions for ${item.scheduled_date}`} items={rowActions(item)} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </DataTableCard>

                {flash.share_url && (
                    <div className="bg-muted/50 flex flex-col gap-2 rounded-lg border p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span className="break-all">Share link copied: {flash.share_url}</span>
                        <Button variant="outline" size="sm" onClick={() => void navigator.clipboard.writeText(flash.share_url!)}>
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
