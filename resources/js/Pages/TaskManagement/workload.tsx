import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface Row {
    id: number;
    name: string;
    employee_code: string;
    assigned_hours: number;
    available_hours: number;
    utilisation_pct: number;
    band: string;
}

interface Props {
    week: { start: string; end: string };
    rows: Paginated<Row>;
    filters?: { week: string };
}

const BAND: Record<string, { label: string; variant: 'success' | 'warning' | 'danger' | 'info' | 'neutral' }> = {
    overallocated: { label: 'Over allocated', variant: 'danger' },
    on_track: { label: 'On track', variant: 'success' },
    bench: { label: 'Bench', variant: 'neutral' },
    unavailable: { label: 'Unavailable', variant: 'neutral' },
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Workload', href: '/tasks/workload' },
];

function shiftWeek(start: string, days: number): string {
    const date = new Date(start);
    date.setDate(date.getDate() + days);

    return date.toISOString().slice(0, 10);
}

export default function Workload({ week, rows }: Props) {
    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/workload',
            {
                week: week.start,
                per_page: rows.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Workload" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Workload"
                    description="Assigned task hours against available capacity for the week. Over 110% is over-allocated; under 50% is bench time."
                    toolbar={
                        <div className="flex items-center gap-3">
                            <Button variant="outline" size="icon" onClick={() => apply({ week: shiftWeek(week.start, -7) })} aria-label="Previous week">
                                <ChevronLeft />
                            </Button>
                            <div className="text-sm font-medium">
                                {week.start} – {week.end}
                            </div>
                            <Button variant="outline" size="icon" onClick={() => apply({ week: shiftWeek(week.start, 7) })} aria-label="Next week">
                                <ChevronRight />
                            </Button>
                        </div>
                    }
                    footer={
                        <DataTableFooter
                            page={rows}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/tasks/workload/export"
                            exportParams={{ week: week.start }}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Employee</TableHead>
                                <TableHead>Assigned</TableHead>
                                <TableHead>Available</TableHead>
                                <TableHead>Utilisation</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-muted-foreground py-10 text-center">
                                        No assignable employees.
                                    </TableCell>
                                </TableRow>
                            )}
                            {rows.data.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell>
                                        <div className="font-medium">{row.name}</div>
                                        <div className="text-muted-foreground text-xs">{row.employee_code}</div>
                                    </TableCell>
                                    <TableCell>{row.assigned_hours} h</TableCell>
                                    <TableCell>{row.available_hours} h</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <div className="bg-muted h-2 w-24 overflow-hidden rounded-full">
                                                <div className="bg-primary h-2" style={{ width: `${Math.min(row.utilisation_pct, 100)}%` }} />
                                            </div>
                                            <span className="text-sm">{row.utilisation_pct}%</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={BAND[row.band]?.variant ?? 'neutral'}>{BAND[row.band]?.label ?? row.band}</Badge>
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
