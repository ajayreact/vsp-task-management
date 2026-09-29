import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

interface Props {
    assessment: { id: number; title: string; description: string | null };
}

export default function EditAssessment({ assessment }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: 'Manage', href: '/recruiter/assessments/manage' },
        { title: assessment.title, href: `/recruiter/assessments/manage/${assessment.id}` },
        { title: 'Edit', href: `/recruiter/assessments/manage/${assessment.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({ title: assessment.title, description: assessment.description ?? '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(`/recruiter/assessments/manage/${assessment.id}`);
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${assessment.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Edit quiz" description="Title and description are not versioned. Questions and settings change through versions." />

                <form onSubmit={submit} className="max-w-3xl space-y-6">
                    <Card>
                        <CardContent className="space-y-4 pt-6">
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

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/recruiter/assessments/manage/${assessment.id}`}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </RecruiterLayout>
    );
}
