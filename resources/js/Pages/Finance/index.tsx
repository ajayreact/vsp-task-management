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
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDownLeft,
    ArrowUpRight,
    Banknote,
    Download,
    HandCoins,
    IndianRupee,
    Plus,
    Scale,
    Wallet,
} from 'lucide-react';

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
    remaining_amount: number;
    status: string;
    alert: 'overdue' | 'due_soon' | 'remaining';
    alert_label: string;
}

interface Props {
    period: { period: string; date_from: string | null; date_to: string | null };
    period_options: Option[];
    summaries: {
        total_income: number;
        total_received: number;
        total_expenses: number;
        paid_expenses: number;
        total_loans: number;
        loan_paid: number;
        loan_remaining: number;
        net_balance: number;
    };
    overview: { income: number; expenses: number; loan_payments: number };
    counts: { income: number; expenses: number; loans: number };
    recent_activity: ActivityRow[];
    loan_alerts: LoanAlert[];
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

function OverviewBar({ label, amount, max, className }: { label: string; amount: number; max: number; className: string }) {
    const width = max > 0 ? Math.max(4, Math.round((amount / max) * 100)) : 0;

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-3 text-sm">
                <span className="text-muted-foreground font-medium">{label}</span>
                <span className="font-semibold tabular-nums">{formatInr(amount)}</span>
            </div>
            <div className="bg-muted/60 h-2.5 overflow-hidden rounded-full">
                <div className={`h-full rounded-full transition-all ${className}`} style={{ width: `${width}%` }} />
            </div>
        </div>
    );
}

export default function MyFinanceDashboard({ period, period_options, summaries, overview, counts, recent_activity, loan_alerts }: Props) {
    const applyPeriod = (changes: Record<string, string | null>) => {
        router.get(
            '/admin/finance',
            {
                period: period.period,
                date_from: period.date_from || undefined,
                date_to: period.date_to || undefined,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const overviewMax = Math.max(overview.income, overview.expenses, overview.loan_payments, 1);
    const exportQuery =
        period.period === 'custom'
            ? `?period=custom&date_from=${period.date_from ?? ''}&date_to=${period.date_to ?? ''}`
            : `?period=${period.period}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Finance" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="My Finance"
                    description="Private personal finance dashboard. Amounts are tracked in Indian Rupees (₹) only. Net Balance = Received Income − Paid Expenses."
                    action={
                        <Badge variant="secondary" className="gap-1.5 px-3 py-1.5 text-xs font-medium">
                            <Wallet className="size-3.5" aria-hidden="true" />
                            Private
                        </Badge>
                    }
                />

                <FinanceSectionNav active="dashboard" />

                <Card className="border-border/70 shadow-sm">
                    <CardContent className="flex flex-col gap-4 pt-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="grid gap-1.5">
                                <label className="text-muted-foreground text-xs font-medium" htmlFor="finance-period">
                                    Date filter
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
                    <KpiStatCard label="Total Income" value={formatInr(summaries.total_income)} icon={ArrowDownLeft} tone="indigo" />
                    <KpiStatCard label="Total Received" value={formatInr(summaries.total_received)} icon={IndianRupee} tone="emerald" />
                    <KpiStatCard label="Total Expenses" value={formatInr(summaries.total_expenses)} icon={ArrowUpRight} tone="amber" />
                    <KpiStatCard label="Net Balance" value={formatInr(summaries.net_balance)} icon={Scale} tone="teal" />
                    <KpiStatCard label="Total Loans" value={String(summaries.total_loans)} icon={HandCoins} tone="sky" />
                    <KpiStatCard label="Loan Paid" value={formatInr(summaries.loan_paid)} icon={Banknote} tone="fuchsia" />
                    <KpiStatCard label="Loan Remaining" value={formatInr(summaries.loan_remaining)} icon={Wallet} tone="indigo" />
                    <KpiStatCard
                        label="Paid Expenses"
                        value={formatInr(summaries.paid_expenses)}
                        icon={ArrowUpRight}
                        tone="amber"
                        footer={<span className="text-muted-foreground text-xs">Used in Net Balance</span>}
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="border-border/70 shadow-sm xl:col-span-1">
                        <CardHeader>
                            <CardTitle className="text-lg">Financial overview</CardTitle>
                            <CardDescription>Received income, paid expenses, and loan payments for the selected period.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <OverviewBar label="Income (Received)" amount={overview.income} max={overviewMax} className="bg-emerald-500" />
                            <OverviewBar label="Expenses (Paid)" amount={overview.expenses} max={overviewMax} className="bg-rose-500" />
                            <OverviewBar label="Loan Payments" amount={overview.loan_payments} max={overviewMax} className="bg-sky-500" />
                        </CardContent>
                    </Card>

                    <Card className="border-border/70 shadow-sm xl:col-span-2">
                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle className="text-lg">Loan payments due</CardTitle>
                                <CardDescription>Overdue, due soon, and remaining balances — no notifications yet.</CardDescription>
                            </div>
                            <Button type="button" variant="outline" size="sm" asChild>
                                <Link href="/admin/finance/loans">View loans</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {loan_alerts.length === 0 ? (
                                <div className="bg-muted/40 rounded-xl border border-dashed px-4 py-10 text-center">
                                    <p className="text-foreground text-sm font-medium">No outstanding loan alerts</p>
                                    <p className="text-muted-foreground mt-1 text-xs">Loans with remaining balances will appear here.</p>
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
                                                </p>
                                            </div>
                                            <p className="text-sm font-semibold tabular-nums">{formatInr(alert.remaining_amount)}</p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {[
                        {
                            key: 'income',
                            title: 'My Income',
                            description: 'Record and track money you receive.',
                            icon: ArrowDownLeft,
                            count: counts.income,
                            href: '/admin/finance/income',
                            exportHref: `/admin/finance/export/income${exportQuery}`,
                        },
                        {
                            key: 'expenses',
                            title: 'My Expenses',
                            description: 'Track personal spending in rupees.',
                            icon: ArrowUpRight,
                            count: counts.expenses,
                            href: '/admin/finance/expenses',
                            exportHref: `/admin/finance/export/expenses${exportQuery}`,
                        },
                        {
                            key: 'loans',
                            title: 'My Loans',
                            description: 'Monitor loans you need to repay.',
                            icon: HandCoins,
                            count: counts.loans,
                            href: '/admin/finance/loans',
                            exportHref: `/admin/finance/export/loans${exportQuery}`,
                        },
                    ].map((section) => {
                        const Icon = section.icon;

                        return (
                            <Card key={section.key} className="border-border/70 shadow-sm">
                                <CardHeader className="space-y-3">
                                    <div className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                                        <Icon className="size-5" strokeWidth={1.75} aria-hidden="true" />
                                    </div>
                                    <div className="space-y-1">
                                        <CardTitle className="text-lg">{section.title}</CardTitle>
                                        <CardDescription>{section.description}</CardDescription>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <p className="text-muted-foreground text-sm">
                                        {section.count === 0
                                            ? 'No records yet'
                                            : `${section.count} record${section.count === 1 ? '' : 's'}`}
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        <Button type="button" className="flex-1" asChild>
                                            <Link href={section.href}>Open</Link>
                                        </Button>
                                        <Button type="button" variant="outline" asChild>
                                            <a href={section.exportHref}>
                                                <Download className="size-4" />
                                                Excel
                                            </a>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
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
                        <p>You have overdue loan balances. Review My Loans and record payments when ready.</p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
