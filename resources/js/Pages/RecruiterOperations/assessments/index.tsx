import { PageHeader } from '@/components/admin/page-header';
import {
    AssessmentSubNav,
    AssignmentStatusBadge,
    ResultBadge,
    formatDate,
    type LearnerAssignment,
} from '@/components/recruiter-operations/assessments/assessment-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';

interface Props {
    hasEmployeeProfile: boolean;
    counts: { assigned: number; open: number; overdue: number; pending_review: number; passed: number; failed: number } | null;
    assignments: LearnerAssignment[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
];

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-3.5 py-3">
            <div className="text-foreground text-2xl font-semibold tabular-nums">{value}</div>
            <div className="text-muted-foreground text-xs">{label}</div>
        </div>
    );
}

export default function MyAssessments({ hasEmployeeProfile, counts, assignments }: Props) {
    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="My Quizzes" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Assessments" description="Quizzes assigned to you. Your answers are scored on the server when you submit." />
                <AssessmentSubNav />

                {!hasEmployeeProfile && (
                    <Card>
                        <CardContent className="text-muted-foreground py-8 text-center text-sm">Quizzes are available once your account has an employee profile.</CardContent>
                    </Card>
                )}

                {counts && (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <Stat label="To take" value={counts.open} />
                        <Stat label="Overdue" value={counts.overdue} />
                        <Stat label="Awaiting review" value={counts.pending_review} />
                        <Stat label="Passed" value={counts.passed} />
                        <Stat label="Not passed" value={counts.failed} />
                    </div>
                )}

                {hasEmployeeProfile && assignments.length === 0 && (
                    <Card>
                        <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-10 text-center text-sm">
                            <ClipboardCheck className="size-8 opacity-50" />
                            No quizzes have been assigned to you yet.
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    {assignments.map((assignment) => (
                        <Card key={assignment.id}>
                            <CardContent className="space-y-3 pt-6">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <Link href={`/recruiter/assessments/${assignment.id}`} className="font-semibold hover:underline">
                                            {assignment.assessment.title}
                                        </Link>
                                        <div className="text-muted-foreground text-xs">
                                            {assignment.version.question_count} questions · Pass mark {assignment.version.passing_percentage}%
                                            {assignment.version.time_limit_minutes ? ` · ${assignment.version.time_limit_minutes} min` : ' · No time limit'}
                                            {assignment.from_training ? ' · From training' : ''}
                                        </div>
                                    </div>
                                    <div className="flex gap-1.5">
                                        <AssignmentStatusBadge status={assignment.status} label={assignment.status_label} />
                                        <ResultBadge result={assignment.result} label={assignment.result_label} />
                                    </div>
                                </div>
                                <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                    <span>Due {formatDate(assignment.due_at)}</span>
                                    {assignment.eligibility && (
                                        <span>
                                            Attempts {assignment.eligibility.attempts_used} of {assignment.eligibility.attempts_allowed}
                                        </span>
                                    )}
                                </div>
                                <Button asChild size="sm" variant={assignment.eligibility?.can_start || assignment.eligibility?.active_attempt_id ? 'default' : 'outline'}>
                                    <Link
                                        href={
                                            assignment.eligibility?.active_attempt_id
                                                ? `/recruiter/assessments/attempts/${assignment.eligibility.active_attempt_id}`
                                                : `/recruiter/assessments/${assignment.id}`
                                        }
                                    >
                                        {assignment.eligibility?.active_attempt_id ? 'Continue attempt' : assignment.eligibility?.can_start ? 'Open quiz' : 'View results'}
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </RecruiterLayout>
    );
}
