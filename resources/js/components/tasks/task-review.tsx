import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { PROOF_ACCEPT, validateProofFiles } from '@/lib/upload-limits';
import { router, useForm, usePage } from '@inertiajs/react';
import { Download, ExternalLink, Link2, Share2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

export interface DeliverableRow {
    id: number;
    version: number;
    is_latest: boolean;
    status: string;
    status_label: string;
    notes: string | null;
    client_feedback: string | null;
    submitted_by: string;
    submitted_at: string;
    can_share: boolean;
    share_url: string | null;
    share_message: string | null;
    files: { id: number; name: string; url: string; download_url: string; mime: string }[];
    reviews: { id: number; round: number; decision: string; comments: string | null; reviewer: string; reviewed_at: string }[];
}

export interface SubmitReviewContext {
    can_submit: boolean;
    is_assignee: boolean;
    blocked_reason: string | null;
    status_label: string;
}

function buildShareMessage(title: string, description: string | null, url: string): string {
    const lines = [title.trim()];

    if (description?.trim()) {
        lines.push('', description.trim());
    }

    lines.push('', 'Review Link:', url);

    return lines.join('\n');
}

function formattedUpload(value: string): string {
    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export function TaskReview({
    taskId,
    taskTitle,
    taskDescription,
    deliverables,
    canSubmit,
    canReview,
    employeeMode = false,
    submitReview,
    embedded = false,
}: {
    taskId: number;
    taskTitle: string;
    taskDescription: string | null;
    deliverables: DeliverableRow[];
    canSubmit: boolean;
    canReview: boolean;
    employeeMode?: boolean;
    submitReview?: SubmitReviewContext;
    embedded?: boolean;
}) {
    const submitForm = useForm<{ notes: string; files: File[] }>({ notes: '', files: [] });
    const reviewForm = useForm<{ decision: string; comments: string }>({ decision: 'approve', comments: '' });
    const [copyingId, setCopyingId] = useState<number | null>(null);
    const [clientError, setClientError] = useState<string | null>(null);
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
    const pendingShareCopy = useRef(false);

    const { flash } = usePage<{ flash: { share_message?: string } }>().props;

    const latestDeliverable = deliverables[0];
    const latestFeedback = latestDeliverable?.reviews.at(-1);
    const hasVersions = deliverables.length > 0;
    const showUpload = employeeMode && canSubmit;

    const isReviewable = (deliverable: DeliverableRow) =>
        canReview && deliverable.is_latest && (deliverable.status === 'submitted' || deliverable.status === 'in_review');

    useEffect(() => {
        if (pendingShareCopy.current && flash.share_message) {
            void navigator.clipboard.writeText(flash.share_message).then(() => {
                toast.success('Review message copied to clipboard');
            });
            pendingShareCopy.current = false;
            setCopyingId(null);
        }
    }, [flash.share_message]);

    const resolveShareMessage = (deliverable: DeliverableRow, url: string) =>
        deliverable.share_message ?? buildShareMessage(taskTitle, taskDescription, url);

    const copyShareMessage = (deliverable: DeliverableRow) => {
        if (deliverable.share_url) {
            void navigator.clipboard.writeText(resolveShareMessage(deliverable, deliverable.share_url)).then(() => {
                toast.success('Review message copied to clipboard');
            });

            return;
        }

        pendingShareCopy.current = true;
        setCopyingId(deliverable.id);

        router.post(
            `/tasks/deliverables/${deliverable.id}/share-link`,
            {},
            {
                preserveScroll: true,
                onError: () => {
                    pendingShareCopy.current = false;
                    toast.error('Could not create the review link');
                    setCopyingId(null);
                },
            },
        );
    };

    const copyClientLink = (deliverable: DeliverableRow) => {
        if (deliverable.share_url) {
            void navigator.clipboard.writeText(deliverable.share_url).then(() => {
                toast.success('Client link copied');
            });

            return;
        }

        setCopyingId(deliverable.id);

        router.post(
            `/tasks/deliverables/${deliverable.id}/share-link`,
            {},
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const rows = (page.props as { deliverables?: DeliverableRow[] }).deliverables ?? [];
                    const url = rows.find((row) => row.id === deliverable.id)?.share_url;

                    if (url) {
                        void navigator.clipboard.writeText(url);
                        toast.success('Client link copied');
                    }
                },
                onError: () => toast.error('Could not create the client link'),
                onFinish: () => setCopyingId(null),
            },
        );
    };

    const removeSelectedFile = (index: number) => {
        const next = selectedFiles.filter((_, fileIndex) => fileIndex !== index);
        setSelectedFiles(next);
        submitForm.setData('files', next);
        setClientError(validateProofFiles(next));
    };

    const uploadForm = (
        <form
            className="space-y-3 rounded-lg border p-3"
            onSubmit={(event) => {
                event.preventDefault();

                const validationError = validateProofFiles(selectedFiles);

                if (validationError) {
                    setClientError(validationError);

                    return;
                }

                setClientError(null);
                submitForm.transform(() => ({
                    notes: submitForm.data.notes,
                    files: selectedFiles,
                }));
                submitForm.post(`/tasks/${taskId}/deliverables`, {
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        submitForm.reset();
                        setSelectedFiles([]);
                        setClientError(null);
                        toast.success(hasVersions ? 'New version uploaded' : 'Work submitted for review');
                    },
                    onError: () => toast.error('Could not submit deliverables. Check the files and try again.'),
                });
            }}
        >
            <div className="grid gap-2">
                <Label htmlFor="files">{hasVersions ? 'Corrected deliverable files' : employeeMode ? 'Deliverable files' : 'Proof files'}</Label>
                <Input
                    id="files"
                    type="file"
                    multiple
                    accept={PROOF_ACCEPT}
                    onChange={(event) => {
                        const files = Array.from(event.target.files ?? []);
                        setSelectedFiles(files);
                        submitForm.setData('files', files);
                        setClientError(validateProofFiles(files));
                    }}
                />
                <p className="text-muted-foreground text-xs">
                    Images up to 20 MB, videos (MP4, MOV, WEBM) up to 100 MB, documents and archives (PDF, Word, Excel,
                    PowerPoint, ZIP, AI, PSD) up to 50 MB.
                </p>
                {selectedFiles.length > 0 && (
                    <ul className="space-y-1 text-sm">
                        {selectedFiles.map((file, index) => (
                            <li key={`${file.name}-${index}`} className="flex items-center justify-between gap-2 rounded border px-2 py-1">
                                <span className="truncate">{file.name}</span>
                                <Button type="button" variant="ghost" size="sm" onClick={() => removeSelectedFile(index)}>
                                    Remove
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
                <InputError message={clientError ?? submitForm.errors.files ?? (submitForm.errors as Record<string, string | undefined>)['files.0']} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="proof_notes">{employeeMode ? 'Submission note' : 'Notes'}</Label>
                <Textarea
                    id="proof_notes"
                    value={submitForm.data.notes}
                    onChange={(event) => submitForm.setData('notes', event.target.value)}
                    placeholder={employeeMode ? 'Briefly describe what you are submitting…' : undefined}
                />
            </div>
            <Button type="submit" disabled={submitForm.processing || selectedFiles.length === 0 || clientError !== null}>
                {hasVersions ? 'Upload new version' : 'Submit for review'}
            </Button>
        </form>
    );

    const body = (
        <>
            {employeeMode && submitReview?.is_assignee && !canSubmit && submitReview.blocked_reason && (
                <div className="rounded-lg border border-dashed p-3 text-sm">
                    <p className="font-medium">Submission not available yet</p>
                    <p className="text-muted-foreground mt-1">{submitReview.blocked_reason}</p>
                    <p className="text-muted-foreground mt-2 text-xs">Current task status: {submitReview.status_label}</p>
                </div>
            )}

            {employeeMode && (latestFeedback?.comments || latestDeliverable?.client_feedback) && (
                <div className="rounded-lg border border-amber-500/40 bg-amber-500/5 p-3 text-sm">
                    <p className="font-medium">Latest feedback</p>
                    {latestFeedback?.comments && (
                        <>
                            <p className="text-muted-foreground mt-1">
                                {latestFeedback.decision} · {latestFeedback.reviewer}
                            </p>
                            <p className="mt-2 whitespace-pre-wrap">{latestFeedback.comments}</p>
                        </>
                    )}
                    {latestDeliverable?.client_feedback && (
                        <>
                            <p className="text-muted-foreground mt-1">Client feedback</p>
                            <p className="mt-2 whitespace-pre-wrap">{latestDeliverable.client_feedback}</p>
                        </>
                    )}
                </div>
            )}

            {deliverables.length === 0 && !showUpload && (
                <p className="text-muted-foreground text-sm">
                    {employeeMode ? 'No submissions yet.' : 'No versions submitted yet.'}
                </p>
            )}

            {deliverables.map((deliverable) => {
                const showShareActions = deliverable.can_share && (!employeeMode || deliverable.is_latest);
                const reviewable = isReviewable(deliverable);

                return (
                    <div key={deliverable.id} className="space-y-3 rounded-lg border p-3">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <div className="space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <div className="font-medium">Version {deliverable.version}</div>
                                    {deliverable.is_latest && <Badge variant="secondary">Latest</Badge>}
                                </div>
                                {!employeeMode ? (
                                    <p className="text-muted-foreground text-sm">
                                        {deliverable.submitted_by} · {formattedUpload(deliverable.submitted_at)}
                                    </p>
                                ) : (
                                    <p className="text-muted-foreground text-xs">Uploaded: {formattedUpload(deliverable.submitted_at)}</p>
                                )}
                            </div>
                            {employeeMode && (
                                <Badge variant={deliverable.status === 'approved' ? 'default' : 'secondary'}>{deliverable.status_label}</Badge>
                            )}
                            {!employeeMode && deliverable.status !== 'in_review' && (
                                <Badge variant={deliverable.status === 'approved' ? 'default' : 'secondary'}>{deliverable.status_label}</Badge>
                            )}
                        </div>

                        {employeeMode && <p className="text-muted-foreground text-xs">{deliverable.submitted_by}</p>}
                        {deliverable.notes && <p className="text-sm whitespace-pre-wrap">{deliverable.notes}</p>}
                        {deliverable.client_feedback && (
                            <p className="text-sm whitespace-pre-wrap">
                                <span className="font-medium">Client feedback: </span>
                                {deliverable.client_feedback}
                            </p>
                        )}

                        {deliverable.files.length > 0 && (
                            <ul className={employeeMode ? 'space-y-2 text-sm' : 'space-y-1 text-sm'}>
                                {deliverable.files.map((file) => (
                                    <li key={file.id} className={employeeMode ? 'flex flex-wrap items-center gap-2' : 'truncate'}>
                                        {employeeMode ? (
                                            <>
                                                <span className="min-w-0 flex-1 truncate">{file.name}</span>
                                                <Button asChild variant="outline" size="sm">
                                                    <a href={file.url} target="_blank" rel="noreferrer">
                                                        <ExternalLink className="size-3.5" />
                                                        View
                                                    </a>
                                                </Button>
                                                <Button asChild variant="outline" size="sm">
                                                    <a href={file.download_url} download={file.name}>
                                                        <Download className="size-3.5" />
                                                        Download
                                                    </a>
                                                </Button>
                                            </>
                                        ) : (
                                            file.name
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}

                        {!employeeMode && deliverable.files.length > 0 && (
                            <div className="flex flex-wrap items-center gap-2">
                                {deliverable.files.map((file) => (
                                    <span key={file.id} className="inline-flex flex-wrap gap-2">
                                        <Button asChild variant="outline" size="sm">
                                            <a href={file.url} target="_blank" rel="noreferrer">
                                                <ExternalLink className="size-3.5" />
                                                View
                                            </a>
                                        </Button>
                                        <Button asChild variant="outline" size="sm">
                                            <a href={file.download_url} download={file.name}>
                                                <Download className="size-3.5" />
                                                Download
                                            </a>
                                        </Button>
                                    </span>
                                ))}
                                {reviewable &&
                                    ['approve', 'request_changes', 'reject'].map((decision) => (
                                        <Button
                                            key={decision}
                                            type="button"
                                            size="sm"
                                            variant={decision === 'approve' ? 'default' : 'outline'}
                                            disabled={reviewForm.processing}
                                            onClick={() => {
                                                router.post(
                                                    `/tasks/deliverables/${deliverable.id}/review`,
                                                    {
                                                        decision,
                                                        comments: reviewForm.data.comments,
                                                    },
                                                    { preserveScroll: true },
                                                );
                                            }}
                                        >
                                            {decision === 'approve'
                                                ? 'Approve'
                                                : decision === 'reject'
                                                  ? 'Reject'
                                                  : 'Request changes'}
                                        </Button>
                                    ))}
                            </div>
                        )}

                        {!employeeMode && reviewable && (
                            <div className="grid gap-2">
                                <Label htmlFor={`review_comments_${deliverable.id}`} className="text-xs">
                                    Review comments (optional)
                                </Label>
                                <Textarea
                                    id={`review_comments_${deliverable.id}`}
                                    placeholder="Review comments"
                                    value={reviewForm.data.comments}
                                    onChange={(event) => reviewForm.setData('comments', event.target.value)}
                                    rows={2}
                                />
                            </div>
                        )}

                        {showShareActions && (
                            <div className="flex flex-wrap gap-2 pt-1">
                                {employeeMode ? (
                                    <>
                                        <Button
                                            type="button"
                                            size="sm"
                                            disabled={copyingId === deliverable.id}
                                            onClick={() => copyShareMessage(deliverable)}
                                        >
                                            <Share2 className="size-3.5" />
                                            Share review
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={copyingId === deliverable.id}
                                            onClick={() => copyShareMessage(deliverable)}
                                        >
                                            <Link2 className="size-3.5" />
                                            Copy link + description
                                        </Button>
                                    </>
                                ) : (
                                    <>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={copyingId === deliverable.id}
                                            onClick={() => copyShareMessage(deliverable)}
                                        >
                                            <Link2 className="size-3.5" />
                                            Copy link + description
                                        </Button>
                                        {deliverable.share_url ? (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                disabled={copyingId === deliverable.id}
                                                onClick={() => copyClientLink(deliverable)}
                                            >
                                                Copy client link
                                            </Button>
                                        ) : (
                                            deliverable.status === 'approved' && (
                                                <span className="text-muted-foreground self-center text-xs">
                                                    Share link ready after internal approval
                                                </span>
                                            )
                                        )}
                                    </>
                                )}
                            </div>
                        )}

                        {!employeeMode &&
                            deliverable.reviews.map((review) => (
                                <div key={review.id} className="text-sm">
                                    <span className="font-medium">
                                        Round {review.round}: {review.decision}
                                    </span>
                                    <span className="text-muted-foreground"> · {review.reviewer}</span>
                                    {review.comments && <p className="text-muted-foreground whitespace-pre-wrap">{review.comments}</p>}
                                </div>
                            ))}
                    </div>
                );
            })}

            {showUpload && uploadForm}
        </>
    );

    if (embedded) {
        return (
            <section className="space-y-6">
                <div>
                    <h3 className="text-sm font-semibold">{employeeMode ? 'Creative review' : 'Submit for review'}</h3>
                    <p className="text-muted-foreground text-sm">
                        {employeeMode
                            ? 'Upload deliverables for review. Each upload creates a new version you can share with the client.'
                            : 'Upload final deliverables when you are ready for review.'}
                    </p>
                </div>
                {body}
            </section>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>{employeeMode ? 'Creative review' : 'Creative review'}</CardTitle>
                <CardDescription>
                    {employeeMode
                        ? 'Upload deliverables for review. Each upload creates a new version you can share with the client.'
                        : 'Review deliverables submitted by the assignee. New uploads appear here as additional versions.'}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">{body}</CardContent>
        </Card>
    );
}
