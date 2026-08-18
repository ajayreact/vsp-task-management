import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { RowActions } from '@/components/admin/row-actions';
import { SearchInput } from '@/components/admin/search-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

interface EmployeeRow {
    id: number;
    employee_code: string;
    designation: { id: number; name: string } | null;
    status: string;
    user: { id: number; name: string; email: string; is_active: boolean };
    department: { id: number; name: string } | null;
    manager: { id: number; employee_code: string; user: { id: number; name: string } } | null;
}

interface Props {
    employees: Paginated<EmployeeRow>;
    filters: { search: string | null; department: number | null; status: string | null };
    departments: { id: number; name: string }[];
    statuses: Option[];
    can: { manage: boolean };
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employees', href: '/admin/employees' },
];

const statusTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    active: 'success',
    on_leave: 'warning',
    suspended: 'danger',
    exited: 'neutral',
};

export default function EmployeeIndex({ employees, filters, departments, statuses, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(() => applyFilter({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const applyFilter = (changes: Record<string, string | number | null>) => {
        router.get(
            '/admin/employees',
            {
                search: search || undefined,
                department: filters.department ?? undefined,
                status: filters.status || undefined,
                per_page: employees.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Employees" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Employees"
                    description="Everyone with an internal account. Both modules assign work to the people listed here."
                    action={
                        can.manage ? (
                            <Button asChild>
                                <Link href="/admin/employees/create">
                                    <Plus /> Add employee
                                </Link>
                            </Button>
                        ) : undefined
                    }
                    toolbar={
                        <>
                            <div className="flex flex-wrap items-center gap-3">
                                <Select
                                    value={filters.department ? String(filters.department) : ALL}
                                    onValueChange={(value) => applyFilter({ department: value === ALL ? null : value })}
                                >
                                    <SelectTrigger className="w-52" aria-label="Filter by department">
                                        <SelectValue placeholder="All departments" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>All departments</SelectItem>
                                        {departments.map((department) => (
                                            <SelectItem key={department.id} value={String(department.id)}>
                                                {department.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={filters.status || ALL}
                                    onValueChange={(value) => applyFilter({ status: value === ALL ? null : value })}
                                >
                                    <SelectTrigger className="w-44" aria-label="Filter by status">
                                        <SelectValue placeholder="Any status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>Any status</SelectItem>
                                        {statuses.map((status) => (
                                            <SelectItem key={status.value} value={status.value}>
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search name, email, code or designation"
                                aria-label="Search employees"
                            />
                        </>
                    }
                    footer={
                        <DataTableFooter
                            page={employees}
                            onPerPageChange={(perPage) => applyFilter({ per_page: perPage })}
                            exportBasePath="/admin/employees/export"
                            exportParams={{
                                search,
                                department: filters.department,
                                status: filters.status,
                            }}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Employee</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Reports to</TableHead>
                                <TableHead>Status</TableHead>
                                {can.manage && <TableHead className="w-16 text-right">Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {employees.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={can.manage ? 6 : 5} className="text-muted-foreground py-10 text-center">
                                        No employees match these filters.
                                    </TableCell>
                                </TableRow>
                            )}

                            {employees.data.map((employee) => (
                                <TableRow key={employee.id}>
                                    <TableCell>
                                        <div className="font-medium">{employee.user.name}</div>
                                        <div className="text-muted-foreground text-xs">
                                            {employee.user.email}
                                            {employee.designation?.name && ` · ${employee.designation.name}`}
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">{employee.employee_code}</TableCell>
                                    <TableCell>{employee.department?.name ?? '—'}</TableCell>
                                    <TableCell>{employee.manager?.user?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap items-center gap-1">
                                            <Badge variant={statusTone[employee.status] ?? 'neutral'}>
                                                {statuses.find((status) => status.value === employee.status)?.label ?? employee.status}
                                            </Badge>
                                            {!employee.user.is_active && <Badge variant="neutral">Login disabled</Badge>}
                                        </div>
                                    </TableCell>
                                    {can.manage && (
                                        <TableCell className="text-right">
                                            <RowActions
                                                label={`Actions for ${employee.employee_code}`}
                                                items={[
                                                    {
                                                        key: 'edit',
                                                        label: 'Edit',
                                                        href: `/admin/employees/${employee.id}/edit`,
                                                    },
                                                    {
                                                        key: 'delete',
                                                        label: 'Delete',
                                                        confirm: {
                                                            url: `/admin/employees/${employee.id}`,
                                                            title: `Remove ${employee.user.name}?`,
                                                            description:
                                                                'This deletes the employee profile and its login. Mark them as exited instead if you need to keep their history.',
                                                        },
                                                    },
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
        </AppLayout>
    );
}
