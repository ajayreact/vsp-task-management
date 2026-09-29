import { PageHeader } from '@/components/admin/page-header';
import {
    RecruiterDailyActivityForm,
    type RecruiterDailyActivityFormOptions,
} from '@/components/recruiter-operations/recruiter-daily-activity-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface EditableActivity {
    id: number;
    recruiter_name: string | null;
    is_own: boolean;
    activity_date: string;
    activity_type: string;
    title: string;
    description: string | null;
    start_time: string | null;
    end_time: string | null;
    quantity: number | null;
    recruiter_task_id: number | null;
    remarks: string | null;
}

export default function EditRecruiterDailyActivity({ activity, ...options }: RecruiterDailyActivityFormOptions & { activity: EditableActivity }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Daily Activities', href: '/recruiter/activities' },
        { title: activity.title, href: `/recruiter/activities/${activity.id}` },
        { title: 'Edit', href: `/recruiter/activities/${activity.id}/edit` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${activity.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Edit activity"
                    description={activity.is_own ? 'Change an entry in your activity log.' : `Correcting an entry in ${activity.recruiter_name ?? 'a recruiter'}'s activity log.`}
                />

                <RecruiterDailyActivityForm
                    options={options}
                    action={`/recruiter/activities/${activity.id}`}
                    method="put"
                    submitLabel="Save changes"
                    cancelUrl={`/recruiter/activities/${activity.id}`}
                    description={
                        options.minDate ? 'You can change activities from today and the previous 7 days.' : 'The recruiter the activity belongs to does not change.'
                    }
                    initial={{
                        activity_date: activity.activity_date,
                        activity_type: activity.activity_type,
                        title: activity.title,
                        description: activity.description ?? '',
                        start_time: activity.start_time ?? '',
                        end_time: activity.end_time ?? '',
                        quantity: activity.quantity !== null ? String(activity.quantity) : '',
                        recruiter_task_id: activity.recruiter_task_id !== null ? String(activity.recruiter_task_id) : '',
                        remarks: activity.remarks ?? '',
                    }}
                />
            </div>
        </RecruiterLayout>
    );
}
