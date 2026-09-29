import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { TrainingAssignmentTable, type TrainingAssignmentRow } from '@/components/recruiter-operations/training/training-assignment-table';
import { TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, router } from '@inertiajs/react';

interface Props {
    assignments: Paginated<TrainingAssignmentRow>;
    filters: { recruiter: number | null; course: number | null; status: string };
    recruiters: { id: number; label: string }[];
    courses: { id: number; label: string }[];
    statuses: Option[];
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'Team Progress', href: '/recruiter/training/team' },
];

export default function TrainingTeam({ assignments, filters, recruiters, courses, statuses }: Props) {
    const current = {
        recruiter: filters.recruiter ?? undefined,
        course: filters.course ?? undefined,
        status: filters.status || undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/training/team', { ...current, per_page: assignments.per_page, ...changes }, { preserveState: true, replace: true });
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Team Training Progress" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />
                <DataTableCard
                    title="Team Training Progress"
                    description="Learning progress for each assigned course. Not a performance score."
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-3 lg:flex lg:flex-wrap">
                            <Select value={filters.recruiter ? String(filters.recruiter) : ALL} onValueChange={(value) => apply({ recruiter: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-56" aria-label="Filter by recruiter">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All recruiters</SelectItem>
                                    {recruiters.map((recruiter) => (
                                        <SelectItem key={recruiter.id} value={String(recruiter.id)}>
                                            {recruiter.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.course ? String(filters.course) : ALL} onValueChange={(value) => apply({ course: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-64" aria-label="Filter by course">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All courses</SelectItem>
                                    {courses.map((course) => (
                                        <SelectItem key={course.id} value={String(course.id)}>
                                            {course.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-44" aria-label="Filter by status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Any status</SelectItem>
                                    {statuses.map((status) => (
                                        <SelectItem key={status.value} value={status.value}>
                                            {status.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                    footer={
                        <Pagination page={assignments} leading={<EntriesSelect value={assignments.per_page} onChange={(perPage) => apply({ per_page: perPage })} />} />
                    }
                >
                    <TrainingAssignmentTable rows={assignments.data} emptyMessage="No training assignments match these filters." />
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
