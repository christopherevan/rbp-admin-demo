import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { DemoBanner } from '@/components/demo/demo-banner';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="min-w-0 overflow-x-clip">
                <DemoBanner />
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
