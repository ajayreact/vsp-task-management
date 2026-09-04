import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Copy,
    Download,
    ExternalLink,
    Eye,
    FileText,
    FolderOpen,
    Link2,
    Pencil,
    Share2,
} from 'lucide-react';
import { useEffect, useState } from 'react';

interface MediaInfo {
    uuid: string;
    name: string;
    preview_url: string;
    download_url: string;
}

interface ContractDetail {
    id: number;
    contract_number: string;
    title: string;
    contract_type_label: string;
    country_label: string;
    currency: string;
    status: string;
    status_label: string;
    status_variant: 'default' | 'secondary' | 'outline' | 'destructive';
    effective_date: string;
    start_date: string | null;
    end_date: string | null;
    created_at: string;
    signed_at: string | null;
    client: { id: number; name: string };
    created_by: string;
    version_number: number | null;
    pdf: MediaInfo | null;
    signed_pdf: MediaInfo | null;
    share_link: {
        url: string;
        expires_at: string | null;
        viewed_at: string | null;
        expiry_preset: string;
    } | null;
    signature: {
        signer_name: string;
        authorized_person: string | null;
        signature_type: string;
        signed_at: string;
        ip_address: string | null;
    } | null;
    documents: {
        original: { id: number; title: string; url: string } | null;
        signed: { id: number; title: string; url: string } | null;
    };
    versions: {
        id: number;
        version_number: number;
        status: string;
        change_summary: string | null;
        created_at: string;
        created_by: string | null;
        has_pdf: boolean;
    }[];
    timeline: {
        id: number;
        label: string;
        by: string | null;
        at: string;
    }[];
}

interface Props {
    contract: ContractDetail;
    can: {
        update: boolean;
        share: boolean;
        generate_pdf: boolean;
    };
}

type ShowPageProps = Props & {
    flash?: { share_url?: string; share_message?: string; success?: string };
};

const date = (value: string | null) =>
    value ? new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' }) : '—';

const datetime = (value: string) =>
    new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });

