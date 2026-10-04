import { PageHeader } from '@/components/admin/page-header';
import { TrainingCourseForm } from '@/components/recruiter-operations/training/training-course-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    categories: { id: number; label: string }[];
    tracks: { id: number; label: string }[];
    defaults: { category_id: string; training_track_id: string };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'Manage', href: '/recruiter/training/manage' },
    { title: 'New course', href: '/recruiter/training/manage/courses/create' },
];

export default function CreateTrainingCourse({ categories, tracks, defaults }: Props) {
    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="New training course" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New training course" description="Create the course, then add lessons. Every change is live as soon as you save it." />
                <TrainingCourseForm
                    categories={categories}
                    tracks={tracks}
                    initial={{
                        category_id: defaults.category_id,
                        training_track_id: defaults.training_track_id,
                        title: '',
                        description: '',
                        estimated_minutes: '',
                    }}
                    action="/recruiter/training/manage/courses"
                    method="post"
                    submitLabel="Create course"
                    cancelUrl="/recruiter/training/manage"
                    showEstimate
                />
            </div>
        </RecruiterLayout>
    );
}
