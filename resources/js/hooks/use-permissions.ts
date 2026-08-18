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

    const canAny = (permissions?: string[]) => {
        if (!permissions || permissions.length === 0) {
            return true;
        }

        return permissions.some((permission) => auth.permissions.includes(permission));
    };

    return {
        can,
        canAny,
        hasRole: (role: string) => auth.roles.includes(role),
        permissions: auth.permissions,
        roles: auth.roles,
    };
}
