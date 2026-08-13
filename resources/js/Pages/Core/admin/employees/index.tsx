import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

interface EmployeeRow {
    id: number;
    employee_code: string;
    designation: string | null;
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

const statusTone: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    active: 'default',
    on_leave: 'secondary',
    suspended: 'destructive',
    exited: 'outline',
};

export default function EmployeeIndex({ employees, filters, departments, statuses, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    // Debounced so typing does not fire a request per keystroke.
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
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Employees" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Employees"
                    description="Everyone with an internal account. Both modules assign work to the people listed here."
                    action={
                        can.manage && (
                            <Button asChild>
                                <Link href="/admin/employees/create">
                                    <Plus /> Add employee
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="flex flex-wrap gap-3">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search name, email, code or designation"
                        className="max-w-xs"
                        aria-label="Search employees"
                    />

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

                    <Select value={filters.status || ALL} onValueChange={(value) => applyFilter({ status: value === ALL ? null : value })}>
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

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Employee</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Reports to</TableHead>
                                <TableHead>Status</TableHead>
                                {can.manage && <TableHead className="w-24 text-right">Actions</TableHead>}
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
                                            {employee.designation && ` · ${employee.designation}`}
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">{employee.employee_code}</TableCell>
                                    <TableCell>{employee.department?.name ?? '—'}</TableCell>
                                    <TableCell>{employee.manager?.user?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap items-center gap-1">
                                            <Badge variant={statusTone[employee.status] ?? 'outline'}>
                                                {statuses.find((status) => status.value === employee.status)?.label ?? employee.status}
                                            </Badge>
                                            {!employee.user.is_active && <Badge variant="outline">Login disabled</Badge>}
                                        </div>
                                    </TableCell>
                                    {can.manage && (
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/admin/employees/${employee.id}/edit`} aria-label={`Edit ${employee.employee_code}`}>
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <ConfirmDelete
                                                    url={`/admin/employees/${employee.id}`}
                                                    title={`Remove ${employee.user.name}?`}
                                                    description="This deletes the employee profile and its login. Mark them as exited instead if you need to keep their history."
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Remove ${employee.employee_code}`}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Pagination page={employees} />
            </div>
        </AppLayout>
    );
}
