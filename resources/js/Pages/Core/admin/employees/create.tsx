import { EmployeeForm, type EmployeeFormOptions } from '@/components/admin/employee-form';
import { PageHeader } from '@/components/admin/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employees', href: '/admin/employees' },
    { title: 'Add', href: '/admin/employees/create' },
];

export default function CreateEmployee(options: EmployeeFormOptions) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add employee" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader title="Add employee" description="Creates the login and the profile together." />

                <EmployeeForm
                    options={options}
                    action="/admin/employees"
                    method="post"
                    submitLabel="Create employee"
                    initial={{
                        name: '',
                        email: '',
                        password: '',
                        password_confirmation: '',
                        employee_code: '',
                        department_id: '',
                        designation_id: '',
                        reporting_to_id: '',
                        office_location_id: '',
                        phone: '',
                        joined_on: '',
                        exited_on: '',
                        status: 'active',
                        work_arrangement: 'office',
                        is_active: true,
                        roles: ['employee'],
                    }}
                />
            </div>
        </AppLayout>
    );
}
