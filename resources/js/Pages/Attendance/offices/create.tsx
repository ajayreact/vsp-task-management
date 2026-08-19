import { OfficeLocationForm } from '@/components/admin/office-location-form';
import { PageHeader } from '@/components/admin/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/admin/attendance' },
    { title: 'Office Locations', href: '/admin/attendance/offices' },
    { title: 'Add', href: '/admin/attendance/offices/create' },
];

export default function CreateOfficeLocation() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add office location" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Add office location"
                    description="Set the office name, address, and GPS boundary for future attendance check-in."
                />

                <OfficeLocationForm
                    action="/admin/attendance/offices"
                    method="post"
                    submitLabel="Create office location"
                    initial={{
                        name: '',
                        address: '',
                        latitude: '',
                        longitude: '',
                        allowed_gps_radius_meters: '100',
                        late_check_in_time: '09:30',
                        network_verification_enabled: false,
                        authorized_public_ips_text: '',
                        is_active: true,
                    }}
                />
            </div>
        </AppLayout>
    );
}
