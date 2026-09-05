import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, Download, MessageSquareWarning } from 'lucide-react';

interface SharedAttachment {
    name: string;
    mime: string;
    preview_url: string;
    download_url: string;
    can_preview: boolean;
}

interface SharedItem {
    scheduled_date: string;
    scheduled_time: string | null;
    content_type: string;
    topic?: string;
    platform: string;
    platforms?: string[];
    description: string | null;
    caption?: string | null;
    hashtags?: string | null;
    attachments: SharedAttachment[];
}

interface Props {
    brand: string;
    client_name: string;
    item: SharedItem;
    can_respond?: boolean;
    status_label?: string;
    approve_url?: string;
    request_changes_url?: string;
}

type SharePageProps = Props & {
    flash?: { success?: string; error?: string };
};

export default function PublicContentItemShareShow({
    brand,
    client_name,
    item,
    can_respond = false,
    status_label,
    approve_url = '',
    request_changes_url = '',
}: Props) {
    const { flash } = usePage<SharePageProps>().props;
    const feedbackForm = useForm({ feedback: '' });

    return (
        <>
            <Head title={`${client_name} · ${item.content_type}`} />

            <div className="bg-muted/40 min-h-screen px-4 py-10">
                <div className="mx-auto w-full max-w-3xl space-y-6">
                    <div className="text-center">
                        <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">{brand}</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{client_name}</h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {item.scheduled_date}
                            {item.scheduled_time ? ` · ${item.scheduled_time}` : ''} · {item.topic ?? item.content_type} ·{' '}
                            {(item.platforms && item.platforms.length > 0 ? item.platforms.join(', ') : item.platform)}
                        </p>
                        {status_label && (
                            <div className="mt-3 flex justify-center">
                                <Badge variant="secondary">{status_label}</Badge>
                            </div>
                        )}
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

                    {(item.description || item.caption || item.hashtags) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Post details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {item.description && (
                                    <div>
                                        <p className="text-muted-foreground text-xs tracking-wide uppercase">Description</p>
                                        <p className="whitespace-pre-wrap break-words text-sm [overflow-wrap:anywhere]">
                                            {item.description}
                                        </p>
                                    </div>
                                )}
                                {item.caption && (
                                    <div>
                                        <p className="text-muted-foreground text-xs tracking-wide uppercase">Caption</p>
                                        <p className="whitespace-pre-wrap break-words text-sm [overflow-wrap:anywhere]">{item.caption}</p>
                                    </div>
                                )}
                                {item.hashtags && (
                                    <div>
                                        <p className="text-muted-foreground text-xs tracking-wide uppercase">Hashtags</p>
                                        <p className="text-sm">{item.hashtags}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {item.attachments.map((attachment) => (
                        <Card key={attachment.name + attachment.download_url}>
                            <CardHeader>
                                <CardTitle className="text-base">{attachment.name}</CardTitle>
                                <CardDescription>{attachment.mime}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {attachment.can_preview && attachment.mime.startsWith('image/') && (
                                    <img
                                        src={attachment.preview_url}
                                        alt={attachment.name}
                                        className="max-h-[420px] max-w-full rounded-lg border object-contain"
                                    />
                                )}
                                {attachment.can_preview && attachment.mime.startsWith('video/') && (
                                    <div className="flex justify-center">
                                        <video
                                            src={attachment.preview_url}
                                            controls
                                            playsInline
                                            className="h-auto w-full max-w-[800px] rounded-lg border"
                                        />
                                    </div>
                                )}
                                {attachment.can_preview && attachment.mime === 'application/pdf' && (
                                    <iframe src={attachment.preview_url} title={attachment.name} className="h-[480px] w-full rounded-lg border" />
                                )}
                                <Button asChild>
                                    <a href={attachment.download_url}>
                                        <Download className="mr-2 size-4" />
                                        Download
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}

                    {can_respond && approve_url && request_changes_url && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Your decision</CardTitle>
                                <CardDescription>Review the creative above, then approve or request changes.</CardDescription>
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
                                        <Label htmlFor="feedback">Feedback (required for changes)</Label>
                                        <Textarea
                                            id="feedback"
                                            value={feedbackForm.data.feedback}
                                            onChange={(event) => feedbackForm.setData('feedback', event.target.value)}
                                            rows={4}
                                            required
                                            placeholder="Tell the team what to adjust..."
                                        />
                                        {feedbackForm.errors.feedback && (
                                            <p className="text-destructive text-sm">{feedbackForm.errors.feedback}</p>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            className="gap-2"
                                            disabled={feedbackForm.processing}
                                            onClick={() => feedbackForm.post(approve_url, { preserveScroll: true })}
                                        >
                                            <Check className="size-4" />
                                            Approve
                                        </Button>
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            className="gap-2"
                                            disabled={feedbackForm.processing || feedbackForm.data.feedback.trim().length < 3}
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
