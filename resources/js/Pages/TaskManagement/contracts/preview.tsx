import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileText, Save } from 'lucide-react';

interface ContractPreview {
    id: number;
    title: string;
    contract_number: string;
    status_label: string;
    status_variant: 'default' | 'secondary' | 'outline' | 'destructive';
    client: { id: number; name: string };
    pdf_preview_url: string;
    pdf: { preview_url: string } | null;
}

interface Props {
    contract: ContractPreview;
    can: {
        update: boolean;
        generate_pdf: boolean;
    };
}

export default function ContractPreview({ contract, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tasks', href: '/tasks' },
        { title: 'Contracts', href: '/tasks/contracts' },
        { title: contract.title, href: `/tasks/contracts/${contract.id}` },
        { title: 'Preview', href: `/tasks/contracts/${contract.id}/preview` },
    ];

    const previewUrl = contract.pdf?.preview_url ?? contract.pdf_preview_url;

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title={`Preview · ${contract.title}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Contract preview"
                    description={`${contract.client.name} · ${contract.contract_number}`}
                    action={
                        <div className="flex flex-wrap gap-2">
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={`/tasks/contracts/${contract.id}/edit`}>
                                        <ArrowLeft className="size-4" />
                                        Back to Edit
                                    </Link>
                                </Button>
                            )}
                            {can.generate_pdf && (
                                <Button
                                    onClick={() =>
                                        router.post(`/tasks/contracts/${contract.id}/generate-pdf`, {}, { preserveScroll: true })
                                    }
                                >
                                    <FileText className="size-4" />
                                    Generate PDF
                                </Button>
                            )}
                            {can.update && (
                                <Button variant="secondary" asChild>
                                    <Link href={`/tasks/contracts/${contract.id}/edit`}>
                                        <Save className="size-4" />
                                        Save Draft
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <div>
                            <CardTitle>{contract.title}</CardTitle>
                            <p className="text-muted-foreground text-sm">{contract.contract_number}</p>
                        </div>
                        <Badge variant={contract.status_variant}>{contract.status_label}</Badge>
                    </CardHeader>
                    <CardContent>
                        <iframe
                            src={previewUrl}
                            title="Contract PDF preview"
                            className="h-[75vh] w-full rounded-lg border bg-white"
                        />
                    </CardContent>
                </Card>
            </div>
        </TaskLayout>
    );
}
