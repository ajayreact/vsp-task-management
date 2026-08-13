import { PageHeader } from '@/components/admin/page-header';
import { ProjectForm, type ProjectFormOptions, type ProjectMemberValue } from '@/components/tasks/project-form';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface ProjectProps {
    id: number;
    tm_company_id: number;
    name: string;
    code: string;
    description: string | null;
    status: string;
    start_date: string | null;
    due_date: string | null;
    manager_employee_id: number | null;
    budget_hours: string | null;
    members: ProjectMemberValue[];
}

export default function EditProject({ project, ...options }: ProjectFormOptions & { project: ProjectProps }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Projects', href: '/tasks/projects' },
        { title: project.name, href: `/tasks/projects/${project.id}` },
        { title: 'Edit', href: `/tasks/projects/${project.id}/edit` },
    ];

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${project.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader title="Edit project" description={project.code} />

                <ProjectForm
                    options={options}
                    action={`/tasks/projects/${project.id}`}
                    method="put"
                    submitLabel="Save changes"
                    cancelUrl={`/tasks/projects/${project.id}`}
                    initial={{
                        tm_company_id: String(project.tm_company_id),
                        name: project.name,
                        code: project.code,
                        description: project.description ?? '',
                        status: project.status,
                        start_date: project.start_date ?? '',
                        due_date: project.due_date ?? '',
                        manager_employee_id: project.manager_employee_id ? String(project.manager_employee_id) : '',
                        budget_hours: project.budget_hours ?? '',
                        members: project.members,
                    }}
                />
            </div>
        </TaskLayout>
    );
}
