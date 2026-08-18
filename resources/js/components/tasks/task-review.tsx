import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { PROOF_ACCEPT, validateProofFiles } from '@/lib/upload-limits';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

export interface DeliverableRow {
    id: number;
    version: number;
    status: string;
    status_label: string;
    notes: string | null;
    submitted_by: string;
    submitted_at: string;
    can_share: boolean;
    share_url: string | null;
    files: { id: number; name: string; url: string; mime: string }[];
    reviews: { id: number; round: number; decision: string; comments: string | null; reviewer: string; reviewed_at: string }[];
}

export function TaskReview({
    taskId,
    deliverables,
    canSubmit,
    canReview,
}: {
    taskId: number;
    deliverables: DeliverableRow[];
    canSubmit: boolean;
    canReview: boolean;
}) {
    const submitForm = useForm<{ notes: string; files: File[] }>({ notes: '', files: [] });
    const reviewForm = useForm<{ decision: string; comments: string }>({ decision: 'approve', comments: '' });
    const [copyingId, setCopyingId] = useState<number | null>(null);
    const [clientError, setClientError] = useState<string | null>(null);

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

    return (
        <Card>
            <CardHeader>
                <CardTitle>Creative review</CardTitle>
                <CardDescription>Upload a proof. Each version keeps its own review rounds.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {canSubmit && (
                    <form
                        className="space-y-3 rounded-lg border p-3"
                        onSubmit={(event) => {
                            event.preventDefault();

                            const validationError = validateProofFiles(submitForm.data.files);

                            if (validationError) {
                                setClientError(validationError);

                                return;
                            }

                            setClientError(null);
                            submitForm.post(`/tasks/${taskId}/deliverables`, {
                                forceFormData: true,
                                preserveScroll: true,
                                onSuccess: () => {
                                    submitForm.reset();
                                    setClientError(null);
                                },
                            });
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="files">Proof files</Label>
                            <Input
                                id="files"
                                type="file"
                                multiple
                                accept={PROOF_ACCEPT}
                                onChange={(event) => {
                                    const files = Array.from(event.target.files ?? []);
                                    submitForm.setData('files', files);
                                    setClientError(validateProofFiles(files));
                                }}
                            />
                            <p className="text-muted-foreground text-xs">
                                Images up to 20 MB, videos (MP4, MOV, WEBM) up to 100 MB, documents and design files (PDF, DOC, DOCX, AI, PSD) up to 50 MB.
                            </p>
                            <InputError message={clientError ?? submitForm.errors.files ?? (submitForm.errors as Record<string, string | undefined>)['files.0']} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="proof_notes">Notes</Label>
                            <Textarea
                                id="proof_notes"
                                value={submitForm.data.notes}
                                onChange={(event) => submitForm.setData('notes', event.target.value)}
                            />
                        </div>
                        <Button type="submit" disabled={submitForm.processing || submitForm.data.files.length === 0 || clientError !== null}>
                            Submit for review
                        </Button>
                    </form>
                )}

                {deliverables.length === 0 && <p className="text-muted-foreground text-sm">No proofs yet.</p>}

                {deliverables.map((deliverable) => (
                    <div key={deliverable.id} className="space-y-3 rounded-lg border p-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div className="font-medium">Version {deliverable.version}</div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant={deliverable.status === 'approved' ? 'default' : 'secondary'}>{deliverable.status_label}</Badge>
                                {deliverable.can_share && (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={copyingId === deliverable.id}
                                        onClick={() => copyClientLink(deliverable)}
                                    >
                                        Copy Client Link
                                    </Button>
                                )}
                            </div>
                        </div>
                        <p className="text-muted-foreground text-xs">
                            {deliverable.submitted_by} · {new Date(deliverable.submitted_at).toLocaleString()}
                        </p>
                        {deliverable.notes && <p className="text-sm">{deliverable.notes}</p>}
                        <ul className="space-y-1 text-sm">
                            {deliverable.files.map((file) => (
                                <li key={file.id}>
                                    <a href={file.url} target="_blank" rel="noreferrer" className="hover:underline">
                                        {file.name}
                                    </a>
                                </li>
                            ))}
                        </ul>
                        {deliverable.reviews.map((review) => (
                            <div key={review.id} className="text-sm">
                                <span className="font-medium">
                                    Round {review.round}: {review.decision}
                                </span>
                                <span className="text-muted-foreground"> · {review.reviewer}</span>
                                {review.comments && <p className="text-muted-foreground">{review.comments}</p>}
                            </div>
                        ))}

                        {canReview && (deliverable.status === 'submitted' || deliverable.status === 'in_review') && (
                            <form
                                className="space-y-2 pt-2"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    reviewForm.post(`/tasks/deliverables/${deliverable.id}/review`, { preserveScroll: true });
                                }}
                            >
                                <div className="flex flex-wrap gap-2">
                                    {['approve', 'request_changes', 'reject'].map((decision) => (
                                        <Button
                                            key={decision}
                                            type="submit"
                                            size="sm"
                                            variant={decision === 'approve' ? 'default' : 'outline'}
                                            onClick={() => reviewForm.setData('decision', decision)}
                                        >
                                            {decision === 'approve' ? 'Approve' : decision === 'reject' ? 'Reject' : 'Request changes'}
                                        </Button>
                                    ))}
                                </div>
                                <Textarea
                                    placeholder="Review comments"
                                    value={reviewForm.data.comments}
                                    onChange={(event) => reviewForm.setData('comments', event.target.value)}
                                />
                            </form>
                        )}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
