import { PageHeader } from '@/components/admin/page-header';
import { TaskForm, type TaskFormOptions } from '@/components/tasks/task-form';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface TaskProps {
    id: number;
    tm_project_id: number;
    department_id: number | null;
    title: string;
    description: string | null;
    type: string;
    priority: string;
    estimated_hours: string | null;
    due_at: string | null;
}

export default function EditTask({ task, ...options }: TaskFormOptions & { task: TaskProps }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: task.title, href: `/tasks/${task.id}` },
        { title: 'Edit', href: `/tasks/${task.id}/edit` },
    ];

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${task.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Edit task"
                    description="Status and assignment are changed from the task page, so that every move goes through the workflow."
                />

                <TaskForm
                    options={options}
                    action={`/tasks/${task.id}`}
                    method="put"
                    submitLabel="Save changes"
                    cancelUrl={`/tasks/${task.id}`}
                    initial={{
                        tm_project_id: String(task.tm_project_id),
                        department_id: task.department_id ? String(task.department_id) : '',
                        title: task.title,
                        description: task.description ?? '',
                        type: task.type,
                        priority: task.priority,
                        estimated_hours: task.estimated_hours ?? '',
                        due_at: task.due_at ?? '',
                    }}
                />
            </div>
        </TaskLayout>
    );
}
