import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { KpiStatCard } from '@/components/admin/kpi-stat-card';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { RowActions } from '@/components/admin/row-actions';
import { SearchInput } from '@/components/admin/search-input';
import { FinanceSectionNav } from '@/components/finance/finance-section-nav';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatInr } from '@/lib/money';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowDownLeft, Clock3, IndianRupee, Plus, Wallet } from 'lucide-react';
import { useEffect, useState } from 'react';

interface IncomeRow {
    id: number;
    income_date: string;
    person_name: string;
    mobile_number: string | null;
    reason: string;
    amount: number;
    status: string;
    notes: string | null;
}

interface Props {
    incomes: Paginated<IncomeRow>;
    filters: {
        search: string;
        status: string;
        date_from: string;
        date_to: string;
    };
    statuses: Option[];
    summaries: {
        total: number;
        received: number;
        pending: number;
    };
    has_any_records: boolean;
    open_create?: boolean;
}

type IncomeFormValues = {
    income_date: string;
    person_name: string;
    mobile_number: string;
    reason: string;
    amount: string;
    status: string;
    notes: string;
};

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Finance', href: '/admin/finance' },
    { title: 'My Income', href: '/admin/finance/income' },
];

const statusTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    received: 'success',
    pending: 'warning',
    cancelled: 'neutral',
};

