import { ContractForm, normalizeFormValues, type ContractFormOptions, type ContractFormValues, type CountryOption } from '@/components/contracts/contract-form';
import { PageHeader } from '@/components/admin/page-header';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Props extends ContractFormOptions {
    contract: Record<string, unknown> & { id: number; title: string };
}

export default function EditContract({ contract, clients, contractTypes, countries }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Contracts', href: '/tasks/contracts' },
        { title: contract.title, href: `/tasks/contracts/${contract.id}` },
        { title: 'Edit', href: `/tasks/contracts/${contract.id}/edit` },
    ];

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit · ${contract.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Edit contract" description={contract.title} />

                <ContractForm
                    options={{
                        clients,
                        contractTypes,
                        countries: countries as CountryOption[],
                    }}
                    initial={normalizeFormValues(contract) as ContractFormValues & { has_document_logo?: boolean }}
                    action={`/tasks/contracts/${contract.id}`}
                    method="put"
                    contractId={contract.id}
                    cancelUrl={`/tasks/contracts/${contract.id}`}
                />
            </div>
        </TaskLayout>
    );
}
