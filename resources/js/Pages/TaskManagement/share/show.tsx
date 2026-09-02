import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, MessageSquareWarning } from 'lucide-react';

interface SharedFile {
    name: string;
    mime: string;
    size: number;
    url: string;
    download_url: string;
}

interface Props {
    brand: string;
    client_name: string;
    project_name: string;
    task_title: string;
    deliverable: {
        title: string;
        status: string;
        submitted_at: string;
    };
    files: SharedFile[];
    can_respond: boolean;
    approve_url: string;
    request_changes_url: string;
}

type SharePageProps = Props & {
    flash?: { success?: string; error?: string };
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function fileKind(mime: string): 'image' | 'video' | 'pdf' | 'other' {
    if (mime.startsWith('image/')) {
        return 'image';
    }

    if (mime.startsWith('video/')) {
        return 'video';
    }

    if (mime === 'application/pdf') {
        return 'pdf';
    }

    return 'other';
}

function ProofFile({ file }: { file: SharedFile }) {
    const kind = fileKind(file.mime);

    return (
        <div className="space-y-3 rounded-lg border p-3">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="text-sm font-medium">{file.name}</p>
                    <p className="text-muted-foreground text-xs">
                        {file.mime} · {formatBytes(file.size)}
                    </p>
                </div>
                <Button asChild size="sm" variant="outline">
                    <a href={file.download_url} download={file.name}>
                        Download
                    </a>
                </Button>
            </div>

            {kind === 'image' && (
                <img src={file.url} alt={file.name} className="bg-muted max-h-[32rem] w-full rounded object-contain" />
            )}

            {kind === 'video' && <video controls className="bg-muted w-full rounded" src={file.url} />}

            {kind === 'pdf' && (
                <iframe title={file.name} src={file.url} className="h-[32rem] w-full rounded border" />
            )}
        </div>
    );
}

export default function PublicDeliverableShare({
    brand,
    client_name,
    project_name,
    task_title,
    deliverable,
    files,
    can_respond,
    approve_url,
    request_changes_url,
}: Props) {
    const { flash } = usePage<SharePageProps>().props;
    const feedbackForm = useForm({ feedback: '' });

    return (
        <>
            <Head title="Deliverable" />

            <div className="bg-muted/40 min-h-screen px-4 py-10">
                <div className="mx-auto w-full max-w-3xl space-y-6">
                    <div className="text-center">
                        <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">{brand}</p>
                        <p className="text-lg font-semibold">{client_name}</p>
                    </div>

                    {(flash?.success || flash?.error) && (
                        <div
                            className={
                                flash.error
                                    ? 'border-destructive/40 bg-destructive/5 rounded-lg border p-3 text-sm'
                                    : 'border-primary/40 bg-primary/5 rounded-lg border p-3 text-sm'
                            }
                        >
                            {flash.error ?? flash.success}
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Deliverable</CardTitle>
                            <CardDescription>
                                {project_name} · {task_title}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-muted-foreground text-xs tracking-wide uppercase">Title</p>
                                <p className="text-base font-medium">{deliverable.title}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs tracking-wide uppercase">Submitted</p>
                                <p className="text-sm">{deliverable.submitted_at}</p>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-muted-foreground text-xs tracking-wide uppercase">Status</span>
                                <Badge variant="secondary">{deliverable.status}</Badge>
                            </div>
                            <p className="text-muted-foreground pt-2 text-sm">Shared securely with you</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Proof files</CardTitle>
                            <CardDescription>
                                {files.length === 0 ? 'No proof files were attached to this version.' : `${files.length} file${files.length === 1 ? '' : 's'}`}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {files.map((file) => (
                                <ProofFile key={file.url} file={file} />
                            ))}
                        </CardContent>
                    </Card>

                    {can_respond && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Your decision</CardTitle>
                                <CardDescription>Review the files above, then approve or request changes.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        feedbackForm.post(request_changes_url, { preserveScroll: true });
                                    }}
                                    className="space-y-3"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="feedback">Feedback (optional for approval, recommended for changes)</Label>
                                        <Textarea
                                            id="feedback"
                                            value={feedbackForm.data.feedback}
                                            onChange={(event) => feedbackForm.setData('feedback', event.target.value)}
                                            rows={4}
                                            placeholder="Tell the team what to adjust..."
                                        />
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            className="gap-2"
                                            disabled={feedbackForm.processing}
                                            onClick={() => feedbackForm.post(approve_url, { preserveScroll: true })}
                                        >
                                            <Check className="size-4" />
                                            Approve work
                                        </Button>
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            className="gap-2"
                                            disabled={feedbackForm.processing}
                                        >
                                            <MessageSquareWarning className="size-4" />
                                            Request changes
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}
