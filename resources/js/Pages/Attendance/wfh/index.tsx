import { PageHeader } from '@/components/admin/page-header';
import { RowActions } from '@/components/admin/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Home, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

interface WfhRequestRow {
    id: number;
    type: string;
    type_label: string;
    source_label: string;
    start_date: string;
    end_date: string;
    date_range_label: string;
    reason: string;
    status: string;
    status_label: string;
    approved_by: string | null;
    assigned_by: string | null;
    approved_at: string | null;
    created_at: string | null;
    can_edit?: boolean;
    can_cancel?: boolean;
}

interface Props {
    requests: WfhRequestRow[];
    mode?: 'direct' | 'request';
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'WFH Requests', href: '/attendance/wfh' }];

const STATUS_TONE: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    assigned: 'info',
    cancelled: 'neutral',
    scheduled: 'info',
    completed: 'success',
};

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString();
}

export default function WfhRequestsIndex({ requests, mode = 'request' }: Props) {
    const isDirect = mode === 'direct';
    const [editing, setEditing] = useState<WfhRequestRow | null>(null);

    const form = useForm({
        start_date: '',
        end_date: '',
        reason: '',
    });

    const editForm = useForm({
        start_date: '',
        end_date: '',
        reason: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post('/attendance/wfh', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const openEdit = (row: WfhRequestRow) => {
        setEditing(row);
        editForm.clearErrors();
        editForm.setData({
            start_date: row.start_date,
            end_date: row.end_date,
            reason: row.reason,
        });
    };

    const submitEdit = (event: React.FormEvent) => {
        event.preventDefault();
        if (!editing) {
            return;
        }

        editForm.put(`/attendance/wfh/${editing.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(null);
                editForm.reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isDirect ? 'My WFH' : 'WFH Requests'} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={isDirect ? 'My WFH' : 'Work From Home'}
                    description={
                        isDirect
                            ? 'Record the dates you will be working from home.'
                            : 'Request approval or view Operations-assigned WFH dates.'
                    }
                />

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Home className="size-5" />
                                {isDirect ? 'Record WFH' : 'Request WFH'}
                            </CardTitle>
                            <CardDescription>
                                {isDirect
                                    ? 'Add your WFH dates directly. No approval is required.'
                                    : 'Submit a request for one day or a date range. Overlapping requests are blocked.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="wfh-start">From Date</Label>
                                        <Input
                                            id="wfh-start"
                                            type="date"
                                            value={form.data.start_date}
                                            onChange={(event) => form.setData('start_date', event.target.value)}
                                            required
                                        />
                                        {form.errors.start_date && <p className="text-destructive text-sm">{form.errors.start_date}</p>}
                                        {form.errors.date && <p className="text-destructive text-sm">{form.errors.date}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="wfh-end">To Date</Label>
                                        <Input
                                            id="wfh-end"
                                            type="date"
                                            value={form.data.end_date}
                                            onChange={(event) => form.setData('end_date', event.target.value)}
                                        />
                                        {form.errors.end_date && <p className="text-destructive text-sm">{form.errors.end_date}</p>}
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="wfh-reason">{isDirect ? 'Reason / Notes' : 'Reason'}</Label>
                                    <Textarea
                                        id="wfh-reason"
                                        value={form.data.reason}
                                        onChange={(event) => form.setData('reason', event.target.value)}
                                        rows={4}
                                        placeholder={
                                            isDirect
                                                ? 'Optional context for your WFH dates.'
                                                : 'Explain why you need to work from home.'
                                        }
                                        required
                                    />
                                    {form.errors.reason && <p className="text-destructive text-sm">{form.errors.reason}</p>}
                                </div>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <LoaderCircle className="animate-spin" />}
                                    {isDirect ? 'Add WFH' : 'Submit request'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{isDirect ? 'My WFH Records' : 'Your WFH records'}</CardTitle>
                            <CardDescription>
                                {isDirect
                                    ? 'Your recorded WFH dates. Status is Scheduled or Completed based on the date range.'
                                    : 'Employee requests and Operations assignments.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {requests.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No WFH records yet.</p>
                            ) : isDirect ? (
                                <div className="overflow-x-auto rounded-xl border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>From Date</TableHead>
                                                <TableHead>To Date</TableHead>
                                                <TableHead>Reason</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead className="w-16 text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {requests.map((request) => (
                                                <TableRow key={request.id}>
                                                    <TableCell className="whitespace-nowrap">{formatDate(request.start_date)}</TableCell>
                                                    <TableCell className="whitespace-nowrap">{formatDate(request.end_date)}</TableCell>
                                                    <TableCell className="max-w-[16rem] break-words text-sm">{request.reason}</TableCell>
                                                    <TableCell>
                                                        <Badge variant={STATUS_TONE[request.status] ?? 'neutral'}>
                                                            {request.status_label}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {(request.can_edit || request.can_cancel) && (
                                                            <RowActions
                                                                label={`Actions for ${request.date_range_label}`}
                                                                items={[
                                                                    ...(request.can_edit
                                                                        ? [
                                                                              {
                                                                                  key: 'edit',
                                                                                  label: 'Edit',
                                                                                  onSelect: () => openEdit(request),
                                                                              },
                                                                          ]
                                                                        : []),
                                                                    ...(request.can_cancel
                                                                        ? [
                                                                              {
                                                                                  key: 'delete',
                                                                                  label: 'Delete',
                                                                                  confirm: {
                                                                                      url: `/attendance/wfh/${request.id}`,
                                                                                      title: 'Remove this WFH record?',
                                                                                      description:
                                                                                          'This cancels the record. Attendance history is kept.',
                                                                                  },
                                                                              },
                                                                          ]
                                                                        : []),
                                                                ]}
                                                            />
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : (
                                requests.map((request) => (
                                    <div
                                        key={request.id}
                                        className="rounded-xl border border-[rgba(120,115,110,0.12)] bg-white px-4 py-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium">{request.date_range_label}</p>
                                                <p className="text-muted-foreground mt-1 text-xs">{request.source_label}</p>
                                                <p className="text-muted-foreground mt-1 text-sm break-words">{request.reason}</p>
                                            </div>
                                            <div className="flex flex-col items-end gap-2">
                                                <Badge variant={request.type === 'assignment' ? 'info' : 'neutral'}>
                                                    {request.type_label}
                                                </Badge>
                                                <Badge variant={STATUS_TONE[request.status] ?? 'neutral'}>
                                                    {request.status_label}
                                                </Badge>
                                            </div>
                                        </div>
                                        {request.status === 'rejected' && (
                                            <p className="text-destructive mt-2 text-xs font-medium">This request was rejected.</p>
                                        )}
                                        {(request.assigned_by || request.approved_by) && (
                                            <p className="text-muted-foreground mt-2 text-xs">
                                                {request.assigned_by
                                                    ? `Assigned by ${request.assigned_by}`
                                                    : `Reviewed by ${request.approved_by}`}
                                                {request.approved_at ? ` · ${new Date(request.approved_at).toLocaleString()}` : ''}
                                            </p>
                                        )}
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit WFH record</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="edit-wfh-start">From Date</Label>
                                <Input
                                    id="edit-wfh-start"
                                    type="date"
                                    value={editForm.data.start_date}
                                    onChange={(event) => editForm.setData('start_date', event.target.value)}
                                    required
                                />
                                {editForm.errors.start_date && (
                                    <p className="text-destructive text-sm">{editForm.errors.start_date}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="edit-wfh-end">To Date</Label>
                                <Input
                                    id="edit-wfh-end"
                                    type="date"
                                    value={editForm.data.end_date}
                                    onChange={(event) => editForm.setData('end_date', event.target.value)}
                                    required
                                />
                                {editForm.errors.end_date && <p className="text-destructive text-sm">{editForm.errors.end_date}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit-wfh-reason">Reason / Notes</Label>
                            <Textarea
                                id="edit-wfh-reason"
                                value={editForm.data.reason}
                                onChange={(event) => editForm.setData('reason', event.target.value)}
                                rows={4}
                                required
                            />
                            {editForm.errors.reason && <p className="text-destructive text-sm">{editForm.errors.reason}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditing(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing && <LoaderCircle className="animate-spin" />}
                                Save changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
