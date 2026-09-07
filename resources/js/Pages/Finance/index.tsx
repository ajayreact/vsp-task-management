import { KpiStatCard } from '@/components/admin/kpi-stat-card';
import { PageHeader } from '@/components/admin/page-header';
import { FinanceSectionNav } from '@/components/finance/finance-section-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatInr } from '@/lib/money';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDownLeft,
    ArrowUpRight,
    CalendarClock,
    HandCoins,
    IndianRupee,
    Plus,
    Repeat,
    Scale,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';

interface ActivityRow {
    id: string;
    date: string;
    type: 'income' | 'expense' | 'loan_payment';
    type_label: string;
    label: string;
    amount: number;
    status: string;
    status_label: string;
    href: string;
}

interface LoanAlert {
    id: number;
    lender_name: string;
    reason: string;
    due_date: string | null;
    emi_amount: number | null;
    remaining_amount: number;
    status: string;
    alert: 'overdue' | 'due_soon' | 'remaining';
    alert_label: string;
}

interface CleanupCandidate {
    id: number;
    expense_date: string;
    description: string;
    amount: number;
    notes: string | null;
}

interface Props {
    period: {
        period: string;
        date_from: string | null;
        date_to: string | null;
        month: string | null;
        label: string;
    };
    period_options: Option[];
    summaries: {
        received_income: number;
        paid_expenses: number;
        pending_expenses: number;
        emi_due: number;
        recurring_due: number;
        total_monthly_commitments: number;
        loan_outstanding: number;
        net_balance: number;
    };
    buckets: {
        actual_spending: { paid_expenses: number };
        upcoming_committed: {
            emi_due: number;
            recurring_due: number;
            pending_expenses: number;
            total_monthly_commitments: number;
        };
        liabilities: { loan_outstanding: number };
    };
    overview: { income: number; expenses: number; loan_payments: number };
    counts: { income: number; expenses: number; loans: number; recurring: number };
    recent_activity: ActivityRow[];
    loan_alerts: LoanAlert[];
    cleanup_candidates: CleanupCandidate[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Finance', href: '/admin/finance' }];

const typeTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    income: 'success',
    expense: 'warning',
    loan_payment: 'info',
};

const alertTone: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    overdue: 'danger',
    due_soon: 'warning',
    remaining: 'info',
};

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function MyFinanceDashboard({
    period,
    period_options,
    summaries,
    buckets,
    overview,
    counts,
    recent_activity,
    loan_alerts,
    cleanup_candidates,
}: Props) {
    const [convertingId, setConvertingId] = useState<number | null>(null);
    const convertForm = useForm({
        confirm: false as boolean,
        loan_type: 'gold',
        lender_name: '',
        reason: '',
    });

    const applyPeriod = (changes: Record<string, string | null>) => {
        router.get(
            '/admin/finance',
            {
                period: period.period,
                month: period.month || undefined,
                date_from: period.date_from || undefined,
                date_to: period.date_to || undefined,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const startConvert = (candidate: CleanupCandidate) => {
        setConvertingId(candidate.id);
        convertForm.clearErrors();
        convertForm.setData({
            confirm: false,
            loan_type: candidate.description.toLowerCase().includes('gold') ? 'gold' : 'other',
            lender_name: candidate.description,
            reason: candidate.description,
        });
    };

    const submitConvert = (event: React.FormEvent) => {
        event.preventDefault();
        if (!convertingId) {
            return;
        }

        convertForm.transform((data) => ({
            ...data,
            confirm: data.confirm ? 1 : 0,
        }));

        convertForm.post(`/admin/finance/expenses/${convertingId}/convert-to-loan`, {
            preserveScroll: true,
            onSuccess: () => setConvertingId(null),
        });
    };

    const exportQuery =
        period.period === 'custom'
            ? `?period=custom&date_from=${period.date_from ?? ''}&date_to=${period.date_to ?? ''}`
            : period.period === 'month' && period.month
              ? `?period=month&month=${period.month}`
              : `?period=${period.period}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Finance" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="My Finance"
                    description={`Monthly position for ${period.label}. Net Balance = Received Income − Paid Expenses. Loan outstanding is a liability, not a monthly expense.`}
                    action={
                        <Badge variant="secondary" className="gap-1.5 px-3 py-1.5 text-xs font-medium">
                            <Wallet className="size-3.5" aria-hidden="true" />
                            Private · INR only
                        </Badge>
                    }
                />

                <FinanceSectionNav active="dashboard" />

                <Card className="border-border/70 shadow-sm">
                    <CardContent className="flex flex-col gap-4 pt-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="grid gap-1.5">
                                <label className="text-muted-foreground text-xs font-medium" htmlFor="finance-period">
                                    Period
                                </label>
                                <Select value={period.period} onValueChange={(value) => applyPeriod({ period: value })}>
                                    <SelectTrigger id="finance-period" className="w-52">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {period_options.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {(period.period === 'month' || period.month) && period.period !== 'custom' && period.period !== 'this_year' && period.period !== 'all' && (
                                <div className="grid gap-1.5">
                                    <label className="text-muted-foreground text-xs font-medium" htmlFor="finance-month">
                                        Month
                                    </label>
                                    <Input
                                        id="finance-month"
                                        type="month"
                                        value={period.month ?? ''}
                                        onChange={(event) =>
                                            applyPeriod({
                                                period: 'month',
                                                month: event.target.value || null,
                                            })
                                        }
                                        className="w-44"
                                    />
                                </div>
                            )}
                            {period.period === 'custom' && (
                                <>
                                    <Input
                                        type="date"
                                        value={period.date_from ?? ''}
                                        onChange={(event) => applyPeriod({ date_from: event.target.value || null })}
                                        aria-label="Custom from date"
                                        className="w-40"
                                    />
                                    <Input
                                        type="date"
                                        value={period.date_to ?? ''}
                                        onChange={(event) => applyPeriod({ date_to: event.target.value || null })}
                                        aria-label="Custom to date"
                                        className="w-40"
                                    />
                                </>
                            )}
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button type="button" asChild>
                                <Link href="/admin/finance/income?create=1">
                                    <Plus /> Add Income
                                </Link>
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/finance/expenses?create=1">
                                    <Plus /> Add Expense
                                </Link>
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/finance/loans?create=1">
                                    <Plus /> Add Loan
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <KpiStatCard label="Received Income" value={formatInr(summaries.received_income)} icon={ArrowDownLeft} tone="emerald" />
                    <KpiStatCard label="Paid Expenses" value={formatInr(summaries.paid_expenses)} icon={ArrowUpRight} tone="amber" />
                    <KpiStatCard label="Pending Expenses" value={formatInr(summaries.pending_expenses)} icon={CalendarClock} tone="sky" />
                    <KpiStatCard label="Net Balance" value={formatInr(summaries.net_balance)} icon={Scale} tone="teal" />
                    <KpiStatCard label="EMI Due" value={formatInr(summaries.emi_due)} icon={HandCoins} tone="fuchsia" />
                    <KpiStatCard label="Recurring Due" value={formatInr(summaries.recurring_due)} icon={Repeat} tone="indigo" />
                    <KpiStatCard
                        label="Total Monthly Commitments"
                        value={formatInr(summaries.total_monthly_commitments)}
                        icon={IndianRupee}
                        tone="amber"
                        footer={<span className="text-muted-foreground text-xs">EMI Due + Recurring Due</span>}
                    />
                    <KpiStatCard
                        label="Loan Outstanding"
                        value={formatInr(summaries.loan_outstanding)}
                        icon={Wallet}
                        tone="indigo"
                        footer={<span className="text-muted-foreground text-xs">Liability — not in commitments</span>}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="border-border/70 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg">Actual spending</CardTitle>
                            <CardDescription>Money that already went out this period.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <span className="text-muted-foreground">Paid Expenses</span>
                                <span className="font-semibold tabular-nums">{formatInr(buckets.actual_spending.paid_expenses)}</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-border/70 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg">Upcoming / committed</CardTitle>
                            <CardDescription>Due amounts until you record the payment.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <span className="text-muted-foreground">EMI Due</span>
                                <span className="tabular-nums">{formatInr(buckets.upcoming_committed.emi_due)}</span>
                            </div>
                            <div className="flex justify-between gap-3">
                                <span className="text-muted-foreground">Recurring Due</span>
                                <span className="tabular-nums">{formatInr(buckets.upcoming_committed.recurring_due)}</span>
                            </div>
                            <div className="flex justify-between gap-3">
                                <span className="text-muted-foreground">Pending Expenses</span>
                                <span className="tabular-nums">{formatInr(buckets.upcoming_committed.pending_expenses)}</span>
                            </div>
                            <div className="flex justify-between gap-3 border-t pt-2 font-medium">
                                <span>Total Monthly Commitments</span>
                                <span className="tabular-nums">{formatInr(buckets.upcoming_committed.total_monthly_commitments)}</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-border/70 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg">Liabilities</CardTitle>
                            <CardDescription>Outstanding loan principal — never mixed into monthly expenses.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <span className="text-muted-foreground">Loan Outstanding</span>
                                <span className="font-semibold tabular-nums">{formatInr(buckets.liabilities.loan_outstanding)}</span>
                            </div>
                            <div className="flex justify-between gap-3">
                                <span className="text-muted-foreground">Loan payments this period</span>
                                <span className="tabular-nums">{formatInr(overview.loan_payments)}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {cleanup_candidates.length > 0 && (
                    <Card className="border-amber-200 bg-amber-50/60 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg">Possible misfiled liabilities</CardTitle>
                            <CardDescription>
                                Large or loan-like expenses can be converted to Loans & Liabilities. Original expense is kept and excluded from totals
                                (reversible).
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {cleanup_candidates.map((candidate) => (
                                <div
                                    key={candidate.id}
                                    className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-white px-4 py-3"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">{candidate.description}</p>
                                        <p className="text-muted-foreground text-xs">
                                            {formatDate(candidate.expense_date)} · {formatInr(candidate.amount)}
                                        </p>
                                    </div>
                                    <Button type="button" size="sm" variant="outline" onClick={() => startConvert(candidate)}>
                                        Convert to loan
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="border-border/70 shadow-sm xl:col-span-2">
                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle className="text-lg">Loan alerts</CardTitle>
                                <CardDescription>Outstanding liabilities and upcoming EMI dates.</CardDescription>
                            </div>
                            <Button type="button" variant="outline" size="sm" asChild>
                                <Link href="/admin/finance/loans">View loans</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {loan_alerts.length === 0 ? (
                                <div className="bg-muted/40 rounded-xl border border-dashed px-4 py-10 text-center">
                                    <p className="text-foreground text-sm font-medium">No outstanding loan alerts</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {loan_alerts.map((alert) => (
                                        <div
                                            key={alert.id}
                                            className="flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3"
                                        >
                                            <div className="min-w-0 space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-medium">{alert.lender_name}</p>
                                                    <Badge variant={alertTone[alert.alert] ?? 'neutral'}>{alert.alert_label}</Badge>
                                                </div>
                                                <p className="text-muted-foreground truncate text-xs">
                                                    {alert.reason}
                                                    {alert.due_date ? ` · Due ${formatDate(alert.due_date)}` : ''}
                                                    {alert.emi_amount ? ` · EMI ${formatInr(alert.emi_amount)}` : ' · No EMI'}
                                                </p>
                                            </div>
                                            <p className="text-sm font-semibold tabular-nums">{formatInr(alert.remaining_amount)}</p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg">Sections</CardTitle>
                            <CardDescription>Quick links</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            {[
                                { title: 'My Income', count: counts.income, href: '/admin/finance/income' },
                                { title: 'My Expenses', count: counts.expenses, href: '/admin/finance/expenses' },
                                { title: 'Recurring', count: counts.recurring, href: '/admin/finance/recurring' },
                                { title: 'Loans & Liabilities', count: counts.loans, href: '/admin/finance/loans' },
                            ].map((section) => (
                                <div key={section.href} className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="font-medium">{section.title}</p>
                                        <p className="text-muted-foreground text-xs">{section.count} record(s)</p>
                                    </div>
                                    <Button type="button" size="sm" variant="outline" asChild>
                                        <Link href={section.href}>Open</Link>
                                    </Button>
                                </div>
                            ))}
                            <Button type="button" variant="ghost" size="sm" className="w-full" asChild>
                                <a href={`/admin/finance/export/expenses${exportQuery}`}>Export expenses Excel</a>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card className="border-border/70 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-lg">Recent activity</CardTitle>
                        <CardDescription>Latest income, expenses, and loan payments in the selected period.</CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Person / Description</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recent_activity.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-muted-foreground py-10 text-center">
                                            No activity for this period yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {recent_activity.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="whitespace-nowrap text-sm">{formatDate(row.date)}</TableCell>
                                        <TableCell>
                                            <Badge variant={typeTone[row.type] ?? 'neutral'}>{row.type_label}</Badge>
                                        </TableCell>
                                        <TableCell className="max-w-[20rem] truncate text-sm">
                                            <Link href={row.href} className="hover:underline">
                                                {row.label}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="text-right text-sm font-medium tabular-nums">{formatInr(row.amount)}</TableCell>
                                        <TableCell className="text-sm">{row.status_label}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {loan_alerts.some((alert) => alert.alert === 'overdue') && (
                    <div className="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <p>You have overdue loan balances. Review Loans & Liabilities and record payments when ready.</p>
                    </div>
                )}
            </div>

            {convertingId !== null && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <form onSubmit={submitConvert} className="bg-background w-full max-w-md space-y-4 rounded-xl border p-6 shadow-lg">
                        <div>
                            <h2 className="text-lg font-semibold">Convert expense to loan</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Creates a loan liability and excludes this expense from monthly totals. Reversible until the loan has payments.
                            </p>
                        </div>
                        <div className="grid gap-3">
                            <div className="grid gap-1.5">
                                <label className="text-sm font-medium" htmlFor="convert-loan-type">
                                    Loan type
                                </label>
                                <Select value={convertForm.data.loan_type} onValueChange={(value) => convertForm.setData('loan_type', value)}>
                                    <SelectTrigger id="convert-loan-type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="gold">Gold</SelectItem>
                                        <SelectItem value="personal">Personal</SelectItem>
                                        <SelectItem value="bank">Bank</SelectItem>
                                        <SelectItem value="emi">EMI</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-1.5">
                                <label className="text-sm font-medium" htmlFor="convert-lender">
                                    Loan name / lender
                                </label>
                                <Input
                                    id="convert-lender"
                                    value={convertForm.data.lender_name}
                                    onChange={(event) => convertForm.setData('lender_name', event.target.value)}
                                />
                            </div>
                            <label className="flex items-start gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={convertForm.data.confirm}
                                    onChange={(event) => convertForm.setData('confirm', event.target.checked)}
                                    className="mt-1"
                                />
                                <span>I confirm this expense should become a loan liability and be excluded from expense totals.</span>
                            </label>
                            {convertForm.errors.confirm && <p className="text-destructive text-sm">{convertForm.errors.confirm}</p>}
                            {convertForm.errors.expense && <p className="text-destructive text-sm">{convertForm.errors.expense}</p>}
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setConvertingId(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={convertForm.processing || !convertForm.data.confirm}>
                                Convert
                            </Button>
                        </div>
                    </form>
                </div>
            )}
        </AppLayout>
    );
}
