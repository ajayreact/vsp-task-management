import { ConfirmDelete } from '@/components/admin/confirm-delete';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TASK_ATTACHMENT_ACCEPT, TASK_ATTACHMENT_MAX_FILES, validateTaskAttachments } from '@/lib/upload-limits';
import { useForm } from '@inertiajs/react';
import { Download, Trash2 } from 'lucide-react';
import { useState } from 'react';

export interface TaskAttachmentRow {
    id: number;
    name: string;
    url: string;
    mime: string | null;
    size: number;
    uploaded_by: string;
    uploaded_at: string | null;
    can_delete: boolean;
}

export function TaskAttachments({ taskId, attachments, canUpload }: { taskId: number; attachments: TaskAttachmentRow[]; canUpload: boolean }) {
    const form = useForm<{ files: File[] }>({ files: [] });
    const [clientError, setClientError] = useState<string | null>(null);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Working files</CardTitle>
                <CardDescription>
                    Reference and handover files for this task. Proofs submitted for review stay in Creative review below.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {canUpload && (
                    <form
                        className="space-y-3 rounded-lg border p-3"
                        onSubmit={(event) => {
                            event.preventDefault();

                            const validationError = validateTaskAttachments(form.data.files);

                            if (validationError) {
                                setClientError(validationError);

                                return;
                            }

                            setClientError(null);
                            form.post(`/tasks/${taskId}/attachments`, {
                                forceFormData: true,
                                preserveScroll: true,
                                onSuccess: () => {
                                    form.reset();
                                    setClientError(null);
                                },
                            });
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="working_files">Attach files</Label>
                            <Input
                                id="working_files"
                                type="file"
                                multiple
                                accept={TASK_ATTACHMENT_ACCEPT}
                                onChange={(event) => {
                                    const files = Array.from(event.target.files ?? []);
                                    form.setData('files', files);
                                    setClientError(validateTaskAttachments(files));
                                }}
                            />
                            <p className="text-muted-foreground text-xs">
                                Images, PDF, Word, Excel, PowerPoint, ZIP or text. Up to {TASK_ATTACHMENT_MAX_FILES} files, 50 MB each.
                                Video files are not supported here — upload reels in Creative review below.
                            </p>
                            <InputError message={clientError ?? form.errors.files ?? (form.errors as Record<string, string | undefined>)['files.0']} />
                        </div>
                        <Button type="submit" disabled={form.processing || form.data.files.length === 0 || clientError !== null}>
                            Upload
                        </Button>
                    </form>
                )}

                {attachments.length === 0 && <p className="text-muted-foreground text-sm">No working files yet.</p>}

                {attachments.map((attachment) => (
                    <div key={attachment.id} className="flex items-start justify-between gap-3 rounded-lg border p-3 text-sm">
                        <div>
                            <a
                                href={attachment.url}
                                target="_blank"
                                rel="noreferrer"
                                className="hover:text-primary inline-flex items-center gap-1 font-medium"
                            >
                                <Download className="size-3.5" />
                                {attachment.name}
                            </a>
                            <div className="text-muted-foreground mt-1 text-xs">
                                {formatSize(attachment.size)}
                                {attachment.mime && ` · ${attachment.mime}`}
                                {` · ${attachment.uploaded_by}`}
                                {attachment.uploaded_at && ` · ${new Date(attachment.uploaded_at).toLocaleString()}`}
                            </div>
                        </div>
                        {attachment.can_delete && (
                            <ConfirmDelete
                                url={`/tasks/${taskId}/attachments/${attachment.id}`}
                                title={`Remove "${attachment.name}"?`}
                                description="This only removes a working file. Creative review proofs are not affected."
                                trigger={
                                    <Button variant="ghost" size="icon" aria-label={`Delete ${attachment.name}`}>
                                        <Trash2 className="text-destructive size-4" />
                                    </Button>
                                }
                            />
                        )}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
