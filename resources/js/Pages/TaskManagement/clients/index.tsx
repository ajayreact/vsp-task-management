import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { RowActions } from '@/components/admin/row-actions';
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
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

interface ClientRow {
    id: number;
    name: string;
    code: string;
    status: string;
    primary_contact_name: string | null;
    primary_contact_email: string | null;
    primary_contact_phone: string | null;
    notes: string | null;
    projects_count: number;
    can_delete: boolean;
}

interface Props {
    clients: Paginated<ClientRow>;
    statuses: Option[];
    can: { manage: boolean };
}

type ClientFormValues = {
    name: string;
    code: string;
    status: string;
    primary_contact_name: string;
    primary_contact_email: string;
    primary_contact_phone: string;
    notes: string;
};

const blank: ClientFormValues = {
    name: '',
    code: '',
    status: 'active',
    primary_contact_name: '',
    primary_contact_email: '',
    primary_contact_phone: '',
    notes: '',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Clients', href: '/tasks/clients' },
];

export default function ClientIndex({ clients, statuses, can }: Props) {
    const [editing, setEditing] = useState<ClientRow | null>(null);
    const [open, setOpen] = useState(false);
    const form = useForm<ClientFormValues>(blank);

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/clients',
            {
                per_page: clients.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const start = (client: ClientRow | null) => {
        setEditing(client);
        form.clearErrors();
        form.setData(
            client
                ? {
                      name: client.name,
                      code: client.code,
                      status: client.status,
                      primary_contact_name: client.primary_contact_name ?? '',
                      primary_contact_email: client.primary_contact_email ?? '',
                      primary_contact_phone: client.primary_contact_phone ?? '',
                      notes: client.notes ?? '',
                  }
                : blank,
        );
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(`/tasks/clients/${editing.id}`, options);
        } else {
            form.post('/tasks/clients', options);
        }
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Clients" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Clients"
                    description="The businesses you deliver work for."
                    action={
                        can.manage ? (
                            <Button onClick={() => start(null)}>
                                <Plus /> New client
                            </Button>
                        ) : undefined
                    }
                    footer={
                        <DataTableFooter
                            page={clients}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/tasks/clients/export"
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Client</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Projects</TableHead>
                                <TableHead>Status</TableHead>
                                {can.manage && <TableHead className="w-16 text-right">Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {clients.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={can.manage ? 5 : 4} className="text-muted-foreground py-10 text-center">
                                        No clients yet.
                                    </TableCell>
                                </TableRow>
                            )}

                            {clients.data.map((client) => (
                                <TableRow key={client.id}>
                                    <TableCell>
                                        <div className="font-medium">{client.name}</div>
                                        <div className="text-muted-foreground text-xs">{client.code}</div>
                                        <Link
                                            href={`/tasks/content-calendar?client=${client.id}`}
                                            className="text-primary mt-1 inline-block text-xs hover:underline"
                                        >
                                            Content calendar
                                        </Link>
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {client.primary_contact_name ?? <span className="text-muted-foreground">—</span>}
                                        {client.primary_contact_email && (
                                            <div className="text-muted-foreground text-xs">{client.primary_contact_email}</div>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {client.projects_count > 0 ? (
                                            <Link href={`/tasks/projects?client=${client.id}`} className="hover:underline">
                                                {client.projects_count}
                                            </Link>
                                        ) : (
                                            <span className="text-muted-foreground">0</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={client.status === 'active' ? 'success' : 'neutral'}>
                                            {statuses.find((status) => status.value === client.status)?.label ?? client.status}
                                        </Badge>
                                    </TableCell>
                                    {can.manage && (
                                        <TableCell className="text-right">
                                            <RowActions
                                                label={`Actions for ${client.name}`}
                                                items={[
                                                    {
                                                        key: 'edit',
                                                        label: 'Edit',
                                                        onSelect: () => start(client),
                                                    },
                                                    ...(client.can_delete
                                                        ? [
                                                              {
                                                                  key: 'delete',
                                                                  label: 'Delete',
                                                                  confirm: {
                                                                      url: `/tasks/clients/${client.id}`,
                                                                      title: `Delete ${client.name}?`,
                                                                      description: 'This client has no projects, so nothing else is affected.',
                                                                  },
                                                              },
                                                          ]
                                                        : []),
                                                ]}
                                            />
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <form onSubmit={submit} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{editing ? `Edit ${editing.name}` : 'New client'}</DialogTitle>
                        </DialogHeader>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code">Code</Label>
                                <Input
                                    id="code"
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                                    required
                                />
                                <InputError message={form.errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                                    <SelectTrigger id="status">
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
                                <InputError message={form.errors.status} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="primary_contact_name">Primary contact</Label>
                                <Input
                                    id="primary_contact_name"
                                    value={form.data.primary_contact_name}
                                    onChange={(e) => form.setData('primary_contact_name', e.target.value)}
                                />
                                <InputError message={form.errors.primary_contact_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="primary_contact_email">Email</Label>
                                <Input
                                    id="primary_contact_email"
                                    type="email"
                                    value={form.data.primary_contact_email}
                                    onChange={(e) => form.setData('primary_contact_email', e.target.value)}
                                />
                                <InputError message={form.errors.primary_contact_email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="primary_contact_phone">Phone</Label>
                                <Input
                                    id="primary_contact_phone"
                                    value={form.data.primary_contact_phone}
                                    onChange={(e) => form.setData('primary_contact_phone', e.target.value)}
                                />
                                <InputError message={form.errors.primary_contact_phone} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                                <InputError message={form.errors.notes} />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {editing ? 'Save changes' : 'Create client'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}
