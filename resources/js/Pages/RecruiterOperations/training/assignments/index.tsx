import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { TrainingAssignmentTable, type TrainingAssignmentRow } from '@/components/recruiter-operations/training/training-assignment-table';
import { TrackSwitcher, type TrackOption } from '@/components/recruiter-operations/training/training-tracks';
import { TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';

interface Props {
    assignments: Paginated<TrainingAssignmentRow>;
    filters: { track: string; recruiter: number | null; course: number | null; status: string; due_from: string; due_to: string };
    recruiters: { id: number; label: string }[];
    courses: { id: number; label: string }[];
    tracks: TrackOption[];
    statuses: Option[];
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'Assignments', href: '/recruiter/training/assignments' },
];

export default function TrainingAssignments({ assignments, filters, recruiters, courses, tracks, statuses }: Props) {
    const current = {
        track: filters.track || undefined,
        recruiter: filters.recruiter ?? undefined,
        course: filters.course ?? undefined,
        status: filters.status || undefined,
        due_from: filters.due_from || undefined,
        due_to: filters.due_to || undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/recruiter/training/assignments',
            { ...current, per_page: assignments.per_page, ...changes },
            { preserveState: true, replace: true },
        );
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Training Assignments" />

            <div className="flex max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />
                <TrackSwitcher
                    tracks={tracks}
                    value={filters.track}
                    allLabel="All tracks"
                    onChange={(track) => apply({ track: track || null, course: null, page: null })}
                />
                <DataTableCard
                    title="Training Assignments"
                    description="Who has been given which course version. Assignments stay on their version when a course is updated."
                    action={
                        <Button asChild className="w-full shrink-0 sm:w-auto">
                            <Link
                                href={
                                    filters.track
                                        ? `/recruiter/training/assignments/create?track=${filters.track}`
                                        : '/recruiter/training/assignments/create'
                                }
                            >
                                <UserPlus /> Assign training
                            </Link>
                        </Button>
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
                            <Select
                                value={filters.recruiter ? String(filters.recruiter) : ALL}
                                onValueChange={(value) => apply({ recruiter: value === ALL ? null : value })}
                            >
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
                            <Select
                                value={filters.course ? String(filters.course) : ALL}
                                onValueChange={(value) => apply({ course: value === ALL ? null : value })}
                            >
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
                            <div className="grid gap-1">
                                <Label htmlFor="due_from" className="text-muted-foreground text-xs">
                                    Due from
                                </Label>
                                <Input
                                    id="due_from"
                                    type="date"
                                    value={filters.due_from}
                                    onChange={(event) => apply({ due_from: event.target.value || null })}
                                    className="w-full lg:w-40"
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="due_to" className="text-muted-foreground text-xs">
                                    Due to
                                </Label>
                                <Input
                                    id="due_to"
                                    type="date"
                                    value={filters.due_to}
                                    onChange={(event) => apply({ due_to: event.target.value || null })}
                                    className="w-full lg:w-40"
                                />
                            </div>
                        </div>
                    }
                    footer={
                        <Pagination
                            page={assignments}
                            leading={<EntriesSelect value={assignments.per_page} onChange={(perPage) => apply({ per_page: perPage })} />}
                        />
                    }
                >
                    <TrainingAssignmentTable rows={assignments.data} withActions emptyMessage="No training assignments match these filters." />
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
