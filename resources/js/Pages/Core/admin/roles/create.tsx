import { PageHeader } from '@/components/admin/page-header';
import { type AbilityGroup, RoleForm } from '@/components/admin/role-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
    { title: 'Add', href: '/admin/roles/create' },
];

export default function CreateRole({ abilities }: { abilities: AbilityGroup[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add role" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader title="Add role" description="Group the permissions a job needs, then assign the role to people." />

                <RoleForm
                    abilities={abilities}
                    action="/admin/roles"
                    method="post"
                    submitLabel="Create role"
                    initial={{ name: '', permissions: [] }}
                />
            </div>
        </AppLayout>
    );
}
