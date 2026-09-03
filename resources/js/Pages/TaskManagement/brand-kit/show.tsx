import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Copy, Download, ExternalLink, FileText, Link2, Mail, Phone, Plus, Trash2, Upload } from 'lucide-react';
import { useMemo, useState } from 'react';

interface LogoItem {
    uuid: string;
    name: string;
    mime: string;
    size: number;
    variant: string | null;
    variant_label: string;
    title: string | null;
    description: string | null;
    preview_url: string;
    download_url: string;
    can_delete: boolean;
}

interface AssetFile {
    uuid: string;
    name: string;
    mime: string;
    size: number;
    extension: string;
    file_type: 'image' | 'file';
    preview_url: string;
    download_url: string;
}

interface BrandAssetGroup {
    asset_id: string;
    category: string;
    category_label: string;
    title: string;
    description: string | null;
    files: AssetFile[];
    can_delete: boolean;
}

interface PhoneRow {
    id: number | null;
    label: string | null;
    phone: string;
    is_primary: boolean;
}

interface CompanyDetail {
    id: number;
    name: string;
    website: string | null;
    email: string | null;
    phone: string | null;
    phones: PhoneRow[];
    asset_count: number;
    logos: LogoItem[];
    assets: BrandAssetGroup[];
    share_url: string | null;
}

interface CategoryOption extends Option {
    tab_label: string;
}

interface Props {
    company: CompanyDetail;
    categories: CategoryOption[];
    variants: Option[];
    can: { manage: boolean; share: boolean };
}

type PhoneFormRow = {
    id: number | null;
    label: string;
    phone: string;
    is_primary: boolean;
};

