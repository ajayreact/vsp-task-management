import { PageHeader } from '@/components/admin/page-header';
import { AttemptSummary, ReviewedQuestionList } from '@/components/recruiter-operations/assessments/attempt-review';
import { type AttemptResult, type Eligibility } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Button } from '@/components/ui/button';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    assessment: { id: number; title: string };
    assignmentId: number;
    version: { label: string; instructions: string | null; time_limit_minutes: number | null; passing_percentage: number };
    result: AttemptResult;
    eligibility: Eligibility;
}

export default function AssessmentResultPage({ assessment, assignmentId, result, eligibility }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: assessment.title, href: `/recruiter/assessments/${assignmentId}` },
        { title: `Attempt ${result.attempt_number}`, href: `/recruiter/assessments/attempts/${result.id}` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`${assessment.title} · Result`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={assessment.title}
                    description="Your result"
                    action={
                        <Button asChild variant={eligibility.can_start ? 'default' : 'outline'}>
                            <Link href={`/recruiter/assessments/${assignmentId}`}>
                                {eligibility.can_start ? `Try again (${eligibility.attempts_left} left)` : 'Back to quiz'}
                            </Link>
                        </Button>
                    }
                />

                <AttemptSummary result={result} />

                {result.show_review ? (
                    <ReviewedQuestionList questions={result.questions} />
                ) : (
                    <p className="text-muted-foreground text-sm">Answer review is not available for this quiz.</p>
                )}
            </div>
        </RecruiterLayout>
    );
}
