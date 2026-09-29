import { PageHeader } from '@/components/admin/page-header';
import {
    RecruiterDailyActivityForm,
    type RecruiterDailyActivityFormOptions,
} from '@/components/recruiter-operations/recruiter-daily-activity-form';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Daily Activities', href: '/recruiter/activities' },
    { title: 'Record activity', href: '/recruiter/activities/create' },
];

interface Props extends RecruiterDailyActivityFormOptions {
    defaults: { activity_date: string; recruiter_task_id: string };
}

export default function CreateRecruiterDailyActivity({ defaults, ...options }: Props) {
    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Record activity" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Record activity" description="Log something you worked on. It is added to your own activity log." />

                <RecruiterDailyActivityForm
                    options={options}
                    action="/recruiter/activities"
                    method="post"
                    submitLabel="Save activity"
                    cancelUrl="/recruiter/activities"
                    description={
                        options.minDate
                            ? 'You can record activities for today and the previous 7 days.'
                            : 'Choose the day the work was done.'
                    }
                    initial={{
                        activity_date: defaults.activity_date,
                        activity_type: '',
                        title: '',
                        description: '',
                        start_time: '',
                        end_time: '',
                        quantity: '',
                        recruiter_task_id: defaults.recruiter_task_id,
                        remarks: '',
                    }}
                />
            </div>
        </RecruiterLayout>
    );
}
