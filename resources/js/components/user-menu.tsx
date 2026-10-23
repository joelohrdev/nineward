import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, SlidersHorizontal } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';

export function UserMenu() {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    if (!auth.user) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="flex size-10 items-center justify-center rounded-sm border border-board-rule bg-board-ink font-display text-lg text-board transition-colors hover:bg-board-ink/85"
                data-test="user-menu-button"
            >
                {getInitials(auth.user.name)}
                <span className="sr-only">Open account menu</span>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <DropdownMenuLabel className="font-normal">
                    <span className="block truncate font-semibold">
                        {auth.user.name}
                    </span>
                    <span className="block truncate text-xs text-ink-muted">
                        {auth.user.email}
                    </span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link
                        className="w-full cursor-pointer"
                        href={edit()}
                        prefetch
                    >
                        <SlidersHorizontal />
                        Settings
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link
                        className="w-full cursor-pointer"
                        href={logout()}
                        as="button"
                        onClick={() => router.flushAll()}
                        data-test="logout-button"
                    >
                        <LogOut />
                        Log out
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
