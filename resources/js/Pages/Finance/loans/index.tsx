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
import { AlertTriangle, Banknote, HandCoins, IndianRupee, Plus, Wallet } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface LoanRow {
    id: number;
    loan_date: string;
    lender_name: string;
    mobile_number: string | null;
    reason: string;
    loan_amount: number;
    amount_paid: number;
    remaining_amount: number;
    due_date: string | null;
    status: string;
    notes: string | null;
}

interface Props {
    loans: Paginated<LoanRow>;
    filters: { search: string; status: string; date_from: string; date_to: string };
    statuses: Option[];
    summaries: {
        count: number;
        loan_amount: number;
        paid: number;
        remaining: number;
        overdue: number;
    };
    has_any_records: boolean;
    open_create?: boolean;
}

type LoanFormValues = {
    loan_date: string;
    lender_name: string;
    mobile_number: string;
    reason: string;
    loan_amount: string;
    amount_paid: string;
    due_date: string;
    status: string;
    notes: string;
};

type PaymentFormValues = {
    payment_date: string;
    amount: string;
    note: string;
};

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Finance', href: '/admin/finance' },
    { title: 'My Loans', href: '/admin/finance/loans' },
];

const statusTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    active: 'info',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'danger',
    cancelled: 'neutral',
};

function todayInputValue(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function blankForm(): LoanFormValues {
    return {
        loan_date: todayInputValue(),
        lender_name: '',
        mobile_number: '',
        reason: '',
        loan_amount: '',
        amount_paid: '0',
        due_date: '',
        status: 'active',
        notes: '',
    };
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function MyLoansIndex({ loans, filters, statuses, summaries, has_any_records, open_create = false }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [editing, setEditing] = useState<LoanRow | null>(null);
    const [viewing, setViewing] = useState<LoanRow | null>(null);
    const [paying, setPaying] = useState<LoanRow | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const form = useForm<LoanFormValues>(blankForm());
    const paymentForm = useForm<PaymentFormValues>({
        payment_date: todayInputValue(),
        amount: '',
        note: '',
    });

    const remainingPreview = useMemo(() => {
        const loanAmount = Number(form.data.loan_amount) || 0;
        const amountPaid = Number(form.data.amount_paid) || 0;

        return Math.max(0, loanAmount - amountPaid);
    }, [form.data.loan_amount, form.data.amount_paid]);

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
            '/admin/finance/loans',
            {
                search: search || undefined,
                status: filters.status || undefined,
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
                per_page: loans.per_page,
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

    const startEdit = (loan: LoanRow) => {
        setEditing(loan);
        form.clearErrors();
        form.setData({
            loan_date: loan.loan_date,
            lender_name: loan.lender_name,
            mobile_number: loan.mobile_number ?? '',
            reason: loan.reason,
            loan_amount: String(loan.loan_amount),
            amount_paid: String(loan.amount_paid),
            due_date: loan.due_date ?? '',
            status: loan.status,
            notes: loan.notes ?? '',
        });
        setFormOpen(true);
    };

    const startPayment = (loan: LoanRow) => {
        setPaying(loan);
        paymentForm.clearErrors();
        paymentForm.setData({
            payment_date: todayInputValue(),
            amount: loan.remaining_amount > 0 ? String(loan.remaining_amount) : '',
            note: '',
        });
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setFormOpen(false) };

        if (editing) {
            form.put(`/admin/finance/loans/${editing.id}`, options);
        } else {
            form.post('/admin/finance/loans', options);
        }
    };

    const submitPayment = (event: React.FormEvent) => {
        event.preventDefault();
        if (!paying) {
            return;
        }

        paymentForm.post(`/admin/finance/loans/${paying.id}/payments`, {
            preserveScroll: true,
            onSuccess: () => setPaying(null),
        });
    };

    const emptyMessage = has_any_records ? 'No loan records match these filters.' : 'No loan records yet';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Loans" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="My Loans"
                    description="Monitor money you owe and record repayments in Indian Rupees (₹)."
                    action={
                        <Button type="button" onClick={startCreate}>
                            <Plus /> Add Loan
                        </Button>
                    }
                />

                <FinanceSectionNav active="loans" />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <KpiStatCard label="Total Loans" value={String(summaries.count)} icon={HandCoins} tone="indigo" />
                    <KpiStatCard label="Total Loan Amount" value={formatInr(summaries.loan_amount)} icon={Wallet} tone="sky" />
                    <KpiStatCard label="Total Paid" value={formatInr(summaries.paid)} icon={IndianRupee} tone="emerald" />
                    <KpiStatCard label="Total Remaining" value={formatInr(summaries.remaining)} icon={Banknote} tone="teal" />
                    <KpiStatCard label="Overdue" value={formatInr(summaries.overdue)} icon={AlertTriangle} tone="amber" />
                </div>

                <DataTableCard
                    title="Loan records"
                    description="Remaining amount is calculated automatically from loan amount minus amount paid."
                    action={
                        <Button type="button" size="sm" onClick={startCreate}>
                            <Plus /> Add Loan
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
                                placeholder="Search lender, mobile, reason"
                                aria-label="Search loans"
                            />
                        </>
                    }
                    footer={
                        <Pagination
                            page={loans}
                            leading={<EntriesSelect value={loans.per_page} onChange={(perPage) => applyFilter({ per_page: perPage })} />}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Person / Lender</TableHead>
                                <TableHead>Mobile</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead className="text-right">Loan Amount</TableHead>
                                <TableHead className="text-right">Amount Paid</TableHead>
                                <TableHead className="text-right">Remaining</TableHead>
                                <TableHead>Due Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-16 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {loans.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={10} className="text-muted-foreground py-12 text-center">
                                        <div className="flex flex-col items-center gap-3">
                                            <span className="bg-primary/10 text-primary flex size-12 items-center justify-center rounded-full">
                                                <HandCoins className="size-5" strokeWidth={1.75} />
                                            </span>
                                            <p className="text-foreground text-sm font-medium">{emptyMessage}</p>
                                            {!has_any_records && (
                                                <Button type="button" onClick={startCreate}>
                                                    <Plus /> Add Loan
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}

                            {loans.data.map((loan) => (
                                <TableRow key={loan.id}>
                                    <TableCell className="whitespace-nowrap text-sm">{formatDate(loan.loan_date)}</TableCell>
                                    <TableCell className="font-medium">{loan.lender_name}</TableCell>
                                    <TableCell className="text-sm">{loan.mobile_number ?? '—'}</TableCell>
                                    <TableCell className="max-w-[12rem] truncate text-sm" title={loan.reason}>
                                        {loan.reason}
                                    </TableCell>
                                    <TableCell className="text-right text-sm tabular-nums">{formatInr(loan.loan_amount)}</TableCell>
                                    <TableCell className="text-right text-sm tabular-nums">{formatInr(loan.amount_paid)}</TableCell>
                                    <TableCell className="text-right text-sm font-medium tabular-nums">{formatInr(loan.remaining_amount)}</TableCell>
                                    <TableCell className="whitespace-nowrap text-sm">{formatDate(loan.due_date)}</TableCell>
                                    <TableCell>
                                        <Badge variant={statusTone[loan.status] ?? 'neutral'}>
                                            {statuses.find((status) => status.value === loan.status)?.label ?? loan.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <RowActions
                                            label={`Actions for loan from ${loan.lender_name}`}
                                            items={[
                                                { key: 'view', label: 'View', onSelect: () => setViewing(loan) },
                                                { key: 'edit', label: 'Edit', onSelect: () => startEdit(loan) },
                                                ...(loan.remaining_amount > 0 && loan.status !== 'cancelled'
                                                    ? [{ key: 'pay', label: 'Record Payment', onSelect: () => startPayment(loan) }]
                                                    : []),
                                                {
                                                    key: 'delete',
                                                    label: 'Delete',
                                                    confirm: {
                                                        url: `/admin/finance/loans/${loan.id}`,
                                                        title: `Delete loan from ${loan.lender_name}?`,
                                                        description: 'This also removes payment history for the loan.',
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
                            <DialogTitle>{editing ? 'Edit Loan' : 'Add Loan'}</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="loan_date">Date</Label>
                                <Input
                                    id="loan_date"
                                    type="date"
                                    value={form.data.loan_date}
                                    onChange={(event) => form.setData('loan_date', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.loan_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="due_date">Due Date</Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    value={form.data.due_date}
                                    onChange={(event) => form.setData('due_date', event.target.value)}
                                />
                                <InputError message={form.errors.due_date} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="lender_name">Person / Lender Name</Label>
                                <Input
                                    id="lender_name"
                                    value={form.data.lender_name}
                                    onChange={(event) => form.setData('lender_name', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.lender_name} />
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
                                <Label htmlFor="reason">Reason</Label>
                                <Input
                                    id="reason"
                                    value={form.data.reason}
                                    onChange={(event) => form.setData('reason', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.reason} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="loan_amount">Loan Amount (₹)</Label>
                                <Input
                                    id="loan_amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={form.data.loan_amount}
                                    onChange={(event) => form.setData('loan_amount', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.loan_amount} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="amount_paid">Amount Paid (₹)</Label>
                                <Input
                                    id="amount_paid"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.data.amount_paid}
                                    onChange={(event) => form.setData('amount_paid', event.target.value)}
                                />
                                <InputError message={form.errors.amount_paid} />
                            </div>
                            <div className="bg-muted/40 grid gap-1 rounded-xl border border-dashed px-3 py-3 sm:col-span-2">
                                <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">Remaining Amount</p>
                                <p className="text-foreground text-lg font-semibold tabular-nums">{formatInr(remainingPreview)}</p>
                                <p className="text-muted-foreground text-xs">Calculated as Loan Amount − Amount Paid (never negative).</p>
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
                                {editing ? 'Save changes' : 'Add Loan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={paying !== null} onOpenChange={(open) => !open && setPaying(null)}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={submitPayment} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>Record Payment</DialogTitle>
                        </DialogHeader>
                        {paying && (
                            <div className="bg-muted/40 space-y-1 rounded-xl border border-dashed px-3 py-3 text-sm">
                                <p className="font-medium">{paying.lender_name}</p>
                                <p className="text-muted-foreground">Remaining: {formatInr(paying.remaining_amount)}</p>
                            </div>
                        )}
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="payment_date">Payment Date</Label>
                                <Input
                                    id="payment_date"
                                    type="date"
                                    value={paymentForm.data.payment_date}
                                    onChange={(event) => paymentForm.setData('payment_date', event.target.value)}
                                    required
                                />
                                <InputError message={paymentForm.errors.payment_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="payment_amount">Payment Amount (₹)</Label>
                                <Input
                                    id="payment_amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    max={paying?.remaining_amount}
                                    value={paymentForm.data.amount}
                                    onChange={(event) => paymentForm.setData('amount', event.target.value)}
                                    required
                                />
                                <InputError message={paymentForm.errors.amount} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="payment_note">Note</Label>
                                <Textarea
                                    id="payment_note"
                                    value={paymentForm.data.note}
                                    onChange={(event) => paymentForm.setData('note', event.target.value)}
                                    rows={2}
                                />
                                <InputError message={paymentForm.errors.note} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setPaying(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={paymentForm.processing}>
                                Record Payment
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={viewing !== null} onOpenChange={(open) => !open && setViewing(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Loan details</DialogTitle>
                    </DialogHeader>
                    {viewing && (
                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">Date</dt>
                                <dd className="mt-0.5 font-medium">{formatDate(viewing.loan_date)}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Person / Lender</dt>
                                <dd className="mt-0.5 font-medium">{viewing.lender_name}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Mobile</dt>
                                <dd className="mt-0.5 font-medium">{viewing.mobile_number ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Reason</dt>
                                <dd className="mt-0.5 font-medium">{viewing.reason}</dd>
                            </div>
                            <div className="grid grid-cols-3 gap-2">
                                <div>
                                    <dt className="text-muted-foreground">Loan</dt>
                                    <dd className="mt-0.5 font-medium tabular-nums">{formatInr(viewing.loan_amount)}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">Paid</dt>
                                    <dd className="mt-0.5 font-medium tabular-nums">{formatInr(viewing.amount_paid)}</dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">Remaining</dt>
                                    <dd className="mt-0.5 font-medium tabular-nums">{formatInr(viewing.remaining_amount)}</dd>
                                </div>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Due Date</dt>
                                <dd className="mt-0.5 font-medium">{formatDate(viewing.due_date)}</dd>
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
                        {viewing && viewing.remaining_amount > 0 && viewing.status !== 'cancelled' && (
                            <Button
                                type="button"
                                onClick={() => {
                                    const row = viewing;
                                    setViewing(null);
                                    startPayment(row);
                                }}
                            >
                                Record Payment
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
