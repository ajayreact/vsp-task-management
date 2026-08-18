import { DataTableCard } from '@/components/admin/data-table-card';
import { DataTableFooter } from '@/components/admin/data-table-footer';
import { RowActions } from '@/components/admin/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';

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

interface Props {
    roles: Paginated<RoleRow>;
    can: { manage: boolean };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
];

export default function RoleIndex({ roles, can }: Props) {
    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/admin/roles',
            {
                per_page: roles.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Roles"
                    description="Permissions are granted through roles, never directly to a person. Super admin passes every check and cannot be edited here."
                    action={
                        can.manage ? (
                            <Button asChild>
                                <Link href="/admin/roles/create">
                                    <Plus /> Add role
                                </Link>
                            </Button>
                        ) : undefined
                    }
                    footer={
                        <DataTableFooter
                            page={roles}
                            onPerPageChange={(perPage) => apply({ per_page: perPage })}
                            exportBasePath="/admin/roles/export"
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Role</TableHead>
                                <TableHead className="text-right">Permissions</TableHead>
                                <TableHead className="text-right">People</TableHead>
                                <TableHead className="w-16 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-muted-foreground py-10 text-center">
                                        No roles yet.
                                    </TableCell>
                                </TableRow>
                            )}

                            {roles.data.map((role) => {
                                const items = [
                                    ...(role.can_update
                                        ? [{ key: 'edit', label: 'Edit', href: `/admin/roles/${role.id}/edit` }]
                                        : []),
                                    ...(role.can_delete
                                        ? [
                                              {
                                                  key: 'delete',
                                                  label: 'Delete',
                                                  confirm: {
                                                      url: `/admin/roles/${role.id}`,
                                                      title: `Delete the ${role.name} role?`,
                                                      description:
                                                          "Nobody currently holds this role, so removing it will not change anyone's access.",
                                                  },
                                              },
                                          ]
                                        : []),
                                ];

                                return (
                                    <TableRow key={role.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{role.label}</span>
                                                {role.is_system && <Badge variant="neutral">Built-in</Badge>}
                                            </div>
                                            <div className="text-muted-foreground font-mono text-xs">{role.name}</div>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {role.name === 'super-admin' ? (
                                                <span className="text-muted-foreground">All</span>
                                            ) : (
                                                role.permissions_count
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">{role.users_count}</TableCell>
                                        <TableCell className="text-right">
                                            <RowActions label={`Actions for ${role.name}`} items={items} />
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </AppLayout>
    );
}
