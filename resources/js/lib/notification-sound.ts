/**
 * Configurable notification sound playback for realtime alerts.
 * Uses HTMLAudioElement with gesture unlock and debounce for burst events.
 */

export type NotificationSoundPlaybackConfig = {
    enabled: boolean;
    source?: 'system' | 'custom' | null;
    system_sound?: string | null;
    url?: string | null;
};

let playbackConfig: NotificationSoundPlaybackConfig = {
    enabled: true,
    source: 'system',
    system_sound: 'classic_chime',
    url: '/audio/notifications/classic-chime.wav',
};

let unlocked = false;
let lastPlayedAt = 0;
const DEBOUNCE_MS = 1500;

function ensureGestureUnlock(): void {
    if (unlocked || typeof window === 'undefined') {
        return;
    }

    const once = () => {
        unlocked = true;
        window.removeEventListener('pointerdown', once);
        window.removeEventListener('keydown', once);
    };

    window.addEventListener('pointerdown', once, { passive: true });
    window.addEventListener('keydown', once);
}

ensureGestureUnlock();

export function configureNotificationSound(config: NotificationSoundPlaybackConfig | null | undefined): void {
    if (!config) {
        playbackConfig = { enabled: false, url: null };

        return;
    }

    playbackConfig = config;
}

function playAudio(url: string, respectDebounce: boolean): void {
    if (typeof window === 'undefined' || url === '') {
        return;
    }

    const now = Date.now();

    if (respectDebounce && now - lastPlayedAt < DEBOUNCE_MS) {
        return;
    }

    try {
        const audio = new Audio(url);
        audio.volume = 0.75;

        void audio
            .play()
            .then(() => {
                if (respectDebounce) {
                    lastPlayedAt = Date.now();
                }
            })
            .catch(() => {
                // Autoplay blocked until the user interacts.
            });
    } catch {
        // Never throw from audio into the UI.
    }
}

/**
 * Play the configured company-wide notification sound once.
 */
export function playNotificationSound(): void {
    if (!playbackConfig.enabled || !playbackConfig.url) {
        return;
    }

    ensureGestureUnlock();
    playAudio(playbackConfig.url, true);
}

/**
 * Preview a sound from settings without affecting realtime debounce timing.
 */
export function previewNotificationSound(url: string): void {
    if (!url) {
        return;
    }

    ensureGestureUnlock();
    playAudio(url, false);
}
