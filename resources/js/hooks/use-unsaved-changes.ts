import { router } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Warn before leaving with unsaved edits, whether by an Inertia visit or by
 * closing the tab.
 */
export function useUnsavedChanges(
    dirty: boolean,
    message = 'You have unsaved changes. Leave anyway?',
): void {
    useEffect(() => {
        if (!dirty) {
            return;
        }

        const onBeforeUnload = (event: BeforeUnloadEvent) => {
            event.preventDefault();
        };

        const removeRouterListener = router.on('before', (event) => {
            const visit = (event as CustomEvent).detail?.visit;

            // Ignore background prefetch requests on hover
            if (visit?.prefetch) {
                return;
            }

            // Reloads and form posts from this page are fine.
            if (visit && visit.method !== 'get') {
                return;
            }

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });

        window.addEventListener('beforeunload', onBeforeUnload);

        return () => {
            removeRouterListener();
            window.removeEventListener('beforeunload', onBeforeUnload);
        };
    }, [dirty, message]);
}
