import { type Appearance } from '@/hooks/use-appearance';
import { Sun } from 'lucide-react';

/**
 * Appearance is fixed to Light mode. Theme switching is disabled.
 */
export default function AppearanceTabs() {
    const value: Appearance = 'light';

    return (
        <div className="vsp-card mb-0 flex items-start gap-3 rounded-xl p-4">
            <span className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-400 text-white shadow-sm">
                <Sun className="size-4" strokeWidth={1.75} />
            </span>
            <div>
                <p className="text-sm font-semibold">Light</p>
                <p className="text-muted-foreground mt-0.5 text-xs leading-relaxed">
                    VSP CRM uses Light mode only. Dark and System themes are not available.
                </p>
                <p className="sr-only">{value}</p>
            </div>
        </div>
    );
}
