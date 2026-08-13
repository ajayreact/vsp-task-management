import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export interface RoadmapPhase {
    phase: string;
    title: string;
    summary: string;
    done?: boolean;
}

/**
 * Temporary scaffolding for a module that has no screens yet. Replaced by the
 * real dashboard when the module is built.
 */
export function ModuleRoadmap({ heading, description, phases }: { heading: string; description: string; phases: RoadmapPhase[] }) {
    return (
        <div className="flex h-full flex-1 flex-col gap-6 p-4">
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight">{heading}</h1>
                <p className="text-muted-foreground max-w-2xl text-sm">{description}</p>
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {phases.map((phase) => (
                    <Card key={phase.phase} className={phase.done ? 'border-primary/40' : undefined}>
                        <CardHeader>
                            <CardDescription className="text-xs font-medium tracking-wide uppercase">
                                Phase {phase.phase}
                                {phase.done && ' · complete'}
                            </CardDescription>
                            <CardTitle className="text-base">{phase.title}</CardTitle>
                        </CardHeader>
                        <CardContent className="text-muted-foreground text-sm">{phase.summary}</CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
