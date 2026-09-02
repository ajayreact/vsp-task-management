import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TASK_ATTACHMENT_ACCEPT, TASK_ATTACHMENT_MAX_FILES, validateTaskAttachments } from '@/lib/upload-limits';
import { Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

export type DraftAttachment = {
    key: string;
    file: File;
};

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function TaskCreateAttachments({
    items,
    onChange,
    errors,
}: {
    items: DraftAttachment[];
    onChange: (items: DraftAttachment[]) => void;
    errors?: Record<string, string | undefined>;
}) {
    const [clientError, setClientError] = useState<string | null>(null);

    const addFiles = (files: File[]) => {
        const nextItems = [
            ...items,
            ...files.map((file) => ({
                key: crypto.randomUUID(),
                file,
            })),
        ];

        const validationError = validateTaskAttachments(nextItems.map((item) => item.file));

        if (validationError) {
            setClientError(validationError);

            return;
        }

        setClientError(null);
        onChange(nextItems);
    };

    const removeFile = (key: string) => {
        const nextItems = items.filter((item) => item.key !== key);
        onChange(nextItems);
        setClientError(validateTaskAttachments(nextItems.map((item) => item.file)));
    };

    const remainingSlots = useMemo(() => Math.max(TASK_ATTACHMENT_MAX_FILES - items.length, 0), [items.length]);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Working files</CardTitle>
                <CardDescription>
                    Attach reference and draft files now. Proofs submitted for review stay on the task detail page.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-3 rounded-lg border p-3">
                    <div className="grid gap-2">
                        <Label htmlFor="create_working_files">Attach files</Label>
                        <Input
                            id="create_working_files"
                            type="file"
                            multiple
                            accept={TASK_ATTACHMENT_ACCEPT}
                            disabled={remainingSlots === 0}
                            onChange={(event) => {
                                const files = Array.from(event.target.files ?? []);

                                if (files.length === 0) {
                                    return;
                                }

                                addFiles(files.slice(0, remainingSlots));
                                event.target.value = '';
                            }}
                        />
                        <p className="text-muted-foreground text-xs">
                            Images, PDF, Word, Excel, PowerPoint, ZIP or text. Up to {TASK_ATTACHMENT_MAX_FILES} files, 50 MB each.
                        </p>
                        <InputError message={clientError ?? errors?.files ?? errors?.['files.0']} />
                    </div>
                </div>

                {items.length === 0 && <p className="text-muted-foreground text-sm">No working files selected yet.</p>}

                {items.map((item, index) => (
                    <div key={item.key} className="flex items-start justify-between gap-3 rounded-lg border p-3 text-sm">
                        <div>
                            <p className="font-medium">{item.file.name}</p>
                            <div className="text-muted-foreground mt-1 text-xs">
                                {formatSize(item.file.size)}
                                {item.file.type && ` · ${item.file.type}`}
                            </div>
                            <InputError message={errors?.[`files.${index}`]} />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="text-destructive hover:text-destructive shrink-0"
                            onClick={() => removeFile(item.key)}
                            aria-label={`Remove ${item.file.name}`}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

export function attachmentFiles(items: DraftAttachment[]): File[] {
    return items.map((item) => item.file);
}
