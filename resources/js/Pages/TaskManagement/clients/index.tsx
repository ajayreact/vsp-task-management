import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { RowActions } from '@/components/admin/row-actions';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import TaskLayout from '@/layouts/task-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';

interface TeamEmployee {
    id: number;
    name: string;
    avatar: string | null;
}

interface ClientRow {
    id: number;
    name: string;
    code: string;
    status: string;
    primary_responsible_employee_id: number | null;
    primary_responsible: TeamEmployee | null;
    supporting_employees: TeamEmployee[];
    supporting_employee_ids: number[];
    primary_contact_name: string | null;
    primary_contact_email: string | null;
    primary_contact_phone: string | null;
    notes: string | null;
    monthly_post_target: number | null;
    holiday_india_enabled: boolean;
    holiday_usa_enabled: boolean;
    projects_count: number;
    can_delete: boolean;
}

interface EmployeeOption {
    id: number;
    label: string;
}

interface Props {
    clients: Paginated<ClientRow>;
    filters: { search: string; responsible: number | null };
    statuses: Option[];
    employees: EmployeeOption[];
    can: { manage: boolean };
}

type ClientFormValues = {
    name: string;
    code: string;
    status: string;
    primary_responsible_employee_id: string;
    supporting_employee_ids: number[];
    primary_contact_name: string;
    primary_contact_email: string;
    primary_contact_phone: string;
    notes: string;
    monthly_post_target: string;
    holiday_india_enabled: boolean;
    holiday_usa_enabled: boolean;
};

const NONE = 'none';
const ALL = 'all';

const blank: ClientFormValues = {
    name: '',
    code: '',
    status: 'active',
    primary_responsible_employee_id: '',
    supporting_employee_ids: [],
    primary_contact_name: '',
    primary_contact_email: '',
    primary_contact_phone: '',
    notes: '',
    monthly_post_target: '18',
    holiday_india_enabled: true,
    holiday_usa_enabled: false,
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Clients', href: '/tasks/clients' },
];

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function EmployeeChip({ employee, compact = false }: { employee: TeamEmployee; compact?: boolean }) {
    return (
        <div className={cn('flex items-center gap-2', compact && 'gap-1.5')}>
            {employee.avatar ? (
                <img src={employee.avatar} alt="" className="size-6 rounded-full object-cover" />
            ) : (
                <span className="bg-muted text-muted-foreground flex size-6 items-center justify-center rounded-full text-[10px] font-medium">
                    {initials(employee.name) || '?'}
                </span>
            )}
            <span className={cn('text-sm', compact && 'text-xs')}>{employee.name}</span>
        </div>
    );
}

function ResponsibleCell({ client }: { client: ClientRow }) {
    if (!client.primary_responsible) {
        return <span className="text-muted-foreground text-sm">—</span>;
    }

    const supporting = client.supporting_employees;
    const supportingNames = supporting.map((employee) => employee.name).join(', ');

    return (
        <div className="space-y-1">
            <EmployeeChip employee={client.primary_responsible} />
            {supporting.length > 0 && (
                <TooltipProvider delayDuration={150}>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button type="button" className="text-muted-foreground hover:text-foreground text-xs underline-offset-2 hover:underline">
                                + {supporting.length} other{supporting.length === 1 ? '' : 's'}
                            </button>
                        </TooltipTrigger>
                        <TooltipContent side="bottom" align="start" className="max-w-xs">
                            <p className="font-medium">Supporting</p>
                            <p className="text-xs opacity-90">{supportingNames}</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            )}
        </div>
    );
}

