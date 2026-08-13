import { ModuleRoadmap, type RoadmapPhase } from '@/components/module-roadmap';
import PortalLayout from '@/layouts/portal-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Portal', href: '/portal' }];

const phases: RoadmapPhase[] = [
    {
        phase: '6',
        title: 'Scoped client access',
        summary:
            'Campaigns, leads and reports for one client only, enforced by a route group, a context middleware, a global query scope and per-model policies.',
    },
];

export default function PortalDashboard() {
    return (
        <PortalLayout breadcrumbs={breadcrumbs}>
            <Head title="Client Portal" />
            <ModuleRoadmap
                heading="Client Portal"
                description="Client-facing view of the CRM. Every query here is scoped to the signed-in user's crm_client_id."
                phases={phases}
            />
        </PortalLayout>
    );
}
