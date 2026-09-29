import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';

type Mode = 'individual' | 'multiple' | 'team';

interface Props {
    courses: { id: number; label: string }[];
    recruiters: { id: number; label: string }[];
    defaults: { course_id: string };
    minDate: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'Assignments', href: '/recruiter/training/assignments' },
    { title: 'Assign', href: '/recruiter/training/assignments/create' },
];

const MODES: { value: Mode; label: string; hint: string }[] = [
    { value: 'individual', label: 'One recruiter', hint: 'Assign to a single recruiter.' },
    { value: 'multiple', label: 'Several recruiters', hint: 'Pick the recruiters to assign.' },
    { value: 'team', label: 'Whole recruiter team', hint: 'Everyone with recruiter access and an active profile.' },
];

export default function AssignTraining({ courses, recruiters, defaults, minDate }: Props) {
    const [search, setSearch] = useState('');
    const { data, setData, post, processing, errors } = useForm<{
        course_id: string;
        mode: Mode;
        employee_ids: number[];
        due_at: string;
    }>({
        course_id: defaults.course_id,
        mode: 'individual',
        employee_ids: [],
        due_at: '',
    });

    const visibleRecruiters = useMemo(() => {
        const term = search.trim().toLowerCase();

        return term === '' ? recruiters : recruiters.filter((recruiter) => recruiter.label.toLowerCase().includes(term));
    }, [recruiters, search]);

    const toggle = (id: number, checked: boolean) => {
        setData('employee_ids', checked ? [...data.employee_ids, id] : data.employee_ids.filter((value) => value !== id));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/recruiter/training/assignments', { preserveScroll: true });
    };

    const idErrors = Object.entries(errors)
        .filter(([key]) => key.startsWith('employee_ids'))
        .map(([, message]) => message);

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Assign Training" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Assign Training"
                    description="Recruiters receive the course's current published version. Anyone who already has the course open, or finished this version, is skipped."
                />
                <TrainingSubNav />

                <form onSubmit={submit} className="max-w-3xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Course</CardTitle>
                            <CardDescription>Only published courses can be assigned.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="course_id">Course</Label>
                                <Select value={data.course_id} onValueChange={(value) => setData('course_id', value)}>
                                    <SelectTrigger id="course_id">
                                        <SelectValue placeholder={courses.length ? 'Choose a course' : 'No published courses yet'} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {courses.map((course) => (
                                            <SelectItem key={course.id} value={String(course.id)}>
                                                {course.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.course_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="due_at">Due date</Label>
                                <Input id="due_at" type="date" min={minDate} value={data.due_at} onChange={(e) => setData('due_at', e.target.value)} />
                                <p className="text-muted-foreground text-xs">Optional. Due at the end of that day.</p>
                                <InputError message={errors.due_at} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recruiters</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2 sm:grid-cols-3" role="radiogroup" aria-label="Assign to">
                                {MODES.map((mode) => (
                                    <button
                                        key={mode.value}
                                        type="button"
                                        role="radio"
                                        aria-checked={data.mode === mode.value}
                                        onClick={() => {
                                            setData((current) => ({ ...current, mode: mode.value, employee_ids: [] }));
                                        }}
                                        className={cn(
                                            'rounded-lg border p-3 text-left transition-colors',
                                            data.mode === mode.value ? 'border-emerald-600 bg-emerald-600/5' : 'hover:bg-muted/50',
                                        )}
                                    >
                                        <div className="text-sm font-medium">{mode.label}</div>
                                        <div className="text-muted-foreground text-xs">{mode.hint}</div>
                                    </button>
                                ))}
                            </div>
                            <InputError message={errors.mode} />

                            {data.mode === 'individual' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="recruiter">Recruiter</Label>
                                    <Select
                                        value={data.employee_ids[0] ? String(data.employee_ids[0]) : ''}
                                        onValueChange={(value) => setData('employee_ids', [Number(value)])}
                                    >
                                        <SelectTrigger id="recruiter">
                                            <SelectValue placeholder="Choose a recruiter" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {recruiters.map((recruiter) => (
                                                <SelectItem key={recruiter.id} value={String(recruiter.id)}>
                                                    {recruiter.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            {data.mode === 'multiple' && (
                                <div className="space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Input
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Search recruiters"
                                            className="max-w-xs"
                                            aria-label="Search recruiters"
                                        />
                                        <span className="text-muted-foreground text-xs">{data.employee_ids.length} selected</span>
                                    </div>
                                    <div className="max-h-72 space-y-1 overflow-y-auto rounded-lg border p-2">
                                        {visibleRecruiters.length === 0 && <p className="text-muted-foreground p-2 text-sm">No recruiters found.</p>}
                                        {visibleRecruiters.map((recruiter) => (
                                            <label key={recruiter.id} className="hover:bg-muted/50 flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm">
                                                <Checkbox
                                                    checked={data.employee_ids.includes(recruiter.id)}
                                                    onCheckedChange={(checked) => toggle(recruiter.id, checked === true)}
                                                />
                                                {recruiter.label}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {data.mode === 'team' && (
                                <p className="text-muted-foreground rounded-lg border border-dashed p-3 text-sm">
                                    The course goes to all {recruiters.length} active recruiters. People who already have it are skipped.
                                </p>
                            )}

                            {idErrors.length > 0 && <InputError message={idErrors[0]} />}
                        </CardContent>
                    </Card>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing || courses.length === 0}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Assign training
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/recruiter/training/assignments">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </RecruiterLayout>
    );
}
