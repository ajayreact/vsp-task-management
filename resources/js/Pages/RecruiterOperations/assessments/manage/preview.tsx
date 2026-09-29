import { PageHeader } from '@/components/admin/page-header';
import { ManagerQuestionView, type ManagerQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { ContentStatusBadge } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    assessment: { id: number; title: string; description: string | null };
    version: {
        id: number;
        label: string;
        status: string;
        status_label: string;
        instructions: string | null;
        passing_percentage: number;
        time_limit_minutes: number | null;
        max_attempts: number;
        randomize_questions: boolean;
        randomize_options: boolean;
        show_result: boolean;
        allow_review: boolean;
    };
    questions: ManagerQuestion[];
}

export default function AssessmentPreview({ assessment, version, questions }: Props) {
    const back = `/recruiter/assessments/manage/${assessment.id}?version=${version.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: 'Manage', href: '/recruiter/assessments/manage' },
        { title: assessment.title, href: back },
        { title: `Preview ${version.label}`, href: `/recruiter/assessments/manage/versions/${version.id}/preview` },
    ];
    const totalPoints = questions.reduce((sum, question) => sum + question.points, 0);
    const facts = [
        `${questions.length} questions`,
        `${totalPoints} points`,
        `${version.passing_percentage}% to pass`,
        `${version.max_attempts} ${version.max_attempts === 1 ? 'attempt' : 'attempts'}`,
        version.time_limit_minutes ? `${version.time_limit_minutes} min time limit` : 'No time limit',
        version.randomize_questions ? 'Questions shuffled' : 'Fixed question order',
        version.randomize_options ? 'Options shuffled' : 'Fixed option order',
        version.show_result ? 'Result shown' : 'Result hidden from recruiters',
        version.allow_review ? 'Answer review allowed' : 'No answer review',
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Preview ${assessment.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={`${assessment.title} · ${version.label}`}
                    description="Manager preview with the answer key. Recruiters never see correct answers while taking the quiz."
                    action={
                        <Button asChild variant="outline">
                            <Link href={back}>Back to builder</Link>
                        </Button>
                    }
                />

                <Card>
                    <CardContent className="space-y-3 pt-6">
                        <ContentStatusBadge status={version.status} label={version.status_label} />
                        <p className="text-muted-foreground text-sm">{facts.join(' · ')}</p>
                        {version.instructions && <p className="text-sm whitespace-pre-line">{version.instructions}</p>}
                    </CardContent>
                </Card>

                <div className="space-y-3">
                    {questions.map((question, index) => (
                        <Card key={question.id}>
                            <CardContent className="pt-6">
                                <ManagerQuestionView question={question} number={index + 1} />
                            </CardContent>
                        </Card>
                    ))}
                    {questions.length === 0 && <p className="text-muted-foreground text-sm">This version has no questions yet.</p>}
                </div>
            </div>
        </RecruiterLayout>
    );
}
