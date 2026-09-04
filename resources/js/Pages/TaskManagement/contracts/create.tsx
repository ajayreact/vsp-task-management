import { ContractForm, normalizeFormValues, type ContractFormOptions, type ContractFormValues, type CountryOption } from '@/components/contracts/contract-form';
import { PageHeader } from '@/components/admin/page-header';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props extends ContractFormOptions {
    defaults: Record<string, unknown>;
    suggestedNumber: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Contracts', href: '/tasks/contracts' },
    { title: 'New', href: '/tasks/contracts/create' },
];

export default function CreateContract({ clients, contractTypes, countries, defaults, suggestedNumber }: Props) {
    const initial = normalizeFormValues({
        ...defaults,
        contract_number: suggestedNumber,
    }) as ContractFormValues & { has_document_logo?: boolean; logo_url?: string | null };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="New contract" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New contract" description="Draft a service agreement for a client." />

                <ContractForm
                    options={{
                        clients,
                        contractTypes,
                        countries: countries as CountryOption[],
                    }}
                    initial={initial}
                    action="/tasks/contracts"
                    method="post"
                    cancelUrl="/tasks/contracts"
                />
            </div>
        </TaskLayout>
    );
}
