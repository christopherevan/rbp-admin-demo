import type { Auth } from '@/types/auth';
import type { DemoProps } from '@/types/domain';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            demo: DemoProps;
            badges: { newInquiries: number } | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
