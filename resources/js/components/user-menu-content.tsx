import { Link, router } from '@inertiajs/react';
import { LogOut, Monitor, Moon, RotateCcw, Sun, UserCog } from 'lucide-react';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useAppearance } from '@/hooks/use-appearance';
import type { Appearance } from '@/hooks/use-appearance';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import demo from '@/routes/demo';
import type { User } from '@/types';

type Props = {
    user: User;
};

export function UserMenuContent({ user }: Props) {
    const cleanup = useMobileNavigation();
    const { appearance, updateAppearance } = useAppearance();
    const otherRole = user.role === 'admin' ? 'sales' : 'admin';

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showRole />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem
                    onSelect={() =>
                        router.post(
                            demo.switchRole.url(),
                            { role: otherRole },
                            { preserveScroll: true },
                        )
                    }
                >
                    <UserCog className="mr-2" />
                    Switch to {otherRole === 'admin' ? 'Admin' : 'Sales'}
                </DropdownMenuItem>
                <DropdownMenuItem
                    onSelect={() => router.post(demo.reset.url())}
                >
                    <RotateCcw className="mr-2" />
                    Reset demo data
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <div className="flex gap-1 p-1">
                {(
                    [
                        ['light', Sun],
                        ['dark', Moon],
                        ['system', Monitor],
                    ] as [Appearance, typeof Sun][]
                ).map(([mode, Icon]) => (
                    <button
                        key={mode}
                        type="button"
                        onClick={() => updateAppearance(mode)}
                        className={cn(
                            'flex flex-1 items-center justify-center gap-1 rounded-md py-1.5 text-xs capitalize transition-colors',
                            appearance === mode
                                ? 'bg-muted font-medium'
                                : 'text-muted-foreground hover:bg-muted/60',
                        )}
                    >
                        <Icon className="size-3.5" />
                        {mode}
                    </button>
                ))}
            </div>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
