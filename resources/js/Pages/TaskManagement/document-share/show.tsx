import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';

interface SharedFile {
    name: string;
    mime: string;
    size: number;
    preview_url: string;
    download_url: string;
    can_preview: boolean;
}

interface Props {
    brand: string;
    client_name: string;
    document: {
        title: string;
        category: string;
        description: string | null;
        file: SharedFile | null;
    };
}

export default function PublicDocumentShareShow({ brand, client_name, document }: Props) {
    return (
        <>
            <Head title={`${document.title} · ${client_name}`} />

            <div className="bg-muted/40 min-h-screen px-4 py-10">
                <div className="mx-auto w-full max-w-3xl space-y-6">
                    <div className="text-center">
                        <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">{brand}</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{document.title}</h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {client_name} · {document.category}
                        </p>
                    </div>

                    {document.description && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Notes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-wrap break-words text-sm [overflow-wrap:anywhere]">{document.description}</p>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Document</CardTitle>
                            <CardDescription>{document.file?.name ?? 'No file attached.'}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {document.file?.can_preview && document.file.mime.startsWith('image/') && (
                                <div className="bg-muted/40 flex items-center justify-center overflow-hidden rounded-lg border p-4">
                                    <img src={document.file.preview_url} alt={document.title} className="max-h-[420px] max-w-full object-contain" />
                                </div>
                            )}

                            {document.file?.can_preview && document.file.mime === 'application/pdf' && (
                                <iframe src={document.file.preview_url} title={document.title} className="h-[480px] w-full rounded-lg border" />
                            )}

                            {document.file && (
                                <Button asChild>
                                    <a href={document.file.download_url}>
                                        <Download className="mr-2 size-4" />
                                        Download
                                    </a>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
