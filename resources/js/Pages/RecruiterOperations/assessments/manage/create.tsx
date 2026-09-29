import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { AssessmentSubNav } from '@/components/recruiter-operations/assessments/assessment-ui';
import { VersionSettingsFields, type VersionSettings } from '@/components/recruiter-operations/assessments/version-settings-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

interface Props {
    defaults: { passing_percentage: number; max_attempts: number };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
    { title: 'Manage', href: '/recruiter/assessments/manage' },
    { title: 'New quiz', href: '/recruiter/assessments/manage/create' },
];

export default function CreateAssessment({ defaults }: Props) {
    const { data, setData, post, processing, errors } = useForm<{ title: string; description: string } & VersionSettings>({
        title: '',
        description: '',
        instructions: '',
        passing_percentage: defaults.passing_percentage,
        time_limit_minutes: '',
        max_attempts: defaults.max_attempts,
        randomize_questions: false,
        randomize_options: false,
        show_result: true,
        allow_review: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/recruiter/assessments/manage');
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="New quiz" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New training quiz" description="The quiz starts with a draft Version 1. Add questions, then publish it to assign it." />
                <AssessmentSubNav />

                <form onSubmit={submit} className="max-w-3xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Quiz</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" value={data.title} onChange={(event) => setData('title', event.target.value)} maxLength={200} required />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea id="description" rows={3} value={data.description} onChange={(event) => setData('description', event.target.value)} />
                                <InputError message={errors.description} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Version 1 settings</CardTitle>
                            <CardDescription>Defaults: 70% to pass, 2 attempts, no time limit. These can be changed until the version is published.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <VersionSettingsFields data={data} errors={errors} onChange={(key, value) => setData((current) => ({ ...current, [key]: value }))} />
                        </CardContent>
                    </Card>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Create quiz
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/recruiter/assessments/manage">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </RecruiterLayout>
    );
}
