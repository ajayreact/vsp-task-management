import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

export interface AbilityGroup {
    group: string;
    abilities: { value: string; label: string }[];
}

type RoleFormValues = {
    name: string;
    permissions: string[];
};

export function RoleForm({
    abilities,
    initial,
    action,
    method,
    submitLabel,
    nameLocked = false,
}: {
    abilities: AbilityGroup[];
    initial: RoleFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    nameLocked?: boolean;
}) {
    const { data, setData, transform, post, put, processing, errors } = useForm<RoleFormValues>(initial);

    // Built-in role names are rejected server side rather than ignored, so the
    // field has to be left out of the payload entirely.
    transform((values) => (nameLocked ? { permissions: values.permissions } : values));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, { preserveScroll: true });
    };

    const toggle = (permission: string, checked: boolean) => {
        setData('permissions', checked ? [...data.permissions, permission] : data.permissions.filter((current) => current !== permission));
    };

    const toggleGroup = (group: AbilityGroup, checked: boolean) => {
        const values = group.abilities.map((ability) => ability.value);

        setData(
            'permissions',
            checked ? [...new Set([...data.permissions, ...values])] : data.permissions.filter((current) => !values.includes(current)),
        );
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Role</CardTitle>
                    <CardDescription>
                        {nameLocked
                            ? 'This is a built-in role. Its name is fixed because code refers to it, but its permissions can change.'
                            : 'Lowercase letters, numbers and hyphens.'}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid max-w-sm gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            disabled={nameLocked}
                            placeholder="content-lead"
                            required={!nameLocked}
                        />
                        <InputError message={errors.name} />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Permissions</CardTitle>
                    <CardDescription>Everything this role can do. Users get the union of the permissions on all of their roles.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 sm:grid-cols-2">
                    {abilities.map((group) => {
                        const allChecked = group.abilities.every((ability) => data.permissions.includes(ability.value));

                        return (
                            <div key={group.group} className="space-y-3">
                                <div className="flex items-center gap-3 border-b pb-2">
                                    <Checkbox
                                        id={`group-${group.group}`}
                                        checked={allChecked}
                                        onCheckedChange={(checked) => toggleGroup(group, checked === true)}
                                    />
                                    <Label htmlFor={`group-${group.group}`} className="text-sm font-semibold">
                                        {group.group}
                                    </Label>
                                </div>

                                {group.abilities.map((ability) => (
                                    <div key={ability.value} className="flex items-start gap-3">
                                        <Checkbox
                                            id={ability.value}
                                            checked={data.permissions.includes(ability.value)}
                                            onCheckedChange={(checked) => toggle(ability.value, checked === true)}
                                        />
                                        <Label htmlFor={ability.value} className="font-normal">
                                            {ability.label}
                                            <span className="text-muted-foreground block font-mono text-xs">{ability.value}</span>
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        );
                    })}
                    <InputError message={errors.permissions} />
                </CardContent>
            </Card>

            <div className="flex gap-2">
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    {submitLabel}
                </Button>
                <Button type="button" variant="outline" asChild>
                    <Link href="/admin/roles">Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
