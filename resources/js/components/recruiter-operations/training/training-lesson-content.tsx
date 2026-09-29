import { Button } from '@/components/ui/button';
import { ExternalLink, FileText } from 'lucide-react';

export interface TrainingLessonFile {
    name: string;
    mime: string;
    size: number;
    url: string;
}

export interface TrainingLessonData {
    id: number;
    title: string;
    description: string | null;
    body: string | null;
    content_type: string;
    content_type_label: string;
    duration_minutes: number | null;
    is_required: boolean;
    external_url: string | null;
    sort_order: number;
    file: TrainingLessonFile | null;
}

function safeHttpUrl(value: string | null): string | null {
    if (!value) {
        return null;
    }

    try {
        const url = new URL(value);

        return url.protocol === 'http:' || url.protocol === 'https:' ? url.toString() : null;
    } catch {
        return null;
    }
}

function formatBytes(bytes: number): string {
    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * The lesson's material. The body is plain text shown as text (never HTML);
 * files come from the authorized lesson file route.
 */
export function TrainingLessonContent({ lesson }: { lesson: TrainingLessonData }) {
    const externalUrl = safeHttpUrl(lesson.external_url);
    const file = lesson.file;

    return (
        <div className="space-y-5">
            {lesson.description && <p className="text-muted-foreground text-base leading-relaxed">{lesson.description}</p>}

            {file && lesson.content_type === 'video' && (
                <video controls preload="metadata" className="w-full rounded-xl border bg-black" src={file.url}>
                    Your browser cannot play this video.{' '}
                    <a href={file.url} className="underline">
                        Open the video
                    </a>
                    .
                </video>
            )}

            {file && lesson.content_type === 'image' && (
                <a href={file.url} target="_blank" rel="noopener noreferrer" className="block">
                    <img src={file.url} alt={lesson.title} className="max-h-[32rem] w-full rounded-xl border object-contain" />
                </a>
            )}

            {file && lesson.content_type === 'pdf' && (
                <div className="space-y-2">
                    <iframe src={file.url} title={`${lesson.title} (PDF)`} className="h-[36rem] w-full rounded-xl border bg-white" />
                    <Button asChild variant="outline" size="sm">
                        <a href={file.url} target="_blank" rel="noopener noreferrer">
                            <FileText /> Open PDF in a new tab ({formatBytes(file.size)})
                        </a>
                    </Button>
                </div>
            )}

            {externalUrl && (
                <div className="flex flex-wrap items-center gap-3 rounded-xl border p-4">
                    <ExternalLink className="text-muted-foreground size-4" />
                    <div className="min-w-0 flex-1">
                        <div className="text-sm font-medium">External resource</div>
                        <div className="text-muted-foreground truncate text-xs">{externalUrl}</div>
                    </div>
                    <Button asChild variant="outline" size="sm">
                        <a href={externalUrl} target="_blank" rel="noopener noreferrer nofollow">
                            Open resource
                        </a>
                    </Button>
                </div>
            )}

            {lesson.body ? (
                <div className="text-foreground text-[15px] leading-7 break-words whitespace-pre-line">{lesson.body}</div>
            ) : (
                !file &&
                !externalUrl && <p className="text-muted-foreground text-sm italic">The written content for this lesson has not been added yet.</p>
            )}
        </div>
    );
}
