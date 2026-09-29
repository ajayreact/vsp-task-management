import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type Option } from '@/types';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

export type RecruiterTaskFormValues = {
    title: string;
    description: string;
    instructions: string;
    work_type: string;
    priority: string;
    due_at: string;
    target_count: string;
    assigned_employee_id: string;
};

export interface RecruiterTaskFormOptions {
    workTypes: Option[];
    priorities: Option[];
    recruiters: { id: number; label: string }[];
}

export function RecruiterTaskForm({
    options,
    initial,
    action,
    method,
    submitLabel,
    cancelUrl,
    showAssignee = false,
}: {
    options: RecruiterTaskFormOptions;
    initial: RecruiterTaskFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
    showAssignee?: boolean;
}) {
    const { data, setData, post, put, processing, errors } = useForm<RecruiterTaskFormValues>(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>What needs doing</CardTitle>
                    <CardDescription>
                        {showAssignee
                            ? 'The recruiter is notified and accepts the task before work starts.'
                            : 'Status and the assigned recruiter are changed from the task page.'}
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="Source STEM OPT candidates"
                            required
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={4} />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="work_type">Work type</Label>
                        <Select value={data.work_type} onValueChange={(value) => setData('work_type', value)}>
                            <SelectTrigger id="work_type">
                                <SelectValue placeholder="Choose a work type" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.workTypes.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.work_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="priority">Priority</Label>
                        <Select value={data.priority} onValueChange={(value) => setData('priority', value)}>
                            <SelectTrigger id="priority">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.priorities.map((priority) => (
                                    <SelectItem key={priority.value} value={priority.value}>
                                        {priority.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.priority} />
                    </div>

                    {showAssignee && (
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="assigned_employee_id">Assigned recruiter</Label>
                            <Select value={data.assigned_employee_id} onValueChange={(value) => setData('assigned_employee_id', value)}>
                                <SelectTrigger id="assigned_employee_id">
                                    <SelectValue placeholder="Choose a recruiter" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.recruiters.map((recruiter) => (
                                        <SelectItem key={recruiter.id} value={String(recruiter.id)}>
                                            {recruiter.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {options.recruiters.length === 0 && (
                                <p className="text-muted-foreground text-sm">No active recruiters yet. Give someone the Recruiter role first.</p>
                            )}
                            <InputError message={errors.assigned_employee_id} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="due_at">Due date and time</Label>
                        <Input id="due_at" type="datetime-local" value={data.due_at} onChange={(e) => setData('due_at', e.target.value)} />
                        <InputError message={errors.due_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="target_count">Target count</Label>
                        <Input
                            id="target_count"
                            type="number"
                            min="1"
                            step="1"
                            value={data.target_count}
                            onChange={(e) => setData('target_count', e.target.value)}
                            placeholder="Optional, e.g. 30"
                        />
                        <p className="text-muted-foreground text-xs">A number to report against. It does not create any records.</p>
                        <InputError message={errors.target_count} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="instructions">Instructions</Label>
                        <Textarea
                            id="instructions"
                            value={data.instructions}
                            onChange={(e) => setData('instructions', e.target.value)}
                            rows={4}
                            placeholder="Optional guidance for the recruiter"
                        />
                        <InputError message={errors.instructions} />
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
