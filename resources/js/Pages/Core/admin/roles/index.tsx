import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

interface RoleRow {
    id: number;
    name: string;
    label: string;
    is_system: boolean;
    users_count: number;
    permissions_count: number;
    can_update: boolean;
    can_delete: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
];

export default function RoleIndex({ roles, can }: { roles: RoleRow[]; can: { manage: boolean } }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Roles"
                    description="Permissions are granted through roles, never directly to a person. Super admin passes every check and cannot be edited here."
                    action={
                        can.manage && (
                            <Button asChild>
                                <Link href="/admin/roles/create">
                                    <Plus /> Add role
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Role</TableHead>
                                <TableHead className="text-right">Permissions</TableHead>
                                <TableHead className="text-right">People</TableHead>
                                <TableHead className="w-24 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">{role.label}</span>
                                            {role.is_system && <Badge variant="outline">Built-in</Badge>}
                                        </div>
                                        <div className="text-muted-foreground font-mono text-xs">{role.name}</div>
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {role.name === 'super-admin' ? <span className="text-muted-foreground">All</span> : role.permissions_count}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">{role.users_count}</TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            {role.can_update && (
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/admin/roles/${role.id}/edit`} aria-label={`Edit ${role.name}`}>
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                            )}
                                            {role.can_delete && (
                                                <ConfirmDelete
                                                    url={`/admin/roles/${role.id}`}
                                                    title={`Delete the ${role.name} role?`}
                                                    description="Nobody currently holds this role, so removing it will not change anyone's access."
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Delete ${role.name}`}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    }
                                                />
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
