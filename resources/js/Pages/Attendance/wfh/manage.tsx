import { DataTableCard } from '@/components/admin/data-table-card';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, Home, LoaderCircle, Pencil, Plus, X } from 'lucide-react';
import { useState } from 'react';

interface WfhRequestRow {
    id: number;
    employee_id: number;
    employee: string;
    employee_code: string;
    department: string;
    type: string;
    type_label: string;
    source_label: string;
    start_date: string;
    end_date: string;
    date_range_label: string;
    reason: string;
    notes: string | null;
    status: string;
    status_label: string;
    approved_by: string | null;
    assigned_by: string | null;
    can_approve: boolean;
    can_reject: boolean;
    can_edit: boolean;
    can_cancel: boolean;
}

interface Props {
    requests: WfhRequestRow[];
    filters: {
        status: string;
        type: string;
        employee_id: number | null;
        department_id: number | null;
        date: string;
    };
    statuses: Option[];
    types: Option[];
    filterOptions: {
        employees: { id: number; name: string; employee_code: string; department_id: number | null; department: string }[];
        departments: { id: number; name: string }[];
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/admin/attendance' },
    { title: 'WFH Management', href: '/admin/attendance/wfh' },
];

const ALL = 'all';
const STATUS_TONE: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    assigned: 'info',
    cancelled: 'neutral',
};

