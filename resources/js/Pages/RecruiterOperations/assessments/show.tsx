import { PageHeader } from '@/components/admin/page-header';
import {
    AssignmentStatusBadge,
    ResultBadge,
    formatDate,
    formatDateTime,
    type AttemptResult,
    type LearnerAssignment,
} from '@/components/recruiter-operations/assessments/assessment-ui';
import { ConfirmPost } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    assignment: LearnerAssignment;
    attempts: AttemptResult[];
}

export default function AssessmentAssignmentPage({ assignment, attempts }: Props) {
    const eligibility = assignment.eligibility;
    const version = assignment.version;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: assignment.assessment.title, href: `/recruiter/assessments/${assignment.id}` },
    ];

    const startDescription = version.time_limit_minutes
        ? `The ${version.time_limit_minutes}-minute timer starts now and keeps running even if you close the page. Unanswered questions score 0.`
        : 'Your answers are saved as you go. Unanswered questions score 0 when you submit.';

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={assignment.assessment.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title={assignment.assessment.title} description={assignment.assessment.description ?? undefined} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>About this quiz</CardTitle>
                            <CardDescription>
                                {version.label} · {version.question_count} questions
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground text-xs">Pass mark</dt>
                                    <dd className="font-medium">{version.passing_percentage}%</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Time limit</dt>
                                    <dd className="font-medium">{version.time_limit_minutes ? `${version.time_limit_minutes} minutes` : 'None'}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Attempts allowed</dt>
                                    <dd className="font-medium">{version.max_attempts}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Due</dt>
                                    <dd className="font-medium">{formatDate(assignment.due_at)}</dd>
                                </div>
                            </dl>
                            {version.instructions && (
                                <div className="bg-muted/40 rounded-lg p-3 text-sm whitespace-pre-line">{version.instructions}</div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Your status</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap gap-1.5">
                                <AssignmentStatusBadge status={assignment.status} label={assignment.status_label} />
                                <ResultBadge result={assignment.result} label={assignment.result_label} />
                            </div>
                            {eligibility && (
                                <p className="text-muted-foreground text-sm">
                                    {eligibility.attempts_used} of {eligibility.attempts_allowed} attempts used.
                                </p>
                            )}
                            {eligibility?.active_attempt_id ? (
                                <Button asChild className="w-full">
                                    <Link href={`/recruiter/assessments/attempts/${eligibility.active_attempt_id}`}>Continue attempt</Link>
                                </Button>
                            ) : eligibility?.can_start ? (
                                <ConfirmPost
                                    trigger={<Button className="w-full">{eligibility.attempts_used > 0 ? 'Start another attempt' : 'Start quiz'}</Button>}
                                    title={`Start attempt ${eligibility.attempts_used + 1} of ${eligibility.attempts_allowed}?`}
                                    description={startDescription}
                                    url={`/recruiter/assessments/${assignment.id}/start`}
                                    confirmLabel="Start"
                                />
                            ) : (
                                eligibility?.reason && <p className="rounded-lg border border-dashed p-3 text-sm">{eligibility.reason}</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Attempts</CardTitle>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Attempt</TableHead>
                                    <TableHead>Started</TableHead>
                                    <TableHead>Submitted</TableHead>
                                    <TableHead>Score</TableHead>
                                    <TableHead>Result</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {attempts.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-muted-foreground py-6 text-center">
                                            No attempts yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {attempts.map((attempt) => (
                                    <TableRow key={attempt.id}>
                                        <TableCell>#{attempt.attempt_number}</TableCell>
                                        <TableCell className="text-sm">{formatDateTime(attempt.started_at)}</TableCell>
                                        <TableCell className="text-sm">
                                            {attempt.status === 'in_progress' ? 'In progress' : formatDateTime(attempt.submitted_at)}
                                            {attempt.auto_submitted && <span className="text-muted-foreground block text-xs">Submitted when time ran out</span>}
                                        </TableCell>
                                        <TableCell className="text-sm">
                                            {attempt.percentage !== null ? `${attempt.percentage}%` : attempt.pending_review ? 'Awaiting review' : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <ResultBadge result={attempt.result} label={attempt.result_label} />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={`/recruiter/assessments/attempts/${attempt.id}`}>{attempt.status === 'in_progress' ? 'Continue' : 'View'}</Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </RecruiterLayout>
    );
}
