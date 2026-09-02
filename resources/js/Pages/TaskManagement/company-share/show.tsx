import BrandLogo from '@/components/brand-logo';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/react';
import { Copy, Download, ExternalLink, Globe, Mail, Phone, Sparkles } from 'lucide-react';
import { useState } from 'react';

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

export default function PublicCompanyShareShow({ brand, company, logos }: Props) {
    const featuredLogo = logos[0] ?? null;

    return (
        <>
            <Head title={`${company.name} · Brand assets`} />

            <div className="bg-background min-h-screen">
                <header className="border-b border-[rgba(120,115,110,0.12)] bg-white/80 backdrop-blur-md">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <BrandLogo variant="card" />
                        <Badge variant="outline" className="hidden sm:inline-flex">
                            Brand asset library
                        </Badge>
                    </div>
                </header>

                <section className="relative overflow-hidden border-b border-[rgba(120,115,110,0.1)]">
                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-600/10 via-sky-500/5 to-emerald-500/10" />
                    <div
                        className="pointer-events-none absolute inset-0 opacity-[0.35]"
                        style={{
                            backgroundImage:
                                'radial-gradient(circle at 20% 20%, rgba(99,102,241,0.18) 0, transparent 42%), radial-gradient(circle at 80% 0%, rgba(14,165,233,0.16) 0, transparent 38%)',
                        }}
                    />

                    <div className="relative mx-auto grid w-full max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-14">
                        <div className="space-y-5">
                            <div className="inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                <Sparkles className="size-3.5" />
                                Approved brand assets
                            </div>
                            <div className="space-y-2">
                                <p className="text-muted-foreground text-sm font-medium tracking-[0.18em] uppercase">{brand}</p>
                                <h1 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl lg:text-[2.75rem] lg:leading-tight">
                                    {company.name}
                                </h1>
                                <p className="text-muted-foreground max-w-xl text-base leading-relaxed">
                                    Official logos and contact details shared for partners, vendors, and creative teams.
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3">
                                <ContactTile
                                    icon={Globe}
                                    label="Website"
                                    value={company.website}
                                    href={company.website}
                                    tone="sky"
                                />
                                <ContactTile
                                    icon={Mail}
                                    label="Email"
                                    value={company.email}
                                    href={company.email ? `mailto:${company.email}` : null}
                                    copyValue={company.email}
                                    tone="emerald"
                                />
                                <ContactTile
                                    icon={Phone}
                                    label="Phone"
                                    value={company.phone}
                                    href={company.phone ? `tel:${company.phone}` : null}
                                    tone="amber"
                                />
                            </div>
                        </div>

                        <div className="vsp-card relative overflow-hidden bg-gradient-to-br from-white to-indigo-50/70 p-6 sm:p-8">
                            <div className="absolute top-0 right-0 h-24 w-24 rounded-full bg-indigo-500/10 blur-2xl" />
                            <div className="relative space-y-4">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-muted-foreground text-xs font-semibold tracking-[0.14em] uppercase">Featured logo</p>
                                        <p className="mt-1 text-sm font-medium">{featuredLogo?.variant_label ?? 'Preview'}</p>
                                    </div>
                                    {featuredLogo && (
                                        <Badge variant="neutral">{formatBytes(featuredLogo.size)}</Badge>
                                    )}
                                </div>

                                <div className="flex aspect-[5/3] items-center justify-center overflow-hidden rounded-2xl border border-[rgba(120,115,110,0.12)] bg-[linear-gradient(45deg,#f8fafc_25%,transparent_25%,transparent_75%,#f8fafc_75%,#f8fafc),linear-gradient(45deg,#f8fafc_25%,transparent_25%,transparent_75%,#f8fafc_75%,#f8fafc)] bg-[length:16px_16px] bg-[position:0_0,8px_8px] p-6 shadow-inner">
                                    {featuredLogo ? (
                                        <img
                                            src={featuredLogo.preview_url}
                                            alt={featuredLogo.variant_label}
                                            className="max-h-full max-w-full object-contain drop-shadow-sm"
                                        />
                                    ) : (
                                        <p className="text-muted-foreground text-sm">No logo preview available yet.</p>
                                    )}
                                </div>

                                {featuredLogo && (
                                    <Button asChild className="w-full sm:w-auto">
                                        <a href={featuredLogo.download_url} download={featuredLogo.name}>
                                            <Download className="mr-2 size-4" />
                                            Download featured logo
                                        </a>
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                <main className="mx-auto w-full max-w-6xl space-y-6 px-4 py-10 sm:px-6">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 className="text-2xl font-semibold tracking-tight">Logo variations</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {logos.length === 0
                                    ? 'No logo files are attached to this share link.'
                                    : `${logos.length} approved ${logos.length === 1 ? 'file' : 'files'} ready to download.`}
                            </p>
                        </div>
                    </div>

                    {logos.length === 0 ? (
                        <div className="vsp-card bg-gradient-to-br from-white to-slate-50/80 px-6 py-14 text-center">
                            <p className="text-muted-foreground text-sm">Ask your VSP contact to publish logo variations on this link.</p>
                        </div>
                    ) : (
                        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            {logos.map((logo) => (
                                <LogoCard key={`${logo.variant_label}-${logo.name}`} logo={logo} />
                            ))}
                        </div>
                    )}
                </main>

                <footer className="border-t border-[rgba(120,115,110,0.12)] bg-muted/20">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-3 px-4 py-6 text-center sm:flex-row sm:text-left sm:px-6">
                        <p className="text-muted-foreground text-xs">
                            Shared securely through {brand}. Use these assets according to your brand guidelines.
                        </p>
                        <BrandLogo variant="card" className="opacity-80" />
                    </div>
                </footer>
            </div>
        </>
    );
}

function ContactTile({
    icon: Icon,
    label,
    value,
    href,
    copyValue,
    tone,
}: {
    icon: typeof Globe;
    label: string;
    value: string | null;
    href?: string | null;
    copyValue?: string | null;
    tone: 'sky' | 'emerald' | 'amber';
}) {
    const [copied, setCopied] = useState(false);

    const toneClass = {
        sky: 'from-sky-500 to-blue-500',
        emerald: 'from-emerald-500 to-teal-500',
        amber: 'from-amber-500 to-orange-500',
    }[tone];

    const copyText = async (text: string) => {
        await navigator.clipboard.writeText(text);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 1600);
    };

    return (
        <div className="vsp-card group mb-0 overflow-hidden bg-white/90 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[var(--vsp-card-shadow-hover)]">
            <div className="flex items-start gap-3">
                <span className={`flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ${toneClass} text-white shadow-sm`}>
                    <Icon className="size-4.5" strokeWidth={1.75} />
                </span>
                <div className="min-w-0 flex-1 space-y-1">
                    <p className="text-muted-foreground text-[11px] font-semibold tracking-[0.14em] uppercase">{label}</p>
                    {!value ? (
                        <p className="text-muted-foreground text-sm">Not provided</p>
                    ) : (
                        <div className="space-y-2">
                            {href ? (
                                <a
                                    href={href}
                                    target={href.startsWith('http') ? '_blank' : undefined}
                                    rel="noreferrer"
                                    className="block text-sm leading-snug font-medium break-words hover:underline"
                                >
                                    {value}
                                </a>
                            ) : (
                                <p className="text-sm leading-snug font-medium break-words">{value}</p>
                            )}
                            <div className="flex flex-wrap gap-1.5">
                                {copyValue && (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        className="h-7 px-2 text-xs"
                                        onClick={() => void copyText(copyValue)}
                                    >
                                        <Copy className="mr-1 size-3.5" />
                                        {copied ? 'Copied' : 'Copy'}
                                    </Button>
                                )}
                                {href?.startsWith('http') && (
                                    <Button asChild size="sm" variant="ghost" className="h-7 px-2 text-xs">
                                        <a href={href} target="_blank" rel="noreferrer">
                                            <ExternalLink className="mr-1 size-3.5" />
                                            Open
                                        </a>
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

function LogoCard({ logo }: { logo: SharedLogo }) {
    return (
        <article className="vsp-card group mb-0 overflow-hidden bg-gradient-to-br from-white to-slate-50/80 transition-all duration-300 hover:-translate-y-1 hover:shadow-[var(--vsp-card-shadow-hover)]">
            <div className="border-b border-[rgba(120,115,110,0.1)] px-5 py-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h3 className="truncate text-base font-semibold">{logo.variant_label}</h3>
                        <p className="text-muted-foreground mt-1 truncate text-xs">
                            {logo.name} · {formatBytes(logo.size)}
                        </p>
                    </div>
                    <Badge variant="outline" className="shrink-0 uppercase">
                        {logo.mime.split('/')[1] ?? 'file'}
                    </Badge>
                </div>
            </div>

            <div className="relative p-5">
                <div className="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-2xl border border-[rgba(120,115,110,0.12)] bg-[linear-gradient(45deg,#f8fafc_25%,transparent_25%,transparent_75%,#f8fafc_75%,#f8fafc),linear-gradient(45deg,#f8fafc_25%,transparent_25%,transparent_75%,#f8fafc_75%,#f8fafc)] bg-[length:14px_14px] bg-[position:0_0,7px_7px] p-5">
                    <img
                        src={logo.preview_url}
                        alt={logo.variant_label}
                        className="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-[1.02]"
                    />
                </div>

                <div className="mt-4 flex flex-wrap gap-2">
                    <Button asChild className="flex-1 sm:flex-none">
                        <a href={logo.download_url} download={logo.name}>
                            <Download className="mr-2 size-4" />
                            Download
                        </a>
                    </Button>
                    <Button asChild variant="outline" className="flex-1 sm:flex-none">
                        <a href={logo.preview_url} target="_blank" rel="noreferrer">
                            <ExternalLink className="mr-2 size-4" />
                            Preview
                        </a>
                    </Button>
                </div>
            </div>
        </article>
    );
}
