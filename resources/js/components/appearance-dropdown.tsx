import { HTMLAttributes } from 'react';

/**
 * Theme switching was removed. VSP CRM is Light mode only.
 * Kept as a no-op component so any residual imports do not break.
 */
export default function AppearanceToggleDropdown({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={className} {...props} hidden aria-hidden="true" />;
}
