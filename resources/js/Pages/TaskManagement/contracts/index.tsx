import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { RowActions, type RowActionItem } from '@/components/admin/row-actions';
import { SearchInput } from '@/components/admin/search-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Copy, Plus } from 'lucide-react';
import { useEffect } from 'react';

interface ContractRow {
    id: number;
    contract_number: string;
    title: string;
    contract_type: string;
    contract_type_label: string;
    country: string;
    country_label: string;
    currency: string;
    status: string;
    status_label: string;
    status_variant: 'default' | 'secondary' | 'outline' | 'destructive';
    client: { id: number; name: string };
    effective_date: string;
    created_at: string;
    signed_at: string | null;
    created_by: string;
    has_pdf: boolean;
    urls: {
        show: string;
        edit: string;
        preview: string;
        download: string;
    };
    can: { update: boolean; share: boolean };
}

interface Props {
    contracts: Paginated<ContractRow>;
    clients: { id: number; name: string }[];
    creators: { id: number; name: string }[];
    statuses: Option[];
    contractTypes: Option[];
    countries: Option[];
    filters: {
        search: string | null;
        status: string | null;
        client: number | null;
        contract_type: string | null;
        country: string | null;
        created_by: number | null;
    };
    can: { manage: boolean; share: boolean };
}

type ContractsPageProps = Props & {
    flash?: { share_url?: string; share_message?: string; success?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Contracts', href: '/tasks/contracts' },
];

const date = (value: string) => new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });

export default function ContractsIndex({ contracts, clients, creators, statuses, contractTypes, countries, filters, can }: Props) {
    const { flash } = usePage<ContractsPageProps>().props;
    const shareText = flash?.share_message ?? flash?.share_url ?? '';

    useEffect(() => {
        if (shareText) {
            void navigator.clipboard.writeText(shareText);
        }
    }, [shareText]);

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/contracts',
            {
                per_page: contracts.per_page,
                search: filters.search,
                status: filters.status,
                client: filters.client,
                contract_type: filters.contract_type,
                country: filters.country,
                created_by: filters.created_by,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const rowActions = (contract: ContractRow): RowActionItem[] => {
        const items: RowActionItem[] = [
            { key: 'view', label: 'View', href: contract.urls.show },
            { key: 'preview', label: 'Preview', href: contract.urls.preview },
        ];

        if (contract.has_pdf) {
            items.push({ key: 'download', label: 'Download PDF', href: contract.urls.download });
        }

        if (contract.can.update) {
            items.push({ key: 'edit', label: 'Edit', href: contract.urls.edit });
        }

        if (contract.can.share) {
            items.push({
                key: 'share',
                label: 'Copy signing link',
                onSelect: () => router.post(`/tasks/contracts/${contract.id}/share-link`, {}, { preserveScroll: true }),
            });
        }

        return items;
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Contracts" />

            <div className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Contracts"
                    description="Create, send, and track client service agreements."
                    action={
                        can.manage ? (
                            <Button asChild>
                                <Link href="/tasks/contracts/create">
                                    <Plus /> New contract
                                </Link>
                            </Button>
                        ) : undefined
                    }
                    toolbar={
                        <div className="flex min-w-0 flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
                            <SearchInput
                                value={filters.search ?? ''}
                                onChange={(search) => apply({ search: search || null })}
                                placeholder="Search contracts..."
                                className="w-full lg:max-w-xs"
                            />
                            <Select
                                value={filters.status ?? 'all'}
                                onValueChange={(value) => apply({ status: value === 'all' ? null : value })}
                            >
                                <SelectTrigger className="w-full lg:w-44">
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    {statuses.map((status) => (
                                        <SelectItem key={status.value} value={status.value}>
                                            {status.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.client ? String(filters.client) : 'all'}
                                onValueChange={(value) => apply({ client: value === 'all' ? null : Number(value) })}
                            >
                                <SelectTrigger className="w-full lg:w-44">
                                    <SelectValue placeholder="All clients" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All clients</SelectItem>
                                    {clients.map((client) => (
                                        <SelectItem key={client.id} value={String(client.id)}>
                                            {client.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.contract_type ?? 'all'}
                                onValueChange={(value) => apply({ contract_type: value === 'all' ? null : value })}
                            >
                                <SelectTrigger className="w-full lg:w-52">
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
                                    {contractTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.country ?? 'all'}
                                onValueChange={(value) => apply({ country: value === 'all' ? null : value })}
                            >
                                <SelectTrigger className="w-full lg:w-40">
                                    <SelectValue placeholder="All countries" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All countries</SelectItem>
                                    {countries.map((country) => (
                                        <SelectItem key={country.value} value={country.value}>
                                            {country.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.created_by ? String(filters.created_by) : 'all'}
                                onValueChange={(value) => apply({ created_by: value === 'all' ? null : Number(value) })}
                            >
                                <SelectTrigger className="w-full lg:w-44">
                                    <SelectValue placeholder="All creators" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All creators</SelectItem>
                                    {creators.map((creator) => (
                                        <SelectItem key={creator.id} value={String(creator.id)}>
                                            {creator.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                    footer={<DataTableFooter page={contracts} onPerPageChange={(perPage) => apply({ per_page: perPage })} />}
                >
                    <div className="overflow-x-auto">
                        <Table className="min-w-max">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Contract</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Effective</TableHead>
                                    <TableHead>Created by</TableHead>
                                    <TableHead className="w-16 text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {contracts.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-muted-foreground py-10 text-center">
                                            No contracts yet.
                                        </TableCell>
                                    </TableRow>
                                )}

                                {contracts.data.map((contract) => (
                                    <TableRow key={contract.id}>
                                        <TableCell className="max-w-xs">
                                            <Link href={contract.urls.show} className="font-medium hover:underline">
                                                {contract.title}
                                            </Link>
                                            <div className="text-muted-foreground text-xs">{contract.contract_number}</div>
                                        </TableCell>
                                        <TableCell>{contract.client.name}</TableCell>
                                        <TableCell>
                                            <Badge variant="neutral">{contract.contract_type_label}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={contract.status_variant}>{contract.status_label}</Badge>
                                        </TableCell>
                                        <TableCell className="text-sm">{date(contract.effective_date)}</TableCell>
                                        <TableCell className="text-sm">{contract.created_by}</TableCell>
                                        <TableCell className="text-right">
                                            <RowActions label={`Actions for ${contract.title}`} items={rowActions(contract)} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </DataTableCard>

                {shareText && (
                    <div className="bg-muted/50 flex flex-col gap-2 rounded-lg border p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span className="break-all whitespace-pre-line">Signing message copied to clipboard.</span>
                        <Button variant="outline" size="sm" onClick={() => void navigator.clipboard.writeText(shareText)}>
                            <Copy className="size-4" /> Copy again
                        </Button>
                    </div>
                )}
            </div>
        </TaskLayout>
    );
}
