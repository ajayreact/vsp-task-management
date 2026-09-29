import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { SearchInput } from '@/components/admin/search-input';
import {
    RecruiterDueDate,
    RecruiterTaskPriorityBadge,
    RecruiterTaskStatusBadge,
} from '@/components/recruiter-operations/recruiter-task-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

interface RecruiterTaskRow {
    id: number;
    title: string;
    work_type: string;
    work_type_label: string;
    priority: string;
    priority_label: string;
    status: string;
    status_label: string;
    assignee_name: string | null;
    due_at: string | null;
    target_count: number | null;
    achieved_count: number | null;
    completed_at: string | null;
}

interface Filters {
    scope: string;
    search: string;
    recruiter: number | null;
    status: string;
    work_type: string;
    priority: string;
    due_date: string;
}

interface Props {
    tasks: Paginated<RecruiterTaskRow>;
    filters: Filters;
    statuses: Option[];
    priorities: Option[];
    workTypes: Option[];
    recruiters: { id: number; label: string }[];
    pageTitle: string;
    can: { create: boolean; viewTeam: boolean };
}

const ALL = 'all';
const OPEN_STATUSES = ['assigned', 'in_progress', 'on_hold', 'declined'];

export default function RecruiterTaskIndex({ tasks, filters, statuses, priorities, workTypes, recruiters, pageTitle, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(() => apply({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const current = {
        scope: can.viewTeam ? filters.scope : undefined,
        search: search || undefined,
        recruiter: filters.recruiter ?? undefined,
        status: filters.status || undefined,
        work_type: filters.work_type || undefined,
        priority: filters.priority || undefined,
        due_date: filters.due_date || undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/tasks', { ...current, per_page: tasks.per_page, ...changes }, { preserveState: true, replace: true });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: pageTitle, href: '/recruiter/tasks' },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title={pageTitle}
                    description={can.viewTeam ? 'Every recruiter task. Narrow it with the filters.' : 'The recruiter tasks assigned to you.'}
                    action={
                        can.create ? (
                            <Button asChild className="w-full shrink-0 sm:w-auto">
                                <Link href="/recruiter/tasks/create">
                                    <Plus /> New task
                                </Link>
                            </Button>
                        ) : undefined
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center">
                            {can.viewTeam && (
                                <>
                                    <Select value={filters.scope} onValueChange={(value) => apply({ scope: value })}>
                                        <SelectTrigger className="w-full lg:w-40" aria-label="Scope">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All tasks</SelectItem>
                                            <SelectItem value="mine">Assigned to me</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <Select
                                        value={filters.recruiter ? String(filters.recruiter) : ALL}
                                        onValueChange={(value) => apply({ recruiter: value === ALL ? null : value })}
                                    >
                                        <SelectTrigger className="w-full lg:w-52" aria-label="Filter by recruiter">
                                            <SelectValue placeholder="All recruiters" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>All recruiters</SelectItem>
                                            {recruiters.map((recruiter) => (
                                                <SelectItem key={recruiter.id} value={String(recruiter.id)}>
                                                    {recruiter.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </>
                            )}

                            <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-40" aria-label="Filter by status">
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

                            <Select value={filters.work_type || ALL} onValueChange={(value) => apply({ work_type: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-48" aria-label="Filter by work type">
                                    <SelectValue placeholder="Any work type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Any work type</SelectItem>
                                    {workTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={filters.priority || ALL} onValueChange={(value) => apply({ priority: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-36" aria-label="Filter by priority">
                                    <SelectValue placeholder="Any priority" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Any priority</SelectItem>
                                    {priorities.map((priority) => (
                                        <SelectItem key={priority.value} value={priority.value}>
                                            {priority.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Input
                                type="date"
                                value={filters.due_date}
                                onChange={(event) => apply({ due_date: event.target.value || null })}
                                aria-label="Filter by due date"
                                className="w-full lg:w-44"
                            />

                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search by title"
                                aria-label="Search recruiter tasks"
                                containerClassName="w-full min-w-0 lg:ml-auto lg:max-w-xs"
                            />
                        </div>
                    }
                    footer={
                        <DataTableFooter
                            page={tasks}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/recruiter/tasks/export"
                            exportParams={current}
                        />
                    }
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Task</TableHead>
                                {can.viewTeam && <TableHead>Recruiter</TableHead>}
                                <TableHead>Priority</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Due</TableHead>
                                <TableHead className="text-right">Target</TableHead>
                                <TableHead className="text-right">Achieved</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tasks.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={can.viewTeam ? 7 : 6} className="text-muted-foreground py-10 text-center">
                                        No recruiter tasks match.
                                    </TableCell>
                                </TableRow>
                            )}

                            {tasks.data.map((task) => (
                                <TableRow key={task.id}>
                                    <TableCell>
                                        <Link href={`/recruiter/tasks/${task.id}`} className="font-medium hover:underline">
                                            {task.title}
                                        </Link>
                                        <div className="text-muted-foreground text-xs">{task.work_type_label}</div>
                                    </TableCell>
                                    {can.viewTeam && (
                                        <TableCell className="text-sm">
                                            {task.assignee_name ?? <span className="text-muted-foreground">Unassigned</span>}
                                        </TableCell>
                                    )}
                                    <TableCell>
                                        <RecruiterTaskPriorityBadge priority={task.priority} label={task.priority_label} />
                                    </TableCell>
                                    <TableCell>
                                        <RecruiterTaskStatusBadge status={task.status} label={task.status_label} />
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        <RecruiterDueDate value={task.due_at} open={OPEN_STATUSES.includes(task.status)} />
                                    </TableCell>
                                    <TableCell className="text-right text-sm">{task.target_count ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm">{task.achieved_count ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
