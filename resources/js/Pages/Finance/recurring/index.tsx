import { PageHeader } from '@/components/admin/page-header';
import { RowActions } from '@/components/admin/row-actions';
import { FinanceSectionNav } from '@/components/finance/finance-section-nav';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatInr } from '@/lib/money';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Repeat } from 'lucide-react';
import { useState } from 'react';

interface RecurringRow {
    id: number;
    title: string;
    amount: number;
    category: string;
    due_day: number;
    active: boolean;
    start_date: string;
    end_date: string | null;
    notes: string | null;
}

interface Props {
    recurring: RecurringRow[];
    categories: Option[];
}

type FormValues = {
    title: string;
    amount: string;
    category: string;
    due_day: string;
    active: boolean;
    start_date: string;
    end_date: string;
    notes: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Finance', href: '/admin/finance' },
    { title: 'Recurring', href: '/admin/finance/recurring' },
];

function todayInputValue(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function blankForm(): FormValues {
    return {
        title: '',
        amount: '',
        category: 'personal',
        due_day: '1',
        active: true,
        start_date: todayInputValue(),
        end_date: '',
        notes: '',
    };
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function RecurringExpensesIndex({ recurring, categories }: Props) {
    const [editing, setEditing] = useState<RecurringRow | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const form = useForm<FormValues>(blankForm());

    const startCreate = () => {
        setEditing(null);
        form.clearErrors();
        form.setData(blankForm());
        setFormOpen(true);
    };

    const startEdit = (row: RecurringRow) => {
        setEditing(row);
        form.clearErrors();
        form.setData({
            title: row.title,
            amount: String(row.amount),
            category: row.category,
            due_day: String(row.due_day),
            active: row.active,
            start_date: row.start_date,
            end_date: row.end_date ?? '',
            notes: row.notes ?? '',
        });
        setFormOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setFormOpen(false) };

        form.transform((data) => ({
            ...data,
            end_date: data.end_date || null,
            active: data.active ? 1 : 0,
        }));

        if (editing) {
            form.put(`/admin/finance/recurring/${editing.id}`, options);
        } else {
            form.post('/admin/finance/recurring', options);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recurring Expenses" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Recurring Expenses"
                    description="Templates such as Room Rent. Shown as Due on the dashboard until you record a linked paid expense for that month."
                    action={
                        <Button type="button" onClick={startCreate}>
                            <Plus /> Add Recurring
                        </Button>
                    }
                />

                <FinanceSectionNav active="recurring" />

                <Card className="border-border/70 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-lg">Recurring templates</CardTitle>
                        <CardDescription>
                            These are not auto-duplicated each month. Record the actual payment under Expenses and link the template.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                    <TableHead>Due Day</TableHead>
                                    <TableHead>Active</TableHead>
                                    <TableHead>Start</TableHead>
                                    <TableHead>End</TableHead>
                                    <TableHead className="w-16 text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recurring.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-muted-foreground py-12 text-center">
                                            <div className="flex flex-col items-center gap-3">
                                                <span className="bg-primary/10 text-primary flex size-12 items-center justify-center rounded-full">
                                                    <Repeat className="size-5" strokeWidth={1.75} />
                                                </span>
                                                <p className="text-foreground text-sm font-medium">No recurring templates yet</p>
                                                <Button type="button" onClick={startCreate}>
                                                    <Plus /> Add Recurring
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                                {recurring.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="font-medium">{row.title}</TableCell>
                                        <TableCell className="text-sm">
                                            {categories.find((category) => category.value === row.category)?.label ?? row.category}
                                        </TableCell>
                                        <TableCell className="text-right text-sm tabular-nums">{formatInr(row.amount)}</TableCell>
                                        <TableCell className="text-sm">{row.due_day}</TableCell>
                                        <TableCell>
                                            <Badge variant={row.active ? 'success' : 'neutral'}>{row.active ? 'Active' : 'Inactive'}</Badge>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-sm">{formatDate(row.start_date)}</TableCell>
                                        <TableCell className="whitespace-nowrap text-sm">{formatDate(row.end_date)}</TableCell>
                                        <TableCell className="text-right">
                                            <RowActions
                                                label={`Actions for ${row.title}`}
                                                items={[
                                                    { key: 'edit', label: 'Edit', onSelect: () => startEdit(row) },
                                                    {
                                                        key: 'delete',
                                                        label: 'Delete',
                                                        confirm: {
                                                            url: `/admin/finance/recurring/${row.id}`,
                                                            title: `Delete “${row.title}”?`,
                                                            description: 'Past expenses linked to this template are kept.',
                                                        },
                                                    },
                                                ]}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="sm:max-w-lg">
                    <form onSubmit={submit} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Edit Recurring' : 'Add Recurring'}</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(event) => form.setData('title', event.target.value)}
                                    placeholder="Room Rent"
                                    required
                                />
                                <InputError message={form.errors.title} />
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
                                <Label htmlFor="due_day">Due Day (1–28)</Label>
                                <Input
                                    id="due_day"
                                    type="number"
                                    min="1"
                                    max="28"
                                    value={form.data.due_day}
                                    onChange={(event) => form.setData('due_day', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.due_day} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="category">Category</Label>
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
                            <div className="grid gap-2">
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(event) => form.setData('start_date', event.target.value)}
                                    required
                                />
                                <InputError message={form.errors.start_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_date">End Date (optional)</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(event) => form.setData('end_date', event.target.value)}
                                />
                                <InputError message={form.errors.end_date} />
                            </div>
                            <label className="flex items-center gap-2 text-sm sm:col-span-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.active}
                                    onChange={(event) => form.setData('active', event.target.checked)}
                                />
                                Active
                            </label>
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
                                {editing ? 'Save changes' : 'Add Recurring'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
