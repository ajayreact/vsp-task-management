import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

interface Props {
    project: {
        id: number;
        name: string;
        code: string;
        description: string | null;
        status: string;
        status_label: string;
        start_date: string | null;
        due_date: string | null;
        budget_hours: string | null;
        company: { id: number; name: string };
        manager_name: string | null;
        members: { id: number; name: string; employee_code: string; project_role: string | null }[];
    };
    taskCounts: Record<string, number>;
    can: { manage: boolean };
}

const date = (value: string | null) => (value ? new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' }) : '—');

export default function ProjectShow({ project, taskCounts, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Projects', href: '/tasks/projects' },
        { title: project.name, href: `/tasks/projects/${project.id}` },
    ];

    const totalTasks = Object.values(taskCounts).reduce((sum, count) => sum + count, 0);

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={project.name} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={project.name}
                    description={`${project.company.name} · ${project.code}`}
                    action={
                        can.manage && (
                            <div className="flex gap-2">
                                <Button asChild>
                                    <Link href={`/tasks/create?project=${project.id}`}>
                                        <Plus /> New task
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={`/tasks/projects/${project.id}/edit`}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>
                                {totalTasks === 0 && (
                                    <ConfirmDelete
                                        url={`/tasks/projects/${project.id}`}
                                        title={`Delete ${project.name}?`}
                                        description="This project has no tasks, so nothing else is affected."
                                        trigger={
                                            <Button variant="outline" className="text-destructive hover:text-destructive">
                                                <Trash2 /> Delete
                                            </Button>
                                        }
                                    />
                                )}
                            </div>
                        )
                    }
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Overview</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-sm whitespace-pre-wrap">
                                {project.description || <span className="text-muted-foreground">No description.</span>}
                            </p>

                            <dl className="grid gap-4 text-sm sm:grid-cols-4">
                                <div>
                                    <dt className="text-muted-foreground text-xs">Status</dt>
                                    <dd className="mt-1">
                                        <Badge variant={project.status === 'active' ? 'default' : 'outline'}>{project.status_label}</Badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Manager</dt>
                                    <dd className="mt-1">{project.manager_name ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Starts</dt>
                                    <dd className="mt-1">{date(project.start_date)}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Due</dt>
                                    <dd className="mt-1">{date(project.due_date)}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Budget</dt>
                                    <dd className="mt-1">{project.budget_hours ? `${project.budget_hours} h` : '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Tasks</dt>
                                    <dd className="mt-1">
                                        <Link href={`/tasks?project=${project.id}`} className="hover:underline">
                                            {totalTasks}
                                        </Link>
                                    </dd>
                                </div>
                            </dl>

                            {totalTasks > 0 && (
                                <div className="flex flex-wrap gap-2 pt-2">
                                    {Object.entries(taskCounts).map(([status, count]) => (
                                        <Badge key={status} variant="secondary">
                                            {status.replace('_', ' ')}: {count}
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Team</CardTitle>
                            <CardDescription>{project.members.length} on this project.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {project.members.length === 0 && <p className="text-muted-foreground text-sm">Nobody assigned yet.</p>}

                            {project.members.map((member) => (
                                <div key={member.id} className="flex items-center justify-between text-sm">
                                    <div>
                                        <div className="font-medium">{member.name}</div>
                                        <div className="text-muted-foreground text-xs">{member.employee_code}</div>
                                    </div>
                                    <Badge variant="outline">{member.project_role}</Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </TaskLayout>
    );
}
