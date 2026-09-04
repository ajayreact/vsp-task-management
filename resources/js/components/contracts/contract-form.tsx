import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { type Option } from '@/types';
import { Link, router, useForm } from '@inertiajs/react';
import { Eye, LoaderCircle, Plus, X } from 'lucide-react';
import { type ChangeEvent, type FormEvent, useRef } from 'react';

export type ContactBlock = {
    name: string;
    authorized_person: string;
    phone: string;
    email: string;
    website: string;
    address: string;
};

export type ContractFormValues = {
    title: string;
    contract_number: string;
    contract_type: string;
    country: string;
    currency: string;
    effective_date: string;
    start_date: string;
    end_date: string;
    tm_company_id: string;
    provider: ContactBlock;
    client: ContactBlock;
    service_plan: {
        monthly_fee: string;
        currency: string;
        billing_frequency: string;
    };
    deliverables: { quantity: string; name: string; description: string }[];
    extra_work: { description: string; fee: string; currency: string; affects_monthly_fee: boolean }[];
    requirements: { label: string; value: string }[];
    responsibilities: { text: string }[];
    campaign_objective: { type: string; custom: string };
    client_content: { items: string[]; description: string };
    lead_generation: {
        lead_type: string;
        cpl: string;
        currency: string;
        qualification: string;
        notes: string;
    };
    lead_pricing: { lead_type: string; cpl: string; currency: string; description: string }[];
    lead_example: { quantity: string; cpl: string; currency: string };
    payment_terms: {
        invoice_payment_period: string;
        advance_payment: string;
        non_payment_terms: string;
        other: string;
    };
    custom_terms: string;
    document_logo: string;
    provider_signature: string;
    provider_signature_date: string;
};

export interface ContractClientOption {
    id: number;
    name: string;
    primary_contact_name?: string | null;
    primary_contact_email?: string | null;
    primary_contact_phone?: string | null;
    website?: string | null;
}

export interface CountryOption extends Option {
    currency: string;
    symbol: string;
}

export interface ContractFormOptions {
    clients: ContractClientOption[];
    contractTypes: Option[];
    countries: CountryOption[];
}

const CLIENT_CONTENT_OPTIONS = [
    { value: 'logo_brand', label: 'Logo and brand materials' },
    { value: 'photos_videos', label: 'Photos / videos' },
    { value: 'website_access', label: 'Website / landing page access' },
    { value: 'social_credentials', label: 'Social media credentials' },
    { value: 'marketing_copy', label: 'Marketing copy / brochure' },
    { value: 'ad_creatives', label: 'Existing ad creatives' },
    { value: 'other', label: 'Other materials' },
];

const CAMPAIGN_OBJECTIVES = [
    { value: 'lead_generation', label: 'Lead generation' },
    { value: 'brand_awareness', label: 'Brand awareness' },
    { value: 'sales', label: 'Sales / conversions' },
    { value: 'custom', label: 'Custom' },
];

const BILLING_FREQUENCIES = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'annually', label: 'Annually' },
];

function contactField(
    prefix: 'provider' | 'client',
    field: keyof ContactBlock,
    label: string,
    data: ContractFormValues,
    setData: ReturnType<typeof useForm<ContractFormValues>>['setData'],
    errors: ReturnType<typeof useForm<ContractFormValues>>['errors'],
    options?: { required?: boolean; type?: string; colSpan?: boolean },
) {
    const id = `${prefix}_${field}`;

    return (
        <div className={`grid gap-2 ${options?.colSpan ? 'sm:col-span-2' : ''}`}>
            <Label htmlFor={id}>{label}</Label>
            {field === 'address' ? (
                <Textarea
                    id={id}
                    value={data[prefix][field]}
                    onChange={(event) =>
                        setData(prefix, {
                            ...data[prefix],
                            [field]: event.target.value,
                        })
                    }
                    rows={3}
                />
            ) : (
                <Input
                    id={id}
                    type={options?.type ?? 'text'}
                    value={data[prefix][field]}
                    onChange={(event) =>
                        setData(prefix, {
                            ...data[prefix],
                            [field]: event.target.value,
                        })
                    }
                    required={options?.required}
                />
            )}
            <InputError message={errors[`${prefix}.${field}` as keyof typeof errors]} />
        </div>
    );
}

