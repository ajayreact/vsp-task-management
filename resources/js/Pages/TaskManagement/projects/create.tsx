import { PageHeader } from '@/components/admin/page-header';
import { ProjectForm, type ProjectFormOptions } from '@/components/tasks/project-form';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Projects', href: '/tasks/projects' },
    { title: 'New', href: '/tasks/projects/create' },
];

export default function CreateProject(options: ProjectFormOptions) {
    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="New project" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader title="New project" description="Set up a body of work for a company." />

                <ProjectForm
                    options={options}
                    action="/tasks/projects"
                    method="post"
                    submitLabel="Create project"
                    cancelUrl="/tasks/projects"
                    initial={{
                        tm_company_id: '',
                        name: '',
                        code: '',
                        description: '',
                        status: 'planning',
                        start_date: '',
                        due_date: '',
                        manager_employee_id: '',
                        budget_hours: '',
                        members: [],
                    }}
                />
            </div>
        </TaskLayout>
    );
}
