import { DataTableCard } from '@/components/admin/data-table-card';
import { Pagination } from '@/components/admin/pagination';
import { SearchInput } from '@/components/admin/search-input';
import { ManagerQuestionView, type ManagerQuestion } from '@/components/recruiter-operations/assessments/assessment-ui';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

type PickerQuestion = ManagerQuestion & { in_version: boolean };

interface Props {
    assessment: { id: number; title: string };
    version: { id: number; label: string };
    questions: Paginated<PickerQuestion>;
    filters: { type: string; category: string; search: string };
    types: Option[];
    categories: string[];
}

const ALL = 'all';

export default function BankPicker({ assessment, version, questions, filters, types, categories }: Props) {
    const pickerUrl = `/recruiter/assessments/manage/versions/${version.id}/bank`;
    const back = `/recruiter/assessments/manage/${assessment.id}?version=${version.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recruiter Operations', href: '/recruiter' },
        { title: 'Assessments', href: '/recruiter/assessments' },
        { title: assessment.title, href: back },
        { title: 'Add from bank', href: pickerUrl },
    ];

    const [selected, setSelected] = useState<number[]>([]);
    const [processing, setProcessing] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const current = { type: filters.type || undefined, category: filters.category || undefined, search: filters.search || undefined };

    const apply = (changes: Record<string, string | null>) => {
        router.get(pickerUrl, { ...current, ...changes }, { preserveState: true, replace: true });
    };

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timer = window.setTimeout(() => apply({ search: search.trim() || null }), 350);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const toggle = (id: number, checked: boolean) => setSelected((ids) => (checked ? [...ids, id] : ids.filter((value) => value !== id)));

    const add = () => {
        router.post(pickerUrl, { question_ids: selected }, { onStart: () => setProcessing(true), onFinish: () => setProcessing(false) });
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Add from Question Bank" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title={`Add to ${assessment.title} · ${version.label}`}
                    description="Selected questions are copied into this draft. Later edits to the bank do not change the copies."
                    action={
                        <div className="flex gap-2">
                            <Button asChild variant="outline">
                                <Link href={back}>Cancel</Link>
                            </Button>
                            <Button onClick={add} disabled={selected.length === 0 || processing}>
                                {processing && <LoaderCircle className="animate-spin" />}
                                Add {selected.length > 0 ? selected.length : ''} selected
                            </Button>
                        </div>
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-3 lg:flex lg:flex-wrap">
                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search questions"
                                aria-label="Search questions"
                                containerClassName="w-full min-w-0 lg:max-w-xs"
                            />
                            <Select value={filters.type || ALL} onValueChange={(value) => apply({ type: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-48" aria-label="Filter by type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All types</SelectItem>
                                    {types.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.category || ALL} onValueChange={(value) => apply({ category: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-48" aria-label="Filter by category">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All categories</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category} value={category}>
                                            {category}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                    footer={<Pagination page={questions} />}
                >
                    <div className="divide-y">
                        {questions.data.length === 0 && <p className="text-muted-foreground px-6 py-10 text-center text-sm">No bank questions match these filters.</p>}
                        {questions.data.map((question) => (
                            <label key={question.id} className="flex cursor-pointer gap-3 px-6 py-4">
                                <Checkbox
                                    checked={question.in_version || selected.includes(question.id)}
                                    disabled={question.in_version}
                                    onCheckedChange={(checked) => toggle(question.id, checked === true)}
                                    aria-label="Select question"
                                    className="mt-0.5"
                                />
                                <div className="min-w-0 flex-1">
                                    <ManagerQuestionView question={question} />
                                    {question.in_version && <p className="text-muted-foreground mt-1 text-xs">Already in this version.</p>}
                                </div>
                            </label>
                        ))}
                    </div>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
