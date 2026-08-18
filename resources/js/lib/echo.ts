import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

type ReverbEcho = Echo<'reverb'>;

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo?: ReverbEcho;
    }
}

/**
 * Configure Laravel Echo for Reverb when Vite env is present.
 * Returns null when broadcasting is not configured (tests / log driver).
 */
export function getEcho(): ReverbEcho | null {
    const key = import.meta.env.VITE_REVERB_APP_KEY;

    if (!key) {
        return null;
    }

    if (window.Echo) {
        return window.Echo;
    }

    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    });

    return window.Echo;
}
