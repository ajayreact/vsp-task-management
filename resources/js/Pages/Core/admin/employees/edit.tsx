import { EmployeeForm, type EmployeeFormOptions } from '@/components/admin/employee-form';
import { PageHeader } from '@/components/admin/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface EmployeeProps {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    employee_code: string;
    department_id: number | null;
    designation_id: number | null;
    reporting_to_id: number | null;
    phone: string | null;
    joined_on: string | null;
    exited_on: string | null;
    status: string;
    roles: string[];
}

export default function EditEmployee({ employee, ...options }: EmployeeFormOptions & { employee: EmployeeProps }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Employees', href: '/admin/employees' },
        { title: employee.name, href: `/admin/employees/${employee.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${employee.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader title={employee.name} description={`Employee ${employee.employee_code}`} />

                <EmployeeForm
                    options={options}
                    action={`/admin/employees/${employee.id}`}
                    method="put"
                    submitLabel="Save changes"
                    initial={{
                        name: employee.name,
                        email: employee.email,
                        password: '',
                        password_confirmation: '',
                        employee_code: employee.employee_code,
                        department_id: employee.department_id ? String(employee.department_id) : '',
                        designation_id: employee.designation_id ? String(employee.designation_id) : '',
                        reporting_to_id: employee.reporting_to_id ? String(employee.reporting_to_id) : '',
                        phone: employee.phone ?? '',
                        joined_on: employee.joined_on ?? '',
                        exited_on: employee.exited_on ?? '',
                        status: employee.status,
                        is_active: employee.is_active,
                        roles: employee.roles,
                    }}
                />
            </div>
        </AppLayout>
    );
}