type CompanyFormValues = {
    name: string;
    website: string;
    primary_contact_email: string;
    phones: PhoneFormRow[];
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

function emptyPhoneRow(primary = false): PhoneFormRow {
    return { id: null, label: '', phone: '', is_primary: primary };
}

export default function BrandKitShow({ company, categories, variants, can }: Props) {
    const { flash } = usePage<SharePageProps>().props;
    const [editOpen, setEditOpen] = useState(false);
    const [assetOpen, setAssetOpen] = useState(false);
    const [previewLogo, setPreviewLogo] = useState<LogoItem | null>(null);
    const [activeTab, setActiveTab] = useState(categories[0]?.value ?? 'logos');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Brand Kit', href: '/tasks/brand-kit' },
        { title: company.name, href: `/tasks/brand-kit/${company.id}` },
    ];

    const form = useForm<CompanyFormValues>({
        name: company.name,
        website: company.website ?? '',
        primary_contact_email: company.email ?? '',
        phones:
            company.phones.length > 0
                ? company.phones.map((phone) => ({
                      id: phone.id,
                      label: phone.label ?? '',
                      phone: phone.phone,
                      is_primary: phone.is_primary,
                  }))
                : company.phone
                  ? [emptyPhoneRow(true)]
                  : [emptyPhoneRow(true)],
    });

    const assetForm = useForm<{
        category: string;
        title: string;
        description: string;
        variant: string;
        files: File[];
    }>({
        category: categories[0]?.value ?? 'logos',
        title: '',
        description: '',
        variant: variants[0]?.value ?? 'original',
        files: [],
    });

    const assetsByCategory = useMemo(() => {
        const grouped: Record<string, BrandAssetGroup[]> = {};

        categories.forEach((category) => {
            grouped[category.value] = company.assets.filter((asset) => asset.category === category.value);
        });

        return grouped;
    }, [categories, company.assets]);

    const submitDetails = (event: React.FormEvent) => {
        event.preventDefault();

        const phones = form.data.phones
            .filter((row) => row.phone.trim() !== '')
            .map((row, index) => ({
                id: row.id,
                label: row.label.trim() === '' ? null : row.label.trim(),
                phone: row.phone.trim(),
                is_primary: row.is_primary || (index === 0 && !form.data.phones.some((item) => item.is_primary)),
            }));

        form.transform((data) => ({ ...data, phones }));
        form.put(`/tasks/brand-kit/${company.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    const submitAsset = (event: React.FormEvent) => {
        event.preventDefault();

        if (assetForm.data.files.length === 0) {
            assetForm.setError('files', 'Choose at least one file to upload.');
            return;
        }

        assetForm.post(`/tasks/brand-kit/${company.id}/assets`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                assetForm.reset();
                assetForm.setData('category', categories[0]?.value ?? 'logos');
                assetForm.setData('variant', variants[0]?.value ?? 'original');
                setAssetOpen(false);
            },
        });
    };

    const deleteLogo = (logo: LogoItem) => {
        if (!window.confirm(`Remove the ${logo.variant_label} logo?`)) {
            return;
        }

        router.delete(`/tasks/brand-kit/${company.id}/logos/${logo.uuid}`, {
            preserveScroll: true,
            preserveUrl: true,
        });
    };

    const deleteAsset = (asset: BrandAssetGroup) => {
        if (!window.confirm(`Remove "${asset.title}"?`)) {
            return;
        }

        router.delete(`/tasks/brand-kit/${company.id}/assets/${asset.asset_id}`, {
            preserveScroll: true,
            preserveUrl: true,
        });
    };

    const createShareLink = () => {
        router.post(`/tasks/brand-kit/${company.id}/share-link`, {}, { preserveScroll: true });
    };

    const shareUrl = flash?.share_url ?? company.share_url;
    const showVariantField = assetForm.data.category === 'logos';

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={`${company.name} · Brand Kit`} />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
                <div className="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-sm">Brand Kit</p>
                        <h1 className="text-2xl font-semibold tracking-tight break-words">{company.name} — Brand Kit</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Logos, stationery, signatures, guidelines, and supporting brand files in one place.
                        </p>
                    </div>
                    <div className="flex w-full min-w-0 flex-wrap gap-2 lg:w-auto lg:justify-end">
                        {can.manage && (
                            <>
                                <Button className="w-full sm:w-auto" variant="outline" onClick={() => setEditOpen(true)}>
                                    Edit details
                                </Button>
                                <Button className="w-full sm:w-auto" onClick={() => setAssetOpen(true)}>
                                    <Plus className="mr-2 size-4" />
                                    Add Asset
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
                        <CardDescription>Contact information for this client brand kit.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <DetailRow label="Website" value={company.website} href={company.website} />
                        <DetailRow label="Email" value={company.email} copyValue={company.email} />
                        <div className="min-w-0 space-y-1 sm:col-span-2">
                            <p className="text-muted-foreground text-xs uppercase tracking-wide">Phone</p>
                            {company.phones.length > 0 ? (
                                <div className="space-y-2">
                                    {company.phones.map((phone) => (
                                        <div key={`${phone.id ?? phone.phone}`} className="flex min-w-0 flex-wrap items-center gap-2">
                                            {phone.label && (
                                                <Badge variant="outline" className="shrink-0">
                                                    {phone.label}
                                                </Badge>
                                            )}
                                            <a href={`tel:${phone.phone}`} className="min-w-0 break-words font-medium hover:underline">
                                                {phone.phone}
                                            </a>
                                            <Button size="icon" variant="ghost" className="size-7" onClick={() => copyText(phone.phone)}>
                                                <Copy className="size-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">Not provided</p>
                            )}
                        </div>
                        <div className="space-y-1">
                            <p className="text-muted-foreground text-xs uppercase tracking-wide">Brand Kit assets</p>
                            <p className="font-medium">{company.asset_count}</p>
                        </div>
                    </CardContent>
                </Card>

                {shareUrl && (
                    <Card className="min-w-0">
                        <CardHeader>
                            <CardTitle>Share link</CardTitle>
                            <CardDescription>Recipients can view company details and download brand assets.</CardDescription>
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

                <div className="min-w-0 space-y-4">
                    <div className="flex flex-wrap gap-2">
                        {categories.map((category) => (
                            <Button
                                key={category.value}
                                type="button"
                                size="sm"
                                variant={activeTab === category.value ? 'default' : 'outline'}
                                onClick={() => setActiveTab(category.value)}
                            >
                                {category.tab_label}
                                {category.value === 'logos' && company.logos.length > 0 && (
                                    <Badge variant="secondary" className="ml-2">
                                        {company.logos.length}
                                    </Badge>
                                )}
                                {category.value !== 'logos' && (assetsByCategory[category.value]?.length ?? 0) > 0 && (
                                    <Badge variant="secondary" className="ml-2">
                                        {assetsByCategory[category.value]?.length}
                                    </Badge>
                                )}
                            </Button>
                        ))}
                    </div>

                    {categories.map((category) =>
                        activeTab === category.value ? (
                            <div key={category.value} className="space-y-4">
                            {category.value === 'logos' ? (
                                company.logos.length === 0 ? (
                                    <Card>
                                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                                            No logos uploaded yet. Use Add Asset to upload logo variations.
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <div className="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                        {company.logos.map((logo) => (
                                            <AssetCard
                                                key={logo.uuid}
                                                title={logo.title ?? logo.variant_label}
                                                description={logo.description ?? `${logo.name} · ${formatBytes(logo.size)}`}
                                                previewUrl={logo.preview_url}
                                                previewAlt={logo.variant_label}
                                                isImage
                                                onPreview={() => setPreviewLogo(logo)}
                                                downloadUrl={logo.download_url}
                                                onDelete={logo.can_delete ? () => deleteLogo(logo) : undefined}
                                            />
                                        ))}
                                    </div>
                                )
                            ) : (assetsByCategory[category.value]?.length ?? 0) === 0 ? (
                                <Card>
                                    <CardContent className="text-muted-foreground py-10 text-center text-sm">
                                        No {category.tab_label.toLowerCase()} assets yet.
                                    </CardContent>
                                </Card>
                            ) : (
                                <div className="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {assetsByCategory[category.value]?.map((asset) => {
                                        const featured = asset.files[0];

                                        return (
                                            <AssetCard
                                                key={asset.asset_id}
                                                title={asset.title}
                                                description={asset.description ?? `${asset.files.length} file(s)`}
                                                previewUrl={featured?.file_type === 'image' ? featured.preview_url : undefined}
                                                previewAlt={asset.title}
                                                isImage={featured?.file_type === 'image'}
                                                fileCount={asset.files.length}
                                                files={asset.files}
                                                onDelete={asset.can_delete ? () => deleteAsset(asset) : undefined}
                                            />
                                        );
                                    })}
                                </div>
                            )}
                            </div>
                        ) : null,
                    )}
                </div>
            </div>

            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent className="max-w-lg">
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
                        <div className="space-y-3">
                            <div className="flex items-center justify-between gap-2">
                                <Label>Phone numbers</Label>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => form.setData('phones', [...form.data.phones, emptyPhoneRow()])}
                                >
                                    <Plus className="mr-1 size-4" />
                                    Add another number
                                </Button>
                            </div>
                            {form.data.phones.map((row, index) => (
                                <div key={`phone-${index}`} className="grid gap-2 rounded-lg border p-3">
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor={`phone-label-${index}`}>Label</Label>
                                            <Input
                                                id={`phone-label-${index}`}
                                                placeholder="Office, Sales, WhatsApp..."
                                                value={row.label}
                                                onChange={(event) => {
                                                    const phones = [...form.data.phones];
                                                    phones[index] = { ...phones[index], label: event.target.value };
                                                    form.setData('phones', phones);
                                                }}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor={`phone-number-${index}`}>Number</Label>
                                            <Input
                                                id={`phone-number-${index}`}
                                                value={row.phone}
                                                onChange={(event) => {
                                                    const phones = [...form.data.phones];
                                                    phones[index] = { ...phones[index], phone: event.target.value };
                                                    form.setData('phones', phones);
                                                }}
                                            />
                                        </div>
                                    </div>
                                    <div className="flex items-center justify-between gap-2">
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="radio"
                                                name="primary_phone"
                                                checked={row.is_primary}
                                                onChange={() => {
                                                    form.setData(
                                                        'phones',
                                                        form.data.phones.map((phone, phoneIndex) => ({
                                                            ...phone,
                                                            is_primary: phoneIndex === index,
                                                        })),
                                                    );
                                                }}
                                            />
                                            Primary number
                                        </label>
                                        {form.data.phones.length > 1 && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    form.setData(
                                                        'phones',
                                                        form.data.phones.filter((_, phoneIndex) => phoneIndex !== index),
                                                    )
                                                }
                                            >
                                                <Trash2 className="mr-1 size-4" />
                                                Remove
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                            <InputError message={form.errors.phones as string | undefined} />
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

            <Dialog open={assetOpen} onOpenChange={setAssetOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Add Asset</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitAsset} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="asset-category">Asset category</Label>
                            <Select
                                value={assetForm.data.category}
                                onValueChange={(value) => assetForm.setData('category', value)}
                            >
                                <SelectTrigger id="asset-category">
                                    <SelectValue placeholder="Choose a category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem key={category.value} value={category.value}>
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={assetForm.errors.category} />
                        </div>
                        {showVariantField && (
                            <div className="space-y-2">
                                <Label htmlFor="asset-variant">Logo type</Label>
                                <Select
                                    value={assetForm.data.variant}
                                    onValueChange={(value) => assetForm.setData('variant', value)}
                                >
                                    <SelectTrigger id="asset-variant">
                                        <SelectValue placeholder="Choose a logo type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {variants.map((variant) => (
                                            <SelectItem key={variant.value} value={variant.value}>
                                                {variant.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={assetForm.errors.variant} />
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="asset-title">Asset name</Label>
                            <Input
                                id="asset-title"
                                value={assetForm.data.title}
                                onChange={(event) => assetForm.setData('title', event.target.value)}
                                placeholder="VSP Official Letterhead"
                            />
                            <InputError message={assetForm.errors.title} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="asset-description">Description</Label>
                            <Textarea
                                id="asset-description"
                                value={assetForm.data.description}
                                onChange={(event) => assetForm.setData('description', event.target.value)}
                                rows={3}
                                placeholder="Optional notes about how this asset should be used."
                            />
                            <InputError message={assetForm.errors.description} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="asset-files">Files</Label>
                            <Input
                                id="asset-files"
                                type="file"
                                multiple={!showVariantField}
                                accept={showVariantField ? '.jpg,.jpeg,.png,.gif,.webp,image/*' : undefined}
                                onChange={(event) => assetForm.setData('files', Array.from(event.target.files ?? []))}
                            />
                            <InputError message={assetForm.errors.files as string | undefined} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAssetOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={assetForm.processing}>
                                <Upload className="mr-2 size-4" />
                                Save asset
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={previewLogo !== null} onOpenChange={(open) => !open && setPreviewLogo(null)}>
                <DialogContent className="max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>{previewLogo?.title ?? previewLogo?.variant_label}</DialogTitle>
                    </DialogHeader>
                    {previewLogo && (
                        <div className="bg-muted/40 flex max-h-[70vh] items-center justify-center overflow-hidden rounded-lg border p-6">
                            <img
                                src={previewLogo.preview_url}
                                alt={previewLogo.variant_label}
                                className="max-h-[60vh] max-w-full object-contain"
                            />
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </TaskLayout>
    );
}

function AssetCard({
    title,
    description,
    previewUrl,
    previewAlt,
    isImage,
    fileCount,
    files,
    onPreview,
    downloadUrl,
    onDelete,
}: {
    title: string;
    description: string;
    previewUrl?: string;
    previewAlt?: string;
    isImage?: boolean;
    fileCount?: number;
    files?: AssetFile[];
    onPreview?: () => void;
    downloadUrl?: string;
    onDelete?: () => void;
}) {
    return (
        <Card className="min-w-0">
            <CardHeader className="pb-3">
                <CardTitle className="text-base">{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="bg-muted/40 flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-lg border">
                    {previewUrl && isImage ? (
                        <button type="button" className="flex h-full w-full items-center justify-center" onClick={onPreview}>
                            <img src={previewUrl} alt={previewAlt ?? title} className="max-h-full max-w-full object-contain p-4" loading="lazy" />
                        </button>
                    ) : (
                        <div className="text-muted-foreground flex flex-col items-center gap-2 p-6 text-center text-sm">
                            <FileText className="size-8" />
                            {fileCount ? `${fileCount} file(s)` : 'File preview unavailable'}
                        </div>
                    )}
                </div>
                <div className="flex flex-wrap gap-2">
                    {downloadUrl && (
                        <Button asChild size="sm" variant="outline">
                            <a href={downloadUrl}>
                                <Download className="mr-2 size-4" />
                                Download
                            </a>
                        </Button>
                    )}
                    {onPreview && (
                        <Button size="sm" variant="ghost" onClick={onPreview}>
                            Preview
                        </Button>
                    )}
                    {files?.map((file) => (
                        <Button key={file.uuid} asChild size="sm" variant="ghost">
                            <a href={file.download_url}>
                                <Download className="mr-2 size-4" />
                                {file.name}
                            </a>
                        </Button>
                    ))}
                    {onDelete && (
                        <Button size="sm" variant="ghost" onClick={onDelete}>
                            <Trash2 className="mr-2 size-4" />
                            Delete
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
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
                    {href?.startsWith('http') && <ExternalLink className="text-muted-foreground size-4" />}
                    {label === 'Email' && value && <Mail className="text-muted-foreground size-4" />}
                    {label === 'Phone' && value && <Phone className="text-muted-foreground size-4" />}
                </div>
            )}
        </div>
    );
}
