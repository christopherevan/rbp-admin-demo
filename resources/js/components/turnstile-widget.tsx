import { useEffect, useRef } from 'react';

type TurnstileApi = {
    render: (
        container: HTMLElement,
        options: {
            sitekey: string;
            theme?: 'light' | 'dark' | 'auto';
            callback?: (token: string) => void;
            'expired-callback'?: () => void;
            'error-callback'?: () => void;
        },
    ) => string;
    reset: (widgetId: string) => void;
    remove: (widgetId: string) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

const SCRIPT_SRC =
    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let scriptPromise: Promise<TurnstileApi> | null = null;

function loadTurnstile(): Promise<TurnstileApi> {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (scriptPromise) {
        return scriptPromise;
    }

    scriptPromise = new Promise<TurnstileApi>((resolve, reject) => {
        const existing = document.querySelector<HTMLScriptElement>(
            `script[src="${SCRIPT_SRC}"]`,
        );

        const onLoad = () => {
            if (window.turnstile) {
                resolve(window.turnstile);
            } else {
                scriptPromise = null;
                reject(new Error('Turnstile failed to initialize.'));
            }
        };

        const onError = () => {
            scriptPromise = null;
            reject(new Error('Failed to load Turnstile script.'));
        };

        if (existing) {
            existing.addEventListener('load', onLoad, { once: true });
            existing.addEventListener('error', onError, { once: true });

            return;
        }

        const script = document.createElement('script');
        script.src = SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.addEventListener('load', onLoad, { once: true });
        script.addEventListener('error', onError, { once: true });
        document.head.appendChild(script);
    });

    return scriptPromise;
}

export function TurnstileWidget({
    siteKey,
    resetKey = 0,
    onVerify,
    onExpire,
}: {
    siteKey: string;
    resetKey?: number;
    onVerify: (token: string) => void;
    onExpire: () => void;
}) {
    const containerRef = useRef<HTMLDivElement>(null);
    const widgetIdRef = useRef<string | null>(null);
    const onVerifyRef = useRef(onVerify);
    const onExpireRef = useRef(onExpire);

    useEffect(() => {
        onVerifyRef.current = onVerify;
        onExpireRef.current = onExpire;
    });

    useEffect(() => {
        let cancelled = false;

        void loadTurnstile()
            .then((api) => {
                if (cancelled || !containerRef.current) {
                    return;
                }

                widgetIdRef.current = api.render(containerRef.current, {
                    sitekey: siteKey,
                    theme: 'auto',
                    callback: (token) => onVerifyRef.current(token),
                    'expired-callback': () => onExpireRef.current(),
                    'error-callback': () => onExpireRef.current(),
                });
            })
            .catch(() => {
                onExpireRef.current();
            });

        return () => {
            cancelled = true;

            if (widgetIdRef.current && window.turnstile) {
                window.turnstile.remove(widgetIdRef.current);
                widgetIdRef.current = null;
            }
        };
    }, [siteKey]);

    useEffect(() => {
        if (resetKey > 0 && widgetIdRef.current && window.turnstile) {
            window.turnstile.reset(widgetIdRef.current);
        }
    }, [resetKey]);

    return <div ref={containerRef} className="min-h-[65px]" />;
}
