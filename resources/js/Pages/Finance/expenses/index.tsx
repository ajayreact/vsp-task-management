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
import { ArrowUpRight, Clock3, IndianRupee, Plus, Wallet } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ExpenseRow {
    id: number;
    expense_date: string;
    category: string;
    description: string;
    amount: number;
    payment_status: string;
    notes: string | null;
}

interface Props {
    expenses: Paginated<ExpenseRow>;
    filters: {
        search: string;
        category: string;
        payment_status: string;
        date_from: string;
        date_to: string;
    };
    categories: Option[];
    payment_statuses: Option[];
    summaries: { total: number; paid: number; pending: number };
    has_any_records: boolean;
    open_create?: boolean;
}

type ExpenseFormValues = {
    expense_date: string;
    category: string;
    description: string;
    amount: string;
    payment_status: string;
    notes: string;
};

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Finance', href: '/admin/finance' },
    { title: 'My Expenses', href: '/admin/finance/expenses' },
];

const statusTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    paid: 'success',
    pending: 'warning',
};

function todayInputValue(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function blankForm(): ExpenseFormValues {
    return {
        expense_date: todayInputValue(),
        category: 'personal',
        description: '',
        amount: '',
        payment_status: 'paid',
        notes: '',
    };
}

function formatDate(value: string): string {
    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function MyExpensesIndex({
    expenses,
    filters,
    categories,
    payment_statuses,
    summaries,
    has_any_records,
    open_create = false,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [editing, setEditing] = useState<ExpenseRow | null>(null);
    const [viewing, setViewing] = useState<ExpenseRow | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const form = useForm<ExpenseFormValues>(blankForm());

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
            '/admin/finance/expenses',
            {
                search: search || undefined,
                category: filters.category || undefined,
                payment_status: filters.payment_status || undefined,
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
                per_page: expenses.per_page,
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

    const startEdit = (expense: ExpenseRow) => {
        setEditing(expense);
        form.clearErrors();
        form.setData({
            expense_date: expense.expense_date,
            category: expense.category,
            description: expense.description,
            amount: String(expense.amount),
            payment_status: expense.payment_status,
            notes: expense.notes ?? '',
        });
        setFormOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setFormOpen(false) };

        if (editing) {
            form.put(`/admin/finance/expenses/${editing.id}`, options);
        } else {
            form.post('/admin/finance/expenses', options);
        }
    };

    const emptyMessage = has_any_records ? 'No expense records match these filters.' : 'No expense records yet';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Expenses" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="My Expenses"
                    description="Track personal spending in Indian Rupees (₹)."
                    action={
                        <Button type="button" onClick={startCreate}>
                            <Plus /> Add Expense
                        </Button>
                    }
                />

                <FinanceSectionNav active="expenses" />

                <div className="grid gap-4 sm:grid-cols-3">
                    <KpiStatCard label="Total Expenses" value={formatInr(summaries.total)} icon={Wallet} tone="indigo" />
                    <KpiStatCard label="Paid" value={formatInr(summaries.paid)} icon={IndianRupee} tone="emerald" />
                    <KpiStatCard label="Pending" value={formatInr(summaries.pending)} icon={Clock3} tone="amber" />
                </div>

                <DataTableCard
                    title="Expense records"
                    description="Private to your Super Admin account."
                    action={
                        <Button type="button" size="sm" onClick={startCreate}>
                            <Plus /> Add Expense
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
                                    value={filters.category || ALL}
                                    onValueChange={(value) => applyFilter({ category: value === ALL ? null : value })}
                                >
                                    <SelectTrigger className="w-44" aria-label="Filter by category">
                                        <SelectValue placeholder="Any category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>Any category</SelectItem>
                                        {categories.map((category) => (
                                            <SelectItem key={category.value} value={category.value}>
                                                {category.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={filters.payment_status || ALL}
                                    onValueChange={(value) => applyFilter({ payment_status: value === ALL ? null : value })}
                                >
                                    <SelectTrigger className="w-40" aria-label="Filter by payment status">
                                        <SelectValue placeholder="Any status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>Any status</SelectItem>
                                        {payment_statuses.map((status) => (
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
                                placeholder="Search description or notes"
                                aria-label="Search expenses"
                            />
                        </>
                    }
                    footer={
                        <Pagination
                            page={expenses}
                            leading={<EntriesSelect value={expenses.per_page} onChange={(perPage) => applyFilter({ per_page: perPage })} />}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-right">Amount</TableHead>
                                <TableHead>Payment Status</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-16 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {expenses.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-muted-foreground py-12 text-center">
                                        <div className="flex flex-col items-center gap-3">
                                            <span className="bg-primary/10 text-primary flex size-12 items-center justify-center rounded-full">
                                                <ArrowUpRight className="size-5" strokeWidth={1.75} />
                                            </span>
                                            <p className="text-foreground text-sm font-medium">{emptyMessage}</p>
                                            {!has_any_records && (
                                                <Button type="button" onClick={startCreate}>
                                                    <Plus /> Add Expense
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}

                            {expenses.data.map((expense) => (
                                <TableRow key={expense.id}>
                                    <TableCell className="whitespace-nowrap text-sm">{formatDate(expense.expense_date)}</TableCell>
                                    <TableCell className="text-sm">
                                        {categories.find((category) => category.value === expense.category)?.label ?? expense.category}
                                    </TableCell>
                                    <TableCell className="max-w-[14rem] truncate font-medium" title={expense.description}>
                                        {expense.description}
                                    </TableCell>
                                    <TableCell className="text-right text-sm font-medium tabular-nums">{formatInr(expense.amount)}</TableCell>
                                    <TableCell>
                                        <Badge variant={statusTone[expense.payment_status] ?? 'neutral'}>
                                            {payment_statuses.find((status) => status.value === expense.payment_status)?.label ??
                                                expense.payment_status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground max-w-[12rem] truncate text-sm" title={expense.notes ?? undefined}>
                                        {expense.notes ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <RowActions
                                            label={`Actions for ${expense.description}`}
                                            items={[
                                                { key: 'view', label: 'View', onSelect: () => setViewing(expense) },
                                                { key: 'edit', label: 'Edit', onSelect: () => startEdit(expense) },
                                                {
                                                    key: 'delete',
                                                    label: 'Delete',
                                                    confirm: {
                                                        url: `/admin/finance/expenses/${expense.id}`,
                                                        title: `Delete expense “${expense.description}”?`,
                                                        description: 'This permanently removes the expense record.',
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
                            <DialogTitle>{editing ? 'Edit Expense' : 'Add Expense'}</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="expense_date">Date</Label>
                                <Input
                                    id="expense_date"
                                    type="date"
                                    value={form.data.expense_date}
                                    onChange={(event) => form.setData('expense_date', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.expense_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="category">Expense Category</Label>
                                <Select value={form.data.category} onValueChange={(value) => form.setData('category', value)}>
                                    <SelectTrigger id="category">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.value} value={category.value}>
                                                {category.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.category} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="description">Description / Reason</Label>
                                <Input
                                    id="description"
                                    value={form.data.description}
                                    onChange={(event) => form.setData('description', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="amount">Amount (₹)</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={form.data.amount}
                                    onChange={(event) => form.setData('amount', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.amount} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="payment_status">Payment Status</Label>
                                <Select value={form.data.payment_status} onValueChange={(value) => form.setData('payment_status', value)}>
                                    <SelectTrigger id="payment_status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {payment_statuses.map((status) => (
                                            <SelectItem key={status.value} value={status.value}>
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.payment_status} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea id="notes" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} rows={3} />
                                <InputError message={form.errors.notes} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setFormOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {editing ? 'Save changes' : 'Add Expense'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={viewing !== null} onOpenChange={(open) => !open && setViewing(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Expense details</DialogTitle>
                    </DialogHeader>
                    {viewing && (
                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">Date</dt>
                                <dd className="mt-0.5 font-medium">{formatDate(viewing.expense_date)}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Category</dt>
                                <dd className="mt-0.5 font-medium">
                                    {categories.find((category) => category.value === viewing.category)?.label ?? viewing.category}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Description</dt>
                                <dd className="mt-0.5 font-medium">{viewing.description}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Amount</dt>
                                <dd className="mt-0.5 font-medium tabular-nums">{formatInr(viewing.amount)}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Payment Status</dt>
                                <dd className="mt-1">
                                    <Badge variant={statusTone[viewing.payment_status] ?? 'neutral'}>
                                        {payment_statuses.find((status) => status.value === viewing.payment_status)?.label ??
                                            viewing.payment_status}
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
