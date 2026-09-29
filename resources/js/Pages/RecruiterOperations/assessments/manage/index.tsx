import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { SearchInput } from '@/components/admin/search-input';
import { AssessmentSubNav } from '@/components/recruiter-operations/assessments/assessment-ui';
import { ContentStatusBadge } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Library, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

interface AssessmentRow {
    id: number;
    title: string;
    type_label: string;
    status: string;
    status_label: string;
    current_version: string | null;
    draft_version: string | null;
    versions_count: number;
    assignments_count: number;
}

interface Props {
    assessments: Paginated<AssessmentRow>;
    filters: { status: string; search: string };
    statuses: Option[];
    can: { create: boolean; bank: boolean };
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
    { title: 'Manage', href: '/recruiter/assessments/manage' },
];

export default function ManageAssessments({ assessments, filters, statuses, can }: Props) {
    const current = { status: filters.status || undefined, search: filters.search || undefined };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/assessments/manage', { ...current, per_page: assessments.per_page, ...changes }, { preserveState: true, replace: true });
    };

    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timer = window.setTimeout(() => apply({ search: search.trim() || null }), 350);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Assessments" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <AssessmentSubNav />
                <DataTableCard
                    title="Training Quizzes"
                    description="Each quiz has numbered versions. Only a draft can be edited; publishing freezes it and new assignments use it."
                    action={
                        <div className="flex w-full flex-wrap gap-2 sm:w-auto">
                            {can.bank && (
                                <Button asChild variant="outline">
                                    <Link href="/recruiter/assessments/questions">
                                        <Library /> Question Bank
                                    </Link>
                                </Button>
                            )}
                            {can.create && (
                                <Button asChild>
                                    <Link href="/recruiter/assessments/manage/create">
                                        <Plus /> New quiz
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap">
                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search quizzes"
                                aria-label="Search quizzes"
                                containerClassName="w-full min-w-0 lg:max-w-xs"
                            />
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
                    footer={<Pagination page={assessments} leading={<EntriesSelect value={assessments.per_page} onChange={(perPage) => apply({ per_page: perPage })} />} />}
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Quiz</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Live version</TableHead>
                                <TableHead>Draft</TableHead>
                                <TableHead className="text-right">Versions</TableHead>
                                <TableHead className="text-right">Assigned (live)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {assessments.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                        No quizzes match these filters.
                                    </TableCell>
                                </TableRow>
                            )}
                            {assessments.data.map((assessment) => (
                                <TableRow key={assessment.id}>
                                    <TableCell>
                                        <Link href={`/recruiter/assessments/manage/${assessment.id}`} className="font-medium hover:underline">
                                            {assessment.title}
                                        </Link>
                                        <div className="text-muted-foreground text-xs">{assessment.type_label}</div>
                                    </TableCell>
                                    <TableCell>
                                        <ContentStatusBadge status={assessment.status} label={assessment.status_label} />
                                    </TableCell>
                                    <TableCell className="text-sm">{assessment.current_version ?? '—'}</TableCell>
                                    <TableCell className="text-sm">{assessment.draft_version ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm">{assessment.versions_count}</TableCell>
                                    <TableCell className="text-right text-sm">{assessment.assignments_count}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
