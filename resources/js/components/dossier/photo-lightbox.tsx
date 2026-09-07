import * as Dialog from '@radix-ui/react-dialog';
import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useEffect } from 'react';
import { NeuIconButton } from '@/components/neu/neu-icon-button';
import type { Photo } from '@/types/dossier';

export type PhotoLightboxProps = {
    photos: Photo[];
    index: number;
    onIndexChange: (index: number) => void;
    onClose: () => void;
};

/**
 * Full-screen photo viewer — Picture1.png panel 4's tap-to-enlarge
 * behaviour. Focus is trapped by Radix Dialog; Escape and the on-screen
 * close button both close it; Left/Right arrow keys swap photos. Pinch-
 * zoom relies on the browser's native pinch-to-zoom on the enlarged
 * image (the viewport meta tag does not disable scaling) rather than a
 * custom touch-gesture implementation — see
 * plan/phases/phase-06-photos-360.md M6.6.
 */
export function PhotoLightbox({
    photos,
    index,
    onIndexChange,
    onClose,
}: PhotoLightboxProps) {
    const photo = photos[index];

    useEffect(() => {
        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'ArrowLeft' && index > 0) {
                onIndexChange(index - 1);
            } else if (
                event.key === 'ArrowRight' &&
                index < photos.length - 1
            ) {
                onIndexChange(index + 1);
            }
        }

        window.addEventListener('keydown', handleKeyDown);

        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [index, photos.length, onIndexChange]);

    if (!photo) {
        return null;
    }

    return (
        <Dialog.Root open onOpenChange={(open) => !open && onClose()}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-50 bg-black/90" />
                <Dialog.Content
                    className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 p-4 outline-none"
                    aria-describedby={undefined}
                >
                    <Dialog.Title className="sr-only">
                        {photo.caption}
                    </Dialog.Title>

                    <Dialog.Close asChild>
                        <NeuIconButton
                            aria-label="Close"
                            className="absolute top-4 right-4"
                        >
                            <X className="size-5" />
                        </NeuIconButton>
                    </Dialog.Close>

                    {index > 0 && (
                        <NeuIconButton
                            aria-label="Previous photo"
                            className="absolute top-1/2 left-4 -translate-y-1/2"
                            onClick={() => onIndexChange(index - 1)}
                        >
                            <ChevronLeft className="size-5" />
                        </NeuIconButton>
                    )}

                    {index < photos.length - 1 && (
                        <NeuIconButton
                            aria-label="Next photo"
                            className="absolute top-1/2 right-4 -translate-y-1/2"
                            onClick={() => onIndexChange(index + 1)}
                        >
                            <ChevronRight className="size-5" />
                        </NeuIconButton>
                    )}

                    <img
                        src={photo.previewUrl}
                        alt={photo.caption}
                        className="max-h-[80dvh] max-w-full touch-pinch-zoom object-contain"
                    />

                    <p className="text-center text-sm font-medium text-white">
                        {photo.typeLabel}
                    </p>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
