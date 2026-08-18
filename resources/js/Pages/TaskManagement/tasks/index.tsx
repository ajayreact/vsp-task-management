import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { SearchInput } from '@/components/admin/search-input';
import { DueDate, PriorityBadge, StatusBadge } from '@/components/tasks/task-badges';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

export interface TaskRow {
    id: number;
    title: string;
    type: string;
    priority: string;
    priority_label: string;
    status: string;
    status_label: string;
    assignment_mode: string;
    project: { id: number; name: string };
    department_name: string | null;
    assignee_name: string | null;
    due_at: string | null;
}

interface Props {
    tasks: Paginated<TaskRow>;
    filters: {
        scope: string;
        search: string | null;
        project: number | null;
        status: string | null;
        priority: string | null;
        overdue?: boolean;
        completed?: string | null;
    };
    projects: { id: number; name: string }[];
    statuses: Option[];
    priorities: Option[];
    pageTitle: string;
    can: { create: boolean; viewAll: boolean };
}

const ALL = 'all';

export default function TaskIndex({ tasks, filters, projects, statuses, priorities, pageTitle, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(() => apply({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks',
            {
                scope: filters.scope || undefined,
                search: search || undefined,
                project: filters.project ?? undefined,
                status: filters.status || undefined,
                priority: filters.priority || undefined,
                overdue: filters.overdue ? 1 : undefined,
                completed: filters.completed || undefined,
                per_page: tasks.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [{ title: pageTitle, href: '/tasks' }];

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title={pageTitle}
                    description={
                        can.viewAll
                            ? 'Every task across the agency. Switch the scope to narrow it to your own work.'
                            : 'The work assigned to you. Pick up more from the open board.'
                    }
                    action={
                        can.create ? (
                            <Button asChild>
                                <Link href="/tasks/create">
                                    <Plus /> New task
                                </Link>
                            </Button>
                        ) : undefined
                    }
                    toolbar={
                        <div className="flex w-full min-w-max items-center gap-3">
                            {can.viewAll && (
                                <Select value={filters.scope} onValueChange={(value) => apply({ scope: value })}>
                                    <SelectTrigger className="w-44 shrink-0" aria-label="Scope">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All tasks</SelectItem>
                                        <SelectItem value="mine">Assigned to me</SelectItem>
                                        <SelectItem value="unassigned">Unassigned</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}

                            <Select
                                value={filters.project ? String(filters.project) : ALL}
                                onValueChange={(value) => apply({ project: value === ALL ? null : value })}
                            >
                                <SelectTrigger className="w-52 shrink-0" aria-label="Filter by project">
                                    <SelectValue placeholder="All projects" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All projects</SelectItem>
                                    {projects.map((project) => (
                                        <SelectItem key={project.id} value={String(project.id)}>
                                            {project.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? null : value })}>
                                <SelectTrigger className="w-48 shrink-0" aria-label="Filter by status">
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

                            <Select value={filters.priority || ALL} onValueChange={(value) => apply({ priority: value === ALL ? null : value })}>
                                <SelectTrigger className="w-40 shrink-0" aria-label="Filter by priority">
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

                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search by title"
                                aria-label="Search tasks"
                                containerClassName="ml-auto w-56 max-w-none shrink-0"
                            />
                        </div>
                    }
                    footer={
                        <DataTableFooter
                            page={tasks}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/tasks/export"
                            exportParams={{
                                scope: filters.scope,
                                search,
                                project: filters.project,
                                status: filters.status,
                                priority: filters.priority,
                            }}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Task</TableHead>
                                <TableHead>Project</TableHead>
                                <TableHead>Assignee</TableHead>
                                <TableHead>Priority</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Due</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tasks.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                        Nothing here. {can.viewAll ? 'Try a wider filter.' : 'Check the open board for work to pick up.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {tasks.data.map((task) => (
                                <TableRow key={task.id}>
                                    <TableCell>
                                        <Link href={`/tasks/${task.id}`} className="font-medium hover:underline">
                                            {task.title}
                                        </Link>
                                        <div className="text-muted-foreground text-xs">
                                            {task.type}
                                            {task.department_name && ` · ${task.department_name}`}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm">{task.project.name}</TableCell>
                                    <TableCell className="text-sm">
                                        {task.assignee_name ?? <span className="text-muted-foreground">Unassigned</span>}
                                    </TableCell>
                                    <TableCell>
                                        <PriorityBadge priority={task.priority} label={task.priority_label} />
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge status={task.status} label={task.status_label} />
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        <DueDate value={task.due_at} />
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