export default function ContractShow({ contract, can }: Props) {
    const { flash } = usePage<ShowPageProps>().props;
    const [expiryPreset, setExpiryPreset] = useState('30_days');
    const shareText = flash?.share_message ?? flash?.share_url ?? '';

    useEffect(() => {
        if (shareText) {
            void navigator.clipboard.writeText(shareText);
        }
    }, [shareText]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Contracts', href: '/tasks/contracts' },
        { title: contract.title, href: `/tasks/contracts/${contract.id}` },
    ];

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={contract.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={contract.title}
                    description={`${contract.client.name} · ${contract.contract_number}`}
                    action={
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <Link href={`/tasks/contracts/${contract.id}/preview`}>
                                    <Eye /> Preview
                                </Link>
                            </Button>
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={`/tasks/contracts/${contract.id}/edit`}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                {flash?.success && (
                    <div className="border-primary/40 bg-primary/5 rounded-lg border p-3 text-sm">{flash.success}</div>
                )}

                {shareText && (
                    <div className="bg-muted/50 flex flex-col gap-2 rounded-lg border p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span className="break-all whitespace-pre-line">Signing message copied to clipboard.</span>
                        <Button variant="outline" size="sm" onClick={() => void navigator.clipboard.writeText(shareText)}>
                            <Copy className="size-4" /> Copy again
                        </Button>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Overview</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground text-xs">Status</dt>
                                    <dd className="mt-1">
                                        <Badge variant={contract.status_variant}>{contract.status_label}</Badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Type</dt>
                                    <dd className="mt-1">{contract.contract_type_label}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Country</dt>
                                    <dd className="mt-1">{contract.country_label}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Currency</dt>
                                    <dd className="mt-1">{contract.currency}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Effective date</dt>
                                    <dd className="mt-1">{date(contract.effective_date)}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Period</dt>
                                    <dd className="mt-1">
                                        {date(contract.start_date)} – {date(contract.end_date)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Created by</dt>
                                    <dd className="mt-1">{contract.created_by}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-xs">Signed</dt>
                                    <dd className="mt-1">{contract.signed_at ? datetime(contract.signed_at) : '—'}</dd>
                                </div>
                            </dl>

                            {contract.signature && (
                                <div className="mt-6 rounded-lg border p-4 text-sm">
                                    <p className="font-medium">Client signature</p>
                                    <p className="text-muted-foreground mt-1">
                                        {contract.signature.signer_name}
                                        {contract.signature.authorized_person ? ` · ${contract.signature.authorized_person}` : ''}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {datetime(contract.signature.signed_at)} · {contract.signature.signature_type}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>PDF & Documents</CardTitle>
                                <CardDescription>Generate, download, and store contract PDFs.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {can.generate_pdf && (
                                    <Button
                                        className="w-full"
                                        onClick={() =>
                                            router.post(`/tasks/contracts/${contract.id}/generate-pdf`, {}, { preserveScroll: true })
                                        }
                                    >
                                        <FileText className="size-4" />
                                        Generate PDF
                                    </Button>
                                )}

                                {contract.pdf && (
                                    <div className="flex flex-col gap-2">
                                        <Button variant="outline" className="w-full" asChild>
                                            <a href={contract.pdf.preview_url} target="_blank" rel="noreferrer">
                                                <ExternalLink className="size-4" />
                                                View PDF
                                            </a>
                                        </Button>
                                        <Button variant="outline" className="w-full" asChild>
                                            <a href={contract.pdf.download_url}>
                                                <Download className="size-4" />
                                                Download PDF
                                            </a>
                                        </Button>
                                    </div>
                                )}

                                {contract.signed_pdf && (
                                    <Button variant="outline" className="w-full" asChild>
                                        <a href={contract.signed_pdf.download_url}>
                                            <Download className="size-4" />
                                            Download signed PDF
                                        </a>
                                    </Button>
                                )}

                                {can.generate_pdf && contract.pdf && (
                                    <Button
                                        variant="secondary"
                                        className="w-full"
                                        onClick={() =>
                                            router.post(
                                                `/tasks/contracts/${contract.id}/store-in-documents`,
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <FolderOpen className="size-4" />
                                        Store in Ops Documents
                                    </Button>
                                )}

                                {can.generate_pdf && contract.signed_pdf && (
                                    <Button
                                        variant="secondary"
                                        className="w-full"
                                        onClick={() =>
                                            router.post(
                                                `/tasks/contracts/${contract.id}/store-in-documents`,
                                                { signed: true },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <FolderOpen className="size-4" />
                                        Store signed in Ops Documents
                                    </Button>
                                )}

                                {(contract.documents.original || contract.documents.signed) && (
                                    <div className="space-y-2 pt-2 text-sm">
                                        {contract.documents.original && (
                                            <Link href={contract.documents.original.url} className="text-primary block hover:underline">
                                                Original in documents
                                            </Link>
                                        )}
                                        {contract.documents.signed && (
                                            <Link href={contract.documents.signed.url} className="text-primary block hover:underline">
                                                Signed in documents
                                            </Link>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {can.share && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Share for signing</CardTitle>
                                    <CardDescription>Send a link for the client to review and sign.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {contract.share_link && (
                                        <div className="bg-muted/50 rounded-lg border p-3 text-xs break-all">
                                            <p className="font-medium">Active link</p>
                                            <p className="text-muted-foreground mt-1">{contract.share_link.url}</p>
                                            {contract.share_link.expires_at && (
                                                <p className="text-muted-foreground mt-1">
                                                    Expires {datetime(contract.share_link.expires_at)}
                                                </p>
                                            )}
                                        </div>
                                    )}

                                    <Select value={expiryPreset} onValueChange={setExpiryPreset}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Link expiry" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="7_days">7 days</SelectItem>
                                            <SelectItem value="15_days">15 days</SelectItem>
                                            <SelectItem value="30_days">30 days</SelectItem>
                                            <SelectItem value="never">Never</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <Button
                                        className="w-full"
                                        variant="outline"
                                        onClick={() =>
                                            router.post(
                                                `/tasks/contracts/${contract.id}/share-link`,
                                                { expiry_preset: expiryPreset },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Share2 className="size-4" />
                                        Copy signing link
                                    </Button>

                                    {contract.share_link && (
                                        <Button
                                            className="w-full"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                const message = `${contract.title}\n\nReview & Sign:\n${contract.share_link!.url}`;
                                                void navigator.clipboard.writeText(message);
                                            }}
                                        >
                                            <Link2 className="size-4" />
                                            Copy message format
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Timeline</CardTitle>
                            <CardDescription>Contract activity history.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {contract.timeline.length === 0 && (
                                <p className="text-muted-foreground text-sm">No activity recorded yet.</p>
                            )}

                            {contract.timeline.map((event) => (
                                <div key={event.id} className="border-l-2 pl-4">
                                    <p className="text-sm font-medium">{event.label}</p>
                                    <p className="text-muted-foreground text-xs">
                                        {datetime(event.at)}
                                        {event.by ? ` · ${event.by}` : ''}
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Versions</CardTitle>
                            <CardDescription>
                                Current version {contract.version_number ?? '—'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {contract.versions.length === 0 && (
                                <p className="text-muted-foreground text-sm">No versions yet.</p>
                            )}

                            {contract.versions.map((version) => (
                                <div key={version.id} className="flex items-start justify-between gap-3 text-sm">
                                    <div>
                                        <p className="font-medium">Version {version.version_number}</p>
                                        {version.change_summary && (
                                            <p className="text-muted-foreground text-xs">{version.change_summary}</p>
                                        )}
                                        <p className="text-muted-foreground text-xs">
                                            {datetime(version.created_at)}
                                            {version.created_by ? ` · ${version.created_by}` : ''}
                                        </p>
                                    </div>
                                    {version.has_pdf && <Badge variant="outline">PDF</Badge>}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </TaskLayout>
    );
}
