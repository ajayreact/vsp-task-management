import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';

interface Props {
    brand: string;
    title: string;
    message: string;
    status: number;
}

export default function PublicDeliverableShareError({ brand, title, message, status }: Props) {
    return (
        <>
            <Head title={title} />

            <div className="bg-muted/40 flex min-h-screen items-center justify-center px-4 py-10">
                <div className="mx-auto w-full max-w-lg">
                    <div className="mb-6 text-center">
                        <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">{brand}</p>
                    </div>

                    <Card>
                        <CardHeader className="text-center">
                            <CardTitle>{title}</CardTitle>
                            <CardDescription>{message}</CardDescription>
                        </CardHeader>
                        <CardContent className="text-muted-foreground text-center text-sm">
                            {status === 404 && 'Please check the link you were given and try again.'}
                            {status === 403 && 'If you believe this is a mistake, contact the team that shared this deliverable with you.'}
                            {status === 410 && 'Ask the team for a fresh link if you still need to review this deliverable.'}
                            {status === 500 && 'If the problem continues, contact the team that shared this deliverable with you.'}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