export default function WfhManagementIndex({ requests, filters, statuses, types, filterOptions }: Props) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [editRow, setEditRow] = useState<WfhRequestRow | null>(null);

    const assignForm = useForm({
        employee_id: '',
        start_date: '',
        end_date: '',
        reason: '',
        notes: '',
    });

    const editForm = useForm({
        employee_id: '',
        start_date: '',
        end_date: '',
        reason: '',
        notes: '',
    });

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/admin/attendance/wfh',
            {
                status: filters.status || undefined,
                type: filters.type || undefined,
                employee_id: filters.employee_id ?? undefined,
                department_id: filters.department_id ?? undefined,
                date: filters.date || undefined,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const submitAssign = (event: React.FormEvent) => {
        event.preventDefault();
        assignForm.post('/admin/attendance/wfh/assign', {
            preserveScroll: true,
            onSuccess: () => {
                assignForm.reset();
                setAssignOpen(false);
            },
        });
    };

    const openEdit = (row: WfhRequestRow) => {
        setEditRow(row);
        editForm.setData({
            employee_id: String(row.employee_id),
            start_date: row.start_date,
            end_date: row.end_date,
            reason: row.reason,
            notes: row.notes ?? '',
        });
        editForm.clearErrors();
    };

    const submitEdit = (event: React.FormEvent) => {
        event.preventDefault();
        if (!editRow) {
            return;
        }

        editForm.put(`/admin/attendance/wfh/${editRow.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditRow(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WFH Management" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="WFH Management"
                    description="Review employee requests and assign work from home directly."
                    action={
                        <Button type="button" onClick={() => setAssignOpen(true)}>
                            <Plus />
                            Assign WFH
                        </Button>
                    }
                />

                <div className="grid gap-3 lg:grid-cols-5">
                    <Select
                        value={filters.employee_id ? String(filters.employee_id) : ALL}
                        onValueChange={(value) => apply({ employee_id: value === ALL ? null : Number(value) })}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Employee" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All employees</SelectItem>
                            {filterOptions.employees.map((employee) => (
                                <SelectItem key={employee.id} value={String(employee.id)}>
                                    {employee.name} ({employee.employee_code})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={filters.type || ALL} onValueChange={(value) => apply({ type: value === ALL ? '' : value })}>
                        <SelectTrigger>
                            <SelectValue placeholder="Type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All types</SelectItem>
                            {types.map((type) => (
                                <SelectItem key={type.value} value={type.value}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? '' : value })}>
                        <SelectTrigger>
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All statuses</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem key={status.value} value={status.value}>
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.department_id ? String(filters.department_id) : ALL}
                        onValueChange={(value) => apply({ department_id: value === ALL ? null : Number(value) })}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Department" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All departments</SelectItem>
                            {filterOptions.departments.map((department) => (
                                <SelectItem key={department.id} value={String(department.id)}>
                                    {department.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <input
                        type="date"
                        value={filters.date}
                        onChange={(event) => apply({ date: event.target.value })}
                        className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                    />
                </div>

                <DataTableCard title="WFH records" description="Employee requests and direct Operations assignments.">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Employee</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>WFH dates</TableHead>
                                    <TableHead>Reason</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Assigned / Reviewed by</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {requests.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-muted-foreground py-10 text-center">
                                            No WFH records match these filters.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    requests.map((request) => (
                                        <TableRow key={request.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">{request.employee}</p>
                                                    <p className="text-muted-foreground text-xs">{request.employee_code}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell>{request.department}</TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <Badge variant={request.type === 'assignment' ? 'info' : 'neutral'}>{request.type_label}</Badge>
                                                    <p className="text-muted-foreground text-xs">{request.source_label}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell className="tabular-nums">{request.date_range_label}</TableCell>
                                            <TableCell className="max-w-xs break-words">{request.reason}</TableCell>
                                            <TableCell>
                                                <Badge variant={STATUS_TONE[request.status] ?? 'neutral'}>{request.status_label}</Badge>
                                            </TableCell>
                                            <TableCell>{request.assigned_by ?? request.approved_by ?? '—'}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {request.can_approve && (
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                router.post(`/admin/attendance/wfh/${request.id}/approve`, {}, { preserveScroll: true })
                                                            }
                                                        >
                                                            <Check />
                                                            Approve
                                                        </Button>
                                                    )}
                                                    {request.can_reject && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                router.post(`/admin/attendance/wfh/${request.id}/reject`, {}, { preserveScroll: true })
                                                            }
                                                        >
                                                            <X />
                                                            Reject
                                                        </Button>
                                                    )}
                                                    {request.can_edit && (
                                                        <Button size="sm" variant="outline" onClick={() => openEdit(request)}>
                                                            <Pencil />
                                                            Edit
                                                        </Button>
                                                    )}
                                                    {request.can_cancel && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                router.post(`/admin/attendance/wfh/${request.id}/cancel`, {}, { preserveScroll: true })
                                                            }
                                                        >
                                                            Cancel
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </DataTableCard>
            </div>

            <Dialog open={assignOpen} onOpenChange={setAssignOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Assign Work From Home</DialogTitle>
                        <DialogDescription>Directly authorize WFH for an employee across a date range.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitAssign} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="assign-employee">Employee</Label>
                            <Select value={assignForm.data.employee_id} onValueChange={(value) => assignForm.setData('employee_id', value)}>
                                <SelectTrigger id="assign-employee">
                                    <SelectValue placeholder="Select employee" />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.employees.map((employee) => (
                                        <SelectItem key={employee.id} value={String(employee.id)}>
                                            {employee.name} ({employee.employee_code})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {assignForm.errors.employee_id && <p className="text-destructive text-sm">{assignForm.errors.employee_id}</p>}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="assign-start">From date</Label>
                                <Input
                                    id="assign-start"
                                    type="date"
                                    value={assignForm.data.start_date}
                                    onChange={(event) => assignForm.setData('start_date', event.target.value)}
                                    required
                                />
                                {assignForm.errors.start_date && <p className="text-destructive text-sm">{assignForm.errors.start_date}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="assign-end">To date</Label>
                                <Input
                                    id="assign-end"
                                    type="date"
                                    value={assignForm.data.end_date}
                                    onChange={(event) => assignForm.setData('end_date', event.target.value)}
                                    required
                                />
                                {assignForm.errors.end_date && <p className="text-destructive text-sm">{assignForm.errors.end_date}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="assign-reason">Reason</Label>
                            <Textarea
                                id="assign-reason"
                                value={assignForm.data.reason}
                                onChange={(event) => assignForm.setData('reason', event.target.value)}
                                rows={3}
                                required
                            />
                            {assignForm.errors.reason && <p className="text-destructive text-sm">{assignForm.errors.reason}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="assign-notes">Optional notes</Label>
                            <Textarea
                                id="assign-notes"
                                value={assignForm.data.notes}
                                onChange={(event) => assignForm.setData('notes', event.target.value)}
                                rows={2}
                            />
                            {assignForm.errors.notes && <p className="text-destructive text-sm">{assignForm.errors.notes}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAssignOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={assignForm.processing}>
                                {assignForm.processing && <LoaderCircle className="animate-spin" />}
                                Assign WFH
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={editRow !== null} onOpenChange={(open) => !open && setEditRow(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit WFH assignment</DialogTitle>
                        <DialogDescription>Update the assigned date range and reason.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="edit-start">From date</Label>
                                <Input
                                    id="edit-start"
                                    type="date"
                                    value={editForm.data.start_date}
                                    onChange={(event) => editForm.setData('start_date', event.target.value)}
                                    required
                                />
                                {editForm.errors.start_date && <p className="text-destructive text-sm">{editForm.errors.start_date}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="edit-end">To date</Label>
                                <Input
                                    id="edit-end"
                                    type="date"
                                    value={editForm.data.end_date}
                                    onChange={(event) => editForm.setData('end_date', event.target.value)}
                                    required
                                />
                                {editForm.errors.end_date && <p className="text-destructive text-sm">{editForm.errors.end_date}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-reason">Reason</Label>
                            <Textarea
                                id="edit-reason"
                                value={editForm.data.reason}
                                onChange={(event) => editForm.setData('reason', event.target.value)}
                                rows={3}
                                required
                            />
                            {editForm.errors.reason && <p className="text-destructive text-sm">{editForm.errors.reason}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-notes">Optional notes</Label>
                            <Textarea
                                id="edit-notes"
                                value={editForm.data.notes}
                                onChange={(event) => editForm.setData('notes', event.target.value)}
                                rows={2}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditRow(null)}>
                                Close
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing && <LoaderCircle className="animate-spin" />}
                                Save changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
