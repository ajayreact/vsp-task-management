import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, Home, RefreshCw } from 'lucide-react';

interface Props {
    status: number;
    title: string;
    message: string;
    hint: string;
    action: 'dashboard' | 'refresh' | 'login';
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Error', href: '#' }];

function ErrorActions({ action }: Pick<Props, 'action'>) {
    if (action === 'refresh') {
        return (
            <Button type="button" onClick={() => window.location.reload()}>
                <RefreshCw />
                Refresh page
            </Button>
        );
    }

    if (action === 'login') {
        return (
            <Button asChild>
                <Link href="/">
                    <Home />
                    Go to sign in
                </Link>
            </Button>
        );
    }

    return (
        <Button asChild>
            <Link href="/dashboard">
                <Home />
                Go to dashboard
            </Link>
        </Button>
    );
}

function ErrorCard({ status, title, message, hint, action }: Props) {
    return (
        <Card className="mx-auto w-full max-w-lg">
            <CardHeader className="text-center">
                <div className="bg-muted mx-auto mb-3 flex size-12 items-center justify-center rounded-full">
                    <AlertTriangle className="text-muted-foreground size-6" strokeWidth={1.75} />
                </div>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{message}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-center">
                <p className="text-muted-foreground text-sm">{hint}</p>
                <div className="flex justify-center">
                    <ErrorActions action={action} />
                </div>
                <p className="text-muted-foreground text-xs tabular-nums">Error {status}</p>
            </CardContent>
        </Card>
    );
}

export default function ErrorPage({ status, title, message, hint, action }: Props) {
    const { auth } = usePage<SharedData>().props;
    const isAuthenticated = auth.user !== null;

    if (isAuthenticated) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={title} />

                <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                    <PageHeader title={title} description={message} />
                    <ErrorCard status={status} title={title} message={message} hint={hint} action={action} />
                </div>
            </AppLayout>
        );
    }

    return (
        <>
            <Head title={title} />

            <div className="bg-muted/40 flex min-h-screen items-center justify-center px-4 py-10">
                <ErrorCard status={status} title={title} message={message} hint={hint} action={action} />
            </div>
        </>
    );
}
