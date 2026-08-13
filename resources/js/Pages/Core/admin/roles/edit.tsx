import { PageHeader } from '@/components/admin/page-header';
import { type AbilityGroup, RoleForm } from '@/components/admin/role-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface RoleProps {
    id: number;
    name: string;
    label: string;
    is_system: boolean;
    permissions: string[];
}

export default function EditRole({ abilities, role }: { abilities: AbilityGroup[]; role: RoleProps }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Roles', href: '/admin/roles' },
        { title: role.label, href: `/admin/roles/${role.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${role.label}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader title={role.label} description="Changes take effect the next time each holder makes a request." />

                <RoleForm
                    abilities={abilities}
                    action={`/admin/roles/${role.id}`}
                    method="put"
                    submitLabel="Save changes"
                    nameLocked={role.is_system}
                    initial={{ name: role.name, permissions: role.permissions }}
                />
            </div>
        </AppLayout>
    );
}
