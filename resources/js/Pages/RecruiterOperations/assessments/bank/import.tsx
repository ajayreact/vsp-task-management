import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { AssessmentSubNav } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Download, LoaderCircle, Upload } from 'lucide-react';
import { type FormEvent } from 'react';

interface Props {
    limits: { max_rows: number; max_kb: number };
    headers: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
    { title: 'Question Bank', href: '/recruiter/assessments/questions' },
    { title: 'Import', href: '/recruiter/assessments/questions/import' },
];

export default function QuestionImport({ limits, headers }: Props) {
    const { setData, post, processing, errors, progress } = useForm<{ file: File | null }>({ file: null });
    const errorBag = errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/recruiter/assessments/questions/import', { forceFormData: true });
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Import questions" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Import questions from Excel" description="Nothing is saved until you review the preview and confirm." />
                <AssessmentSubNav />

                <div className="grid max-w-5xl gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>1. Download the template</CardTitle>
                            <CardDescription>Fill in the Questions sheet. The Instructions sheet explains every column.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Button asChild variant="outline">
                                <a href="/recruiter/assessments/questions/import/template">
                                    <Download /> Download template (.xlsx)
                                </a>
                            </Button>
                            <ul className="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                                <li>Columns: {headers.join(', ')}.</li>
                                <li>Type is one of: single choice, multiple choice, true/false, short answer.</li>
                                <li>Correct answer is the option letter (A), several letters for multiple choice (A, C), or True/False. Leave it empty for short answer.</li>
                                <li>Add more option columns (Option G, Option H, …) if you need them.</li>
                                <li>The grey rows starting with “EXAMPLE:” are flagged and never imported. Delete or overwrite them.</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>2. Upload the file</CardTitle>
                            <CardDescription>
                                .xlsx only, up to {Math.round(limits.max_kb / 1024)} MB and {limits.max_rows} question rows.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="file">Spreadsheet</Label>
                                    <Input
                                        id="file"
                                        type="file"
                                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                        onChange={(event) => setData('file', event.target.files?.[0] ?? null)}
                                        required
                                    />
                                    <InputError message={errors.file} />
                                    <InputError message={errorBag.batch} />
                                </div>
                                {progress && <p className="text-muted-foreground text-xs">Uploading {progress.percentage}%</p>}
                                <Button type="submit" disabled={processing}>
                                    {processing ? <LoaderCircle className="animate-spin" /> : <Upload />}
                                    Check file
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </RecruiterLayout>
    );
}
