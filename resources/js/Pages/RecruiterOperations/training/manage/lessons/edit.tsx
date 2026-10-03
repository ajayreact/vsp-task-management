import { PageHeader } from '@/components/admin/page-header';
import { type TrainingLessonData } from '@/components/recruiter-operations/training/training-lesson-content';
import { TrainingLessonForm, type TrainingContentTypeOption } from '@/components/recruiter-operations/training/training-lesson-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    contentTypes: TrainingContentTypeOption[];
    maxUploadKilobytes: number;
    version: { id: number; label: string; status: string; course: { id: number; title: string } };
    lesson: TrainingLessonData;
}

export default function EditTrainingLesson({ contentTypes, maxUploadKilobytes, version, lesson }: Props) {
    const courseUrl = `/recruiter/training/manage/courses/${version.course.id}?version=${version.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: 'Manage', href: '/recruiter/training/manage' },
        { title: version.course.title, href: courseUrl },
        { title: lesson.title, href: `/recruiter/training/manage/lessons/${lesson.id}` },
        { title: 'Edit', href: `/recruiter/training/manage/lessons/${lesson.id}/edit` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${lesson.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Edit lesson" description={`${version.course.title} · draft ${version.label}`} />
                <TrainingLessonForm
                    contentTypes={contentTypes}
                    maxUploadKilobytes={maxUploadKilobytes}
                    initial={{
                        title: lesson.title,
                        description: lesson.description ?? '',
                        content_type: lesson.content_type,
                        body: lesson.body ?? '',
                        duration_minutes: lesson.duration_minutes ? String(lesson.duration_minutes) : '',
                        is_required: lesson.is_required,
                        external_url: lesson.external_url ?? '',
                        file: null,
                    }}
                    existingFile={lesson.file}
                    structuredContentUrl={
                        lesson.languages.some((language) => language.canonical && language.structured)
                            ? `/recruiter/training/manage/lessons/${lesson.id}/content`
                            : undefined
                    }
                    action={`/recruiter/training/manage/lessons/${lesson.id}`}
                    method="put"
                    submitLabel="Save lesson"
                    cancelUrl={courseUrl}
                />
            </div>
        </RecruiterLayout>
    );
}
