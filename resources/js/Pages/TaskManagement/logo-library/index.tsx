import { SearchInput } from '@/components/admin/search-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Copy, Download, ExternalLink, Mail, Phone } from 'lucide-react';
import { useEffect, useState } from 'react';

interface LogoSummary {
    uuid: string;
    name: string;
    variant_label: string;
    preview_url: string;
    download_url: string;
}

interface CompanySummary {
    id: number;
    name: string;
    website: string | null;
    email: string | null;
    phone: string | null;
    logos: LogoSummary[];
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
    { title: 'Company Logo Library', href: '/tasks/logo-library' },
];

function copyText(value: string) {
    void navigator.clipboard.writeText(value);
}

export default function CompanyLogoLibraryIndex({ companies, filters, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        setSearch(filters.search ?? '');
    }, [filters.search]);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                '/tasks/logo-library',
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
            <Head title="Company Logo Library" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
                <div className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight">Company Logo Library</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Central place for client logos and contact details.
                        </p>
                    </div>
                    <SearchInput
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search company..."
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
                                            <CardDescription className="mt-1 space-y-1">
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
                                                {company.phone && (
                                                    <span className="flex min-w-0 items-center gap-2">
                                                        <Phone className="size-3.5 shrink-0" />
                                                        <span className="truncate">{company.phone}</span>
                                                    </span>
                                                )}
                                            </CardDescription>
                                        </div>
                                        <Badge className="shrink-0" variant="secondary">
                                            {company.logos.length} logos
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="mt-auto space-y-4">
                                    {company.logos.length > 0 ? (
                                        <div className="grid grid-cols-2 gap-3">
                                            {company.logos.slice(0, 4).map((logo) => (
                                                <div key={logo.uuid} className="space-y-2 rounded-lg border p-2">
                                                    <div className="bg-muted/40 flex aspect-[4/3] items-center justify-center overflow-hidden rounded-md">
                                                        <img
                                                            src={logo.preview_url}
                                                            alt={logo.variant_label}
                                                            className="max-h-full max-w-full object-contain p-2"
                                                        />
                                                    </div>
                                                    <div className="flex items-center justify-between gap-2">
                                                        <span className="truncate text-xs font-medium">{logo.variant_label}</span>
                                                        <Button asChild size="icon" variant="ghost" className="size-7 shrink-0">
                                                            <a href={logo.download_url} download={logo.name}>
                                                                <Download className="size-4" />
                                                            </a>
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-muted-foreground text-sm">No logos uploaded yet.</p>
                                    )}

                                    <div className="flex flex-wrap gap-2">
                                        <Button asChild size="sm" variant="outline">
                                            <Link href={`/tasks/logo-library/${company.id}`}>View</Link>
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
                        Manage logos and share links from each company detail page.
                    </p>
                )}
            </div>
        </TaskLayout>
    );
}
