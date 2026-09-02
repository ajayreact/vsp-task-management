import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Home, LoaderCircle } from 'lucide-react';

interface WfhRequestRow {
    id: number;
    date: string;
    reason: string;
    status: string;
    status_label: string;
    approved_by: string | null;
    approved_at: string | null;
    created_at: string | null;
}

interface Props {
    requests: WfhRequestRow[];
    statuses: Option[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'WFH Requests', href: '/attendance/wfh' },
];

const STATUS_TONE: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
};

export default function WfhRequestsIndex({ requests, statuses }: Props) {
    const form = useForm({
        date: '',
        reason: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post('/attendance/wfh', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WFH Requests" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Work From Home"
                    description="Request approval for specific dates. Approved WFH lets you check in without office GPS verification."
                />

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Home className="size-5" />
                                Request WFH
                            </CardTitle>
                            <CardDescription>Submit one request per date. Duplicate requests for the same day are blocked.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="wfh-date">Date</Label>
                                    <Input
                                        id="wfh-date"
                                        type="date"
                                        value={form.data.date}
                                        onChange={(event) => form.setData('date', event.target.value)}
                                        required
                                    />
                                    {form.errors.date && <p className="text-destructive text-sm">{form.errors.date}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="wfh-reason">Reason</Label>
                                    <Textarea
                                        id="wfh-reason"
                                        value={form.data.reason}
                                        onChange={(event) => form.setData('reason', event.target.value)}
                                        rows={4}
                                        placeholder="Explain why you need to work from home on this date."
                                        required
                                    />
                                    {form.errors.reason && <p className="text-destructive text-sm">{form.errors.reason}</p>}
                                </div>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <LoaderCircle className="animate-spin" />}
                                    Submit request
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Your requests</CardTitle>
                            <CardDescription>Track pending, approved, and rejected WFH dates.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {requests.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No WFH requests yet.</p>
                            ) : (
                                requests.map((request) => (
                                    <div
                                        key={request.id}
                                        className="rounded-xl border border-[rgba(120,115,110,0.12)] bg-white px-4 py-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium">{request.date}</p>
                                                <p className="text-muted-foreground mt-1 text-sm break-words">{request.reason}</p>
                                            </div>
                                            <Badge variant={STATUS_TONE[request.status] ?? 'neutral'}>{request.status_label}</Badge>
                                        </div>
                                        {request.status === 'rejected' && (
                                            <p className="text-destructive mt-2 text-xs font-medium">This request was rejected.</p>
                                        )}
                                        {request.approved_by && (
                                            <p className="text-muted-foreground mt-2 text-xs">
                                                Reviewed by {request.approved_by}
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
        </AppLayout>
    );
}
