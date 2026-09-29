import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import {
    AssessmentSubNav,
    AssignmentStatusBadge,
    ResultBadge,
    formatDate,
    type LearnerAssignment,
} from '@/components/recruiter-operations/assessments/assessment-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Trash2, UserPlus } from 'lucide-react';

type AssignmentRow = LearnerAssignment & { can_delete: boolean };

interface Props {
    assignments: Paginated<AssignmentRow>;
    filters: { recruiter: number | null; assessment: number | null; status: string; result: string };
    recruiters: { id: number; label: string }[];
    assessments: { id: number; label: string }[];
    statuses: Option[];
    results: Option[];
    can: { create: boolean };
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
    { title: 'Assignments', href: '/recruiter/assessments/assignments' },
];

export default function AssessmentAssignments({ assignments, filters, recruiters, assessments, statuses, results, can }: Props) {
    const current = {
        recruiter: filters.recruiter ?? undefined,
        assessment: filters.assessment ?? undefined,
        status: filters.status || undefined,
        result: filters.result || undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/assessments/assignments', { ...current, per_page: assignments.per_page, ...changes }, { preserveState: true, replace: true });
    };

    const filterSelect = (value: string, key: string, placeholder: string, options: Option[], width = 'lg:w-48') => (
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
            <Head title="Quiz Assignments" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <AssessmentSubNav />
                <DataTableCard
                    title="Quiz Assignments"
                    description="Who has been given which quiz version. An assignment stays on its version when the quiz is updated."
                    action={
                        can.create && (
                            <Button asChild className="w-full shrink-0 sm:w-auto">
                                <Link href="/recruiter/assessments/assignments/create">
                                    <UserPlus /> Assign quiz
                                </Link>
                            </Button>
                        )
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap">
                            {filterSelect(filters.recruiter ? String(filters.recruiter) : '', 'recruiter', 'All recruiters', toOptions(recruiters), 'lg:w-56')}
                            {filterSelect(filters.assessment ? String(filters.assessment) : '', 'assessment', 'All quizzes', toOptions(assessments), 'lg:w-64')}
                            {filterSelect(filters.status, 'status', 'Any status', statuses, 'lg:w-44')}
                            {filterSelect(filters.result, 'result', 'Any result', results, 'lg:w-44')}
                        </div>
                    }
                    footer={<Pagination page={assignments} leading={<EntriesSelect value={assignments.per_page} onChange={(perPage) => apply({ per_page: perPage })} />} />}
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Recruiter</TableHead>
                                <TableHead>Quiz</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Result</TableHead>
                                <TableHead className="text-right">Attempts</TableHead>
                                <TableHead>Due</TableHead>
                                <TableHead>Assigned</TableHead>
                                <TableHead className="w-12" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {assignments.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-muted-foreground py-10 text-center">
                                        No quiz assignments match these filters.
                                    </TableCell>
                                </TableRow>
                            )}
                            {assignments.data.map((assignment) => (
                                <TableRow key={assignment.id}>
                                    <TableCell className="font-medium">{assignment.employee.name}</TableCell>
                                    <TableCell>
                                        <div>{assignment.assessment.title}</div>
                                        <div className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                            {assignment.version.label}
                                            {assignment.from_training && <Badge variant="outline">Training</Badge>}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <AssignmentStatusBadge status={assignment.status} label={assignment.status_label} />
                                    </TableCell>
                                    <TableCell>
                                        <ResultBadge result={assignment.result} label={assignment.result_label} />
                                    </TableCell>
                                    <TableCell className="text-right text-sm tabular-nums">
                                        {assignment.attempts_count ?? 0} / {assignment.version.max_attempts}
                                    </TableCell>
                                    <TableCell className="text-sm">{formatDate(assignment.due_at)}</TableCell>
                                    <TableCell className="text-sm">
                                        {formatDate(assignment.assigned_at)}
                                        {assignment.assigned_by && <div className="text-muted-foreground text-xs">by {assignment.assigned_by}</div>}
                                    </TableCell>
                                    <TableCell>
                                        {assignment.can_delete && (
                                            <ConfirmDelete
                                                trigger={
                                                    <Button size="icon" variant="ghost" aria-label="Withdraw assignment">
                                                        <Trash2 />
                                                    </Button>
                                                }
                                                title="Withdraw this assignment?"
                                                description="Only assignments with no attempts can be withdrawn."
                                                url={`/recruiter/assessments/assignments/${assignment.id}`}
                                                confirmLabel="Withdraw"
                                            />
                                        )}
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
