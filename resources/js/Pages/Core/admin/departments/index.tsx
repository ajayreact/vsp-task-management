import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { RowActions } from '@/components/admin/row-actions';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface DepartmentRow {
    id: number;
    name: string;
    code: string;
    description: string | null;
    parent_id: number | null;
    parent_name: string | null;
    head_employee_id: number | null;
    head_name: string | null;
    employees_count: number;
    is_active: boolean;
    can_delete: boolean;
}

interface Props {
    departments: Paginated<DepartmentRow>;
    parents: { id: number; name: string }[];
    heads: { id: number; label: string }[];
    can: { manage: boolean };
}

type DepartmentFormValues = {
    name: string;
    code: string;
    description: string;
    parent_id: string;
    head_employee_id: string;
    is_active: boolean;
};

const NONE = 'none';

const blank: DepartmentFormValues = {
    name: '',
    code: '',
    description: '',
    parent_id: '',
    head_employee_id: '',
    is_active: true,
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Departments', href: '/admin/departments' },
];

export default function DepartmentIndex({ departments, parents, heads, can }: Props) {
    const [editing, setEditing] = useState<DepartmentRow | null>(null);
    const [open, setOpen] = useState(false);

    const form = useForm<DepartmentFormValues>(blank);

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/admin/departments',
            {
                per_page: departments.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const openFor = (department: DepartmentRow | null) => {
        setEditing(department);
        form.clearErrors();
        form.setData(
            department
                ? {
                      name: department.name,
                      code: department.code,
                      description: department.description ?? '',
                      parent_id: department.parent_id ? String(department.parent_id) : '',
                      head_employee_id: department.head_employee_id ? String(department.head_employee_id) : '',
                      is_active: department.is_active,
                  }
                : blank,
        );
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const onSuccess = () => setOpen(false);

        if (editing) {
            form.put(`/admin/departments/${editing.id}`, { preserveScroll: true, onSuccess });
        } else {
            form.post('/admin/departments', { preserveScroll: true, onSuccess });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Departments" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Departments"
                    description="Organisational structure shared by both modules. Task Management routes work by department; CRM reports roll up through it."
                    action={
                        can.manage ? (
                            <Button onClick={() => openFor(null)}>
                                <Plus /> Add department
                            </Button>
                        ) : undefined
                    }
                    footer={
                        <DataTableFooter
                            page={departments}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/admin/departments/export"
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Department</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Parent</TableHead>
                                <TableHead>Head</TableHead>
                                <TableHead className="text-right">People</TableHead>
                                <TableHead>Status</TableHead>
                                {can.manage && <TableHead className="w-16 text-right">Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {departments.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={can.manage ? 7 : 6} className="text-muted-foreground py-10 text-center">
                                        No departments yet.
                                    </TableCell>
                                </TableRow>
                            )}

                            {departments.data.map((department) => (
                                <TableRow key={department.id}>
                                    <TableCell>
                                        <div className="font-medium">{department.name}</div>
                                        {department.description && (
                                            <div className="text-muted-foreground text-xs">{department.description}</div>
                                        )}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">{department.code}</TableCell>
                                    <TableCell>{department.parent_name ?? '—'}</TableCell>
                                    <TableCell>{department.head_name ?? '—'}</TableCell>
                                    <TableCell className="text-right tabular-nums">{department.employees_count}</TableCell>
                                    <TableCell>
                                        <Badge variant={department.is_active ? 'success' : 'neutral'}>
                                            {department.is_active ? 'Active' : 'Archived'}
                                        </Badge>
                                    </TableCell>
                                    {can.manage && (
                                        <TableCell className="text-right">
                                            <RowActions
                                                label={`Actions for ${department.name}`}
                                                items={[
                                                    {
                                                        key: 'edit',
                                                        label: 'Edit',
                                                        onSelect: () => openFor(department),
                                                    },
                                                    ...(department.can_delete
                                                        ? [
                                                              {
                                                                  key: 'delete',
                                                                  label: 'Delete',
                                                                  confirm: {
                                                                      url: `/admin/departments/${department.id}`,
                                                                      title: `Delete ${department.name}?`,
                                                                      description:
                                                                          'This department has no people and no sub-departments, so it can be removed safely.',
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
                            <DialogTitle>{editing ? `Edit ${editing.name}` : 'Add department'}</DialogTitle>
                            <DialogDescription>Departments group employees for routing, workload and reporting.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                <InputError message={form.errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="code">Code</Label>
                                <Input id="code" value={form.data.code} onChange={(e) => form.setData('code', e.target.value)} />
                                <InputError message={form.errors.code} />
                            </div>
                            <div className="flex items-center gap-3 pt-6">
                                <Switch
                                    checked={form.data.is_active}
                                    onCheckedChange={(checked) => form.setData('is_active', checked)}
                                    id="is_active"
                                />
                                <Label htmlFor="is_active">Active</Label>
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                />
                                <InputError message={form.errors.description} />
                            </div>
                            <div className="space-y-2">
                                <Label>Parent</Label>
                                <Select
                                    value={form.data.parent_id || NONE}
                                    onValueChange={(value) => form.setData('parent_id', value === NONE ? '' : value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="None" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NONE}>None</SelectItem>
                                        {parents
                                            .filter((department) => department.id !== editing?.id)
                                            .map((department) => (
                                                <SelectItem key={department.id} value={String(department.id)}>
                                                    {department.name}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.parent_id} />
                            </div>
                            <div className="space-y-2">
                                <Label>Head</Label>
                                <Select
                                    value={form.data.head_employee_id || NONE}
                                    onValueChange={(value) => form.setData('head_employee_id', value === NONE ? '' : value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="None" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NONE}>None</SelectItem>
                                        {heads.map((head) => (
                                            <SelectItem key={head.id} value={String(head.id)}>
                                                {head.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.head_employee_id} />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <LoaderCircle className="animate-spin" />}
                                {editing ? 'Save' : 'Create'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
