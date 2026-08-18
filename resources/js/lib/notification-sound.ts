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

const audioCache = new Map<string, HTMLAudioElement>();
let activePreviewAudio: HTMLAudioElement | null = null;

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

function getOrCreateAudio(url: string): HTMLAudioElement {
    const cached = audioCache.get(url);

    if (cached) {
        return cached;
    }

    const audio = new Audio(url);
    audio.preload = 'auto';
    audio.load();
    audioCache.set(url, audio);

    return audio;
}

export function preloadNotificationSound(url: string): void {
    if (typeof window === 'undefined' || url === '') {
        return;
    }

    getOrCreateAudio(url);
}

export function configureNotificationSound(config: NotificationSoundPlaybackConfig | null | undefined): void {
    if (!config) {
        playbackConfig = { enabled: false, url: null };

        return;
    }

    playbackConfig = config;

    if (config.url) {
        preloadNotificationSound(config.url);
    }
}

function playCachedAudio(audio: HTMLAudioElement, respectDebounce: boolean): void {
    audio.volume = 0.75;
    audio.currentTime = 0;

    void audio
        .play()
        .then(() => {
            if (respectDebounce) {
                lastPlayedAt = Date.now();
            }
        })
        .catch(() => {
            const retry = () => {
                void audio.play().catch(() => {
                    // Autoplay blocked or media unavailable.
                });
            };

            if (audio.readyState >= HTMLMediaElement.HAVE_ENOUGH_DATA) {
                retry();

                return;
            }

            audio.addEventListener('canplaythrough', retry, { once: true });
            audio.load();
        });
}

function playAudio(url: string, respectDebounce: boolean): void {
    if (typeof window === 'undefined' || url === '') {
        return;
    }

    const now = Date.now();

    if (respectDebounce && now - lastPlayedAt < DEBOUNCE_MS) {
        return;
    }

    playCachedAudio(getOrCreateAudio(url), respectDebounce);
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

    if (activePreviewAudio && activePreviewAudio !== audioCache.get(url)) {
        activePreviewAudio.pause();
        activePreviewAudio.currentTime = 0;
    }

    const audio = getOrCreateAudio(url);
    activePreviewAudio = audio;
    playCachedAudio(audio, false);
}
