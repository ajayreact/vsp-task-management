import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Row {
    id: number;
    employee_name: string;
    period_start: string;
    period_end: string;
    total_hours: string;
    status: string;
    status_label: string;
}

interface Props {
    timesheets: Paginated<Row>;
    filters: { scope: string; status: string | null };
    statuses: Option[];
    can: { review: boolean };
}

const ALL = 'all';

const statusTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    draft: 'neutral',
    submitted: 'warning',
    approved: 'success',
    rejected: 'danger',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Timesheets', href: '/tasks/timesheets' },
];

export default function TimesheetIndex({ timesheets, filters, statuses, can }: Props) {
    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/timesheets',
            {
                scope: filters.scope,
                status: filters.status || undefined,
                per_page: timesheets.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Timesheets" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Timesheets"
                    description="Weekly rollups of timer and manual entries. Submit yours; managers approve the rest."
                    toolbar={
                        <div className="flex flex-wrap items-center gap-3">
                            {can.review && (
                                <Select value={filters.scope} onValueChange={(value) => apply({ scope: value })}>
                                    <SelectTrigger className="w-40" aria-label="Scope">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="mine">Mine</SelectItem>
                                        <SelectItem value="team">Team</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                            <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? null : value })}>
                                <SelectTrigger className="w-44" aria-label="Status">
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
                    }
                    footer={
                        <DataTableFooter
                            page={timesheets}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/tasks/timesheets/export"
                            exportParams={{
                                scope: filters.scope,
                                status: filters.status,
                            }}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Week</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Hours</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {timesheets.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-muted-foreground py-10 text-center">
                                        No timesheets yet. Start a timer or log time on a task.
                                    </TableCell>
                                </TableRow>
                            )}
                            {timesheets.data.map((sheet) => (
                                <TableRow key={sheet.id}>
                                    <TableCell>
                                        <Link href={`/tasks/timesheets/${sheet.id}`} className="font-medium hover:underline">
                                            {sheet.period_start} – {sheet.period_end}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{sheet.employee_name}</TableCell>
                                    <TableCell>{sheet.total_hours} h</TableCell>
                                    <TableCell>
                                        <Badge variant={statusTone[sheet.status] ?? 'neutral'}>{sheet.status_label}</Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </TaskLayout>
    );
}
