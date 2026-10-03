import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { LessonSections, type LessonLanguage, type LessonSection } from '@/components/recruiter-operations/training/training-lesson-sections';
import { ContentStatusBadge } from '@/components/recruiter-operations/training/training-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowLeft, ArrowUp, ClipboardCheck, Copy, Info, LoaderCircle, Lock, Plus, Save, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Option {
    value: string;
    label: string;
}

interface Props {
    lesson: { id: number; title: string; languages: LessonLanguage[] };
    version: { id: number; label: string; status: string; course: { id: number; title: string } };
    language: string;
    sectionKinds: (Option & { heading: string })[];
    can: { update: boolean };
}

const STATUS_LABEL: Record<string, string> = { draft: 'Draft', published: 'Published', archived: 'Archived' };

function languageBadge(language: LessonLanguage): { text: string; variant: 'success' | 'secondary' | 'outline' } {
    if (!language.structured) {
        return language.canonical && language.available
            ? { text: 'Converted from earlier text', variant: 'outline' }
            : { text: 'Not written yet', variant: 'outline' };
    }

    const text = language.review_label ?? 'Needs review';

    return language.review_status === 'approved' && !language.outdated ? { text, variant: 'success' } : { text, variant: 'secondary' };
}

export default function TrainingLessonContentEditor({ lesson, version, language, sectionKinds, can }: Props) {
    const [active, setActive] = useState(lesson.languages.some((item) => item.code === language) ? language : 'en');
    const current = lesson.languages.find((item) => item.code === active) ?? lesson.languages[0];
    const english = lesson.languages.find((item) => item.canonical) ?? null;
    const previewUrl = `/recruiter/training/manage/lessons/${lesson.id}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Training', href: '/recruiter/training' },
        { title: 'Manage', href: '/recruiter/training/manage' },
        { title: version.course.title, href: `/recruiter/training/manage/courses/${version.course.id}?version=${version.id}` },
        { title: lesson.title, href: previewUrl },
        { title: 'Content', href: `${previewUrl}/content` },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title={`Content: ${lesson.title}`} />

            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={lesson.title}
                    description={`Lesson content · ${version.course.title} ${version.label}. English is the source; translations are made from the approved English lesson.`}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <ContentStatusBadge status={version.status} label={STATUS_LABEL[version.status] ?? version.status} />
                            {version.status === 'draft' && current?.structured && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={`/recruiter/training/manage/review/lessons/${lesson.id}/${current.code}`}>
                                        <ClipboardCheck /> Review
                                    </Link>
                                </Button>
                            )}
                            <Button asChild size="sm" variant="outline">
                                <Link href={previewUrl}>
                                    <ArrowLeft /> Back to preview
                                </Link>
                            </Button>
                        </div>
                    }
                />

                {!can.update && (
                    <div className="text-muted-foreground flex items-start gap-2 rounded-xl border border-dashed p-4 text-sm">
                        <Lock className="mt-0.5 size-4 shrink-0" />
                        This version is {STATUS_LABEL[version.status]?.toLowerCase() ?? version.status} and cannot be changed. Create a new draft
                        version of the course to edit its lessons.
                    </div>
                )}

                <div role="tablist" aria-label="Lesson language" className="flex flex-wrap gap-2">
                    {lesson.languages.map((item) => {
                        const badge = languageBadge(item);

                        return (
                            <button
                                key={item.code}
                                type="button"
                                role="tab"
                                aria-selected={item.code === active}
                                onClick={() => setActive(item.code)}
                                className={cn(
                                    'flex items-center gap-2 rounded-lg border px-3 py-2 text-left text-sm transition-colors',
                                    item.code === active ? 'border-emerald-600 bg-emerald-600/10 font-medium' : 'hover:bg-muted/60',
                                )}
                            >
                                <span>{item.canonical ? item.label : `${item.native_label} (${item.label})`}</span>
                                <Badge variant={badge.variant}>{badge.text}</Badge>
                            </button>
                        );
                    })}
                </div>

                {current && (
                    <LanguageEditor
                        key={current.code}
                        lessonId={lesson.id}
                        language={current}
                        english={english}
                        sectionKinds={sectionKinds}
                        editable={can.update}
                    />
                )}
            </div>
        </RecruiterLayout>
    );
}

type FormValues = { sections: LessonSection[] };

function LanguageEditor({
    lessonId,
    language,
    english,
    sectionKinds,
    editable,
}: {
    lessonId: number;
    language: LessonLanguage;
    english: LessonLanguage | null;
    sectionKinds: (Option & { heading: string })[];
    editable: boolean;
}) {
    const { data, setData, setDefaults, put, processing, errors, isDirty } = useForm<FormValues>({
        sections: language.sections ?? [],
    });
    const [addKind, setAddKind] = useState('content');
    const [previewSource, setPreviewSource] = useState<'this' | 'english'>('this');
    const englishReady = !!english?.available;
    const canTranslate = language.canonical || englishReady;
    const fieldError = (index: number, field: string) => (errors as Record<string, string | undefined>)[`sections.${index}.${field}`];

    const update = (index: number, field: keyof LessonSection, value: string) => {
        setData(
            'sections',
            data.sections.map((section, i) => (i === index ? { ...section, [field]: value } : section)),
        );
    };

    const move = (index: number, offset: number) => {
        const target = index + offset;

        if (target < 0 || target >= data.sections.length) {
            return;
        }

        const next = [...data.sections];
        [next[index], next[target]] = [next[target], next[index]];
        setData('sections', next);
    };

    const remove = (index: number) =>
        setData(
            'sections',
            data.sections.filter((_, i) => i !== index),
        );

    const add = () => {
        const kind = sectionKinds.find((option) => option.value === addKind) ?? sectionKinds[0];
        setData('sections', [...data.sections, { kind: kind.value, heading: kind.heading, body: '' }]);
    };

    const startFromEnglish = () =>
        setData(
            'sections',
            (english?.sections ?? []).map((section) => ({ ...section })),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(`/recruiter/training/manage/lessons/${lessonId}/content/${language.code}`, {
            preserveScroll: true,
            onSuccess: () => setDefaults(),
        });
    };

    const previewSections = previewSource === 'english' ? (english?.sections ?? []) : data.sections.filter((section) => section.body.trim() !== '');

    return (
        <div className="grid gap-6 2xl:grid-cols-2">
            <form onSubmit={submit} className="min-w-0 space-y-4">
                {language.outdated && (
                    <p className="flex items-start gap-2 rounded-lg border border-amber-500/30 bg-amber-50/60 p-3 text-sm text-amber-800 dark:bg-amber-950/20 dark:text-amber-200">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        The English lesson changed after this translation was written. Compare it with the English source, update it, and review it
                        again.
                    </p>
                )}
                {editable && language.canonical && !language.structured && language.available && (
                    <p className="text-muted-foreground flex items-start gap-2 rounded-lg border p-3 text-sm">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        These sections were converted automatically from the lesson&apos;s earlier written content. Saving stores them as structured
                        sections.
                    </p>
                )}
                {!language.canonical && !englishReady && (
                    <p className="text-muted-foreground flex items-start gap-2 rounded-lg border p-3 text-sm">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        Write and review the English lesson first. Translations are made from the approved English content.
                    </p>
                )}

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Sections</CardTitle>
                        <CardDescription>
                            One line is one paragraph. Start a line with &quot;- &quot; for a bullet or &quot;1. &quot; for a numbered step. Lines
                            starting with &quot;|&quot; make a table (put &quot;| --- | --- |&quot; under the first row to make it the header). Wrap
                            words in **double asterisks** for bold. Formatting marks are never read aloud.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {!language.canonical && data.sections.length === 0 && englishReady && editable && (
                            <Button type="button" variant="outline" onClick={startFromEnglish}>
                                <Copy /> Start from the English sections
                            </Button>
                        )}

                        {data.sections.map((section, index) => (
                            <div key={index} className="bg-muted/20 space-y-3 rounded-xl border p-3">
                                <div className="flex flex-wrap items-end gap-2">
                                    <div className="grid w-full gap-1.5 sm:w-52">
                                        <Label htmlFor={`kind-${index}`} className="text-xs">
                                            Section type
                                        </Label>
                                        <Select value={section.kind} onValueChange={(value) => update(index, 'kind', value)} disabled={!editable}>
                                            <SelectTrigger id={`kind-${index}`} className="h-9">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {sectionKinds.map((option) => (
                                                    <SelectItem key={option.value} value={option.value}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid min-w-0 flex-1 gap-1.5">
                                        <Label htmlFor={`heading-${index}`} className="text-xs">
                                            Heading
                                        </Label>
                                        <Input
                                            id={`heading-${index}`}
                                            className="h-9"
                                            value={section.heading}
                                            maxLength={150}
                                            onChange={(event) => update(index, 'heading', event.target.value)}
                                            disabled={!editable}
                                        />
                                    </div>
                                    {editable && (
                                        <div className="flex gap-1">
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="outline"
                                                className="size-9"
                                                onClick={() => move(index, -1)}
                                                disabled={index === 0}
                                                aria-label="Move section up"
                                            >
                                                <ArrowUp />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="outline"
                                                className="size-9"
                                                onClick={() => move(index, 1)}
                                                disabled={index === data.sections.length - 1}
                                                aria-label="Move section down"
                                            >
                                                <ArrowDown />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                className="text-destructive size-9"
                                                onClick={() => remove(index)}
                                                aria-label="Remove section"
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                <InputError message={fieldError(index, 'kind') ?? fieldError(index, 'heading')} />
                                <div className="grid gap-1.5">
                                    <Label htmlFor={`body-${index}`} className="sr-only">
                                        Content
                                    </Label>
                                    <Textarea
                                        id={`body-${index}`}
                                        lang={language.code}
                                        value={section.body}
                                        rows={Math.min(14, Math.max(3, section.body.split('\n').length + 1))}
                                        onChange={(event) => update(index, 'body', event.target.value)}
                                        disabled={!editable}
                                        className="font-[inherit] leading-6"
                                    />
                                    <InputError message={fieldError(index, 'body')} />
                                </div>
                            </div>
                        ))}

                        {editable && canTranslate && (
                            <div className="flex flex-wrap items-center gap-2">
                                <Select value={addKind} onValueChange={setAddKind}>
                                    <SelectTrigger className="h-9 w-60" aria-label="Type of section to add">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sectionKinds.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button type="button" variant="outline" onClick={add}>
                                    <Plus /> Add section
                                </Button>
                            </div>
                        )}
                        <InputError message={errors.sections} />
                        <InputError message={(errors as Record<string, string | undefined>).version} />
                    </CardContent>
                </Card>

                {editable && canTranslate && (
                    <div className="flex flex-wrap items-end gap-3">
                        <Button type="submit" disabled={processing || data.sections.length === 0}>
                            {processing ? <LoaderCircle className="animate-spin" /> : <Save />}
                            Save draft
                        </Button>
                        {isDirty && <span className="text-muted-foreground text-xs">Unsaved changes</span>}
                        {!language.canonical && language.structured && (
                            <ConfirmDelete
                                trigger={
                                    <Button type="button" variant="ghost" className="text-destructive ml-auto">
                                        <Trash2 /> Remove translation
                                    </Button>
                                }
                                title={`Remove the ${language.label} translation?`}
                                description={`Recruiters who choose ${language.label} will see the English lesson until a new translation is added.`}
                                url={`/recruiter/training/manage/lessons/${lessonId}/content/${language.code}`}
                                confirmLabel="Remove"
                            />
                        )}
                    </div>
                )}
                {editable && (
                    <p className="text-muted-foreground text-xs">
                        Saving changes this draft only and sends changed content back to review. Approval happens on the review page; recruiters
                        see the content after the version is published from the course page.
                    </p>
                )}
            </form>

            <section className="min-w-0 space-y-3" aria-label="Preview">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-sm font-semibold">Preview</h2>
                    {!language.canonical && englishReady && (
                        <div className="flex gap-1 rounded-lg border p-0.5 text-xs">
                            {(['this', 'english'] as const).map((source) => (
                                <button
                                    key={source}
                                    type="button"
                                    onClick={() => setPreviewSource(source)}
                                    className={cn(
                                        'rounded-md px-2.5 py-1',
                                        previewSource === source ? 'bg-muted font-medium' : 'text-muted-foreground',
                                    )}
                                >
                                    {source === 'this' ? language.native_label : 'English source'}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
                {previewSections.length > 0 ? (
                    <LessonSections sections={previewSections} lang={previewSource === 'english' ? 'en' : language.code} />
                ) : (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">Nothing to preview yet.</p>
                )}
            </section>
        </div>
    );
}
