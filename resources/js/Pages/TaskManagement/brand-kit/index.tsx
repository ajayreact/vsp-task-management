import { SearchInput } from '@/components/admin/search-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Copy, ExternalLink, FileImage, Mail, Palette, Phone } from 'lucide-react';
import { useEffect, useState } from 'react';

interface PreviewAsset {
    uuid: string;
    name: string;
    preview_url: string;
    download_url: string;
    is_image: boolean;
}

interface PhoneRow {
    id: number | null;
    label: string | null;
    phone: string;
    is_primary: boolean;
}

interface CompanySummary {
    id: number;
    name: string;
    website: string | null;
    email: string | null;
    phone: string | null;
    phones: PhoneRow[];
    asset_count: number;
    preview_assets: PreviewAsset[];
    can: {
        manage: boolean;
        share: boolean;
    };
}

interface Props {
    companies: Paginated<CompanySummary>;
    filters: { search: string | null };
    can: { manage: boolean };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Brand Kit', href: '/tasks/brand-kit' },
];

function copyText(value: string) {
    void navigator.clipboard.writeText(value);
}

export default function BrandKitIndex({ companies, filters, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        setSearch(filters.search ?? '');
    }, [filters.search]);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                '/tasks/brand-kit',
                {
                    search: search.trim() === '' ? undefined : search.trim(),
                    page: 1,
                    per_page: companies.per_page,
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [search, companies.per_page]);

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Brand Kit" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
                <div className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <div className="mb-2 inline-flex items-center gap-2 rounded-full border bg-muted/40 px-3 py-1 text-xs font-medium text-muted-foreground">
                            <Palette className="size-3.5" />
                            Centralized brand assets
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight">Brand Kit</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Store logos, letterheads, business cards, email signatures, guidelines, and other client brand assets.
                        </p>
                    </div>
                    <SearchInput
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search companies or contacts..."
                        containerClassName="w-full min-w-0 sm:max-w-sm"
                    />
                </div>

                {companies.data.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No companies match your search.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {companies.data.map((company) => (
                            <Card key={company.id} className="flex h-full min-w-0 flex-col">
                                <CardHeader className="space-y-3">
                                    <div className="flex min-w-0 items-start justify-between gap-3">
                                        <div className="min-w-0 flex-1">
                                            <CardTitle className="truncate text-lg">{company.name}</CardTitle>
                                            <CardDescription className="mt-2 space-y-1.5">
                                                {company.website && (
                                                    <span className="flex min-w-0 items-center gap-2">
                                                        <ExternalLink className="size-3.5 shrink-0" />
                                                        <a
                                                            href={company.website}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="truncate hover:underline"
                                                        >
                                                            {company.website.replace(/^https?:\/\//, '')}
                                                        </a>
                                                    </span>
                                                )}
                                                {company.email && (
                                                    <span className="flex min-w-0 items-center gap-2">
                                                        <Mail className="size-3.5 shrink-0" />
                                                        <span className="truncate">{company.email}</span>
                                                    </span>
                                                )}
                                                {(company.phones.length > 0 ? company.phones : company.phone ? [{ phone: company.phone, label: null }] : []).map(
                                                    (phoneRow, index) => (
                                                        <span key={`${company.id}-phone-${index}`} className="flex min-w-0 items-center gap-2">
                                                            <Phone className="size-3.5 shrink-0" />
                                                            <span className="truncate">
                                                                {phoneRow.label ? `${phoneRow.label}: ` : ''}
                                                                {phoneRow.phone}
                                                            </span>
                                                        </span>
                                                    ),
                                                )}
                                            </CardDescription>
                                        </div>
                                        <Badge className="shrink-0" variant="secondary">
                                            {company.asset_count} assets
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="mt-auto space-y-4">
                                    {company.preview_assets.length > 0 ? (
                                        <div className="grid grid-cols-2 gap-3">
                                            {company.preview_assets.slice(0, 4).map((asset) => (
                                                <div key={asset.uuid} className="space-y-2 rounded-lg border p-2">
                                                    <div className="bg-muted/40 flex aspect-[4/3] items-center justify-center overflow-hidden rounded-md">
                                                        {asset.is_image ? (
                                                            <img
                                                                src={asset.preview_url}
                                                                alt={asset.name}
                                                                className="max-h-full max-w-full object-contain p-2"
                                                            />
                                                        ) : (
                                                            <FileImage className="text-muted-foreground size-8" />
                                                        )}
                                                    </div>
                                                    <p className="truncate text-xs font-medium">{asset.name}</p>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-muted-foreground text-sm">No brand assets uploaded yet.</p>
                                    )}

                                    <div className="flex flex-wrap gap-2">
                                        <Button asChild size="sm">
                                            <Link href={`/tasks/brand-kit/${company.id}`}>View Brand Kit</Link>
                                        </Button>
                                        {company.email && (
                                            <Button size="sm" variant="ghost" onClick={() => copyText(company.email!)}>
                                                <Copy className="mr-1 size-4" />
                                                Copy email
                                            </Button>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {companies.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <p className="text-muted-foreground">
                            Page {companies.current_page} of {companies.last_page}
                        </p>
                        <div className="flex gap-2">
                            {companies.prev_page_url && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={companies.prev_page_url} preserveScroll>
                                        Previous
                                    </Link>
                                </Button>
                            )}
                            {companies.next_page_url && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={companies.next_page_url} preserveScroll>
                                        Next
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}

                {can.manage && (
                    <p className="text-muted-foreground text-xs">
                        Manage brand assets, contact numbers, and share links from each company Brand Kit page.
                    </p>
                )}
            </div>
        </TaskLayout>
    );
}
