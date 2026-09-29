import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { AssessmentSubNav, ResultBadge, formatDateTime } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface AttemptRow {
    id: number;
    assessment: string;
    version: string;
    recruiter: string;
    attempt_number: number;
    status_label: string;
    submitted_at: string | null;
    auto_submitted: boolean;
    awarded_points: number | null;
    total_points: number;
    percentage: number | null;
    result: string | null;
    result_label: string | null;
}

interface Props {
    attempts: Paginated<AttemptRow>;
    filters: { recruiter: number | null; assessment: number | null; result: string };
    recruiters: { id: number; label: string }[];
    assessments: { id: number; label: string }[];
    results: Option[];
    awaitingReview: number;
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
    { title: 'Results', href: '/recruiter/assessments/results' },
];

export default function AssessmentResults({ attempts, filters, recruiters, assessments, results, awaitingReview }: Props) {
    const current = {
        recruiter: filters.recruiter ?? undefined,
        assessment: filters.assessment ?? undefined,
        result: filters.result || undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/assessments/results', { ...current, per_page: attempts.per_page, ...changes }, { preserveState: true, replace: true });
    };

    const filterSelect = (value: string, key: string, placeholder: string, options: Option[], width: string) => (
        <Select value={value || ALL} onValueChange={(next) => apply({ [key]: next === ALL ? null : next })}>
            <SelectTrigger className={`w-full ${width}`} aria-label={placeholder}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>{placeholder}</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    const toOptions = (rows: { id: number; label: string }[]): Option[] => rows.map((row) => ({ value: String(row.id), label: row.label }));

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Quiz Results" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <AssessmentSubNav />
                <DataTableCard
                    title="Quiz Results"
                    description="Submitted attempts, one row per attempt. Short answers are scored by a reviewer before the result is final."
                    action={
                        awaitingReview > 0 && (
                            <Button variant="outline" onClick={() => apply({ result: 'pending_review' })}>
                                {awaitingReview} awaiting review
                            </Button>
                        )
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-3 lg:flex lg:flex-wrap">
                            {filterSelect(filters.recruiter ? String(filters.recruiter) : '', 'recruiter', 'All recruiters', toOptions(recruiters), 'lg:w-56')}
                            {filterSelect(filters.assessment ? String(filters.assessment) : '', 'assessment', 'All quizzes', toOptions(assessments), 'lg:w-64')}
                            {filterSelect(filters.result, 'result', 'Any result', results, 'lg:w-44')}
                        </div>
                    }
                    footer={<Pagination page={attempts} leading={<EntriesSelect value={attempts.per_page} onChange={(perPage) => apply({ per_page: perPage })} />} />}
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Recruiter</TableHead>
                                <TableHead>Quiz</TableHead>
                                <TableHead className="text-right">Attempt</TableHead>
                                <TableHead className="text-right">Score</TableHead>
                                <TableHead>Result</TableHead>
                                <TableHead>Submitted</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {attempts.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                        No submitted attempts match these filters.
                                    </TableCell>
                                </TableRow>
                            )}
                            {attempts.data.map((attempt) => (
                                <TableRow key={attempt.id}>
                                    <TableCell className="font-medium">
                                        <Link href={`/recruiter/assessments/results/attempts/${attempt.id}`} className="hover:underline">
                                            {attempt.recruiter}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <div>{attempt.assessment}</div>
                                        <div className="text-muted-foreground text-xs">{attempt.version}</div>
                                    </TableCell>
                                    <TableCell className="text-right text-sm tabular-nums">{attempt.attempt_number}</TableCell>
                                    <TableCell className="text-right text-sm tabular-nums">
                                        {attempt.percentage !== null ? `${attempt.percentage}%` : '—'}
                                        <div className="text-muted-foreground text-xs">
                                            {attempt.awarded_points ?? 0} / {attempt.total_points}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <ResultBadge result={attempt.result} label={attempt.result_label ?? attempt.status_label} />
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {formatDateTime(attempt.submitted_at)}
                                        {attempt.auto_submitted && <div className="text-muted-foreground text-xs">Auto-submitted</div>}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
