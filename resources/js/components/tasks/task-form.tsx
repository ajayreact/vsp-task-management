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

export type TaskFormValues = {
    tm_project_id: string;
    department_id: string;
    title: string;
    description: string;
    type: string;
    priority: string;
    estimated_hours: string;
    due_at: string;
    assigned_employee_id: string;
};

export interface TaskFormOptions {
    projects: { id: number; name: string; code: string }[];
    departments: { id: number; name: string }[];
    types: Option[];
    priorities: Option[];
    assignableEmployees?: { id: number; label: string }[];
    canAssign?: boolean;
}

const NONE = 'none';

type TaskFormControlProps = {
    data: TaskFormValues;
    setData: (key: keyof TaskFormValues, value: string) => void;
    errors: Partial<Record<keyof TaskFormValues, string>>;
    options: TaskFormOptions;
    showAssignee?: boolean;
    cardTitle?: string;
    cardDescription?: string;
};

export function TaskDetailsCard({
    data,
    setData,
    errors,
    options,
    showAssignee = false,
    cardTitle = 'What needs doing',
    cardDescription,
}: TaskFormControlProps) {
    const description =
        cardDescription ??
        (showAssignee
            ? 'Add the details and optionally assign someone now. They will receive a notification when assigned.'
            : 'A new task starts as a draft. Assign it or put it on the open board once the details are right.');

    return (
        <Card>
            <CardHeader>
                <CardTitle>{cardTitle}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="title">Title</Label>
                    <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                    <InputError message={errors.title} />
                </div>

                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="description">Description</Label>
                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={5} />
                    <InputError message={errors.description} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="tm_project_id">Project</Label>
                    <Select value={data.tm_project_id} onValueChange={(value) => setData('tm_project_id', value)}>
                        <SelectTrigger id="tm_project_id">
                            <SelectValue placeholder="Pick a project" />
                        </SelectTrigger>
                        <SelectContent>
                            {options.projects.map((project) => (
                                <SelectItem key={project.id} value={String(project.id)}>
                                    {project.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.tm_project_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="department_id">Department</Label>
                    <Select value={data.department_id || NONE} onValueChange={(value) => setData('department_id', value === NONE ? '' : value)}>
                        <SelectTrigger id="department_id">
                            <SelectValue placeholder="Any" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NONE}>Any department</SelectItem>
                            {options.departments.map((department) => (
                                <SelectItem key={department.id} value={String(department.id)}>
                                    {department.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.department_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="type">Type</Label>
                    <Select value={data.type} onValueChange={(value) => setData('type', value)}>
                        <SelectTrigger id="type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {options.types.map((type) => (
                                <SelectItem key={type.value} value={type.value}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
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

                <div className="grid gap-2">
                    <Label htmlFor="estimated_hours">Estimated hours</Label>
                    <Input
                        id="estimated_hours"
                        type="number"
                        step="0.25"
                        min="0"
                        value={data.estimated_hours}
                        onChange={(e) => setData('estimated_hours', e.target.value)}
                    />
                    <InputError message={errors.estimated_hours} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="due_at">Due date</Label>
                    <Input id="due_at" type="datetime-local" value={data.due_at} onChange={(e) => setData('due_at', e.target.value)} />
                    <InputError message={errors.due_at} />
                </div>

                {showAssignee && (options.assignableEmployees?.length ?? 0) > 0 && (
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="assigned_employee_id">Assign to</Label>
                        <Select
                            value={data.assigned_employee_id || NONE}
                            onValueChange={(value) => setData('assigned_employee_id', value === NONE ? '' : value)}
                        >
                            <SelectTrigger id="assigned_employee_id">
                                <SelectValue placeholder="Assign later on the task page" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>Assign later</SelectItem>
                                {options.assignableEmployees?.map((employee) => (
                                    <SelectItem key={employee.id} value={String(employee.id)}>
                                        {employee.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.assigned_employee_id} />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export function TaskForm({
    options,
    initial,
    action,
    method,
    submitLabel,
    cancelUrl,
    showAssignee = false,
}: {
    options: TaskFormOptions;
    initial: TaskFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
    showAssignee?: boolean;
}) {
    const { data, setData, post, put, processing, errors } = useForm<TaskFormValues>(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <TaskDetailsCard data={data} setData={setData} errors={errors} options={options} showAssignee={showAssignee} />

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
