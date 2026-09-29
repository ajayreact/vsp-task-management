import { PageHeader } from '@/components/admin/page-header';
import { RecruiterTaskForm, type RecruiterTaskFormOptions } from '@/components/recruiter-operations/recruiter-task-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface EditableTask {
    id: number;
    title: string;
    description: string | null;
    instructions: string | null;
    work_type: string;
    priority: string;
    due_at: string | null;
    target_count: number | null;
}

export default function EditRecruiterTask({ task, ...options }: RecruiterTaskFormOptions & { task: EditableTask }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Recruiter Tasks', href: '/recruiter/tasks' },
        { title: task.title, href: `/recruiter/tasks/${task.id}` },
        { title: 'Edit', href: `/recruiter/tasks/${task.id}/edit` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${task.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Edit recruiter task" description="Status and the assigned recruiter are changed from the task page." />

                <RecruiterTaskForm
                    options={options}
                    action={`/recruiter/tasks/${task.id}`}
                    method="put"
                    submitLabel="Save changes"
                    cancelUrl={`/recruiter/tasks/${task.id}`}
                    initial={{
                        title: task.title,
                        description: task.description ?? '',
                        instructions: task.instructions ?? '',
                        work_type: task.work_type,
                        priority: task.priority,
                        due_at: task.due_at ?? '',
                        target_count: task.target_count !== null ? String(task.target_count) : '',
                        assigned_employee_id: '',
                    }}
                />
            </div>
        </RecruiterLayout>
    );
}
