import { PageHeader } from '@/components/admin/page-header';
import { RecruiterTaskForm, type RecruiterTaskFormOptions } from '@/components/recruiter-operations/recruiter-task-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Recruiter Tasks', href: '/recruiter/tasks' },
    { title: 'New task', href: '/recruiter/tasks/create' },
];

export default function CreateRecruiterTask(options: RecruiterTaskFormOptions) {
    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="New recruiter task" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New recruiter task" description="Assign work directly to a recruiter." />

                <RecruiterTaskForm
                    options={options}
                    action="/recruiter/tasks"
                    method="post"
                    submitLabel="Create and assign"
                    cancelUrl="/recruiter/tasks"
                    showAssignee
                    initial={{
                        title: '',
                        description: '',
                        instructions: '',
                        work_type: '',
                        priority: 'normal',
                        due_at: '',
                        target_count: '',
                        assigned_employee_id: '',
                    }}
                />
            </div>
        </RecruiterLayout>
    );
}
