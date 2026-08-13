import { PageHeader } from '@/components/admin/page-header';
import { TaskForm, type TaskFormOptions } from '@/components/tasks/task-form';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'New', href: '/tasks/create' },
];

export default function CreateTask({ defaultProjectId, ...options }: TaskFormOptions & { defaultProjectId: number | null }) {
    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="New task" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader title="New task" description="Describe the work. Who does it is decided next." />

                <TaskForm
                    options={options}
                    action="/tasks"
                    method="post"
                    submitLabel="Create draft"
                    cancelUrl="/tasks"
                    initial={{
                        tm_project_id: defaultProjectId ? String(defaultProjectId) : '',
                        department_id: '',
                        title: '',
                        description: '',
                        type: 'design',
                        priority: 'normal',
                        estimated_hours: '',
                        due_at: '',
                    }}
                />
            </div>
        </TaskLayout>
    );
}
