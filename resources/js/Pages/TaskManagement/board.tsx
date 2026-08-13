import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { DueDate, PriorityBadge } from '@/components/tasks/task-badges';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface BoardTask {
    id: number;
    title: string;
    type: string;
    priority: string;
    priority_label: string;
    project_name: string;
    department_name: string | null;
    estimated_hours: string | null;
    due_at: string | null;
}

interface Props {
    tasks: Paginated<BoardTask>;
    filters: { department: number | null; mine_only: boolean };
    departments: { id: number; name: string }[];
    can: { claim: boolean };
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Open board', href: '/tasks/board' },
];

export default function OpenBoard({ tasks, filters, departments, can }: Props) {
    const apply = (changes: Record<string, string | number | boolean | null>) => {
        router.get(
            '/tasks/board',
            {
                department: filters.department ?? undefined,
                mine_only: filters.mine_only || undefined,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Open board" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Open board"
                    description="Unclaimed work. Take something and it becomes yours straight away — no acceptance step, because you chose it."
                />

                <div className="flex flex-wrap items-center gap-4">
                    <Select
                        value={filters.department ? String(filters.department) : ALL}
                        onValueChange={(value) => apply({ department: value === ALL ? null : value })}
                    >
                        <SelectTrigger className="w-56" aria-label="Filter by department">
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

                    <div className="flex items-center gap-2">
                        <Switch
                            id="mine_only"
                            checked={filters.mine_only}
                            onCheckedChange={(checked) => apply({ mine_only: checked ? true : null })}
                        />
                        <Label htmlFor="mine_only">My department only</Label>
                    </div>
                </div>

                {tasks.data.length === 0 ? (
                    <div className="text-muted-foreground rounded-xl border border-dashed p-10 text-center text-sm">
                        The board is empty. Everything has been picked up.
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {tasks.data.map((task) => (
                            <Card key={task.id} className="flex flex-col">
                                <CardHeader>
                                    <div className="flex items-start justify-between gap-2">
                                        <CardTitle className="text-base">
                                            <Link href={`/tasks/${task.id}`} className="hover:underline">
                                                {task.title}
                                            </Link>
                                        </CardTitle>
                                        <PriorityBadge priority={task.priority} label={task.priority_label} />
                                    </div>
                                </CardHeader>
                                <CardContent className="flex-1 space-y-1 text-sm">
                                    <p className="text-muted-foreground">{task.project_name}</p>
                                    <p className="text-muted-foreground text-xs">
                                        {task.type}
                                        {task.department_name && ` · ${task.department_name}`}
                                        {task.estimated_hours && ` · ${task.estimated_hours} h`}
                                    </p>
                                    <p className="text-xs">
                                        Due <DueDate value={task.due_at} />
                                    </p>
                                </CardContent>
                                <CardFooter>
                                    <Button
                                        className="w-full"
                                        disabled={!can.claim}
                                        onClick={() => router.post(`/tasks/${task.id}/claim`, {}, { preserveScroll: true })}
                                    >
                                        {can.claim ? 'Claim' : 'Employees only'}
                                    </Button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination page={tasks} />
            </div>
        </TaskLayout>
    );
}
