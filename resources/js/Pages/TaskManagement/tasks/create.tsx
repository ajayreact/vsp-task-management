import { PageHeader } from '@/components/admin/page-header';
import { TaskCreateForm } from '@/components/tasks/task-create-form';
import { type TaskFormOptions } from '@/components/tasks/task-form';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'New', href: '/tasks/create' },
];

export default function CreateTask({
    defaultProjectId,
    assignableEmployees = [],
    can,
    ...options
}: TaskFormOptions & {
    defaultProjectId: number | null;
    can?: {
        assign?: boolean;
        manageChecklist?: boolean;
        manageSubtasks?: boolean;
        attachFiles?: boolean;
    };
}) {
    const canAssign = can?.assign ?? false;

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="New task" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="New task"
                    description={
                        canAssign
                            ? 'Describe the work, add checklist items, subtasks, and working files, then create the task.'
                            : 'Describe the work and add any supporting details before saving the draft.'
                    }
                />

                <TaskCreateForm
                    options={{ ...options, assignableEmployees, canAssign }}
                    submitLabel={canAssign ? 'Create task' : 'Create draft'}
                    cancelUrl="/tasks"
                    showAssignee={canAssign}
                    canManageChecklist={can?.manageChecklist ?? false}
                    canManageSubtasks={can?.manageSubtasks ?? false}
                    canAttachFiles={can?.attachFiles ?? true}
                    initial={{
                        tm_project_id: defaultProjectId ? String(defaultProjectId) : '',
                        department_id: '',
                        title: '',
                        description: '',
                        requirement: '',
                        type: 'design',
                        priority: 'normal',
                        estimated_hours: '',
                        due_at: '',
                        assigned_employee_id: '',
                    }}
                />
            </div>
        </TaskLayout>
    );
}
