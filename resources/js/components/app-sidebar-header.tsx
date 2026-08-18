import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationBell } from '@/components/notifications/notification-bell';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth } = usePage<SharedData>().props;
    const initials = useInitials();

    return (
        <header className="bg-card/95 sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 px-4 shadow-elevated-xs backdrop-blur-sm md:px-6">
            <div className="flex min-w-0 flex-1 items-center gap-3">
                <SidebarTrigger className="text-muted-foreground hover:text-foreground" />
                <div className="min-w-0">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>

            <div className="flex items-center gap-2">
                <div className="relative hidden w-56 lg:block">
                    <Search
                        className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                        strokeWidth={1.75}
                    />
                    <Input
                        type="search"
                        readOnly
                        tabIndex={-1}
                        placeholder="Search…"
                        aria-label="Search"
                        className="bg-muted/60 h-9 cursor-default border-transparent pl-9 shadow-none focus-visible:ring-0"
                    />
                </div>

                <NotificationBell />

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-10 gap-2 px-1.5 sm:px-2">
                            <Avatar className="size-8">
                                <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">
                                    {initials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <span className="hidden max-w-[140px] truncate text-sm font-medium sm:inline">{auth.user.name}</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-56 rounded-xl" align="end">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
