import { Link, usePage } from '@inertiajs/react';
import {
    Boxes,
    ExternalLink,
    FileText,
    Inbox,
    Info,
    LayoutGrid,
    Ruler,
    Sparkles,
    Tags,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { about, dashboard } from '@/routes';
import catalog from '@/routes/catalog';
import customers from '@/routes/customers';
import inquiries from '@/routes/inquiries';
import parts from '@/routes/parts';
import quotations from '@/routes/quotations';
import quoteRequest from '@/routes/quote-request';
import uoms from '@/routes/uoms';
import type { NavGroup } from '@/types';

export function AppSidebar() {
    const { badges } = usePage().props;

    const groups: NavGroup[] = [
        {
            label: 'Overview',
            items: [
                { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            ],
        },
        {
            label: 'Sales',
            items: [
                {
                    title: 'Quotations',
                    href: quotations.index(),
                    icon: FileText,
                },
                {
                    title: 'Inquiries',
                    href: inquiries.index(),
                    icon: Inbox,
                    badge: badges?.newInquiries,
                },
            ],
        },
        {
            label: 'Master data',
            items: [
                { title: 'Customers', href: customers.index(), icon: Users },
                { title: 'Parts & stock', href: parts.index(), icon: Boxes },
                { title: 'Units', href: uoms.index(), icon: Ruler },
            ],
        },
        {
            label: 'Catalog',
            items: [
                {
                    title: 'AI descriptions',
                    href: catalog.descriptions.index(),
                    icon: Sparkles,
                },
                {
                    title: 'Product types',
                    href: catalog.types.index(),
                    icon: Tags,
                },
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={groups} />
            </SidebarContent>

            <SidebarFooter>
                <SidebarGroup className="p-0">
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    asChild
                                    size="sm"
                                    tooltip={{ children: 'Public quote form' }}
                                >
                                    <a
                                        href={quoteRequest.create.url()}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <ExternalLink />
                                        <span>Public quote form</span>
                                    </a>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    asChild
                                    size="sm"
                                    tooltip={{ children: 'About this demo' }}
                                >
                                    <Link href={about()}>
                                        <Info />
                                        <span>About this demo</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
