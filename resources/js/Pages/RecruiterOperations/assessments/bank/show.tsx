import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { ManagerQuestionView, formatDateTime, type ManagerQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { ConfirmPost } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Copy, Pencil, Trash2 } from 'lucide-react';

interface Props {
    question: ManagerQuestion & {
        created_by: string | null;
        created_at: string;
        in_bank: boolean;
        version: { id: number; label: string; assessment_id: number; assessment_title: string } | null;
        used_in: number;
    };
    can: { update: boolean; delete: boolean; duplicate: boolean; restore: boolean };
}

export default function QuestionShow({ question, can }: Props) {
    const url = `/recruiter/assessments/questions/${question.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        question.version
            ? { title: question.version.assessment_title, href: `/recruiter/assessments/manage/${question.version.assessment_id}?version=${question.version.id}` }
            : { title: 'Question Bank', href: '/recruiter/assessments/questions' },
        { title: 'Question', href: url },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Question" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={question.in_bank ? 'Question Bank item' : `Question in ${question.version?.assessment_title} · ${question.version?.label}`}
                    description={question.archived ? 'Archived. It can no longer be added to quizzes.' : undefined}
                    action={
                        <div className="flex flex-wrap gap-2">
                            {can.update && (
                                <Button asChild>
                                    <Link href={`${url}/edit`}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>
                            )}
                            {can.duplicate && (
                                <ConfirmPost
                                    trigger={
                                        <Button variant="outline">
                                            <Copy /> Duplicate
                                        </Button>
                                    }
                                    title="Duplicate this question?"
                                    description="A copy is added to the Question Bank and opened for editing."
                                    url={`${url}/duplicate`}
                                    confirmLabel="Duplicate"
                                />
                            )}
                            {can.restore && (
                                <ConfirmPost
                                    trigger={<Button variant="outline">Restore</Button>}
                                    title="Restore this question?"
                                    description="It becomes available to add to quizzes again."
                                    url={`${url}/restore`}
                                    confirmLabel="Restore"
                                />
                            )}
                            {can.delete && (
                                <ConfirmDelete
                                    trigger={
                                        <Button variant="outline">
                                            <Trash2 /> {question.in_bank ? 'Archive' : 'Remove'}
                                        </Button>
                                    }
                                    title={question.in_bank ? 'Archive this question?' : 'Remove this question?'}
                                    description={
                                        question.in_bank
                                            ? 'It is hidden from the bank. Quizzes that already use it keep their own copy.'
                                            : 'It is removed from this draft version.'
                                    }
                                    url={url}
                                    confirmLabel={question.in_bank ? 'Archive' : 'Remove'}
                                />
                            )}
                        </div>
                    }
                />

                <Card className="max-w-3xl">
                    <CardContent className="space-y-4 pt-6">
                        <ManagerQuestionView question={question} />
                        <dl className="text-muted-foreground grid gap-1 border-t pt-4 text-xs sm:grid-cols-2">
                            <div>
                                Source: {question.source_label}
                                {question.import_row !== null && ` (row ${question.import_row})`}
                            </div>
                            <div>
                                Added by {question.created_by ?? '—'} on {formatDateTime(question.created_at)}
                            </div>
                            {question.in_bank && <div>Copied into {question.used_in} quiz versions</div>}
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </RecruiterLayout>
    );
}
