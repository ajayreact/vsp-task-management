import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    permissions: string[];
    roles: string[];
    capabilities?: {
        logo_library?: boolean;
    };
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
    /** Hide the whole section unless the user holds at least one of these. */
    anyPermission?: string[];
}

export interface NavItem {
    title: string;
    /** Shown instead of title when the user lacks tasks.view_all. */
    contributorTitle?: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** Hidden unless the signed-in user holds this permission. */
    permission?: string;
    /** Hidden unless the signed-in user has this server-computed capability. */
    capability?: keyof NonNullable<Auth['capabilities']>;
    /** Hidden unless the signed-in user holds this role. Super Admin only items use this. */
    role?: string;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { success: string | null; error: string | null; share_url?: string | null };
    notifications: {
        unread_count: number;
        recent: AppNotification[];
    };
    notificationSound?: NotificationSoundPlaybackConfig | null;
    notificationPreferences?: NotificationPreferences | null;
    [key: string]: unknown;
}

export type NotificationSoundPlaybackConfig = {
    enabled: boolean;
    source?: 'system' | 'custom' | null;
    system_sound?: string | null;
    url?: string | null;
};

export type NotificationActor = {
    id: number;
    name: string;
    avatar: string | null;
};

export type NotificationPreferences = {
    browser_notifications: boolean;
    notification_sound: boolean;
    in_app_notifications: boolean;
};

export interface AppNotification {
    id: string;
    event: string | null;
    title: string;
    body: string;
    url: string | null;
    task_id: number | null;
    timesheet_id: number | null;
    actor?: NotificationActor | null;
    read_at: string | null;
    created_at: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    user_type: string;
    is_active: boolean;
    last_login_at: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

/** Shape of Laravel's length-aware paginator as it arrives over Inertia. */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface Option {
    value: string;
    label: string;
}
