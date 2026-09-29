import { PanelEmpty } from '@/components/admin/dashboard-panel';
import { PageHeader } from '@/components/admin/page-header';
import {
    TrainingProgressBar,
    TrainingStatusBadge,
    TrainingSubNav,
    formatMinutes,
    formatTrainingDate,
} from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { type TrainingCard } from './dashboard';

interface Props {
    hasEmployeeProfile: boolean;
    assignments: TrainingCard[];
    filters: { status: string };
    statuses: Option[];
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'My Training', href: '/recruiter/training/my-training' },
];

export default function MyTraining({ hasEmployeeProfile, assignments, filters, statuses }: Props) {
    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="My Training" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="My Training" description="Every course assigned to you, with your progress." />
                <TrainingSubNav />

                <div className="flex flex-wrap items-center gap-3">
                    <Select
                        value={filters.status || ALL}
                        onValueChange={(value) =>
                            router.get('/recruiter/training/my-training', value === ALL ? {} : { status: value }, { preserveState: true, replace: true })
                        }
                    >
                        <SelectTrigger className="w-full sm:w-52" aria-label="Filter by status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All statuses</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem key={status.value} value={status.value}>
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {assignments.length === 0 && (
                    <PanelEmpty>
                        {!hasEmployeeProfile
                            ? 'Training is assigned to employees. Your account has no employee profile.'
                            : filters.status
                              ? 'No courses with this status.'
                              : 'No training has been assigned to you yet.'}
                    </PanelEmpty>
                )}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {assignments.map((card) => (
                        <Card key={card.id} className="flex flex-col">
                            <CardHeader className="space-y-2">
                                <div className="flex items-start justify-between gap-3">
                                    <span className="text-muted-foreground text-xs">
                                        {card.category ?? 'Training'} · {card.version}
                                    </span>
                                    <TrainingStatusBadge status={card.status} label={card.status_label} />
                                </div>
                                <CardTitle className="text-base leading-snug">{card.course.title}</CardTitle>
                            </CardHeader>
                            <CardContent className="mt-auto space-y-3">
                                {card.course.description && <p className="text-muted-foreground line-clamp-2 text-sm">{card.course.description}</p>}
                                <TrainingProgressBar percent={card.progress_percent} label={`${card.course.title} progress`} />
                                <dl className="text-muted-foreground grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                                    <dt>Lessons</dt>
                                    <dd className="text-foreground text-right">
                                        {card.lessons_completed}/{card.lessons_counted}
                                    </dd>
                                    <dt>Estimated time</dt>
                                    <dd className="text-foreground text-right">{formatMinutes(card.estimated_minutes)}</dd>
                                    <dt>{card.completed_at ? 'Completed' : 'Due'}</dt>
                                    <dd className={card.status === 'overdue' ? 'text-destructive text-right font-medium' : 'text-foreground text-right'}>
                                        {formatTrainingDate(card.completed_at ?? card.due_at)}
                                    </dd>
                                </dl>
                                <Button asChild className="w-full" variant={card.status === 'completed' ? 'outline' : 'default'}>
                                    <Link href={`/recruiter/training/courses/${card.course.id}`}>
                                        {card.status === 'completed' ? 'Review course' : card.progress_percent > 0 || card.status === 'in_progress' ? 'Continue' : 'Start course'}
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </RecruiterLayout>
    );
}