function todayInputValue(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function blankForm(): IncomeFormValues {
    return {
        income_date: todayInputValue(),
        person_name: '',
        mobile_number: '',
        reason: '',
        amount: '',
        status: 'received',
        notes: '',
    };
}

function formatIncomeDate(value: string): string {
    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function MyIncomeIndex({ incomes, filters, statuses, summaries, has_any_records, open_create = false }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [editing, setEditing] = useState<IncomeRow | null>(null);
    const [viewing, setViewing] = useState<IncomeRow | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const form = useForm<IncomeFormValues>(blankForm());

    useEffect(() => {
        if (open_create) {
            startCreate();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open_create]);

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(() => applyFilter({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const applyFilter = (changes: Record<string, string | number | null>) => {
        router.get(
            '/admin/finance/income',
            {
                search: search || undefined,
                status: filters.status || undefined,
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
                per_page: incomes.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const startCreate = () => {
        setEditing(null);
        form.clearErrors();
        form.setData(blankForm());
        setFormOpen(true);
    };

    const startEdit = (income: IncomeRow) => {
        setEditing(income);
        form.clearErrors();
        form.setData({
            income_date: income.income_date,
            person_name: income.person_name,
            mobile_number: income.mobile_number ?? '',
            reason: income.reason,
            amount: String(income.amount),
            status: income.status,
            notes: income.notes ?? '',
        });
        setFormOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => setFormOpen(false),
        };

        if (editing) {
            form.put(`/admin/finance/income/${editing.id}`, options);
        } else {
            form.post('/admin/finance/income', options);
        }
    };

    const emptyMessage = has_any_records ? 'No income records match these filters.' : 'No income records yet';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Income" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="My Income"
                    description="Record and monitor personal incoming money in Indian Rupees (₹). Total Income excludes cancelled records."
                    action={
                        <Button type="button" onClick={startCreate}>
                            <Plus /> Add Income
                        </Button>
                    }
                />

                <FinanceSectionNav active="income" />

                <div className="grid gap-4 sm:grid-cols-3">
                    <KpiStatCard
                        label="Total Income"
                        value={formatInr(summaries.total)}
                        icon={Wallet}
                        tone="indigo"
                        footer={<span className="text-muted-foreground text-xs">All non-cancelled records</span>}
                    />
                    <KpiStatCard
                        label="Received"
                        value={formatInr(summaries.received)}
                        icon={IndianRupee}
                        tone="emerald"
                        footer={<span className="text-muted-foreground text-xs">Money already received</span>}
                    />
                    <KpiStatCard
                        label="Pending"
                        value={formatInr(summaries.pending)}
                        icon={Clock3}
                        tone="amber"
                        footer={<span className="text-muted-foreground text-xs">Awaiting receipt</span>}
                    />
                </div>

                <DataTableCard
                    title="Income records"
                    description="Private to your Super Admin account."
                    action={
                        <Button type="button" size="sm" onClick={startCreate}>
                            <Plus /> Add Income
                        </Button>
                    }
                    toolbar={
                        <>
                            <div className="flex flex-wrap items-center gap-3">
                                <Input
                                    type="date"
                                    value={filters.date_from}
                                    onChange={(event) => applyFilter({ date_from: event.target.value || null })}
                                    aria-label="Filter from date"
                                    className="w-40"
                                />
                                <Input
                                    type="date"
                                    value={filters.date_to}
                                    onChange={(event) => applyFilter({ date_to: event.target.value || null })}
                                    aria-label="Filter to date"
                                    className="w-40"
                                />
                                <Select
                                    value={filters.status || ALL}
                                    onValueChange={(value) => applyFilter({ status: value === ALL ? null : value })}
                                >
                                    <SelectTrigger className="w-44" aria-label="Filter by status">
                                        <SelectValue placeholder="Any status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>Any status</SelectItem>
                                        {statuses.map((status) => (
                                            <SelectItem key={status.value} value={status.value}>
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search person, mobile, reason, notes"
                                aria-label="Search income"
                            />
                        </>
                    }
                    footer={
                        <Pagination
                            page={incomes}
                            leading={<EntriesSelect value={incomes.per_page} onChange={(perPage) => applyFilter({ per_page: perPage })} />}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Person Name</TableHead>
                                <TableHead>Mobile Number</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead className="text-right">Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-16 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {incomes.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-muted-foreground py-12 text-center">
                                        <div className="flex flex-col items-center gap-3">
                                            <span className="bg-primary/10 text-primary flex size-12 items-center justify-center rounded-full">
                                                <ArrowDownLeft className="size-5" strokeWidth={1.75} />
                                            </span>
                                            <div className="space-y-1">
                                                <p className="text-foreground text-sm font-medium">{emptyMessage}</p>
                                                {!has_any_records && (
                                                    <p className="text-muted-foreground text-xs">Add your first income record to get started.</p>
                                                )}
                                            </div>
                                            {!has_any_records && (
                                                <Button type="button" onClick={startCreate}>
                                                    <Plus /> Add Income
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}

                            {incomes.data.map((income) => (
                                <TableRow key={income.id}>
                                    <TableCell className="whitespace-nowrap text-sm">{formatIncomeDate(income.income_date)}</TableCell>
                                    <TableCell className="font-medium">{income.person_name}</TableCell>
                                    <TableCell className="text-sm">
                                        {income.mobile_number ?? <span className="text-muted-foreground">—</span>}
                                    </TableCell>
                                    <TableCell className="max-w-[14rem] truncate text-sm" title={income.reason}>
                                        {income.reason}
                                    </TableCell>
                                    <TableCell className="text-right text-sm font-medium tabular-nums">{formatInr(income.amount)}</TableCell>
                                    <TableCell>
                                        <Badge variant={statusTone[income.status] ?? 'neutral'}>
                                            {statuses.find((status) => status.value === income.status)?.label ?? income.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="max-w-[12rem] truncate text-sm text-muted-foreground" title={income.notes ?? undefined}>
                                        {income.notes ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <RowActions
                                            label={`Actions for income from ${income.person_name}`}
                                            items={[
                                                {
                                                    key: 'view',
                                                    label: 'View',
                                                    onSelect: () => setViewing(income),
                                                },
                                                {
                                                    key: 'edit',
                                                    label: 'Edit',
                                                    onSelect: () => startEdit(income),
                                                },
                                                {
                                                    key: 'delete',
                                                    label: 'Delete',
                                                    confirm: {
                                                        url: `/admin/finance/income/${income.id}`,
                                                        title: `Delete income from ${income.person_name}?`,
                                                        description: 'This permanently removes the income record. This cannot be undone.',
                                                    },
                                                },
                                            ]}
                                        />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="sm:max-w-lg">
                    <form onSubmit={submit} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Edit Income' : 'Add Income'}</DialogTitle>
                        </DialogHeader>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="income_date">Date</Label>
                                <Input
                                    id="income_date"
                                    type="date"
                                    value={form.data.income_date}
                                    onChange={(event) => form.setData('income_date', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.income_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem key={status.value} value={status.value}>
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.status} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="person_name">Person Name</Label>
                                <Input
                                    id="person_name"
                                    value={form.data.person_name}
                                    onChange={(event) => form.setData('person_name', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.person_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="mobile_number">Mobile Number</Label>
                                <Input
                                    id="mobile_number"
                                    value={form.data.mobile_number}
                                    onChange={(event) => form.setData('mobile_number', event.target.value)}
                                />
                                <InputError message={form.errors.mobile_number} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="amount">Amount (₹)</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    inputMode="decimal"
                                    min="0.01"
                                    step="0.01"
                                    value={form.data.amount}
                                    onChange={(event) => form.setData('amount', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.amount} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="reason">Reason / Description</Label>
                                <Input
                                    id="reason"
                                    value={form.data.reason}
                                    onChange={(event) => form.setData('reason', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.reason} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    value={form.data.notes}
                                    onChange={(event) => form.setData('notes', event.target.value)}
                                    rows={3}
                                />
                                <InputError message={form.errors.notes} />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setFormOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {editing ? 'Save changes' : 'Add Income'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={viewing !== null} onOpenChange={(open) => !open && setViewing(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Income details</DialogTitle>
                    </DialogHeader>

                    {viewing && (
                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">Date</dt>
                                <dd className="mt-0.5 font-medium">{formatIncomeDate(viewing.income_date)}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Person Name</dt>
                                <dd className="mt-0.5 font-medium">{viewing.person_name}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Mobile Number</dt>
                                <dd className="mt-0.5 font-medium">{viewing.mobile_number ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Reason</dt>
                                <dd className="mt-0.5 font-medium">{viewing.reason}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Amount</dt>
                                <dd className="mt-0.5 font-medium tabular-nums">{formatInr(viewing.amount)}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Status</dt>
                                <dd className="mt-1">
                                    <Badge variant={statusTone[viewing.status] ?? 'neutral'}>
                                        {statuses.find((status) => status.value === viewing.status)?.label ?? viewing.status}
                                    </Badge>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Notes</dt>
                                <dd className="mt-0.5 whitespace-pre-wrap">{viewing.notes ?? '—'}</dd>
                            </div>
                        </dl>
                    )}

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setViewing(null)}>
                            Close
                        </Button>
                        {viewing && (
                            <Button
                                type="button"
                                onClick={() => {
                                    const row = viewing;
                                    setViewing(null);
                                    startEdit(row);
                                }}
                            >
                                Edit
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
