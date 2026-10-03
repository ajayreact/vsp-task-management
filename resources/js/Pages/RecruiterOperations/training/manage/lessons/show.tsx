import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { TrainingAudioPlayer, type TrainingAudioConfig } from '@/components/recruiter-operations/training/training-audio-player';
import {
    DEFAULT_LANGUAGE,
    resolveLessonLanguage,
    TrainingLanguageSelect,
    useTrainingLanguage,
} from '@/components/recruiter-operations/training/training-language';
import { TrainingLessonContent, type TrainingLessonData } from '@/components/recruiter-operations/training/training-lesson-content';
import { ContentStatusBadge, formatMinutes, RequiredBadge } from '@/components/recruiter-operations/training/training-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FilePenLine, Info, Pencil, Trash2 } from 'lucide-react';

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

    const [chosenLanguage, chooseLanguage] = useTrainingLanguage();
    const { shown, fellBack } = resolveLessonLanguage(lesson.languages, chosenLanguage);
    const chosen = lesson.languages.find((item) => item.code === chosenLanguage);
    const language = shown?.code ?? DEFAULT_LANGUAGE;

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Preview: ${lesson.title}`} />

            <div className="flex max-w-4xl min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
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
                            <Button asChild size="sm" variant={can.update ? 'default' : 'outline'}>
                                <Link href={`/recruiter/training/manage/lessons/${lesson.id}/content?language=${language}`}>
                                    <FilePenLine /> {can.update ? 'Edit content' : 'View content'}
                                </Link>
                            </Button>
                            {can.update && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={`/recruiter/training/manage/lessons/${lesson.id}/edit`}>
                                        <Pencil /> Lesson settings
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

                <div className="flex flex-wrap items-center gap-3">
                    <TrainingLanguageSelect languages={lesson.languages} value={chosenLanguage} onChange={chooseLanguage} />
                    {shown && (
                        <Badge variant={shown.review_status === 'approved' && !shown.outdated ? 'success' : 'secondary'}>
                            {shown.label}: {shown.review_label ?? 'Not yet stored as sections'}
                        </Badge>
                    )}
                </div>
                {fellBack && chosen && (
                    <p className="text-muted-foreground -mt-3 flex items-start gap-2 text-sm">
                        <Info className="mt-0.5 size-4 shrink-0" /> No {chosen.label} translation yet. Recruiters who choose {chosen.label} see the
                        English lesson.
                    </p>
                )}

                <TrainingAudioPlayer key={`${lesson.id}-${language}`} audio={audio} language={language} />

                <TrainingLessonContent lesson={lesson} language={shown} />
            </div>
        </RecruiterLayout>
    );
}
