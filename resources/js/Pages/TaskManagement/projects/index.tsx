import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';

interface ProjectRow {
    id: number;
    name: string;
    code: string;
    status: string;
    status_label: string;
    company_name: string;
    manager_name: string | null;
    due_date: string | null;
    tasks_count: number;
    members_count: number;
}

interface Props {
    projects: Paginated<ProjectRow>;
    filters: { client: number | null; status: string | null };
    companies: { id: number; name: string }[];
    statuses: Option[];
    can: { manage: boolean };
}

const ALL = 'all';

const statusTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    planning: 'info',
    active: 'success',
    on_hold: 'warning',
    completed: 'success',
    cancelled: 'neutral',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Projects', href: '/tasks/projects' },
];

export default function ProjectIndex({ projects, filters, companies, statuses, can }: Props) {
    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/projects',
            {
                client: filters.client ?? undefined,
                status: filters.status || undefined,
                per_page: projects.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Projects" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Projects"
                    description="A body of work for a client. Tasks are raised against a project."
                    action={
                        can.manage ? (
                            <Button asChild>
                                <Link href="/tasks/projects/create">
                                    <Plus /> New project
                                </Link>
                            </Button>
                        ) : undefined
                    }
                    toolbar={
                        <div className="flex flex-wrap items-center gap-3">
                            <Select
                                value={filters.client ? String(filters.client) : ALL}
                                onValueChange={(value) => apply({ client: value === ALL ? null : value })}
                            >
                                <SelectTrigger className="w-56" aria-label="Filter by client">
                                    <SelectValue placeholder="All clients" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All clients</SelectItem>
                                    {companies.map((company) => (
                                        <SelectItem key={company.id} value={String(company.id)}>
                                            {company.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? null : value })}>
                                <SelectTrigger className="w-48" aria-label="Filter by status">
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
                            page={projects}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/tasks/projects/export"
                            exportParams={{
                                client: filters.client,
                                status: filters.status,
                            }}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Project</TableHead>
                                <TableHead>Client</TableHead>
                                <TableHead>Manager</TableHead>
                                <TableHead>Team</TableHead>
                                <TableHead>Tasks</TableHead>
                                <TableHead>Due</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {projects.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-muted-foreground py-10 text-center">
                                        No projects match.
                                    </TableCell>
                                </TableRow>
                            )}

                            {projects.data.map((project) => (
                                <TableRow key={project.id}>
                                    <TableCell>
                                        <Link href={`/tasks/projects/${project.id}`} className="font-medium hover:underline">
                                            {project.name}
                                        </Link>
                                        <div className="text-muted-foreground text-xs">{project.code}</div>
                                    </TableCell>
                                    <TableCell className="text-sm">{project.company_name}</TableCell>
                                    <TableCell className="text-sm">
                                        {project.manager_name ?? <span className="text-muted-foreground">—</span>}
                                    </TableCell>
                                    <TableCell className="text-sm">{project.members_count}</TableCell>
                                    <TableCell className="text-sm">{project.tasks_count}</TableCell>
                                    <TableCell className="text-sm">
                                        {project.due_date ? new Date(project.due_date).toLocaleDateString() : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={statusTone[project.status] ?? 'neutral'}>{project.status_label}</Badge>
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
