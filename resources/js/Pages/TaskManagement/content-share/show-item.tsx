import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';

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
    platform: string;
    description: string | null;
    attachments: SharedAttachment[];
}

interface Props {
    brand: string;
    client_name: string;
    item: SharedItem;
}

export default function PublicContentItemShareShow({ brand, client_name, item }: Props) {
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
                            {item.scheduled_time ? ` · ${item.scheduled_time}` : ''} · {item.content_type} · {item.platform}
                        </p>
                    </div>

                    {item.description && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Post description</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-wrap break-words text-sm [overflow-wrap:anywhere]">{item.description}</p>
                            </CardContent>
                        </Card>
                    )}

                    {item.attachments.map((attachment) => (
                        <Card key={attachment.name}>
                            <CardHeader>
                                <CardTitle className="text-base">{attachment.name}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {attachment.can_preview && attachment.mime.startsWith('image/') && (
                                    <img src={attachment.preview_url} alt={attachment.name} className="max-h-[420px] max-w-full rounded-lg border object-contain" />
                                )}
                                {attachment.can_preview && attachment.mime.startsWith('video/') && (
                                    <video src={attachment.preview_url} controls className="max-h-[420px] w-full rounded-lg border" />
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
                </div>
            </div>
        </>
    );
}
