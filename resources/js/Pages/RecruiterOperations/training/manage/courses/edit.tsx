import { PageHeader } from '@/components/admin/page-header';
import { TrainingCourseForm } from '@/components/recruiter-operations/training/training-course-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    course: { id: number; category_id: number; title: string; description: string | null };
    categories: { id: number; label: string }[];
}

export default function EditTrainingCourse({ course, categories }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: 'Manage', href: '/recruiter/training/manage' },
        { title: course.title, href: `/recruiter/training/manage/courses/${course.id}` },
        { title: 'Edit', href: `/recruiter/training/manage/courses/${course.id}/edit` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${course.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Edit course" description="Title, category and description. Lesson content is changed through a draft version." />
                <TrainingCourseForm
                    categories={categories}
                    initial={{ category_id: String(course.category_id), title: course.title, description: course.description ?? '', estimated_minutes: '' }}
                    action={`/recruiter/training/manage/courses/${course.id}`}
                    method="put"
                    submitLabel="Save course"
                    cancelUrl={`/recruiter/training/manage/courses/${course.id}`}
                    showEstimate={false}
                />
            </div>
        </RecruiterLayout>
    );
}
