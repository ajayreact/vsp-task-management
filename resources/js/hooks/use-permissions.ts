import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * Mirrors the server's permission list so navigation and buttons can hide what
 * the user cannot reach. Every one of these is checked again by a policy on the
 * request, so this is presentation only.
 */
export function usePermissions() {
    const { auth } = usePage<SharedData>().props;

    const can = (permission?: string) => (permission ? auth.permissions.includes(permission) : true);

    return {
        can,
        hasRole: (role: string) => auth.roles.includes(role),
        permissions: auth.permissions,
    };
}
