import InputError from '@/components/input-error';
import { DataTableCard } from '@/components/admin/data-table-card';
import { TrainingSubNav } from '@/components/recruiter-operations/training/training-ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import RecruiterLayout from '@/layouts/recruiter-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface CategoryRow {
    id: number;
    name: string;
    description: string | null;
    level_number: number | null;
    sort_order: number;
    is_active: boolean;
    courses_count: number;
}

interface Props {
    categories: CategoryRow[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recruiter Operations', href: '/recruiter' },
    { title: 'Training', href: '/recruiter/training' },
    { title: 'Manage', href: '/recruiter/training/manage' },
    { title: 'Categories', href: '/recruiter/training/manage/categories' },
];

type FormValues = { name: string; description: string; level_number: string; sort_order: string };

function CategoryDialog({ category, open, onOpenChange }: { category: CategoryRow | null; open: boolean; onOpenChange: (open: boolean) => void }) {
    const { data, setData, post, put, processing, errors, reset } = useForm<FormValues>({
        name: category?.name ?? '',
        description: category?.description ?? '',
        level_number: category?.level_number ? String(category.level_number) : '',
        sort_order: category ? String(category.sort_order) : '0',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (category) {
            put(`/recruiter/training/manage/categories/${category.id}`, options);
        } else {
            post('/recruiter/training/manage/categories', options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{category ? 'Edit category' : 'New category'}</DialogTitle>
                        <DialogDescription>A training level that groups courses, such as “Level 1 - U.S. Fundamentals”.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="category-name">Name</Label>
                        <Input id="category-name" value={data.name} onChange={(e) => setData('name', e.target.value)} required maxLength={150} />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="category-description">Description</Label>
                        <Textarea id="category-description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} />
                        <InputError message={errors.description} />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="category-level">Level number</Label>
                            <Input
                                id="category-level"
                                type="number"
                                min="1"
                                max="99"
                                value={data.level_number}
                                onChange={(e) => setData('level_number', e.target.value)}
                                placeholder="Optional"
                            />
                            <InputError message={errors.level_number} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="category-order">Sort order</Label>
                            <Input id="category-order" type="number" min="0" max="9999" value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} />
                            <InputError message={errors.sort_order} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            {category ? 'Save' : 'Create category'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function TrainingCategories({ categories }: Props) {
    const [editing, setEditing] = useState<CategoryRow | null>(null);
    const [open, setOpen] = useState(false);

    const openDialog = (category: CategoryRow | null) => {
        setEditing(category);
        setOpen(true);
    };

    return (
        <RecruiterLayout breadcrumbs={breadcrumbs}>
            <Head title="Training Categories" />

            <div className="flex min-w-0 max-w-full flex-1 flex-col gap-6 p-4 md:p-6">
                <TrainingSubNav />
                <DataTableCard
                    title="Training Categories"
                    description="Levels that group courses. Deactivate a category to stop new courses being filed under it; existing courses keep it."
                    action={
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href="/recruiter/training/manage">Back to courses</Link>
                            </Button>
                            <Button onClick={() => openDialog(null)}>
                                <Plus /> New category
                            </Button>
                        </div>
                    }
                >
                    <Table className="min-w-max">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Level</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-right">Courses</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                        No categories yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {categories.map((category) => (
                                <TableRow key={category.id}>
                                    <TableCell className="text-sm">{category.level_number ?? '—'}</TableCell>
                                    <TableCell className="font-medium">{category.name}</TableCell>
                                    <TableCell className="text-muted-foreground max-w-md truncate text-sm">{category.description ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm">{category.courses_count}</TableCell>
                                    <TableCell>
                                        {category.is_active ? <Badge variant="success">Active</Badge> : <Badge variant="outline">Inactive</Badge>}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button size="sm" variant="outline" onClick={() => openDialog(category)}>
                                                Edit
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => router.post(`/recruiter/training/manage/categories/${category.id}/toggle`, {}, { preserveScroll: true })}
                                            >
                                                {category.is_active ? 'Deactivate' : 'Activate'}
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>

            {open && <CategoryDialog key={editing?.id ?? 'new'} category={editing} open={open} onOpenChange={setOpen} />}
        </RecruiterLayout>
    );
}
