import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Toaster, toast } from 'sonner';

/**
 * Surfaces `session()->flash()` messages set by the controllers. Mounted once
 * per layout so every module gets the same feedback without repeating itself.
 */
export function FlashToaster() {
    const { flash } = usePage<SharedData>().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return <Toaster position="bottom-right" theme="light" richColors closeButton />;
}
