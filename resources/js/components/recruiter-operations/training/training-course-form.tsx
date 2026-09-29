import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

export type TrainingCourseFormValues = {
    category_id: string;
    title: string;
    description: string;
    estimated_minutes: string;
};

export function TrainingCourseForm({
    categories,
    initial,
    action,
    method,
    submitLabel,
    cancelUrl,
    showEstimate,
}: {
    categories: { id: number; label: string }[];
    initial: TrainingCourseFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
    showEstimate: boolean;
}) {
    const { data, setData, post, put, processing, errors } = useForm<TrainingCourseFormValues>(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        (method === 'post' ? post : put)(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Course details</CardTitle>
                    <CardDescription>Lessons live in versions. A new course starts with an empty draft Version 1.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="title">Title</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required maxLength={255} />
                        <InputError message={errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="category_id">Category</Label>
                        <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)}>
                            <SelectTrigger id="category_id">
                                <SelectValue placeholder="Choose a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((category) => (
                                    <SelectItem key={category.id} value={String(category.id)}>
                                        {category.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category_id} />
                    </div>
                    {showEstimate && (
                        <div className="grid gap-2">
                            <Label htmlFor="estimated_minutes">Estimated minutes</Label>
                            <Input
                                id="estimated_minutes"
                                type="number"
                                min="1"
                                max="6000"
                                value={data.estimated_minutes}
                                onChange={(e) => setData('estimated_minutes', e.target.value)}
                                placeholder="Optional"
                            />
                            <InputError message={errors.estimated_minutes} />
                        </div>
                    )}
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={4} maxLength={5000} />
                        <InputError message={errors.description} />
                    </div>
                </CardContent>
            </Card>

            <div className="flex gap-2">
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    {submitLabel}
                </Button>
                <Button type="button" variant="outline" asChild>
                    <Link href={cancelUrl}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
