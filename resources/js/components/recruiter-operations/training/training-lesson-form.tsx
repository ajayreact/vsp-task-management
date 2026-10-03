import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { type TrainingLessonFile } from './training-lesson-content';

export interface TrainingContentTypeOption {
    value: string;
    label: string;
    uses_file: boolean;
    accept: string;
}

export type TrainingLessonFormValues = {
    title: string;
    description: string;
    content_type: string;
    body: string;
    duration_minutes: string;
    is_required: boolean;
    external_url: string;
    file: File | null;
};

export function TrainingLessonForm({
    contentTypes,
    maxUploadKilobytes,
    initial,
    existingFile,
    structuredContentUrl,
    action,
    method,
    submitLabel,
    cancelUrl,
}: {
    contentTypes: TrainingContentTypeOption[];
    maxUploadKilobytes: number;
    initial: TrainingLessonFormValues;
    existingFile?: TrainingLessonFile | null;
    /** Set when the lesson's written content is kept as structured sections, edited on its own page. */
    structuredContentUrl?: string;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
}) {
    const { data, setData, post, transform, processing, errors, progress } = useForm<TrainingLessonFormValues>(initial);
    const [fileError, setFileError] = useState<string | null>(null);

    const type = contentTypes.find((option) => option.value === data.content_type);
    const usesFile = type?.uses_file ?? false;

    const chooseFile = (file: File | null) => {
        setFileError(null);

        if (file && type) {
            const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
            const allowed = type.accept.split(',').map((item) => item.replace('.', '').trim());

            if (!allowed.includes(extension)) {
                setFileError(`Choose a ${type.label.toLowerCase()} file (${type.accept}).`);
                setData('file', null);

                return;
            }

            if (file.size > maxUploadKilobytes * 1024) {
                setFileError(`The file cannot be larger than ${Math.round(maxUploadKilobytes / 1024)} MB.`);
                setData('file', null);

                return;
            }
        }

        setData('file', file);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((values) => ({
            ...values,
            file: usesFile ? values.file : null,
            is_required: values.is_required ? 1 : 0,
            ...(method === 'put' ? { _method: 'put' } : {}),
        }));

        post(action, { preserveScroll: true, forceFormData: true });
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Lesson</CardTitle>
                    <CardDescription>
                        Written content is plain text and is also what Listen to Lesson reads aloud. After saving, use Edit content on the lesson
                        preview to organise it into sections and add translations.
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="title">Title</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required maxLength={255} />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="description">Summary</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={2}
                            maxLength={2000}
                            placeholder="Optional, one or two sentences."
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="content_type">Main material</Label>
                        <Select
                            value={data.content_type}
                            onValueChange={(value) => {
                                setData('content_type', value);
                                setFileError(null);
                            }}
                        >
                            <SelectTrigger id="content_type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {contentTypes.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.content_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="duration_minutes">Duration (minutes)</Label>
                        <Input
                            id="duration_minutes"
                            type="number"
                            min="1"
                            max="600"
                            value={data.duration_minutes}
                            onChange={(e) => setData('duration_minutes', e.target.value)}
                            placeholder="Optional"
                        />
                        <InputError message={errors.duration_minutes} />
                    </div>

                    {usesFile && type && (
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="file">{type.label} file</Label>
                            <Input id="file" type="file" accept={type.accept} onChange={(e) => chooseFile(e.target.files?.[0] ?? null)} />
                            <p className="text-muted-foreground text-xs">
                                Accepted: {type.accept}. Files are private: only training managers and assigned recruiters can open them.
                                {existingFile ? ` Current file: ${existingFile.name}. Choose a new file only to replace it.` : ''}
                            </p>
                            {progress && <p className="text-muted-foreground text-xs">Uploading… {progress.percentage}%</p>}
                            <InputError message={fileError ?? errors.file} />
                        </div>
                    )}

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="external_url">External resource link{data.content_type === 'external_resource' ? '' : ' (optional)'}</Label>
                        <Input
                            id="external_url"
                            type="url"
                            value={data.external_url}
                            onChange={(e) => setData('external_url', e.target.value)}
                            placeholder="https://"
                            required={data.content_type === 'external_resource'}
                            maxLength={2048}
                        />
                        <InputError message={errors.external_url} />
                    </div>

                    {structuredContentUrl ? (
                        <div className="text-muted-foreground flex flex-wrap items-center justify-between gap-3 rounded-lg border border-dashed p-3 text-sm sm:col-span-2">
                            <span>The written content is organised into sections, with its translations, on its own page.</span>
                            <Button type="button" variant="outline" size="sm" asChild>
                                <Link href={structuredContentUrl}>Edit content</Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="body">Written content</Label>
                            <Textarea
                                id="body"
                                value={data.body}
                                onChange={(e) => setData('body', e.target.value)}
                                rows={14}
                                placeholder="Start with a learning objective, then the key points, an example and a key takeaway."
                            />
                            <InputError message={errors.body} />
                        </div>
                    )}

                    <div className="flex items-center gap-2 sm:col-span-2">
                        <Checkbox
                            id="is_required"
                            checked={data.is_required}
                            onCheckedChange={(checked) => setData('is_required', checked === true)}
                        />
                        <Label htmlFor="is_required" className="font-normal">
                            Required lesson (counts towards course completion)
                        </Label>
                        <InputError message={errors.is_required} />
                    </div>
                </CardContent>
            </Card>

            <div className="flex gap-2">
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    {submitLabel}
                </Button>
                <Button type="button" variant="outline" asChild>
                    <Link href={cancelUrl}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
