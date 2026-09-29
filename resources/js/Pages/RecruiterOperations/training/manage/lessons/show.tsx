import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { TrainingAudioPlayer, type TrainingAudioConfig } from '@/components/recruiter-operations/training/training-audio-player';
import { TrainingLessonContent, type TrainingLessonData } from '@/components/recruiter-operations/training/training-lesson-content';
import { ContentStatusBadge, RequiredBadge, formatMinutes } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';

interface Props {
    lesson: TrainingLessonData;
    version: { id: number; label: string; status: string; course: { id: number; title: string } };
    audio: TrainingAudioConfig;
    can: { update: boolean; delete: boolean };
}

const STATUS_LABEL: Record<string, string> = { draft: 'Draft', published: 'Published', archived: 'Archived' };

export default function PreviewTrainingLesson({ lesson, version, audio, can }: Props) {
    const courseUrl = `/recruiter/training/manage/courses/${version.course.id}?version=${version.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: 'Manage', href: '/recruiter/training/manage' },
        { title: version.course.title, href: courseUrl },
        { title: lesson.title, href: `/recruiter/training/manage/lessons/${lesson.id}` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Preview: ${lesson.title}`} />

            <div className="flex min-w-0 max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={lesson.title}
                    description={`Preview · ${version.course.title} ${version.label} · ${lesson.content_type_label}${lesson.duration_minutes ? ` · ${formatMinutes(lesson.duration_minutes)}` : ''}`}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <ContentStatusBadge status={version.status} label={STATUS_LABEL[version.status] ?? version.status} />
                            <RequiredBadge required={lesson.is_required} />
                            <Button asChild size="sm" variant="outline">
                                <Link href={courseUrl}>
                                    <ArrowLeft /> Back to course
                                </Link>
                            </Button>
                            {can.update && (
                                <Button asChild size="sm">
                                    <Link href={`/recruiter/training/manage/lessons/${lesson.id}/edit`}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>
                            )}
                            {can.delete && (
                                <ConfirmDelete
                                    trigger={
                                        <Button size="sm" variant="ghost" className="text-destructive">
                                            <Trash2 /> Delete
                                        </Button>
                                    }
                                    title="Delete this lesson?"
                                    description="The lesson and its file are removed from this draft."
                                    url={`/recruiter/training/manage/lessons/${lesson.id}`}
                                />
                            )}
                        </div>
                    }
                />

                {lesson.description && <p className="text-muted-foreground">{lesson.description}</p>}

                <TrainingAudioPlayer key={lesson.id} audio={audio} />

                <TrainingLessonContent lesson={lesson} />
            </div>
        </RecruiterLayout>
    );
}
