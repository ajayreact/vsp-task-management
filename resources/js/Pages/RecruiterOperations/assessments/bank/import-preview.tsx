import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { formatDateTime } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Download, LoaderCircle } from 'lucide-react';
import { useMemo, useState } from 'react';

interface PreviewRow {
    row: number;
    status: 'valid' | 'error';
    errors: string[];
    duplicate: string | null;
    question: {
        type: string;
        prompt: string;
        points: number;
        category: string | null;
        options: { text: string; correct: boolean }[];
    } | null;
    values: Record<string, string>;
}

interface Props {
    batch: { id: string; file_name: string; uploaded_at: string; option_letters: string[] };
    counts: { total: number; valid: number; errors: number; duplicates: number; blank: number; importable: number };
    rows: PreviewRow[];
}

type View = 'all' | 'valid' | 'error' | 'duplicate';

const TYPE_LABELS: Record<string, string> = {
    single_choice: 'Single choice',
    multiple_choice: 'Multiple choice',
    true_false: 'True / False',
    short_answer: 'Short answer',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
    { title: 'Question Bank', href: '/recruiter/assessments/questions' },
    { title: 'Import preview', href: '#' },
];

export default function ImportPreview({ batch, counts, rows }: Props) {
    const url = `/recruiter/assessments/questions/import/${batch.id}`;
    const [includeDuplicates, setIncludeDuplicates] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [view, setView] = useState<View>(counts.errors > 0 ? 'error' : 'all');
    const errors = usePage().props.errors as Record<string, string | undefined>;

    const importable = includeDuplicates ? counts.valid : counts.importable;
    const visible = useMemo(
        () =>
            rows.filter((row) => {
                if (view === 'valid') return row.status === 'valid' && !row.duplicate;
                if (view === 'error') return row.status === 'error';
                if (view === 'duplicate') return row.status === 'valid' && row.duplicate !== null;
                return true;
            }),
        [rows, view],
    );

    const confirm = () =>
        router.post(`${url}/confirm`, { include_duplicates: includeDuplicates }, { onStart: () => setProcessing(true), onFinish: () => setProcessing(false) });
    const cancel = () => router.delete(url);

    const views: { key: View; label: string; count: number }[] = [
        { key: 'all', label: 'All rows', count: counts.total },
        { key: 'valid', label: 'Ready', count: counts.valid - counts.duplicates },
        { key: 'duplicate', label: 'Duplicates', count: counts.duplicates },
        { key: 'error', label: 'Errors', count: counts.errors },
    ];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Import preview" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Check the import"
                    description={`${batch.file_name} · uploaded ${formatDateTime(batch.uploaded_at)}. Nothing has been saved yet.`}
                    action={
                        counts.errors > 0 || counts.duplicates > 0 ? (
                            <Button asChild variant="outline">
                                <a href={`${url}/errors`}>
                                    <Download /> Download problem report
                                </a>
                            </Button>
                        ) : undefined
                    }
                />

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <Figure label="Question rows" value={counts.total} />
                    <Figure label="Ready to import" value={counts.valid - counts.duplicates} tone="good" />
                    <Figure label="Duplicates" value={counts.duplicates} tone={counts.duplicates > 0 ? 'warn' : undefined} />
                    <Figure label="Rows with errors" value={counts.errors} tone={counts.errors > 0 ? 'bad' : undefined} />
                    <Figure label="Blank rows ignored" value={counts.blank} />
                </div>

                {counts.errors > 0 && (
                    <p className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                        {counts.errors} row(s) have errors and will not be imported. Fix them in the spreadsheet and upload it again, or import the valid rows now.
                    </p>
                )}

                <Card>
                    <CardContent className="flex flex-wrap items-center justify-between gap-4 pt-6">
                        <div className="space-y-2">
                            {counts.duplicates > 0 && (
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox checked={includeDuplicates} onCheckedChange={(checked) => setIncludeDuplicates(checked === true)} />
                                    Also import the {counts.duplicates} duplicate(s)
                                </label>
                            )}
                            <p className="text-muted-foreground text-sm">
                                {importable} {importable === 1 ? 'question' : 'questions'} will be added to the Question Bank. Existing questions are never changed or deleted.
                            </p>
                            <InputError message={errors.batch} />
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={cancel} disabled={processing}>
                                Cancel import
                            </Button>
                            <Button onClick={confirm} disabled={processing || importable === 0}>
                                {processing && <LoaderCircle className="animate-spin" />}
                                {counts.errors > 0 ? 'Import Valid Rows' : 'Import Valid Questions'} ({importable})
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex flex-wrap gap-2">
                    {views.map((item) => (
                        <Button key={item.key} size="sm" variant={view === item.key ? 'default' : 'outline'} onClick={() => setView(item.key)}>
                            {item.label} ({item.count})
                        </Button>
                    ))}
                </div>

                <div className="vsp-card overflow-x-auto bg-white">
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-16">Row</TableHead>
                                <TableHead className="w-28">Status</TableHead>
                                <TableHead>Question</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Options (correct in bold)</TableHead>
                                <TableHead>Problems</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {visible.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-muted-foreground py-8 text-center">
                                        No rows in this view.
                                    </TableCell>
                                </TableRow>
                            )}
                            {visible.map((row) => (
                                <TableRow key={row.row} className={cn(row.status === 'error' && 'bg-red-50/60')}>
                                    <TableCell className="text-sm tabular-nums">{row.row}</TableCell>
                                    <TableCell>
                                        {row.status === 'error' ? (
                                            <Badge variant="danger">Error</Badge>
                                        ) : row.duplicate ? (
                                            <Badge variant="warning">Duplicate</Badge>
                                        ) : (
                                            <Badge variant="success">Ready</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="max-w-sm text-sm whitespace-normal">{row.question?.prompt ?? row.values.question ?? '—'}</TableCell>
                                    <TableCell className="text-sm">{row.question ? TYPE_LABELS[row.question.type] ?? row.question.type : row.values.type || '—'}</TableCell>
                                    <TableCell className="max-w-xs text-sm whitespace-normal">
                                        {row.question ? (
                                            row.question.options.length > 0 ? (
                                                row.question.options.map((option, index) => (
                                                    <span key={index} className={cn('mr-2 inline-block', option.correct && 'font-semibold text-emerald-700')}>
                                                        {String.fromCharCode(65 + index)}. {option.text}
                                                    </span>
                                                ))
                                            ) : (
                                                <span className="text-muted-foreground">Manual review</span>
                                            )
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="max-w-sm text-sm whitespace-normal">
                                        {row.errors.length > 0 && (
                                            <ul className="list-disc space-y-0.5 pl-4 text-red-700">
                                                {row.errors.map((error, index) => (
                                                    <li key={index}>{error}</li>
                                                ))}
                                            </ul>
                                        )}
                                        {row.duplicate && <span className="text-amber-700">{row.duplicate}</span>}
                                        {row.errors.length === 0 && !row.duplicate && <span className="text-muted-foreground">—</span>}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </RecruiterLayout>
    );
}

function Figure({ label, value, tone }: { label: string; value: number; tone?: 'good' | 'warn' | 'bad' }) {
    return (
        <div
            className={cn(
                'rounded-xl border bg-white/80 px-3.5 py-3',
                tone === 'good' && 'border-emerald-200',
                tone === 'warn' && 'border-amber-200',
                tone === 'bad' && 'border-red-200',
            )}
        >
            <div className="text-foreground text-lg font-semibold tabular-nums">{value}</div>
            <div className="text-muted-foreground text-xs">{label}</div>
        </div>
    );
}
