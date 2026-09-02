import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
    period_label: string;
    items: SharedItem[];
}

export default function PublicContentScheduleShareShow({ brand, client_name, period_label, items }: Props) {
    return (
        <>
            <Head title={`${client_name} · Content schedule`} />

            <div className="bg-muted/40 min-h-screen px-4 py-10">
                <div className="mx-auto w-full max-w-6xl space-y-6">
                    <div className="text-center">
                        <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">{brand}</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{client_name}</h1>
                        <p className="text-muted-foreground mt-2 text-sm">Content schedule · {period_label}</p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Upcoming content</CardTitle>
                        </CardHeader>
                        <CardContent className="overflow-x-auto">
                            <Table className="min-w-max">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Platform</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Preview</TableHead>
                                        <TableHead>Download</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground py-8 text-center">
                                                No content in this period.
                                            </TableCell>
                                        </TableRow>
                                    )}

                                    {items.map((item, index) => (
                                        <TableRow key={`${item.scheduled_date}-${index}`}>
                                            <TableCell className="whitespace-nowrap">{item.scheduled_date}</TableCell>
                                            <TableCell>{item.content_type}</TableCell>
                                            <TableCell>{item.platform}</TableCell>
                                            <TableCell className="max-w-sm whitespace-pre-wrap break-words [overflow-wrap:anywhere]">
                                                {item.description || '—'}
                                            </TableCell>
                                            <TableCell>
                                                {item.attachments[0]?.can_preview ? (
                                                    item.attachments[0].mime.startsWith('video/') ? (
                                                        <video src={item.attachments[0].preview_url} controls className="max-h-24 max-w-32 rounded border" />
                                                    ) : item.attachments[0].mime.startsWith('image/') ? (
                                                        <img src={item.attachments[0].preview_url} alt="" className="max-h-24 max-w-32 rounded border object-cover" />
                                                    ) : (
                                                        <a href={item.attachments[0].preview_url} className="text-primary text-sm underline">
                                                            View
                                                        </a>
                                                    )
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {item.attachments[0] ? (
                                                    <Button variant="outline" size="sm" asChild>
                                                        <a href={item.attachments[0].download_url}>
                                                            <Download className="mr-1 size-4" />
                                                            Download
                                                        </a>
                                                    </Button>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
