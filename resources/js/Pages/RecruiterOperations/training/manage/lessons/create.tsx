import { PageHeader } from '@/components/admin/page-header';
import { TrainingLessonForm, type TrainingContentTypeOption } from '@/components/recruiter-operations/training/training-lesson-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    contentTypes: TrainingContentTypeOption[];
    maxUploadKilobytes: number;
    version: { id: number; label: string; status: string; course: { id: number; title: string } };
}

export default function CreateTrainingLesson({ contentTypes, maxUploadKilobytes, version }: Props) {
    const courseUrl = `/recruiter/training/manage/courses/${version.course.id}?version=${version.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: 'Manage', href: '/recruiter/training/manage' },
        { title: version.course.title, href: courseUrl },
        { title: 'New lesson', href: `/recruiter/training/manage/versions/${version.id}/lessons/create` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="New lesson" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New lesson" description={`${version.course.title} · draft ${version.label}`} />
                <TrainingLessonForm
                    contentTypes={contentTypes}
                    maxUploadKilobytes={maxUploadKilobytes}
                    initial={{
                        title: '',
                        description: '',
                        content_type: 'text',
                        body: '',
                        duration_minutes: '',
                        is_required: true,
                        external_url: '',
                        file: null,
                    }}
                    action={`/recruiter/training/manage/versions/${version.id}/lessons`}
                    method="post"
                    submitLabel="Add lesson"
                    cancelUrl={courseUrl}
                />
            </div>
        </RecruiterLayout>
    );
}
