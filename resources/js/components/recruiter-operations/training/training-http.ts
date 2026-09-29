/**
 * Small JSON helpers for the lesson player. Inertia visits would reload the
 * page, so audio and progress calls use fetch with the session cookie and
 * Laravel's XSRF-TOKEN cookie.
 */

function xsrfToken(): string | null {
    const match = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.slice('XSRF-TOKEN='.length)) : null;
}

export class TrainingHttpError extends Error {
    status: number;

    constructor(message: string, status: number) {
        super(message);
        this.status = status;
    }
}

async function errorFrom(response: Response): Promise<TrainingHttpError> {
    let message = 'Something went wrong. Please try again.';

    try {
        const body = (await response.json()) as { message?: string; errors?: Record<string, string[]> };
        const firstError = body.errors ? Object.values(body.errors)[0]?.[0] : undefined;
        message = firstError ?? body.message ?? message;
    } catch {
        // Not JSON; keep the generic message.
    }

    if (response.status === 403) {
        message = 'You do not have access to this lesson.';
    }

    return new TrainingHttpError(message, response.status);
}

export async function getJson<T>(url: string, signal?: AbortSignal): Promise<T> {
    const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal,
    });

    if (!response.ok) {
        throw await errorFrom(response);
    }

    return (await response.json()) as T;
}

export async function getBlob(url: string, signal?: AbortSignal): Promise<Blob> {
    const response = await fetch(url, {
        headers: { Accept: 'audio/*, application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal,
    });

    if (!response.ok) {
        throw await errorFrom(response);
    }

    return response.blob();
}

/**
 * keepalive lets the last progress report finish while the page unloads.
 */
export async function postJson<T>(url: string, body: Record<string, unknown>, keepalive = false): Promise<T | null> {
    const token = xsrfToken();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
        keepalive,
    });

    if (!response.ok) {
        throw await errorFrom(response);
    }

    return keepalive ? null : ((await response.json()) as T);
}
