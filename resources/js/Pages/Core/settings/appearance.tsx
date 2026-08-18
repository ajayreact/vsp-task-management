import AppearanceTabs from '@/components/appearance-tabs';
import { SettingsCard } from '@/components/settings/settings-card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Palette } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appearance settings" />

            <SettingsLayout>
                <SettingsCard
                    title="Theme"
                    description="Pick how VSP looks on this device. System follows your OS preference."
                    icon={Palette}
                    tone="fuchsia"
                >
                    <AppearanceTabs />
                </SettingsCard>
            </SettingsLayout>
        </AppLayout>
    );
}
