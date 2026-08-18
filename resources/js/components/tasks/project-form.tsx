import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type Option } from '@/types';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, X } from 'lucide-react';
import { type FormEvent } from 'react';

// A type alias rather than an interface: Inertia's useForm requires an
// implicit index signature, which interfaces do not provide.
export type ProjectMemberValue = {
    employee_id: number | string;
    project_role: string;
};

export type ProjectFormValues = {
    tm_company_id: string;
    name: string;
    code: string;
    description: string;
    status: string;
    start_date: string;
    due_date: string;
    manager_employee_id: string;
    budget_hours: string;
    members: ProjectMemberValue[];
};

export interface ProjectFormOptions {
    companies: { id: number; name: string }[];
    employees: { id: number; label: string }[];
    statuses: Option[];
    projectRoles: Option[];
}

const NONE = 'none';

export function ProjectForm({
    options,
    initial,
    action,
    method,
    submitLabel,
    cancelUrl,
}: {
    options: ProjectFormOptions;
    initial: ProjectFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<ProjectFormValues>(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, { preserveScroll: true });
    };

    const addMember = () => {
        const taken = data.members.map((member) => String(member.employee_id));
        const next = options.employees.find((employee) => !taken.includes(String(employee.id)));

        if (next) {
            setData('members', [...data.members, { employee_id: next.id, project_role: 'member' }]);
        }
    };

    const updateMember = (index: number, changes: Partial<ProjectMemberValue>) => {
        setData(
            'members',
            data.members.map((member, position) => (position === index ? { ...member, ...changes } : member)),
        );
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Project</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="tm_company_id">Client</Label>
                        <Select value={data.tm_company_id} onValueChange={(value) => setData('tm_company_id', value)}>
                            <SelectTrigger id="tm_company_id">
                                <SelectValue placeholder="Pick a client" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.companies.map((company) => (
                                    <SelectItem key={company.id} value={String(company.id)}>
                                        {company.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tm_company_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                            <SelectTrigger id="status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.statuses.map((status) => (
                                    <SelectItem key={status.value} value={status.value}>
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="code">Code</Label>
                        <Input id="code" value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} required />
                        <InputError message={errors.code} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="start_date">Start date</Label>
                        <Input id="start_date" type="date" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} />
                        <InputError message={errors.start_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="due_date">Due date</Label>
                        <Input id="due_date" type="date" value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} />
                        <InputError message={errors.due_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="manager_employee_id">Manager</Label>
                        <Select
                            value={data.manager_employee_id || NONE}
                            onValueChange={(value) => setData('manager_employee_id', value === NONE ? '' : value)}
                        >
                            <SelectTrigger id="manager_employee_id">
                                <SelectValue placeholder="Unassigned" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>Unassigned</SelectItem>
                                {options.employees.map((employee) => (
                                    <SelectItem key={employee.id} value={String(employee.id)}>
                                        {employee.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.manager_employee_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="budget_hours">Budget hours</Label>
                        <Input
                            id="budget_hours"
                            type="number"
                            step="0.5"
                            min="0"
                            value={data.budget_hours}
                            onChange={(e) => setData('budget_hours', e.target.value)}
                        />
                        <InputError message={errors.budget_hours} />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Team</CardTitle>
                    <CardDescription>Who is on this project, and in what capacity.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    {data.members.length === 0 && <p className="text-muted-foreground text-sm">Nobody on the project yet.</p>}

                    {data.members.map((member, index) => (
                        <div key={index} className="flex flex-wrap items-end gap-2">
                            <div className="grid min-w-56 flex-1 gap-2">
                                <Label className="sr-only">Employee</Label>
                                <Select
                                    value={String(member.employee_id)}
                                    onValueChange={(value) => updateMember(index, { employee_id: Number(value) })}
                                >
                                    <SelectTrigger aria-label={`Member ${index + 1}`}>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.employees.map((employee) => (
                                            <SelectItem key={employee.id} value={String(employee.id)}>
                                                {employee.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <Select value={member.project_role} onValueChange={(value) => updateMember(index, { project_role: value })}>
                                <SelectTrigger className="w-40" aria-label={`Role for member ${index + 1}`}>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.projectRoles.map((role) => (
                                        <SelectItem key={role.value} value={role.value}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Remove member"
                                onClick={() =>
                                    setData(
                                        'members',
                                        data.members.filter((_, position) => position !== index),
                                    )
                                }
                            >
                                <X className="size-4" />
                            </Button>

                            <InputError message={errors[`members.${index}.employee_id` as keyof typeof errors]} />
                        </div>
                    ))}

                    <Button type="button" variant="outline" size="sm" onClick={addMember} disabled={data.members.length >= options.employees.length}>
                        <Plus /> Add member
                    </Button>
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
