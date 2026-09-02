import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
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
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Copy, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

interface DocumentFile {
    uuid: string;
    name: string;
    mime: string;
    preview_url: string;
    download_url: string;
    can_preview: boolean;
}

interface DocumentRow {
    id: number;
    title: string;
    category: string;
    category_label: string;
    description: string | null;
    client: { id: number; name: string };
    uploaded_by: string;
    uploaded_at: string;
    updated_at: string;
    file: DocumentFile | null;
    can: { update: boolean; delete: boolean; share: boolean };
}

interface Props {
    documents: Paginated<DocumentRow>;
    clients: { id: number; name: string }[];
    categories: Option[];
    filters: { search: string | null; client: number | null; category: string | null };
    can: { manage: boolean; share: boolean };
}

type DocumentFormValues = {
    tm_company_id: string;
    title: string;
    category: string;
    description: string;
    file: File | null;
};

const blank: DocumentFormValues = {
    tm_company_id: '',
    title: '',
    category: 'contract',
    description: '',
    file: null,
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Operations Documents', href: '/tasks/documents' },
];

const formatted = (value: string) => new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });

export default function DocumentsIndex({ documents, clients, categories, filters, can }: Props) {
    const [editing, setEditing] = useState<DocumentRow | null>(null);
    const [open, setOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const { flash } = usePage<{ flash: { share_url?: string } }>().props;

    const form = useForm<DocumentFormValues>(blank);

    useEffect(() => {
        if (flash.share_url) {
            void navigator.clipboard.writeText(flash.share_url);
        }
    }, [flash.share_url]);

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/documents',
            {
                per_page: documents.per_page,
                search: filters.search,
                client: filters.client,
                category: filters.category,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const start = (document: DocumentRow | null) => {
        setEditing(document);
        form.clearErrors();
        form.setData(
            document
                ? {
                      tm_company_id: String(document.client.id),
                      title: document.title,
                      category: document.category,
                      description: document.description ?? '',
                      file: null,
                  }
                : blank,
        );
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.post(`/tasks/documents/${editing.id}`, { ...options, method: 'put' });
        } else {
            form.post('/tasks/documents', options);
        }
    };

    const rowActions = (document: DocumentRow): RowActionItem[] => {
        const items: RowActionItem[] = [];

        if (document.file?.can_preview) {
            items.push({
                key: 'view',
                label: 'View',
                onSelect: () => setPreviewUrl(document.file!.preview_url),
            });
        }

        if (document.file) {
            items.push({
                key: 'download',
                label: 'Download',
                href: document.file.download_url,
            });
        }

        if (document.can.update) {
            items.push({ key: 'edit', label: 'Edit', onSelect: () => start(document) });
        }

        if (document.can.share) {
            items.push({
                key: 'share',
                label: 'Share',
                onSelect: () => router.post(`/tasks/documents/${document.id}/share-link`, {}, { preserveScroll: true }),
            });
        }

        if (document.can.delete) {
            items.push({
                key: 'delete',
                label: 'Delete',
                destructive: true,
                confirm: {
                    url: `/tasks/documents/${document.id}`,
                    title: `Delete "${document.title}"?`,
                    description: 'This document and its file will be removed permanently.',
                },
            });
        }

        return items;
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Operations Documents" />

            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Operations Documents"
                    description="Store contracts, agreements, invoices, and other important client documents."
                    action={
                        can.manage ? (
                            <Button onClick={() => start(null)}>
                                <Plus /> Upload document
                            </Button>
                        ) : undefined
                    }
                    toolbar={
                        <div className="flex min-w-0 flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
                            <SearchInput
                                value={filters.search ?? ''}
                                onChange={(search) => apply({ search: search || null })}
                                placeholder="Search documents..."
                                className="w-full lg:max-w-xs"
                            />
                            <Select
                                value={filters.client ? String(filters.client) : 'all'}
                                onValueChange={(value) => apply({ client: value === 'all' ? null : Number(value) })}
                            >
                                <SelectTrigger className="w-full lg:w-48">
                                    <SelectValue placeholder="All clients" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All clients</SelectItem>
                                    {clients.map((client) => (
                                        <SelectItem key={client.id} value={String(client.id)}>
                                            {client.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.category ?? 'all'}
                                onValueChange={(value) => apply({ category: value === 'all' ? null : value })}
                            >
                                <SelectTrigger className="w-full lg:w-48">
                                    <SelectValue placeholder="All categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All categories</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category.value} value={category.value}>
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                    footer={<DataTableFooter page={documents} onPerPageChange={(perPage) => apply({ per_page: perPage })} />}
                >
                    <div className="overflow-x-auto">
                        <Table className="min-w-max">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Document</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Uploaded By</TableHead>
                                    <TableHead>Uploaded Date</TableHead>
                                    <TableHead className="w-16 text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {documents.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                            No documents yet.
                                        </TableCell>
                                    </TableRow>
                                )}

                                {documents.data.map((document) => (
                                    <TableRow key={document.id}>
                                        <TableCell className="max-w-xs">
                                            <div className="font-medium">{document.title}</div>
                                            {document.description && (
                                                <div className="text-muted-foreground truncate text-xs">{document.description}</div>
                                            )}
                                        </TableCell>
                                        <TableCell>{document.client.name}</TableCell>
                                        <TableCell>
                                            <Badge variant="neutral">{document.category_label}</Badge>
                                        </TableCell>
                                        <TableCell>{document.uploaded_by}</TableCell>
                                        <TableCell className="text-sm">{formatted(document.uploaded_at)}</TableCell>
                                        <TableCell className="text-right">
                                            <RowActions label={`Actions for ${document.title}`} items={rowActions(document)} />
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
                        <DialogTitle>{editing ? 'Edit document' : 'Upload document'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="tm_company_id">Client</Label>
                            <Select value={form.data.tm_company_id} onValueChange={(value) => form.setData('tm_company_id', value)}>
                                <SelectTrigger id="tm_company_id">
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

                        <div className="grid gap-2">
                            <Label htmlFor="title">Document title</Label>
                            <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
                            <InputError message={form.errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="category">Category</Label>
                            <Select value={form.data.category} onValueChange={(value) => form.setData('category', value)}>
                                <SelectTrigger id="category">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem key={category.value} value={category.value}>
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.category} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Notes</Label>
                            <Textarea
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                rows={3}
                            />
                            <InputError message={form.errors.description} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="file">{editing ? 'Replace file (optional)' : 'File'}</Label>
                            <Input
                                id="file"
                                type="file"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.ai,.psd"
                                onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)}
                                required={!editing}
                            />
                            <InputError message={form.errors.file} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {editing ? 'Save changes' : 'Upload'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={previewUrl !== null} onOpenChange={() => setPreviewUrl(null)}>
                <DialogContent className="max-h-[90vh] max-w-4xl overflow-hidden">
                    <DialogHeader>
                        <DialogTitle>Document preview</DialogTitle>
                    </DialogHeader>
                    {previewUrl && (
                        <div className="max-h-[70vh] overflow-auto">
                            {previewUrl.includes('pdf') || previewUrl.endsWith('/preview') ? (
                                <iframe src={previewUrl} title="Document preview" className="h-[70vh] w-full rounded-lg border" />
                            ) : (
                                <img src={previewUrl} alt="Document preview" className="mx-auto max-h-[70vh] max-w-full object-contain" />
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}
