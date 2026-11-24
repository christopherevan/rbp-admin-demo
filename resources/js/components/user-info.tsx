import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

export function UserInfo({
    user,
    showRole = false,
}: {
    user: User;
    showRole?: boolean;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarFallback className="rounded-lg bg-primary/10 text-primary">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {showRole && (
                    <span className="truncate text-xs text-muted-foreground capitalize">
                        {user.role} · @{user.username}
                    </span>
                )}
            </div>
        </>
    );
}
