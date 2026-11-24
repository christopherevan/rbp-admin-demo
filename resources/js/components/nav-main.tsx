import { Link, usePage } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavGroup } from '@/types';

export function NavMain({ groups }: { groups: NavGroup[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { auth } = usePage().props;

    return (
        <>
            {groups.map((group) => (
                <SidebarGroup key={group.label} className="px-2 py-0">
                    <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items
                            .filter(
                                (item) =>
                                    !item.adminOnly ||
                                    auth.user?.role === 'admin',
                            )
                            .map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isCurrentOrParentUrl(
                                            item.href,
                                        )}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                    {item.badge ? (
                                        <SidebarMenuBadge className="bg-red-500 text-white peer-hover/menu-button:text-white">
                                            {item.badge}
                                        </SidebarMenuBadge>
                                    ) : null}
                                </SidebarMenuItem>
                            ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
