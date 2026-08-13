import { ModuleRoadmap, type RoadmapPhase } from '@/components/module-roadmap';
import CrmLayout from '@/layouts/crm-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'CRM', href: '/crm' }];

const phases: RoadmapPhase[] = [
    {
        phase: '4',
        title: 'Clients, campaigns and lead capture',
        summary: 'Client records, connected ad accounts, campaigns across all four channels, inbound lead capture, assignment and follow-ups.',
    },
    {
        phase: '5',
        title: 'Pipeline and reporting',
        summary: 'Configurable pipelines and stages, stage history, conversion tracking with deal value, and campaign performance reports.',
    },
    {
        phase: '6',
        title: 'Client portal',
        summary: 'Client logins scoped to a single crm_client_id, showing only their own campaigns, leads and reports.',
    },
    {
        phase: '7',
        title: 'Live channel integrations',
        summary: 'Meta Marketing API, Google Ads API, LinkedIn Lead Sync and the WhatsApp Cloud API, with scheduled metric sync.',
    },
];

export default function CrmDashboard() {
    return (
        <CrmLayout breadcrumbs={breadcrumbs}>
            <Head title="CRM" />
            <ModuleRoadmap
                heading="CRM & Campaign Management"
                description="Owns every crm_ table. This module has no dependency on Task Management — no foreign keys, no shared services, no imports."
                phases={phases}
            />
        </CrmLayout>
    );
}
