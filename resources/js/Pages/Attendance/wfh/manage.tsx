import { DataTableCard } from '@/components/admin/data-table-card';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Check, Home, X } from 'lucide-react';

interface WfhRequestRow {
    id: number;
    employee: string;
    employee_code: string;
    department: string;
    date: string;
    reason: string;
    status: string;
    status_label: string;
    approved_by: string | null;
    approved_at: string | null;
}

interface Props {
    requests: WfhRequestRow[];
    filters: {
        status: string;
        employee_id: number | null;
        department_id: number | null;
        date: string;
    };
    statuses: Option[];
    filterOptions: {
        employees: { id: number; name: string; employee_code: string }[];
        departments: { id: number; name: string }[];
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/admin/attendance' },
    { title: 'WFH Management', href: '/admin/attendance/wfh' },
];

const ALL = 'all';
const STATUS_TONE: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
};

export default function WfhManagementIndex({ requests, filters, statuses, filterOptions }: Props) {
    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/admin/attendance/wfh',
            {
                status: filters.status || undefined,
                employee_id: filters.employee_id ?? undefined,
                department_id: filters.department_id ?? undefined,
                date: filters.date || undefined,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WFH Management" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="WFH Management"
                    description="Review and approve employee work from home requests."
                    action={
                        <div className="text-muted-foreground flex items-center gap-2 text-sm">
                            <Home className="size-4" />
                            Attendance administration
                        </div>
                    }
                />

                <div className="grid gap-3 lg:grid-cols-4">
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

                <DataTableCard title="WFH requests" description="Pending, approved, and rejected requests across the studio.">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Employee</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Reason</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Reviewed by</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {requests.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-muted-foreground py-10 text-center">
                                            No WFH requests match these filters.
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
                                            <TableCell className="tabular-nums">{request.date}</TableCell>
                                            <TableCell className="max-w-xs break-words">{request.reason}</TableCell>
                                            <TableCell>
                                                <Badge variant={STATUS_TONE[request.status] ?? 'neutral'}>{request.status_label}</Badge>
                                            </TableCell>
                                            <TableCell>{request.approved_by ?? '—'}</TableCell>
                                            <TableCell className="text-right">
                                                {request.status === 'pending' ? (
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                router.post(`/admin/attendance/wfh/${request.id}/approve`, {}, { preserveScroll: true })
                                                            }
                                                        >
                                                            <Check />
                                                            Approve
                                                        </Button>
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
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground text-xs">—</span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </DataTableCard>
            </div>
        </AppLayout>
    );
}