export default function ClientIndex({ clients, filters, statuses, employees, can }: Props) {
    const [editing, setEditing] = useState<ClientRow | null>(null);
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const form = useForm<ClientFormValues>(blank);

    const supportingChoices = useMemo(() => {
        const primaryId = form.data.primary_responsible_employee_id;
        return employees.filter((employee) => String(employee.id) !== primaryId);
    }, [employees, form.data.primary_responsible_employee_id]);

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/clients',
            {
                per_page: clients.per_page,
                search: filters.search || undefined,
                responsible: filters.responsible ?? undefined,
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
                      primary_responsible_employee_id: client.primary_responsible_employee_id
                          ? String(client.primary_responsible_employee_id)
                          : '',
                      supporting_employee_ids: client.supporting_employee_ids ?? [],
                      primary_contact_name: client.primary_contact_name ?? '',
                      primary_contact_email: client.primary_contact_email ?? '',
                      primary_contact_phone: client.primary_contact_phone ?? '',
                      notes: client.notes ?? '',
                      monthly_post_target: client.monthly_post_target != null ? String(client.monthly_post_target) : '',
                      holiday_india_enabled: client.holiday_india_enabled,
                      holiday_usa_enabled: client.holiday_usa_enabled,
                  }
                : blank,
        );
        setOpen(true);
    };

    const toggleSupporting = (employeeId: number, checked: boolean) => {
        const current = form.data.supporting_employee_ids;
        form.setData(
            'supporting_employee_ids',
            checked ? [...current, employeeId] : current.filter((id) => id !== employeeId),
        );
    };

    const submit = (event?: React.FormEvent) => {
        event?.preventDefault();

        if (form.processing) {
            return;
        }

        const visit = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.clearErrors();
                form.setData(blank);
                setEditing(null);
            },
            onError: () => {
                // Keep the modal open and surface field errors under inputs.
            },
        };

        const primaryId = form.data.primary_responsible_employee_id.trim();
        const payload = {
            ...form.data,
            primary_responsible_employee_id: primaryId === '' ? null : Number(primaryId),
            supporting_employee_ids: form.data.supporting_employee_ids.filter((id) => String(id) !== primaryId),
            monthly_post_target:
                String(form.data.monthly_post_target ?? '').trim() === ''
                    ? null
                    : Number(form.data.monthly_post_target),
            primary_contact_email: String(form.data.primary_contact_email ?? '').trim() || null,
            primary_contact_name: String(form.data.primary_contact_name ?? '').trim() || null,
            primary_contact_phone: String(form.data.primary_contact_phone ?? '').trim() || null,
            notes: String(form.data.notes ?? '').trim() || null,
            holiday_india_enabled: Boolean(form.data.holiday_india_enabled),
            holiday_usa_enabled: Boolean(form.data.holiday_usa_enabled),
        };

        form.transform(() => payload);

        if (editing) {
            form.put(`/tasks/clients/${editing.id}`, visit);
        } else {
            form.post('/tasks/clients', visit);
        }
    };

    const colSpan = can.manage ? 6 : 5;

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
                    toolbar={
                        <div className="flex flex-wrap gap-3">
                            <form
                                className="flex gap-2"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    apply({ search: search.trim() || null });
                                }}
                            >
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search clients or responsible…"
                                    className="w-56"
                                />
                                <Button type="submit" variant="outline">
                                    Search
                                </Button>
                            </form>

                            <Select
                                value={filters.responsible ? String(filters.responsible) : ALL}
                                onValueChange={(value) => apply({ responsible: value === ALL ? null : value })}
                            >
                                <SelectTrigger className="w-56">
                                    <SelectValue placeholder="Responsible employee" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All employees</SelectItem>
                                    {employees.map((employee) => (
                                        <SelectItem key={employee.id} value={String(employee.id)}>
                                            {employee.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                    footer={
                        <DataTableFooter
                            page={clients}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/tasks/clients/export"
                            exportParams={{
                                search: filters.search || undefined,
                                responsible: filters.responsible ?? undefined,
                            }}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Client</TableHead>
                                <TableHead>Responsible</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Projects</TableHead>
                                <TableHead>Status</TableHead>
                                {can.manage && <TableHead className="w-16 text-right">Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {clients.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={colSpan} className="text-muted-foreground py-10 text-center">
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
                                    <TableCell>
                                        <ResponsibleCell client={client} />
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
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <form noValidate onSubmit={submit} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{editing ? `Edit ${editing.name}` : 'New client'}</DialogTitle>
                        </DialogHeader>

                        {Object.keys(form.errors).length > 0 && (
                            <div className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">
                                Please fix the highlighted fields and try again.
                            </div>
                        )}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code">Code</Label>
                                <Input
                                    id="code"
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
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
                                <Label htmlFor="primary_responsible_employee_id">Primary responsible employee</Label>
                                <Select
                                    value={form.data.primary_responsible_employee_id || NONE}
                                    onValueChange={(value) => {
                                        const next = value === NONE ? '' : value;
                                        form.setData('primary_responsible_employee_id', next);
                                        if (next !== '') {
                                            form.setData(
                                                'supporting_employee_ids',
                                                form.data.supporting_employee_ids.filter((id) => String(id) !== next),
                                            );
                                        }
                                    }}
                                >
                                    <SelectTrigger id="primary_responsible_employee_id">
                                        <SelectValue placeholder="Select employee" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NONE}>None</SelectItem>
                                        {employees.map((employee) => (
                                            <SelectItem key={employee.id} value={String(employee.id)}>
                                                {employee.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-muted-foreground text-xs">Required for active clients. Main person accountable for the client.</p>
                                <InputError message={form.errors.primary_responsible_employee_id} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label>Supporting employees</Label>
                                <p className="text-muted-foreground text-xs">Optional. The primary responsible employee is excluded automatically.</p>
                                {supportingChoices.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">No other assignable employees.</p>
                                ) : (
                                    <div className="max-h-40 space-y-2 overflow-y-auto rounded-md border p-3">
                                        {supportingChoices.map((employee) => {
                                            const checked = form.data.supporting_employee_ids.includes(employee.id);
                                            return (
                                                <label key={employee.id} className="flex items-center gap-2 text-sm">
                                                    <Checkbox
                                                        checked={checked}
                                                        onCheckedChange={(value) => toggleSupporting(employee.id, value === true)}
                                                    />
                                                    <span>{employee.label}</span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                )}
                                <InputError message={form.errors.supporting_employee_ids} />
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
                                    type="text"
                                    inputMode="email"
                                    autoComplete="email"
                                    placeholder="name@company.com"
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

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="monthly_post_target">Monthly post target</Label>
                                <Input
                                    id="monthly_post_target"
                                    type="number"
                                    min={0}
                                    max={999}
                                    value={form.data.monthly_post_target}
                                    onChange={(e) => form.setData('monthly_post_target', e.target.value)}
                                />
                                <InputError message={form.errors.monthly_post_target} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label>Holiday calendars</Label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.data.holiday_india_enabled}
                                        onChange={(e) => form.setData('holiday_india_enabled', e.target.checked)}
                                    />
                                    India
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.data.holiday_usa_enabled}
                                        onChange={(e) => form.setData('holiday_usa_enabled', e.target.checked)}
                                    />
                                    United States
                                </label>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="button" disabled={form.processing} onClick={() => submit()}>
                                {form.processing ? 'Saving…' : editing ? 'Save changes' : 'Create client'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}
