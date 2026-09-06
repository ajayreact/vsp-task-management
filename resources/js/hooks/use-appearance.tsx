import { useEffect, useState } from 'react';

export type Appearance = 'light';

const FORCE_LIGHT: Appearance = 'light';

const applyLightTheme = () => {
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

/**
 * Always force Light mode. Migrates any previously saved dark/system preference.
 */
export function initializeTheme() {
    localStorage.setItem('appearance', FORCE_LIGHT);
    applyLightTheme();
}

export function useAppearance() {
    const [appearance] = useState<Appearance>(FORCE_LIGHT);

    useEffect(() => {
        initializeTheme();
    }, []);

    const updateAppearance = (_mode?: string) => {
        initializeTheme();
    };

    return { appearance, updateAppearance };
}
