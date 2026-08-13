import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
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
import { Head, Link, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface CompanyRow {
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
    companies: CompanyRow[];
    statuses: Option[];
    can: { manage: boolean };
}

type CompanyFormValues = {
    name: string;
    code: string;
    status: string;
    primary_contact_name: string;
    primary_contact_email: string;
    primary_contact_phone: string;
    notes: string;
};

const blank: CompanyFormValues = {
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
    { title: 'Companies', href: '/tasks/companies' },
];

export default function CompanyIndex({ companies, statuses, can }: Props) {
    const [editing, setEditing] = useState<CompanyRow | null>(null);
    const [open, setOpen] = useState(false);
    const form = useForm<CompanyFormValues>(blank);

    const start = (company: CompanyRow | null) => {
        setEditing(company);
        form.clearErrors();
        form.setData(
            company
                ? {
                      name: company.name,
                      code: company.code,
                      status: company.status,
                      primary_contact_name: company.primary_contact_name ?? '',
                      primary_contact_email: company.primary_contact_email ?? '',
                      primary_contact_phone: company.primary_contact_phone ?? '',
                      notes: company.notes ?? '',
                  }
                : blank,
        );
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(`/tasks/companies/${editing.id}`, options);
        } else {
            form.post('/tasks/companies', options);
        }
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Companies" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Work companies"
                    description="The businesses you deliver work for. Separate from CRM clients: these two lists are owned by different teams."
                    action={
                        can.manage && (
                            <Button onClick={() => start(null)}>
                                <Plus /> New company
                            </Button>
                        )
                    }
                />

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Projects</TableHead>
                                <TableHead>Status</TableHead>
                                {can.manage && <TableHead className="w-24" />}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {companies.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={can.manage ? 5 : 4} className="text-muted-foreground py-10 text-center">
                                        No companies yet.
                                    </TableCell>
                                </TableRow>
                            )}

                            {companies.map((company) => (
                                <TableRow key={company.id}>
                                    <TableCell>
                                        <div className="font-medium">{company.name}</div>
                                        <div className="text-muted-foreground text-xs">{company.code}</div>
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {company.primary_contact_name ?? <span className="text-muted-foreground">—</span>}
                                        {company.primary_contact_email && (
                                            <div className="text-muted-foreground text-xs">{company.primary_contact_email}</div>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {company.projects_count > 0 ? (
                                            <Link href={`/tasks/projects?company=${company.id}`} className="hover:underline">
                                                {company.projects_count}
                                            </Link>
                                        ) : (
                                            <span className="text-muted-foreground">0</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={company.status === 'active' ? 'default' : 'outline'}>
                                            {statuses.find((status) => status.value === company.status)?.label ?? company.status}
                                        </Badge>
                                    </TableCell>
                                    {can.manage && (
                                        <TableCell>
                                            <div className="flex gap-1">
                                                <Button variant="ghost" size="icon" onClick={() => start(company)} aria-label="Edit">
                                                    <Pencil className="size-4" />
                                                </Button>
                                                {company.can_delete && (
                                                    <ConfirmDelete
                                                        url={`/tasks/companies/${company.id}`}
                                                        title={`Delete ${company.name}?`}
                                                        description="This company has no projects, so nothing else is affected."
                                                        trigger={
                                                            <Button variant="ghost" size="icon" aria-label="Delete">
                                                                <Trash2 className="text-destructive size-4" />
                                                            </Button>
                                                        }
                                                    />
                                                )}
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <form onSubmit={submit} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{editing ? `Edit ${editing.name}` : 'New company'}</DialogTitle>
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
                                {editing ? 'Save changes' : 'Create company'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}
