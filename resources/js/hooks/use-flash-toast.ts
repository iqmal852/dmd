import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

// Long enough to read the full message; the close button dismisses it sooner.
const FLASH_TOAST_DURATION_MS = 15_000;

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            toast[data.type](data.message, { duration: FLASH_TOAST_DURATION_MS });
        });
    }, []);
}
