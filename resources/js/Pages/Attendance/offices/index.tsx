import { DataTableCard } from '@/components/admin/data-table-card';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { RowActions } from '@/components/admin/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MapPin, Plus } from 'lucide-react';

interface OfficeRow {
    id: number;
    name: string;
    address: string;
    latitude: number;
    longitude: number;
    allowed_gps_radius_meters: number;
    is_active: boolean;
}

interface Props {
    offices: Paginated<OfficeRow>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/admin/attendance' },
    { title: 'Office Locations', href: '/admin/attendance/offices' },
];

function formatCoordinate(value: number): string {
    return value.toFixed(6);
}

export default function OfficeLocationIndex({ offices }: Props) {
    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/admin/attendance/offices',
            {
                per_page: offices.per_page,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const deactivate = (office: OfficeRow) => {
        router.post(`/admin/attendance/offices/${office.id}/deactivate`, {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Office Locations" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <DataTableCard
                    title="Office Locations"
                    description="Define office sites and GPS boundaries for attendance check-in. Verification flows will use these settings in a later phase."
                    action={
                        <Button asChild>
                            <Link href="/admin/attendance/offices/create">
                                <Plus /> Add office location
                            </Link>
                        </Button>
                    }
                    footer={
                        <Pagination
                            page={offices}
                            leading={<EntriesSelect value={offices.per_page} onChange={(perPage) => apply({ per_page: perPage })} />}
                        />
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Office</TableHead>
                                <TableHead>Coordinates</TableHead>
                                <TableHead className="text-right">GPS radius</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-16 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {offices.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-muted-foreground py-10 text-center">
                                        <div className="flex flex-col items-center gap-2">
                                            <MapPin className="text-muted-foreground/70 size-8" strokeWidth={1.5} />
                                            <span>No office locations yet.</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}

                            {offices.data.map((office) => (
                                <TableRow key={office.id}>
                                    <TableCell>
                                        <div className="font-medium">{office.name}</div>
                                        <div className="text-muted-foreground max-w-md text-xs leading-relaxed">{office.address}</div>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        <div>{formatCoordinate(office.latitude)}</div>
                                        <div>{formatCoordinate(office.longitude)}</div>
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">{office.allowed_gps_radius_meters} m</TableCell>
                                    <TableCell>
                                        <Badge variant={office.is_active ? 'success' : 'neutral'}>
                                            {office.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <RowActions
                                            label={`Actions for ${office.name}`}
                                            items={[
                                                {
                                                    key: 'edit',
                                                    label: 'Edit',
                                                    href: `/admin/attendance/offices/${office.id}/edit`,
                                                },
                                                ...(office.is_active
                                                    ? [
                                                          {
                                                              key: 'deactivate',
                                                              label: 'Deactivate',
                                                              onSelect: () => deactivate(office),
                                                          },
                                                      ]
                                                    : []),
                                                {
                                                    key: 'delete',
                                                    label: 'Delete',
                                                    confirm: {
                                                        url: `/admin/attendance/offices/${office.id}`,
                                                        title: `Delete ${office.name}?`,
                                                        description:
                                                            'This removes the office location permanently. Check-in history is not linked yet, so this is safe for now.',
                                                    },
                                                },
                                            ]}
                                        />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </DataTableCard>
            </div>
        </AppLayout>
    );
}
