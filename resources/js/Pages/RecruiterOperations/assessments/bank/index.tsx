import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { SearchInput } from '@/components/admin/search-input';
import { AssessmentSubNav, QuestionTypeBadge, type ManagerQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileSpreadsheet, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

type BankQuestion = ManagerQuestion & { created_by: string | null; created_at: string };

interface Props {
    questions: Paginated<BankQuestion>;
    filters: { type: string; source: string; category: string; search: string; archived: boolean; batch: string };
    types: Option[];
    sources: Option[];
    categories: string[];
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Assessments', href: '/recruiter/assessments' },
    { title: 'Question Bank', href: '/recruiter/assessments/questions' },
];

export default function QuestionBank({ questions, filters, types, sources, categories }: Props) {
    const current = {
        type: filters.type || undefined,
        source: filters.source || undefined,
        category: filters.category || undefined,
        search: filters.search || undefined,
        archived: filters.archived ? 1 : undefined,
        batch: filters.batch || undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/assessments/questions', { ...current, per_page: questions.per_page, ...changes }, { preserveState: true, replace: true });
    };

    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timer = window.setTimeout(() => apply({ search: search.trim() || null }), 350);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const filterSelect = (value: string, key: string, placeholder: string, options: Option[]) => (
        <Select value={value || ALL} onValueChange={(next) => apply({ [key]: next === ALL ? null : next })}>
            <SelectTrigger className="w-full lg:w-44" aria-label={placeholder}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>{placeholder}</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Question Bank" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <AssessmentSubNav />
                <DataTableCard
                    title="Question Bank"
                    description="Reusable questions. Adding one to a quiz copies it, so later edits here never change published quizzes."
                    action={
                        <div className="flex w-full flex-wrap gap-2 sm:w-auto">
                            <Button asChild variant="outline">
                                <Link href="/recruiter/assessments/questions/import">
                                    <FileSpreadsheet /> Import from Excel
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/recruiter/assessments/questions/create">
                                    <Plus /> New question
                                </Link>
                            </Button>
                        </div>
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center">
                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search questions"
                                aria-label="Search questions"
                                containerClassName="w-full min-w-0 lg:max-w-xs"
                            />
                            {filterSelect(filters.type, 'type', 'All types', types)}
                            {filterSelect(filters.source, 'source', 'Any source', sources)}
                            {filterSelect(
                                filters.category,
                                'category',
                                'All categories',
                                categories.map((category) => ({ value: category, label: category })),
                            )}
                            <label className="text-muted-foreground flex items-center gap-2 text-sm">
                                <Switch checked={filters.archived} onCheckedChange={(checked) => apply({ archived: checked ? 1 : null })} />
                                Archived
                            </label>
                            {filters.batch && (
                                <Button variant="outline" size="sm" onClick={() => apply({ batch: null })}>
                                    Showing one import · clear
                                </Button>
                            )}
                        </div>
                    }
                    footer={<Pagination page={questions} leading={<EntriesSelect value={questions.per_page} onChange={(perPage) => apply({ per_page: perPage })} />} />}
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[45%]">Question</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead className="text-right">Points</TableHead>
                                <TableHead>Source</TableHead>
                                <TableHead>Added by</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {questions.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                        No questions match these filters.
                                    </TableCell>
                                </TableRow>
                            )}
                            {questions.data.map((question) => (
                                <TableRow key={question.id}>
                                    <TableCell className="max-w-md whitespace-normal">
                                        <Link href={`/recruiter/assessments/questions/${question.id}`} className="line-clamp-2 font-medium hover:underline">
                                            {question.prompt}
                                        </Link>
                                        {question.archived && <Badge variant="outline">Archived</Badge>}
                                    </TableCell>
                                    <TableCell>
                                        <QuestionTypeBadge label={question.type_label} />
                                    </TableCell>
                                    <TableCell className="text-sm">{question.category ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm">{question.points}</TableCell>
                                    <TableCell className="text-sm">
                                        {question.source_label}
                                        {question.import_row !== null && <span className="text-muted-foreground text-xs"> · row {question.import_row}</span>}
                                    </TableCell>
                                    <TableCell className="text-sm">{question.created_by ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
