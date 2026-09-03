import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Copy, Download, ExternalLink, Link2, Mail, Phone, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';

interface LogoItem {
    uuid: string;
    name: string;
    mime: string;
    size: number;
    variant: string | null;
    variant_label: string;
    preview_url: string;
    download_url: string;
    can_delete: boolean;
}

interface CompanyDetail {
    id: number;
    name: string;
    website: string | null;
    email: string | null;
    phone: string | null;
    logos: LogoItem[];
    share_url: string | null;
}

interface Props {
    company: CompanyDetail;
    variants: Option[];
    can: { manage: boolean; share: boolean };
}

type CompanyFormValues = {
    name: string;
    website: string;
    primary_contact_email: string;
    primary_contact_phone: string;
};

type SharePageProps = Props & {
    flash?: { success?: string; error?: string; share_url?: string };
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

function copyText(value: string) {
    void navigator.clipboard.writeText(value);
}

export default function CompanyLogoLibraryShow({ company, variants, can }: Props) {
    const { flash } = usePage<SharePageProps>().props;
    const [editOpen, setEditOpen] = useState(false);
    const [uploadOpen, setUploadOpen] = useState(false);
    const [previewLogo, setPreviewLogo] = useState<LogoItem | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Company Logo Library', href: '/tasks/logo-library' },
        { title: company.name, href: `/tasks/logo-library/${company.id}` },
    ];

    const form = useForm<CompanyFormValues>({
        name: company.name,
        website: company.website ?? '',
        primary_contact_email: company.email ?? '',
        primary_contact_phone: company.phone ?? '',
    });

    const uploadForm = useForm<{ variant: string; file: File | null }>({
        variant: variants[0]?.value ?? 'original',
        file: null,
    });

    const submitDetails = (event: React.FormEvent) => {
        event.preventDefault();
        form.put(`/tasks/logo-library/${company.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    const submitUpload = (event: React.FormEvent) => {
        event.preventDefault();

        if (!uploadForm.data.file) {
            uploadForm.setError('file', 'Choose a logo file to upload.');
            return;
        }

        uploadForm.post(`/tasks/logo-library/${company.id}/logos`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                uploadForm.reset();
                setUploadOpen(false);
            },
        });
    };

    const deleteLogo = (logo: LogoItem) => {
        if (!window.confirm(`Remove the ${logo.variant_label} logo?`)) {
            return;
        }

        router.delete(`/tasks/logo-library/${company.id}/logos/${logo.uuid}`, {
            preserveScroll: true,
            preserveUrl: true,
        });
    };

    const createShareLink = () => {
        router.post(`/tasks/logo-library/${company.id}/share-link`, {}, { preserveScroll: true });
    };

    const shareUrl = flash?.share_url ?? company.share_url;

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={`${company.name} · Logo Library`} />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
                <div className="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight break-words">{company.name}</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Company details and approved logo variations.</p>
                    </div>
                    <div className="flex w-full min-w-0 flex-wrap gap-2 lg:w-auto lg:justify-end">
                        {can.manage && (
                            <>
                                <Button className="w-full sm:w-auto" variant="outline" onClick={() => setEditOpen(true)}>
                                    Edit details
                                </Button>
                                <Button className="w-full sm:w-auto" onClick={() => setUploadOpen(true)}>
                                    <Upload className="mr-2 size-4" />
                                    Upload logo
                                </Button>
                            </>
                        )}
                        {can.share && (
                            <Button className="w-full sm:w-auto" variant="secondary" onClick={createShareLink}>
                                <Link2 className="mr-2 size-4" />
                                Share
                            </Button>
                        )}
                    </div>
                </div>

                <Card className="min-w-0">
                    <CardHeader>
                        <CardTitle>Company details</CardTitle>
                        <CardDescription>Quick access to contact information.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <DetailRow label="Website" value={company.website} href={company.website} />
                        <DetailRow label="Email" value={company.email} copyValue={company.email} />
                        <DetailRow label="Phone" value={company.phone} copyValue={company.phone} href={company.phone ? `tel:${company.phone}` : null} />
                        <div className="space-y-1">
                            <p className="text-muted-foreground text-xs uppercase tracking-wide">Logo count</p>
                            <p className="font-medium">{company.logos.length}</p>
                        </div>
                    </CardContent>
                </Card>

                {shareUrl && (
                    <Card className="min-w-0">
                        <CardHeader>
                            <CardTitle>Share link</CardTitle>
                            <CardDescription>Recipients can view company details and download logos.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                            <Input readOnly value={shareUrl} className="min-w-0 w-full" />
                            <Button className="w-full shrink-0 sm:w-auto" variant="outline" onClick={() => copyText(shareUrl)}>
                                <Copy className="mr-2 size-4" />
                                Copy link
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <div className="min-w-0 space-y-3">
                    <div className="flex min-w-0 flex-wrap items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold">Logo variations</h2>
                        <Badge variant="secondary">{company.logos.length} uploaded</Badge>
                    </div>

                    {company.logos.length === 0 ? (
                        <Card>
                            <CardContent className="text-muted-foreground py-10 text-center text-sm">
                                No logos uploaded yet.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {company.logos.map((logo) => (
                                <Card key={logo.uuid} className="min-w-0">
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base">{logo.variant_label}</CardTitle>
                                        <CardDescription>
                                            {logo.name} · {formatBytes(logo.size)}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <button
                                            type="button"
                                            className="bg-muted/40 flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-lg border"
                                            onClick={() => setPreviewLogo(logo)}
                                        >
                                            <img
                                                src={logo.preview_url}
                                                alt={logo.variant_label}
                                                className="max-h-full max-w-full object-contain p-4"
                                                loading="lazy"
                                                onError={(event) => {
                                                    event.currentTarget.classList.add('hidden');
                                                    event.currentTarget.nextElementSibling?.classList.remove('hidden');
                                                }}
                                            />
                                            <span className="text-muted-foreground hidden px-4 text-center text-sm">
                                                Preview unavailable. Try downloading the file or re-uploading the logo.
                                            </span>
                                        </button>
                                        <div className="flex flex-wrap gap-2">
                                            <Button asChild size="sm" variant="outline">
                                                <a href={logo.download_url}>
                                                    <Download className="mr-2 size-4" />
                                                    Download
                                                </a>
                                            </Button>
                                            <Button size="sm" variant="ghost" onClick={() => setPreviewLogo(logo)}>
                                                Preview
                                            </Button>
                                            {logo.can_delete && (
                                                <Button size="sm" variant="ghost" onClick={() => deleteLogo(logo)}>
                                                    <Trash2 className="mr-2 size-4" />
                                                    Delete
                                                </Button>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit company details</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitDetails} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Company name</Label>
                            <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="website">Website</Label>
                            <Input id="website" value={form.data.website} onChange={(event) => form.setData('website', event.target.value)} />
                            <InputError message={form.errors.website} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.primary_contact_email}
                                onChange={(event) => form.setData('primary_contact_email', event.target.value)}
                            />
                            <InputError message={form.errors.primary_contact_email} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input
                                id="phone"
                                value={form.data.primary_contact_phone}
                                onChange={(event) => form.setData('primary_contact_phone', event.target.value)}
                            />
                            <InputError message={form.errors.primary_contact_phone} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Save changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={uploadOpen} onOpenChange={setUploadOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Upload logo</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitUpload} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="variant">Logo type</Label>
                            <Select value={uploadForm.data.variant} onValueChange={(value) => uploadForm.setData('variant', value)}>
                                <SelectTrigger id="variant">
                                    <SelectValue placeholder="Choose a variant" />
                                </SelectTrigger>
                                <SelectContent>
                                    {variants.map((variant) => (
                                        <SelectItem key={variant.value} value={variant.value}>
                                            {variant.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={uploadForm.errors.variant} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="file">Logo file</Label>
                            <Input
                                id="file"
                                type="file"
                                accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"
                                onChange={(event) => uploadForm.setData('file', event.target.files?.[0] ?? null)}
                            />
                            <InputError message={uploadForm.errors.file} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setUploadOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={uploadForm.processing}>
                                Upload
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={previewLogo !== null} onOpenChange={(open) => !open && setPreviewLogo(null)}>
                <DialogContent className="max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>{previewLogo?.variant_label}</DialogTitle>
                    </DialogHeader>
                    {previewLogo && (
                        <div className="bg-muted/40 flex max-h-[70vh] items-center justify-center overflow-hidden rounded-lg border p-6">
                            <img src={previewLogo.preview_url} alt={previewLogo.variant_label} className="max-h-[60vh] max-w-full object-contain" />
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}

function DetailRow({
    label,
    value,
    href,
    copyValue,
}: {
    label: string;
    value: string | null;
    href?: string | null;
    copyValue?: string | null;
}) {
    return (
        <div className="min-w-0 space-y-1">
            <p className="text-muted-foreground text-xs uppercase tracking-wide">{label}</p>
            {!value ? (
                <p className="text-muted-foreground text-sm">Not provided</p>
            ) : (
                <div className="flex min-w-0 flex-wrap items-center gap-2">
                    {href ? (
                        <a
                            href={href}
                            target={href.startsWith('http') ? '_blank' : undefined}
                            rel="noreferrer"
                            className="min-w-0 break-all font-medium hover:underline sm:break-normal sm:truncate"
                        >
                            {value}
                        </a>
                    ) : (
                        <span className="min-w-0 break-words font-medium">{value}</span>
                    )}
                    {copyValue && (
                        <Button size="icon" variant="ghost" className="size-7" onClick={() => copyText(copyValue)}>
                            <Copy className="size-4" />
                        </Button>
                    )}
                    {href?.startsWith('http') && (
                        <ExternalLink className="text-muted-foreground size-4" />
                    )}
                    {label === 'Email' && value && <Mail className="text-muted-foreground size-4" />}
                    {label === 'Phone' && value && <Phone className="text-muted-foreground size-4" />}
                </div>
            )}
        </div>
    );
}
