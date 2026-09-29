/**
 * Autosave for quiz answers. An Inertia visit would reload the page, so the
 * save uses fetch with the session cookie and Laravel's XSRF-TOKEN cookie.
 */

function xsrfToken(): string | null {
    const match = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.slice('XSRF-TOKEN='.length)) : null;
}

export class AssessmentHttpError extends Error {
    status: number;

    constructor(message: string, status: number) {
        super(message);
        this.status = status;
    }
}

export async function putJson<T>(url: string, body: Record<string, unknown>): Promise<T> {
    const token = xsrfToken();
    const response = await fetch(url, {
        method: 'PUT',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        let message = 'Your answers could not be saved. Check your connection.';

        try {
            const data = (await response.json()) as { message?: string; errors?: Record<string, string[]> };
            message = (data.errors ? Object.values(data.errors)[0]?.[0] : undefined) ?? data.message ?? message;
        } catch {
            // Not JSON; keep the generic message.
        }

        throw new AssessmentHttpError(message, response.status);
    }

    return (await response.json()) as T;
}
