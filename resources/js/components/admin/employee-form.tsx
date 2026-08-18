import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { type Option } from '@/types';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

export type EmployeeFormValues = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    employee_code: string;
    department_id: string;
    designation_id: string;
    reporting_to_id: string;
    phone: string;
    joined_on: string;
    exited_on: string;
    status: string;
    is_active: boolean;
    roles: string[];
};

export interface EmployeeFormOptions {
    departments: { id: number; name: string }[];
    designations: { id: number; name: string }[];
    managers: { id: number; label: string }[];
    statuses: Option[];
    roles: string[];
}

const NONE = 'none';

export function EmployeeForm({
    options,
    initial,
    action,
    method,
    submitLabel,
}: {
    options: EmployeeFormOptions;
    initial: EmployeeFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<EmployeeFormValues>(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, { preserveScroll: true });
    };

    const toggleRole = (role: string, checked: boolean) => {
        setData('roles', checked ? [...data.roles, role] : data.roles.filter((current) => current !== role));
    };

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Account</CardTitle>
                        <CardDescription>
                            {method === 'post' ? 'Creates the login this person signs in with.' : 'Leave the password blank to keep the current one.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Full name</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} autoComplete="name" required />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                autoComplete="new-password"
                            />
                        </div>

                        <div className="flex items-center gap-3 sm:col-span-2">
                            <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                            <Label htmlFor="is_active" className="font-normal">
                                Allow this person to sign in
                            </Label>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Profile</CardTitle>
                        <CardDescription>Where this person sits in the organisation.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="employee_code">Employee code</Label>
                            <Input
                                id="employee_code"
                                value={data.employee_code}
                                onChange={(e) => setData('employee_code', e.target.value)}
                                required
                            />
                            <InputError message={errors.employee_code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="designation_id">Designation</Label>
                            <Select
                                value={data.designation_id || NONE}
                                onValueChange={(value) => setData('designation_id', value === NONE ? '' : value)}
                            >
                                <SelectTrigger id="designation_id">
                                    <SelectValue placeholder="Select designation" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>Select designation</SelectItem>
                                    {options.designations.map((designation) => (
                                        <SelectItem key={designation.id} value={String(designation.id)}>
                                            {designation.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.designation_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="department_id">Department</Label>
                            <Select
                                value={data.department_id || NONE}
                                onValueChange={(value) => setData('department_id', value === NONE ? '' : value)}
                            >
                                <SelectTrigger id="department_id">
                                    <SelectValue placeholder="Select department" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>Select department</SelectItem>
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
                            <Label htmlFor="reporting_to_id">Reports to</Label>
                            <Select
                                value={data.reporting_to_id || NONE}
                                onValueChange={(value) => setData('reporting_to_id', value === NONE ? '' : value)}
                            >
                                <SelectTrigger id="reporting_to_id">
                                    <SelectValue placeholder="Nobody" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>Nobody</SelectItem>
                                    {options.managers.map((manager) => (
                                        <SelectItem key={manager.id} value={String(manager.id)}>
                                            {manager.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.reporting_to_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                            <InputError message={errors.phone} />
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
                            <Label htmlFor="joined_on">Joined on</Label>
                            <Input id="joined_on" type="date" value={data.joined_on} onChange={(e) => setData('joined_on', e.target.value)} />
                            <InputError message={errors.joined_on} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="exited_on">Exited on</Label>
                            <Input id="exited_on" type="date" value={data.exited_on} onChange={(e) => setData('exited_on', e.target.value)} />
                            <InputError message={errors.exited_on} />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Roles</CardTitle>
                        <CardDescription>What this person can reach. Permissions come from the roles selected here.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {options.roles.map((role) => (
                            <div key={role} className="flex items-center gap-3">
                                <Checkbox
                                    id={`role-${role}`}
                                    checked={data.roles.includes(role)}
                                    onCheckedChange={(checked) => toggleRole(role, checked === true)}
                                />
                                <Label htmlFor={`role-${role}`} className="font-normal capitalize">
                                    {role.replace('-', ' ')}
                                </Label>
                            </div>
                        ))}
                        <InputError message={errors.roles} />
                    </CardContent>
                </Card>

                <div className="flex gap-2">
                    <Button type="submit" disabled={processing}>
                        {processing && <LoaderCircle className="animate-spin" />}
                        {submitLabel}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/employees">Cancel</Link>
                    </Button>
                </div>
            </div>
        </form>
    );
}
