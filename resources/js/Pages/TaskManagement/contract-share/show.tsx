import { SignatureCapture, type SignatureValue } from '@/components/contracts/signature-capture';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, Download, LoaderCircle } from 'lucide-react';

interface ContractSummary {
    title: string;
    contract_number: string;
    effective_date: string;
    client_name: string;
    status: string;
    status_label: string;
    signed: boolean;
}

interface Props {
    provider_name: string;
    contract: ContractSummary;
    snapshot: Record<string, unknown>;
    pdf_url: string;
    sign_url: string;
    can_sign: boolean;
}

type SharePageProps = Props & {
    flash?: { success?: string; error?: string };
};

export default function ContractShareShow({ provider_name, contract, pdf_url, sign_url, can_sign }: Props) {
    const { flash } = usePage<SharePageProps>().props;

    const form = useForm({
        signer_name: '',
        authorized_person: '',
        signature_type: '' as SignatureValue['signature_type'],
        signature_data: '',
        agreed: false as boolean,
    });

    const signatureValue: SignatureValue = {
        signature_type: form.data.signature_type,
        signature_data: form.data.signature_data,
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(sign_url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`${contract.title} · Sign`} />

            <div className="bg-muted/40 min-h-screen px-4 py-8">
                <div className="mx-auto w-full max-w-2xl space-y-6">
                    <div className="text-center">
                        <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">{provider_name}</p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{contract.title}</h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {contract.client_name} · {contract.contract_number}
                        </p>
                        <div className="mt-3 flex justify-center">
                            <Badge variant={contract.signed ? 'default' : 'secondary'}>{contract.status_label}</Badge>
                        </div>
                    </div>

                    {(flash?.success || flash?.error) && (
                        <div
                            className={
                                flash.error
                                    ? 'border-destructive/40 bg-destructive/5 rounded-lg border p-4 text-sm'
                                    : 'border-primary/40 bg-primary/5 rounded-lg border p-4 text-sm'
                            }
                        >
                            {flash.error ?? flash.success}
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Review contract</CardTitle>
                            <CardDescription>
                                Effective {new Date(contract.effective_date).toLocaleDateString(undefined, { dateStyle: 'long' })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <iframe src={pdf_url} title="Contract PDF" className="h-[50vh] w-full rounded-lg border bg-white sm:h-[60vh]" />

                            <Button variant="outline" className="w-full sm:w-auto" asChild>
                                <a href={pdf_url} target="_blank" rel="noreferrer">
                                    <Download className="size-4" />
                                    Open full PDF
                                </a>
                            </Button>
                        </CardContent>
                    </Card>

                    {can_sign && !contract.signed && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Sign agreement</CardTitle>
                                <CardDescription>
                                    Enter your details and sign below to accept this agreement electronically.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit} className="space-y-5">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="signer_name">Full name</Label>
                                            <Input
                                                id="signer_name"
                                                value={form.data.signer_name}
                                                onChange={(event) => form.setData('signer_name', event.target.value)}
                                                required
                                            />
                                            <InputError message={form.errors.signer_name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="authorized_person">Authorized person (optional)</Label>
                                            <Input
                                                id="authorized_person"
                                                value={form.data.authorized_person}
                                                onChange={(event) => form.setData('authorized_person', event.target.value)}
                                            />
                                            <InputError message={form.errors.authorized_person} />
                                        </div>
                                    </div>

                                    <SignatureCapture
                                        value={signatureValue}
                                        onChange={(value) => {
                                            form.setData('signature_type', value.signature_type);
                                            form.setData('signature_data', value.signature_data);
                                        }}
                                    />
                                    <InputError message={form.errors.signature_type ?? form.errors.signature_data} />

                                    <label className="flex items-start gap-3 text-sm">
                                        <Checkbox
                                            checked={form.data.agreed}
                                            onCheckedChange={(checked) => form.setData('agreed', checked === true)}
                                        />
                                        <span>
                                            I have read and agree to the terms of this agreement. I understand that my electronic
                                            signature is legally binding.
                                        </span>
                                    </label>
                                    <InputError message={form.errors.agreed} />

                                    <Button type="submit" className="w-full gap-2 sm:w-auto" disabled={form.processing}>
                                        {form.processing ? (
                                            <LoaderCircle className="size-4 animate-spin" />
                                        ) : (
                                            <Check className="size-4" />
                                        )}
                                        Sign contract
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {contract.signed && (
                        <Card>
                            <CardContent className="flex items-center gap-3 py-6 text-sm">
                                <Check className="text-primary size-5 shrink-0" />
                                <p>This contract has been signed. Thank you.</p>
                            </CardContent>
                        </Card>
                    )}

                    <p className="text-muted-foreground text-center text-xs">Shared securely with you</p>
                </div>
            </div>
        </>
    );
}
