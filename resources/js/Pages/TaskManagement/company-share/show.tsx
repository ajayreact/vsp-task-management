import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { Copy, Download, ExternalLink, Mail, Phone } from 'lucide-react';

interface SharedLogo {
    name: string;
    mime: string;
    size: number;
    variant_label: string;
    preview_url: string;
    download_url: string;
}

interface Props {
    brand: string;
    company: {
        name: string;
        website: string | null;
        email: string | null;
        phone: string | null;
    };
    logos: SharedLogo[];
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function copyText(value: string) {
    void navigator.clipboard.writeText(value);
}

export default function PublicCompanyShareShow({ brand, company, logos }: Props) {
    return (
        <>
            <Head title={`${company.name} · Logos`} />

            <div className="bg-muted/40 min-h-screen px-4 py-10">
                <div className="mx-auto w-full max-w-5xl space-y-6">
                    <div className="text-center">
                        <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">{brand}</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{company.name}</h1>
                        <p className="text-muted-foreground mt-2 text-sm">Approved company logos and contact details.</p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Company details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <DetailBlock label="Website" value={company.website} href={company.website} />
                            <DetailBlock label="Email" value={company.email} copyValue={company.email} />
                            <DetailBlock label="Phone" value={company.phone} href={company.phone ? `tel:${company.phone}` : null} />
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold">Logo variations</h2>
                        {logos.length === 0 ? (
                            <Card>
                                <CardContent className="text-muted-foreground py-10 text-center text-sm">
                                    No logos are available on this share link.
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2">
                                {logos.map((logo) => (
                                    <Card key={`${logo.variant_label}-${logo.name}`}>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">{logo.variant_label}</CardTitle>
                                            <CardDescription>
                                                {logo.name} · {formatBytes(logo.size)}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <div className="bg-muted/40 flex aspect-[4/3] items-center justify-center overflow-hidden rounded-lg border p-4">
                                                <img
                                                    src={logo.preview_url}
                                                    alt={logo.variant_label}
                                                    className="max-h-full max-w-full object-contain"
                                                />
                                            </div>
                                            <Button asChild className="w-full sm:w-auto">
                                                <a href={logo.download_url} download={logo.name}>
                                                    <Download className="mr-2 size-4" />
                                                    Download
                                                </a>
                                            </Button>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function DetailBlock({
    label,
    value,
    href,
    copyValue,
}: {
    label: string;
    value: string | null;
    href?: string | null;
    copyValue?: string | null;
}) {
    return (
        <div className="space-y-1">
            <p className="text-muted-foreground text-xs uppercase tracking-wide">{label}</p>
            {!value ? (
                <p className="text-muted-foreground text-sm">Not provided</p>
            ) : (
                <div className="flex flex-wrap items-center gap-2">
                    {href ? (
                        <a href={href} target={href.startsWith('http') ? '_blank' : undefined} rel="noreferrer" className="font-medium hover:underline">
                            {value}
                        </a>
                    ) : (
                        <span className="font-medium">{value}</span>
                    )}
                    {copyValue && (
                        <Button size="icon" variant="ghost" className="size-7" onClick={() => copyText(copyValue)}>
                            <Copy className="size-4" />
                        </Button>
                    )}
                    {href?.startsWith('http') && <ExternalLink className="text-muted-foreground size-4" />}
                    {label === 'Email' && value && <Mail className="text-muted-foreground size-4" />}
                    {label === 'Phone' && value && <Phone className="text-muted-foreground size-4" />}
                </div>
            )}
        </div>
    );
}
