import { PanelEmpty } from '@/components/admin/dashboard-panel';
import { PageHeader } from '@/components/admin/page-header';
import {
    TrackSwitcher,
    TrainingAssignmentCard,
    type TrackOption,
    type TrainingCard,
} from '@/components/recruiter-operations/training/training-tracks';
import { TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router } from '@inertiajs/react';

interface Props {
    hasEmployeeProfile: boolean;
    assignments: TrainingCard[];
    filters: { status: string; track: string };
    tracks: TrackOption[];
    statuses: Option[];
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'My Training', href: '/recruiter/training/my-training' },
];

export default function MyTraining({ hasEmployeeProfile, assignments, filters, tracks, statuses }: Props) {
    const apply = (changes: Record<string, string>) => {
        const next = { track: filters.track, status: filters.status, ...changes };
        router.get('/recruiter/training/my-training', Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '')), {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="My Training" />

            <div className="flex max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="My Training" description="The courses assigned to you in each training track, with your progress." />
                <TrainingSubNav />

                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    {tracks.length > 1 && <TrackSwitcher tracks={tracks} value={filters.track} onChange={(track) => apply({ track })} />}
                    <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? '' : value })}>
                        <SelectTrigger className="w-full sm:w-52" aria-label="Filter by status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All statuses</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem key={status.value} value={status.value}>
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {assignments.length === 0 && (
                    <PanelEmpty>
                        {!hasEmployeeProfile
                            ? 'Training is assigned to employees. Your account has no employee profile.'
                            : filters.status
                              ? 'No courses with this status.'
                              : 'No training has been assigned to you yet.'}
                    </PanelEmpty>
                )}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {assignments.map((card) => (
                        <TrainingAssignmentCard key={card.id} card={card} />
                    ))}
                </div>
            </div>
        </RecruiterLayout>
    );
}
