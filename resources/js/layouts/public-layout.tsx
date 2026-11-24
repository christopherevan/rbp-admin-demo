import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { about, dashboard, home } from '@/routes';

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth, demo } = usePage().props;

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5" />
                        </span>
                        Kinetra Admin
                    </Link>
                    <nav className="flex items-center gap-4 text-sm">
                        <Link
                            href={about()}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            How it's built
                        </Link>
                        {demo.sourceUrl && (
                            <a
                                href={demo.sourceUrl}
                                className="text-muted-foreground hover:text-foreground"
                                target="_blank"
                                rel="noreferrer"
                            >
                                Source
                            </a>
                        )}
                        {auth.user && (
                            <Link
                                href={dashboard()}
                                className="font-medium text-primary"
                            >
                                Dashboard →
                            </Link>
                        )}
                    </nav>
                </div>
            </header>
            <main className="flex-1">{children}</main>
            <footer className="border-t">
                <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-muted-foreground sm:flex-row sm:justify-between">
                    <p>
                        Every company, person and figure in this demo is
                        fictional.
                    </p>
                    <p>
                        Built by Christopher Evan
                        {demo.portfolioUrl && (
                            <>
                                {' · '}
                                <a
                                    href={demo.portfolioUrl}
                                    className="underline-offset-4 hover:underline"
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    Portfolio
                                </a>
                            </>
                        )}
                    </p>
                </div>
            </footer>
        </div>
    );
}
