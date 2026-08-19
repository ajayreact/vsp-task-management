import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';

export type OfficeLocationFormValues = {
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    allowed_gps_radius_meters: string;
    late_check_in_time: string;
    network_verification_enabled: boolean;
    authorized_public_ips_text: string;
    is_active: boolean;
};

export function OfficeLocationForm({
    initial,
    action,
    method,
    submitLabel,
}: {
    initial: OfficeLocationFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<OfficeLocationFormValues>(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Office details</CardTitle>
                        <CardDescription>Name and address shown to admins when managing attendance.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Office name</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="address">Address</Label>
                            <Textarea
                                id="address"
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                rows={3}
                                required
                            />
                            <InputError message={errors.address} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>GPS boundary</CardTitle>
                        <CardDescription>
                            Coordinates and radius used with employee GPS during check-in and check-out.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="latitude">Latitude</Label>
                            <Input
                                id="latitude"
                                type="number"
                                step="any"
                                inputMode="decimal"
                                value={data.latitude}
                                onChange={(e) => setData('latitude', e.target.value)}
                                placeholder="28.613939"
                                required
                            />
                            <InputError message={errors.latitude} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="longitude">Longitude</Label>
                            <Input
                                id="longitude"
                                type="number"
                                step="any"
                                inputMode="decimal"
                                value={data.longitude}
                                onChange={(e) => setData('longitude', e.target.value)}
                                placeholder="77.209023"
                                required
                            />
                            <InputError message={errors.longitude} />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="allowed_gps_radius_meters">Allowed GPS radius (meters)</Label>
                            <Input
                                id="allowed_gps_radius_meters"
                                type="number"
                                min={1}
                                step={1}
                                inputMode="numeric"
                                value={data.allowed_gps_radius_meters}
                                onChange={(e) => setData('allowed_gps_radius_meters', e.target.value)}
                                placeholder="100"
                                required
                            />
                            <InputError message={errors.allowed_gps_radius_meters} />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="late_check_in_time">Late check-in time</Label>
                            <Input
                                id="late_check_in_time"
                                type="time"
                                value={data.late_check_in_time}
                                onChange={(e) => setData('late_check_in_time', e.target.value)}
                                required
                            />
                            <p className="text-muted-foreground text-xs leading-relaxed">
                                Employees who check in after this time are marked as Late for the day.
                            </p>
                            <InputError message={errors.late_check_in_time} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Office network verification</CardTitle>
                        <CardDescription>
                            Web browsers cannot read Wi-Fi names (SSID). When enabled, check-in and check-out also require
                            the employee&apos;s public IP to match an authorized office network address. On office Wi-Fi,
                            traffic typically exits through the office public IP.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-3">
                            <Switch
                                id="network_verification_enabled"
                                checked={data.network_verification_enabled}
                                onCheckedChange={(checked) => setData('network_verification_enabled', checked)}
                            />
                            <Label htmlFor="network_verification_enabled">Require office network verification</Label>
                        </div>
                        <InputError message={errors.network_verification_enabled} />

                        {data.network_verification_enabled && (
                            <div className="grid gap-2">
                                <Label htmlFor="authorized_public_ips_text">Authorized public IP addresses</Label>
                                <Textarea
                                    id="authorized_public_ips_text"
                                    value={data.authorized_public_ips_text}
                                    onChange={(e) => setData('authorized_public_ips_text', e.target.value)}
                                    rows={5}
                                    placeholder={'203.0.113.10\n203.0.113.0/24'}
                                />
                                <p className="text-muted-foreground text-xs leading-relaxed">
                                    Enter one IPv4/IPv6 address or CIDR range per line. Find your office public IP by
                                    visiting an IP lookup site while connected to office Wi-Fi.
                                </p>
                                <InputError message={errors.authorized_public_ips_text} />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Status</CardTitle>
                        <CardDescription>Inactive offices stay on file but will not accept check-ins later.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-3">
                            <Switch
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) => setData('is_active', checked)}
                            />
                            <Label htmlFor="is_active">Active</Label>
                        </div>
                        <InputError message={errors.is_active} />
                    </CardContent>
                </Card>

                <div className="flex flex-wrap gap-2">
                    <Button type="submit" disabled={processing}>
                        {processing && <LoaderCircle className="animate-spin" />}
                        {submitLabel}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/attendance/offices">Cancel</Link>
                    </Button>
                </div>
            </div>
        </form>
    );
}
