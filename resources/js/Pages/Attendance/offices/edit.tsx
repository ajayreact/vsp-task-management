import { OfficeLocationForm } from '@/components/admin/office-location-form';
import { PageHeader } from '@/components/admin/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface OfficeProps {
    id: number;
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    allowed_gps_radius_meters: string;
    late_check_in_time: string;
    network_verification_enabled: boolean;
    authorized_public_ips_text: string;
    is_active: boolean;
}

export default function EditOfficeLocation({ office }: { office: OfficeProps }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Attendance', href: '/admin/attendance' },
        { title: 'Office Locations', href: '/admin/attendance/offices' },
        { title: office.name, href: `/admin/attendance/offices/${office.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${office.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title={office.name} description="Update office details or deactivate this location." />

                <OfficeLocationForm
                    action={`/admin/attendance/offices/${office.id}`}
                    method="put"
                    submitLabel="Save changes"
                    initial={{
                        name: office.name,
                        address: office.address,
                        latitude: office.latitude,
                        longitude: office.longitude,
                        allowed_gps_radius_meters: office.allowed_gps_radius_meters,
                        late_check_in_time: office.late_check_in_time,
                        network_verification_enabled: office.network_verification_enabled,
                        authorized_public_ips_text: office.authorized_public_ips_text,
                        is_active: office.is_active,
                    }}
                />
            </div>
        </AppLayout>
    );
}
