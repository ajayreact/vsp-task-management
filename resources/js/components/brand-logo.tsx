import { cn } from '@/lib/utils';

export const BRAND_LOGO_SRC = '/images/branding/logo.png';
export const BRAND_FAVICON_SRC = '/favicon.png';

const variantClasses = {
    login: 'h-14 w-auto max-w-[220px] sm:max-w-[240px]',
    loginMobile: 'h-12 w-auto max-w-[190px]',
    sidebar: 'h-9 w-auto max-w-[132px]',
    sidebarIcon: 'size-8 object-contain',
    auth: 'h-10 w-auto max-w-[168px]',
    card: 'h-8 w-auto max-w-[148px]',
} as const;

type BrandLogoVariant = keyof typeof variantClasses;

interface BrandLogoProps {
    variant?: BrandLogoVariant;
    className?: string;
    useFavicon?: boolean;
}

export default function BrandLogo({ variant = 'auth', className, useFavicon = false }: BrandLogoProps) {
    return (
        <img
            src={useFavicon ? BRAND_FAVICON_SRC : BRAND_LOGO_SRC}
            alt="VSP CRM"
            className={cn('object-contain object-left', variantClasses[variant], className)}
            decoding="async"
        />
    );
}
