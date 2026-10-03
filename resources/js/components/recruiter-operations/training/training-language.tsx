import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Languages } from 'lucide-react';
import { useCallback, useState } from 'react';
import { type LessonLanguage } from './training-lesson-sections';

const STORAGE_KEY = 'recruiter-training.language';
export const DEFAULT_LANGUAGE = 'en';

function readStoredLanguage(): string | null {
    try {
        return window.localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

/**
 * The lesson language a recruiter picked, remembered on this device only.
 * English unless they chose otherwise; nothing changes for anyone else.
 */
export function useTrainingLanguage(): [string, (code: string) => void] {
    const [language, setLanguage] = useState<string>(() => readStoredLanguage() ?? DEFAULT_LANGUAGE);

    const choose = useCallback((code: string) => {
        setLanguage(code);

        try {
            window.localStorage.setItem(STORAGE_KEY, code);
        } catch {
            // Storage can be unavailable (private mode); the choice then lasts for this page only.
        }
    }, []);

    return [language, choose];
}

/**
 * What to show for the chosen language: its own sections when the lesson has
 * them, otherwise the English source, flagged so the page can say so.
 */
export function resolveLessonLanguage(languages: LessonLanguage[], chosen: string): { shown: LessonLanguage | null; fellBack: boolean } {
    const english = languages.find((language) => language.canonical) ?? null;
    const wanted = languages.find((language) => language.code === chosen);

    if (wanted && wanted.available) {
        return { shown: wanted, fellBack: false };
    }

    return { shown: english, fellBack: !!wanted && !wanted.canonical };
}

export function TrainingLanguageSelect({
    languages,
    value,
    onChange,
}: {
    languages: LessonLanguage[];
    value: string;
    onChange: (code: string) => void;
}) {
    return (
        <div className="flex items-center gap-2">
            <label htmlFor="training-language" className="text-muted-foreground flex items-center gap-1.5 text-xs font-medium">
                <Languages className="size-3.5" /> Language
            </label>
            <Select value={languages.some((language) => language.code === value) ? value : DEFAULT_LANGUAGE} onValueChange={onChange}>
                <SelectTrigger id="training-language" className="h-8 w-44" aria-label="Lesson language">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {languages.map((language) => (
                        <SelectItem key={language.code} value={language.code}>
                            {language.canonical ? language.label : `${language.native_label} (${language.label})`}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
