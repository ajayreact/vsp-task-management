import { DataTableCard } from '@/components/admin/data-table-card';
import { SearchInput } from '@/components/admin/search-input';
import { ComplianceBadge, ReviewStatusBadge, WordCount } from '@/components/recruiter-operations/training/training-review';
import { TrainingSubNav, formatTrainingDate } from '@/components/recruiter-operations/training/training-ui';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';

interface ReviewRow {
    id: number;
    title: string;
    course: { id: number; title: string };
    level: number | null;
    version: string;
    status: string;
    status_label: string;
    words: number;
    over_target: boolean;
    compliance: { status: string; label: string; required: boolean };
    updated_at: string | null;
    reviewer: string | null;
}

interface Summary {
    english: { total: number; needs_review: number; in_review: number; approved: number; changes_requested: number };
    telugu: { available: number; pending: number; in_review: number; approved: number; changes_requested: number; outdated: number; not_started: number };
    compliance: { required: number; pending: number; approved: number; changes_requested: number };
    over_target: number;
}

interface Props {
    language: string;
    languages: { code: string; label: string }[];
    summary: Summary;
    rows: ReviewRow[];
    statuses: Option[];
    complianceStatuses: Option[];
    overTargetWords: number;
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'Manage', href: '/recruiter/training/manage' },
    { title: 'Content Review', href: '/recruiter/training/manage/review' },
];

function Stat({ label, value, tone }: { label: string; value: number; tone?: 'good' | 'warn' | 'bad' }) {
    return (
        <div className="min-w-0">
            <p className="text-muted-foreground truncate text-xs">{label}</p>
            <p
                className={cn(
                    'text-xl font-semibold tabular-nums',
                    tone === 'good' && 'text-emerald-700 dark:text-emerald-400',
                    tone === 'warn' && 'text-amber-700 dark:text-amber-400',
                    tone === 'bad' && 'text-red-700 dark:text-red-400',
                )}
            >
                {value}
            </p>
        </div>
    );
}

function SummaryCard({ title, children }: { title: string; children: ReactNode }) {
    return (
        <Card className="gap-3 py-4">
            <CardHeader className="px-4">
                <CardTitle className="text-sm">{title}</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 gap-3 px-4 sm:grid-cols-3">{children}</CardContent>
        </Card>
    );
}

