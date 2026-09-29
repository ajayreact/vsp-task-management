import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { SearchInput } from '@/components/admin/search-input';
import { ContentStatusBadge, TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem, type Option, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FolderTree, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

interface CourseRow {
    id: number;
    title: string;
    category: string | null;
    status: string;
    status_label: string;
    current_version: string | null;
    draft_version: string | null;
    versions_count: number;
    assignments_count: number;
}

interface Props {
    courses: Paginated<CourseRow>;
    filters: { category: number | null; status: string; search: string };
    categories: { id: number; label: string }[];
    statuses: Option[];
    can: { create: boolean; assign: boolean };
}

const ALL = 'all';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'Manage', href: '/recruiter/training/manage' },
];

export default function ManageTraining({ courses, filters, categories, statuses, can }: Props) {
    const current = {
        category: filters.category ?? undefined,
        status: filters.status || undefined,
        search: filters.search || undefined,
    };

    const apply = (changes: Record<string, string | number | null>) => {
        router.get('/recruiter/training/manage', { ...current, per_page: courses.per_page, ...changes }, { preserveState: true, replace: true });
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

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Training" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />
                <DataTableCard
                    title="Training Courses"
                    description="Each course has numbered versions. Only a draft can be edited; publishing freezes it."
                    action={
                        <div className="flex w-full flex-wrap gap-2 sm:w-auto">
                            <Button asChild variant="outline">
                                <Link href="/recruiter/training/manage/categories">
                                    <FolderTree /> Categories
                                </Link>
                            </Button>
                            {can.create && (
                                <Button asChild>
                                    <Link href="/recruiter/training/manage/courses/create">
                                        <Plus /> New course
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                    toolbar={
                        <div className="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-3 lg:flex lg:flex-wrap">
                            <SearchInput
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search courses"
                                aria-label="Search courses"
                                containerClassName="w-full min-w-0 lg:max-w-xs"
                            />
                            <Select value={filters.category ? String(filters.category) : ALL} onValueChange={(value) => apply({ category: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-64" aria-label="Filter by category">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>All categories</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={String(category.id)}>
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={filters.status || ALL} onValueChange={(value) => apply({ status: value === ALL ? null : value })}>
                                <SelectTrigger className="w-full lg:w-44" aria-label="Filter by status">
                                    <SelectValue />
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
                    }
                    footer={<Pagination page={courses} leading={<EntriesSelect value={courses.per_page} onChange={(perPage) => apply({ per_page: perPage })} />} />}
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Course</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Live version</TableHead>
                                <TableHead>Draft</TableHead>
                                <TableHead className="text-right">Versions</TableHead>
                                <TableHead className="text-right">Assignments</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {courses.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-muted-foreground py-10 text-center">
                                        No courses match these filters.
                                    </TableCell>
                                </TableRow>
                            )}
                            {courses.data.map((course) => (
                                <TableRow key={course.id}>
                                    <TableCell>
                                        <Link href={`/recruiter/training/manage/courses/${course.id}`} className="font-medium hover:underline">
                                            {course.title}
                                        </Link>
                                    </TableCell>
                                    <TableCell className="text-sm">{course.category ?? '—'}</TableCell>
                                    <TableCell>
                                        <ContentStatusBadge status={course.status} label={course.status_label} />
                                    </TableCell>
                                    <TableCell className="text-sm">{course.current_version ?? '—'}</TableCell>
                                    <TableCell className="text-sm">{course.draft_version ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm">{course.versions_count}</TableCell>
                                    <TableCell className="text-right text-sm">{course.assignments_count}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </RecruiterLayout>
    );
}
