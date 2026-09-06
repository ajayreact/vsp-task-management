import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

/**
 * Theme switching was removed. The route now redirects to Profile.
 * This page is kept only as a safe fallback if SSR resolves it.
 */
export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appearance settings" />

            <SettingsLayout>
                <p className="text-muted-foreground text-sm">VSP CRM uses Light mode only.</p>
            </SettingsLayout>
        </AppLayout>
    );
}