export function ContractForm({
    options,
    initial,
    action,
    method,
    contractId,
    cancelUrl,
}: {
    options: ContractFormOptions;
    initial: ContractFormValues;
    action: string;
    method: 'post' | 'put';
    contractId?: number;
    cancelUrl: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<ContractFormValues>(initial);
    const currencyManual = useRef(false);

    const applyCurrency = (currency: string) => {
        setData((prev) => ({
            ...prev,
            currency,
            service_plan: { ...prev.service_plan, currency },
            lead_generation: { ...prev.lead_generation, currency },
            lead_example: { ...prev.lead_example, currency },
            lead_pricing: prev.lead_pricing.map((row) => ({ ...row, currency })),
            extra_work: prev.extra_work.map((row) => ({ ...row, currency: row.currency || currency })),
        }));
    };

    const onCountryChange = (country: string) => {
        setData('country', country);

        if (!currencyManual.current) {
            const match = options.countries.find((item) => item.value === country);
            if (match) {
                applyCurrency(match.currency);
            }
        }
    };

    const onClientChange = (clientId: string) => {
        setData('tm_company_id', clientId);

        const client = options.clients.find((item) => String(item.id) === clientId);
        if (!client) {
            return;
        }

        setData('client', {
            ...data.client,
            name: client.name,
            authorized_person: client.primary_contact_name ?? '',
            phone: client.primary_contact_phone ?? '',
            email: client.primary_contact_email ?? '',
            website: client.website ?? '',
        });
    };

    const onContractTypeChange = (contractType: string) => {
        setData('contract_type', contractType);
        const match = options.contractTypes.find((item) => item.value === contractType);
        if (match) {
            setData('title', match.label);
        }
    };

    const submit = (event: FormEvent, previewAfterSave = false) => {
        event.preventDefault();
        const submitter = method === 'post' ? post : put;

        submitter(action, {
            preserveScroll: true,
            onSuccess: (page) => {
                if (!previewAfterSave) {
                    return;
                }

                const id =
                    contractId ??
                    (page.props as { contract?: { id: number } }).contract?.id ??
                    Number(String(page.url).match(/\/tasks\/contracts\/(\d+)/)?.[1]);

                if (id) {
                    router.visit(`/tasks/contracts/${id}/preview`);
                }
            },
        });
    };

    const exampleTotal =
        data.lead_example.quantity && data.lead_example.cpl
            ? (parseFloat(data.lead_example.quantity) * parseFloat(data.lead_example.cpl)).toFixed(2)
            : null;

    const onLogoUpload = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                setData('document_logo', reader.result);
            }
        };
        reader.readAsDataURL(file);
    };

    return (
        <form onSubmit={(event) => submit(event, false)} className="max-w-4xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Contract Details</CardTitle>
                    <CardDescription>Core agreement information and dates.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="tm_company_id">Client</Label>
                        <Select value={data.tm_company_id} onValueChange={onClientChange}>
                            <SelectTrigger id="tm_company_id">
                                <SelectValue placeholder="Select client" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.clients.map((client) => (
                                    <SelectItem key={client.id} value={String(client.id)}>
                                        {client.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tm_company_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contract_type">Contract type</Label>
                        <Select value={data.contract_type} onValueChange={onContractTypeChange}>
                            <SelectTrigger id="contract_type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.contractTypes.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.contract_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contract_number">Contract number</Label>
                        <Input
                            id="contract_number"
                            value={data.contract_number}
                            onChange={(event) => setData('contract_number', event.target.value)}
                            placeholder="Auto-generated if blank"
                        />
                        <InputError message={errors.contract_number} />
                    </div>

                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="title">Title</Label>
                        <Input id="title" value={data.title} onChange={(event) => setData('title', event.target.value)} required />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="country">Country</Label>
                        <Select value={data.country} onValueChange={onCountryChange}>
                            <SelectTrigger id="country">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {options.countries.map((country) => (
                                    <SelectItem key={country.value} value={country.value}>
                                        {country.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.country} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        <Input
                            id="currency"
                            value={data.currency}
                            onChange={(event) => {
                                currencyManual.current = true;
                                applyCurrency(event.target.value.toUpperCase());
                            }}
                            required
                        />
                        <InputError message={errors.currency} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="effective_date">Effective date</Label>
                        <Input
                            id="effective_date"
                            type="date"
                            value={data.effective_date}
                            onChange={(event) => setData('effective_date', event.target.value)}
                            required
                        />
                        <InputError message={errors.effective_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="start_date">Start date</Label>
                        <Input id="start_date" type="date" value={data.start_date} onChange={(event) => setData('start_date', event.target.value)} />
                        <InputError message={errors.start_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="end_date">End date</Label>
                        <Input id="end_date" type="date" value={data.end_date} onChange={(event) => setData('end_date', event.target.value)} />
                        <InputError message={errors.end_date} />
                    </div>

                    <div className="grid gap-3 sm:col-span-2">
                        <Label htmlFor="document_logo">Document logo (PDF header)</Label>
                        <div className="flex flex-wrap items-start gap-4 rounded-lg border border-dashed p-4">
                            {data.document_logo ? (
                                <img src={data.document_logo} alt="Contract logo preview" className="max-h-20 max-w-[160px] object-contain" />
                            ) : (
                                <div className="text-muted-foreground flex h-20 w-[160px] items-center justify-center rounded-md border bg-muted/30 text-xs">
                                    No logo uploaded
                                </div>
                            )}
                            <div className="flex flex-col gap-2">
                                <Input id="document_logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" onChange={onLogoUpload} />
                                <p className="text-muted-foreground text-xs">PNG, JPG, WebP or SVG. Shown in the PDF header.</p>
                                {data.document_logo && (
                                    <Button type="button" variant="ghost" size="sm" className="w-fit px-0" onClick={() => setData('document_logo', '')}>
                                        Remove logo
                                    </Button>
                                )}
                            </div>
                        </div>
                        <InputError message={errors.document_logo} />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Service Provider</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    {contactField('provider', 'name', 'Company name', data, setData, errors, { required: true })}
                    {contactField('provider', 'authorized_person', 'Authorized person', data, setData, errors)}
                    {contactField('provider', 'phone', 'Phone', data, setData, errors)}
                    {contactField('provider', 'email', 'Email', data, setData, errors, { type: 'email' })}
                    {contactField('provider', 'website', 'Website', data, setData, errors)}
                    {contactField('provider', 'address', 'Address', data, setData, errors, { colSpan: true })}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Client Details</CardTitle>
                    <CardDescription>Populated from the selected client; you can override any field.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    {contactField('client', 'name', 'Client / company', data, setData, errors, { required: true })}
                    {contactField('client', 'authorized_person', 'Authorized person', data, setData, errors, { required: true })}
                    {contactField('client', 'phone', 'Phone', data, setData, errors)}
                    {contactField('client', 'email', 'Email', data, setData, errors, { type: 'email' })}
                    {contactField('client', 'website', 'Website', data, setData, errors)}
                    {contactField('client', 'address', 'Address', data, setData, errors, { colSpan: true })}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Service Plan</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="monthly_fee">Monthly service fee</Label>
                        <Input
                            id="monthly_fee"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.service_plan.monthly_fee}
                            onChange={(event) =>
                                setData('service_plan', {
                                    ...data.service_plan,
                                    monthly_fee: event.target.value,
                                })
                            }
                            required
                        />
                        <InputError message={errors['service_plan.monthly_fee']} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="service_plan_currency">Currency</Label>
                        <Input
                            id="service_plan_currency"
                            value={data.service_plan.currency}
                            onChange={(event) =>
                                setData('service_plan', {
                                    ...data.service_plan,
                                    currency: event.target.value,
                                })
                            }
                            required
                        />
                        <InputError message={errors['service_plan.currency']} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="billing_frequency">Billing frequency</Label>
                        <Select
                            value={data.service_plan.billing_frequency}
                            onValueChange={(value) =>
                                setData('service_plan', {
                                    ...data.service_plan,
                                    billing_frequency: value,
                                })
                            }
                        >
                            <SelectTrigger id="billing_frequency">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {BILLING_FREQUENCIES.map((item) => (
                                    <SelectItem key={item.value} value={item.value}>
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors['service_plan.billing_frequency']} />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <div>
                        <CardTitle>Deliverables</CardTitle>
                        <CardDescription>Monthly deliverables included in the service plan.</CardDescription>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            setData('deliverables', [...data.deliverables, { quantity: '', name: '', description: '' }])
                        }
                    >
                        <Plus className="size-4" /> Add row
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    {data.deliverables.length === 0 && <p className="text-muted-foreground text-sm">No deliverables added yet.</p>}

                    {data.deliverables.map((row, index) => (
                        <div key={index} className="grid gap-3 rounded-lg border p-3 sm:grid-cols-[80px_1fr_1fr_auto] sm:items-end">
                            <div className="grid gap-2">
                                <Label>Qty</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={row.quantity}
                                    onChange={(event) => {
                                        const next = [...data.deliverables];
                                        next[index] = { ...row, quantity: event.target.value };
                                        setData('deliverables', next);
                                    }}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Service</Label>
                                <Input
                                    value={row.name}
                                    onChange={(event) => {
                                        const next = [...data.deliverables];
                                        next[index] = { ...row, name: event.target.value };
                                        setData('deliverables', next);
                                    }}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Description</Label>
                                <Input
                                    value={row.description}
                                    onChange={(event) => {
                                        const next = [...data.deliverables];
                                        next[index] = { ...row, description: event.target.value };
                                        setData('deliverables', next);
                                    }}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => setData('deliverables', data.deliverables.filter((_, i) => i !== index))}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <div>
                        <CardTitle>Extra Work</CardTitle>
                        <CardDescription>Additional services outside the monthly plan.</CardDescription>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            setData('extra_work', [
                                ...data.extra_work,
                                { description: '', fee: '', currency: data.currency, affects_monthly_fee: false },
                            ])
                        }
                    >
                        <Plus className="size-4" /> Add row
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    {data.extra_work.length === 0 && <p className="text-muted-foreground text-sm">No extra work items.</p>}

                    {data.extra_work.map((row, index) => (
                        <div key={index} className="grid gap-3 rounded-lg border p-3 sm:grid-cols-[1fr_120px_100px_auto] sm:items-end">
                            <div className="grid gap-2 sm:col-span-1">
                                <Label>Description</Label>
                                <Input
                                    value={row.description}
                                    onChange={(event) => {
                                        const next = [...data.extra_work];
                                        next[index] = { ...row, description: event.target.value };
                                        setData('extra_work', next);
                                    }}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Fee</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={row.fee}
                                    onChange={(event) => {
                                        const next = [...data.extra_work];
                                        next[index] = { ...row, fee: event.target.value };
                                        setData('extra_work', next);
                                    }}
                                />
                            </div>
                            <div className="flex items-center gap-2 pb-2">
                                <Switch
                                    checked={row.affects_monthly_fee}
                                    onCheckedChange={(checked) => {
                                        const next = [...data.extra_work];
                                        next[index] = { ...row, affects_monthly_fee: checked };
                                        setData('extra_work', next);
                                    }}
                                />
                                <Label className="text-xs">Affects monthly fee</Label>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => setData('extra_work', data.extra_work.filter((_, i) => i !== index))}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <div>
                        <CardTitle>Client Requirements</CardTitle>
                        <CardDescription>Information needed from the client for campaign setup.</CardDescription>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setData('requirements', [...data.requirements, { label: '', value: '' }])}
                    >
                        <Plus className="size-4" /> Add row
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    {data.requirements.map((row, index) => (
                        <div key={index} className="grid gap-3 rounded-lg border p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                            <div className="grid gap-2">
                                <Label>Label</Label>
                                <Input
                                    value={row.label}
                                    onChange={(event) => {
                                        const next = [...data.requirements];
                                        next[index] = { ...row, label: event.target.value };
                                        setData('requirements', next);
                                    }}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Value</Label>
                                <Input
                                    value={row.value}
                                    onChange={(event) => {
                                        const next = [...data.requirements];
                                        next[index] = { ...row, value: event.target.value };
                                        setData('requirements', next);
                                    }}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => setData('requirements', data.requirements.filter((_, i) => i !== index))}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Campaign Objective</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="campaign_objective_type">Objective type</Label>
                        <Select
                            value={data.campaign_objective.type}
                            onValueChange={(value) =>
                                setData('campaign_objective', {
                                    ...data.campaign_objective,
                                    type: value,
                                })
                            }
                        >
                            <SelectTrigger id="campaign_objective_type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {CAMPAIGN_OBJECTIVES.map((item) => (
                                    <SelectItem key={item.value} value={item.value}>
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {data.campaign_objective.type === 'custom' && (
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="campaign_objective_custom">Custom objective</Label>
                            <Textarea
                                id="campaign_objective_custom"
                                value={data.campaign_objective.custom}
                                onChange={(event) =>
                                    setData('campaign_objective', {
                                        ...data.campaign_objective,
                                        custom: event.target.value,
                                    })
                                }
                                rows={3}
                            />
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Client Content</CardTitle>
                    <CardDescription>Materials the client will provide for the campaign.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {CLIENT_CONTENT_OPTIONS.map((item) => {
                            const checked = data.client_content.items.includes(item.value);

                            return (
                                <label key={item.value} className="flex items-center gap-3 text-sm">
                                    <Checkbox
                                        checked={checked}
                                        onCheckedChange={(next) => {
                                            const items = next
                                                ? [...data.client_content.items, item.value]
                                                : data.client_content.items.filter((value) => value !== item.value);
                                            setData('client_content', {
                                                ...data.client_content,
                                                items,
                                            });
                                        }}
                                    />
                                    {item.label}
                                </label>
                            );
                        })}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="client_content_description">Additional notes</Label>
                        <Textarea
                            id="client_content_description"
                            value={data.client_content.description}
                            onChange={(event) =>
                                setData('client_content', {
                                    ...data.client_content,
                                    description: event.target.value,
                                })
                            }
                            rows={3}
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Lead Generation</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="lead_type">Lead type</Label>
                        <Input
                            id="lead_type"
                            value={data.lead_generation.lead_type}
                            onChange={(event) =>
                                setData('lead_generation', {
                                    ...data.lead_generation,
                                    lead_type: event.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="lead_cpl">Cost per lead (CPL)</Label>
                        <Input
                            id="lead_cpl"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.lead_generation.cpl}
                            onChange={(event) =>
                                setData('lead_generation', {
                                    ...data.lead_generation,
                                    cpl: event.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="lead_qualification">Qualification criteria</Label>
                        <Textarea
                            id="lead_qualification"
                            value={data.lead_generation.qualification}
                            onChange={(event) =>
                                setData('lead_generation', {
                                    ...data.lead_generation,
                                    qualification: event.target.value,
                                })
                            }
                            rows={3}
                        />
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="lead_notes">Notes</Label>
                        <Textarea
                            id="lead_notes"
                            value={data.lead_generation.notes}
                            onChange={(event) =>
                                setData('lead_generation', {
                                    ...data.lead_generation,
                                    notes: event.target.value,
                                })
                            }
                            rows={2}
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <div>
                        <CardTitle>Lead Pricing</CardTitle>
                        <CardDescription>Pricing table for different lead types.</CardDescription>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            setData('lead_pricing', [
                                ...data.lead_pricing,
                                { lead_type: '', cpl: '', currency: data.currency, description: '' },
                            ])
                        }
                    >
                        <Plus className="size-4" /> Add row
                    </Button>
                </CardHeader>
                <CardContent>
                    {data.lead_pricing.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No lead pricing rows.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Lead type</TableHead>
                                        <TableHead>CPL</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead className="w-12" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.lead_pricing.map((row, index) => (
                                        <TableRow key={index}>
                                            <TableCell>
                                                <Input
                                                    value={row.lead_type}
                                                    onChange={(event) => {
                                                        const next = [...data.lead_pricing];
                                                        next[index] = { ...row, lead_type: event.target.value };
                                                        setData('lead_pricing', next);
                                                    }}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={row.cpl}
                                                    onChange={(event) => {
                                                        const next = [...data.lead_pricing];
                                                        next[index] = { ...row, cpl: event.target.value };
                                                        setData('lead_pricing', next);
                                                    }}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    value={row.description}
                                                    onChange={(event) => {
                                                        const next = [...data.lead_pricing];
                                                        next[index] = { ...row, description: event.target.value };
                                                        setData('lead_pricing', next);
                                                    }}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => setData('lead_pricing', data.lead_pricing.filter((_, i) => i !== index))}
                                                >
                                                    <X className="size-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Lead Example Calculation</CardTitle>
                    <CardDescription>Example invoice calculation shown on the contract.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="lead_example_qty">Quantity</Label>
                        <Input
                            id="lead_example_qty"
                            type="number"
                            min="0"
                            value={data.lead_example.quantity}
                            onChange={(event) =>
                                setData('lead_example', {
                                    ...data.lead_example,
                                    quantity: event.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="lead_example_cpl">CPL</Label>
                        <Input
                            id="lead_example_cpl"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.lead_example.cpl}
                            onChange={(event) =>
                                setData('lead_example', {
                                    ...data.lead_example,
                                    cpl: event.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label>Estimated total</Label>
                        <div className="bg-muted flex h-10 items-center rounded-md border px-3 text-sm">
                            {exampleTotal ? `${data.lead_example.currency || data.currency} ${exampleTotal}` : '—'}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Payment Terms</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="invoice_payment_period">Invoice payment period</Label>
                        <Textarea
                            id="invoice_payment_period"
                            value={data.payment_terms.invoice_payment_period}
                            onChange={(event) =>
                                setData('payment_terms', {
                                    ...data.payment_terms,
                                    invoice_payment_period: event.target.value,
                                })
                            }
                            rows={2}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="advance_payment">Advance payment</Label>
                        <Textarea
                            id="advance_payment"
                            value={data.payment_terms.advance_payment}
                            onChange={(event) =>
                                setData('payment_terms', {
                                    ...data.payment_terms,
                                    advance_payment: event.target.value,
                                })
                            }
                            rows={2}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="non_payment_terms">Non-payment terms</Label>
                        <Textarea
                            id="non_payment_terms"
                            value={data.payment_terms.non_payment_terms}
                            onChange={(event) =>
                                setData('payment_terms', {
                                    ...data.payment_terms,
                                    non_payment_terms: event.target.value,
                                })
                            }
                            rows={2}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment_other">Other payment terms</Label>
                        <Textarea
                            id="payment_other"
                            value={data.payment_terms.other}
                            onChange={(event) =>
                                setData('payment_terms', {
                                    ...data.payment_terms,
                                    other: event.target.value,
                                })
                            }
                            rows={2}
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <div>
                        <CardTitle>Client Responsibilities</CardTitle>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setData('responsibilities', [...data.responsibilities, { text: '' }])}
                    >
                        <Plus className="size-4" /> Add row
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    {data.responsibilities.map((row, index) => (
                        <div key={index} className="flex items-end gap-2">
                            <div className="grid flex-1 gap-2">
                                <Label className="sr-only">Responsibility</Label>
                                <Input
                                    value={row.text}
                                    onChange={(event) => {
                                        const next = [...data.responsibilities];
                                        next[index] = { text: event.target.value };
                                        setData('responsibilities', next);
                                    }}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => setData('responsibilities', data.responsibilities.filter((_, i) => i !== index))}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Service Provider Signature</CardTitle>
                    <CardDescription>Pre-applied to the PDF. The client signs separately via the signing link.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="provider_signature">Digital signature</Label>
                        <Input
                            id="provider_signature"
                            value={data.provider_signature}
                            onChange={(event) => setData('provider_signature', event.target.value)}
                            placeholder="Ajay O"
                            className="font-serif text-lg italic"
                        />
                        <InputError message={errors.provider_signature} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="provider_signature_date">Signature date</Label>
                        <Input
                            id="provider_signature_date"
                            type="date"
                            value={data.provider_signature_date}
                            onChange={(event) => setData('provider_signature_date', event.target.value)}
                        />
                        <InputError message={errors.provider_signature_date} />
                    </div>
                    {data.provider_signature && (
                        <div className="sm:col-span-2 rounded-lg border bg-muted/20 p-4">
                            <p className="text-muted-foreground mb-2 text-xs uppercase tracking-wide">Preview</p>
                            <p className="font-serif text-3xl italic text-indigo-900">{data.provider_signature}</p>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Date:{' '}
                                {data.provider_signature_date
                                    ? new Date(data.provider_signature_date).toLocaleDateString(undefined, { dateStyle: 'long' })
                                    : '—'}
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Custom Terms</CardTitle>
                    <CardDescription>Additional terms and conditions appended to the agreement.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Textarea
                        value={data.custom_terms}
                        onChange={(event) => setData('custom_terms', event.target.value)}
                        rows={8}
                        placeholder="Enter any additional terms..."
                    />
                    <InputError message={errors.custom_terms} />
                </CardContent>
            </Card>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Save Draft
                </Button>
                <Button type="button" variant="secondary" disabled={processing} onClick={(event) => submit(event, true)}>
                    <Eye className="size-4" />
                    Preview
                </Button>
                <Button type="button" variant="outline" asChild>
                    <Link href={cancelUrl}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}

function normalizeFormValues(source: Record<string, unknown>): ContractFormValues {
    const contact = (block: unknown): ContactBlock => {
        const value = (block ?? {}) as Partial<ContactBlock>;

        return {
            name: value.name ?? '',
            authorized_person: value.authorized_person ?? '',
            phone: value.phone ?? '',
            email: value.email ?? '',
            website: value.website ?? '',
            address: value.address ?? '',
        };
    };

    const paymentTerms = (source.payment_terms ?? {}) as ContractFormValues['payment_terms'];

    return {
        title: String(source.title ?? ''),
        contract_number: String(source.contract_number ?? ''),
        contract_type: String(source.contract_type ?? ''),
        country: String(source.country ?? ''),
        currency: String(source.currency ?? ''),
        effective_date: String(source.effective_date ?? ''),
        start_date: String(source.start_date ?? ''),
        end_date: String(source.end_date ?? ''),
        tm_company_id: String(source.tm_company_id ?? ''),
        provider: contact(source.provider),
        client: contact(source.client),
        service_plan: {
            monthly_fee: String((source.service_plan as ContractFormValues['service_plan'])?.monthly_fee ?? ''),
            currency: String((source.service_plan as ContractFormValues['service_plan'])?.currency ?? ''),
            billing_frequency: String((source.service_plan as ContractFormValues['service_plan'])?.billing_frequency ?? 'monthly'),
        },
        deliverables: Array.isArray(source.deliverables)
            ? source.deliverables.map((row) => ({
                  quantity: String((row as ContractFormValues['deliverables'][number]).quantity ?? ''),
                  name: String((row as ContractFormValues['deliverables'][number]).name ?? ''),
                  description: String((row as ContractFormValues['deliverables'][number]).description ?? ''),
              }))
            : [],
        extra_work: Array.isArray(source.extra_work)
            ? source.extra_work.map((row) => ({
                  description: String((row as ContractFormValues['extra_work'][number]).description ?? ''),
                  fee: String((row as ContractFormValues['extra_work'][number]).fee ?? ''),
                  currency: String((row as ContractFormValues['extra_work'][number]).currency ?? ''),
                  affects_monthly_fee: Boolean((row as ContractFormValues['extra_work'][number]).affects_monthly_fee),
              }))
            : [],
        requirements: Array.isArray(source.requirements)
            ? source.requirements.map((row) => ({
                  label: String((row as ContractFormValues['requirements'][number]).label ?? ''),
                  value: String((row as ContractFormValues['requirements'][number]).value ?? ''),
              }))
            : [],
        responsibilities: Array.isArray(source.responsibilities)
            ? source.responsibilities.map((row) => ({
                  text: String((row as ContractFormValues['responsibilities'][number]).text ?? ''),
              }))
            : [],
        campaign_objective: {
            type: String((source.campaign_objective as ContractFormValues['campaign_objective'])?.type ?? 'lead_generation'),
            custom: String((source.campaign_objective as ContractFormValues['campaign_objective'])?.custom ?? ''),
        },
        client_content: {
            items: Array.isArray((source.client_content as ContractFormValues['client_content'])?.items)
                ? ((source.client_content as ContractFormValues['client_content']).items as string[])
                : [],
            description: String((source.client_content as ContractFormValues['client_content'])?.description ?? ''),
        },
        lead_generation: {
            lead_type: String((source.lead_generation as ContractFormValues['lead_generation'])?.lead_type ?? ''),
            cpl: String((source.lead_generation as ContractFormValues['lead_generation'])?.cpl ?? ''),
            currency: String((source.lead_generation as ContractFormValues['lead_generation'])?.currency ?? ''),
            qualification: String((source.lead_generation as ContractFormValues['lead_generation'])?.qualification ?? ''),
            notes: String((source.lead_generation as ContractFormValues['lead_generation'])?.notes ?? ''),
        },
        lead_pricing: Array.isArray(source.lead_pricing)
            ? source.lead_pricing.map((row) => ({
                  lead_type: String((row as ContractFormValues['lead_pricing'][number]).lead_type ?? ''),
                  cpl: String((row as ContractFormValues['lead_pricing'][number]).cpl ?? ''),
                  currency: String((row as ContractFormValues['lead_pricing'][number]).currency ?? ''),
                  description: String((row as ContractFormValues['lead_pricing'][number]).description ?? ''),
              }))
            : [],
        lead_example: {
            quantity: String((source.lead_example as ContractFormValues['lead_example'])?.quantity ?? ''),
            cpl: String((source.lead_example as ContractFormValues['lead_example'])?.cpl ?? ''),
            currency: String((source.lead_example as ContractFormValues['lead_example'])?.currency ?? ''),
        },
        payment_terms: {
            invoice_payment_period: String(paymentTerms.invoice_payment_period ?? ''),
            advance_payment: String(paymentTerms.advance_payment ?? ''),
            non_payment_terms: String(paymentTerms.non_payment_terms ?? ''),
            other: String(paymentTerms.other ?? ''),
        },
        custom_terms: String(source.custom_terms ?? ''),
        document_logo: String(source.document_logo ?? ''),
        provider_signature: String(source.provider_signature ?? 'Ajay O'),
        provider_signature_date: String(source.provider_signature_date ?? source.effective_date ?? ''),
    };
}

export { normalizeFormValues };
