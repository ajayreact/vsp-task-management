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

export type RecruiterDailyActivityFormValues = {
    activity_date: string;
    activity_type: string;
    title: string;
    description: string;
    start_time: string;
    end_time: string;
    quantity: string;
    recruiter_task_id: string;
    remarks: string;
};

export interface RecruiterDailyActivityFormOptions {
    activityTypes: Option[];
    tasks: { id: number; label: string }[];
    minDate: string | null;
    maxDate: string;
}

const NO_TASK = 'none';

export function RecruiterDailyActivityForm({
    options,
    initial,
    action,
    method,
    submitLabel,
    cancelUrl,
    description,
}: {
    options: RecruiterDailyActivityFormOptions;
    initial: RecruiterDailyActivityFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
    description: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<RecruiterDailyActivityFormValues>(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>What you did</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="activity_date">Activity date</Label>
                        <Input
                            id="activity_date"
                            type="date"
                            value={data.activity_date}
                            min={options.minDate ?? undefined}
                            max={options.maxDate}
                            onChange={(e) => setData('activity_date', e.target.value)}
                            required
                        />
                        <InputError message={errors.activity_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="activity_type">Activity type</Label>
                        <Select value={data.activity_type} onValueChange={(value) => setData('activity_type', value)}>
                            <SelectTrigger id="activity_type">
                                <SelectValue placeholder="Choose an activity type" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.activityTypes.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.activity_type} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="LinkedIn sourcing for STEM OPT data analysts"
                            required
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={3}
                            placeholder="Optional, e.g. Contacted 25 potential candidates through LinkedIn."
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="start_time">Start time</Label>
                        <Input id="start_time" type="time" value={data.start_time} onChange={(e) => setData('start_time', e.target.value)} />
                        <InputError message={errors.start_time} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="end_time">End time</Label>
                        <Input id="end_time" type="time" value={data.end_time} onChange={(e) => setData('end_time', e.target.value)} />
                        <InputError message={errors.end_time} />
                    </div>

                    <p className="text-muted-foreground -mt-2 text-xs sm:col-span-2">Times are optional. Give both or neither; the duration is worked out for you.</p>

                    <div className="grid gap-2">
                        <Label htmlFor="quantity">Quantity</Label>
                        <Input
                            id="quantity"
                            type="number"
                            min="0"
                            step="1"
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                            placeholder="Optional, e.g. 25"
                        />
                        <p className="text-muted-foreground text-xs">The number you are reporting, such as calls made. It creates no records.</p>
                        <InputError message={errors.quantity} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="recruiter_task_id">Related recruiter task</Label>
                        <Select
                            value={data.recruiter_task_id === '' ? NO_TASK : data.recruiter_task_id}
                            onValueChange={(value) => setData('recruiter_task_id', value === NO_TASK ? '' : value)}
                        >
                            <SelectTrigger id="recruiter_task_id">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NO_TASK}>No related task</SelectItem>
                                {options.tasks.map((task) => (
                                    <SelectItem key={task.id} value={String(task.id)}>
                                        {task.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.recruiter_task_id} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="remarks">Remarks</Label>
                        <Textarea id="remarks" value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} rows={2} placeholder="Optional" />
                        <InputError message={errors.remarks} />
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