export default function TrainingContentReviewIndex({ language, languages, summary, rows, statuses, complianceStatuses, overTargetWords }: Props) {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState(ALL);
    const [compliance, setCompliance] = useState(ALL);
    const [course, setCourse] = useState(ALL);
    const [length, setLength] = useState(ALL);
    const canonical = language === 'en';

    const courses = useMemo(() => {
        const seen = new Map<number, string>();
        rows.forEach((row) => seen.set(row.course.id, row.level ? `Level ${row.level} · ${row.course.title}` : row.course.title));

        return [...seen.entries()];
    }, [rows]);

    const shown = rows.filter((row) => {
        const term = search.trim().toLowerCase();

        return (
            (term === '' || row.title.toLowerCase().includes(term) || row.course.title.toLowerCase().includes(term)) &&
            (status === ALL || row.status === status) &&
            (compliance === ALL || (compliance === 'required' ? row.compliance.required : row.compliance.status === compliance)) &&
            (course === ALL || String(row.course.id) === course) &&
            (length === ALL || row.over_target)
        );
    });

    const statusOptions = canonical ? statuses : [...statuses, { value: 'outdated', label: 'English changed — review required' }];

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Training Content Review" />

            <div className="flex max-w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />

                <div className="grid gap-4 xl:grid-cols-2 2xl:grid-cols-4">
                    <SummaryCard title="English">
                        <Stat label="Total" value={summary.english.total} />
                        <Stat label="Needs review" value={summary.english.needs_review} tone="warn" />
                        <Stat label="In review" value={summary.english.in_review} />
                        <Stat label="Approved" value={summary.english.approved} tone="good" />
                        <Stat label="Changes requested" value={summary.english.changes_requested} tone="bad" />
                    </SummaryCard>
                    <SummaryCard title="Telugu">
                        <Stat label="Available" value={summary.telugu.available} />
                        <Stat label="Pending native review" value={summary.telugu.pending + summary.telugu.in_review} tone="warn" />
                        <Stat label="Approved" value={summary.telugu.approved} tone="good" />
                        <Stat label="Changes requested" value={summary.telugu.changes_requested} tone="bad" />
                        <Stat label="English changed" value={summary.telugu.outdated} tone="bad" />
                        <Stat label="Not started" value={summary.telugu.not_started} />
                    </SummaryCard>
                    <SummaryCard title="Compliance">
                        <Stat label="Required" value={summary.compliance.required} />
                        <Stat label="Pending" value={summary.compliance.pending} tone="warn" />
                        <Stat label="Approved" value={summary.compliance.approved} tone="good" />
                        <Stat label="Changes requested" value={summary.compliance.changes_requested} tone="bad" />
                    </SummaryCard>
                    <SummaryCard title="Length">
                        <Stat label={`Over ${overTargetWords} words`} value={summary.over_target} tone={summary.over_target > 0 ? 'warn' : undefined} />
                    </SummaryCard>
                </div>

                <DataTableCard
                    title="Lessons to review"
                    description="Review status is an internal marker only. Saved content is already live for recruiters; nothing is approved automatically."
                    action={
                        <div role="tablist" aria-label="Review language" className="flex gap-1 rounded-lg border p-0.5 text-sm">
                            {languages.map((item) => (
                                <button
                                    key={item.code}
                                    type="button"
                                    role="tab"
                                    aria-selected={item.code === language}
                                    onClick={() => router.get('/recruiter/training/manage/review', { language: item.code }, { preserveScroll: true })}
                                    className={cn('rounded-md px-3 py-1', item.code === language ? 'bg-muted font-medium' : 'text-muted-foreground')}
                                >
                                    {item.label}
                                </button>
                            ))}
                        </div>
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap">
                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search lessons"
                                aria-label="Search lessons"
                                containerClassName="w-full min-w-0 lg:max-w-xs"
                            />
                            <Select value={course} onValueChange={setCourse}>
                                <SelectTrigger className="w-full lg:w-72" aria-label="Filter by course">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All courses</SelectItem>
                                    {courses.map(([id, label]) => (
                                        <SelectItem key={id} value={String(id)}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger className="w-full lg:w-56" aria-label="Filter by review status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Any review status</SelectItem>
                                    {statusOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {!canonical && option.value === 'needs_review' ? 'Pending native review' : option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={compliance} onValueChange={setCompliance}>
                                <SelectTrigger className="w-full lg:w-52" aria-label="Filter by compliance">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Any compliance</SelectItem>
                                    <SelectItem value="required">Compliance required</SelectItem>
                                    {complianceStatuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            Compliance: {option.label.toLowerCase()}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {canonical && (
                                <Select value={length} onValueChange={setLength}>
                                    <SelectTrigger className="w-full lg:w-44" aria-label="Filter by length">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>Any length</SelectItem>
                                        <SelectItem value="over">Over {overTargetWords} words</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        </div>
                    }
                    footer={
                        <p className="text-muted-foreground px-1 text-sm">
                            Showing {shown.length} of {rows.length} lessons
                        </p>
                    }
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Level</TableHead>
                                <TableHead>Course</TableHead>
                                <TableHead>Lesson</TableHead>
                                <TableHead>Language</TableHead>
                                <TableHead>Review status</TableHead>
                                <TableHead className="text-right">Words</TableHead>
                                <TableHead>Compliance</TableHead>
                                <TableHead>Last updated</TableHead>
                                <TableHead>Reviewer</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {shown.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={9} className="text-muted-foreground py-10 text-center">
                                        {rows.length === 0 ? 'Nothing to review in this language yet.' : 'No lessons match these filters.'}
                                    </TableCell>
                                </TableRow>
                            )}
                            {shown.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell className="text-sm tabular-nums">{row.level ?? '—'}</TableCell>
                                    <TableCell className="max-w-56 truncate text-sm" title={row.course.title}>
                                        {row.course.title}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={`/recruiter/training/manage/review/lessons/${row.id}/${language}`} className="font-medium hover:underline">
                                            {row.title}
                                        </Link>
                                    </TableCell>
                                    <TableCell className="text-sm">{languages.find((item) => item.code === language)?.label ?? language}</TableCell>
                                    <TableCell>
                                        <ReviewStatusBadge status={row.status} label={row.status_label} />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <WordCount words={row.words} overTarget={row.over_target} target={overTargetWords} />
                                    </TableCell>
                                    <TableCell>
                                        <ComplianceBadge status={row.compliance.status} label={row.compliance.label} />
                                    </TableCell>
                                    <TableCell className="text-sm">{formatTrainingDate(row.updated_at)}</TableCell>
                                    <TableCell className="text-sm">{row.reviewer ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
