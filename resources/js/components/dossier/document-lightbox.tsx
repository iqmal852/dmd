import * as Dialog from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { NeuIconButton } from '@/components/neu/neu-icon-button';

export type DocumentLightboxProps = {
    src: string;
    alt: string;
    onClose: () => void;
};

/**
 * Full-screen single-image viewer for a document preview — the same
 * pinch-zoom-via-native-browser approach as PhotoLightbox, minus the
 * next/previous controls a single drawing has no use for. See
 * plan/phases/phase-07-asbuilt-files.md M7.3.
 */
export function DocumentLightbox({ src, alt, onClose }: DocumentLightboxProps) {
    return (
        <Dialog.Root open onOpenChange={(open) => !open && onClose()}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-50 bg-black/90" />
                <Dialog.Content
                    className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 p-4 outline-none"
                    aria-describedby={undefined}
                >
                    <Dialog.Title className="sr-only">{alt}</Dialog.Title>

                    <Dialog.Close asChild>
                        <NeuIconButton
                            aria-label="Close"
                            className="absolute top-4 right-4"
                        >
                            <X className="size-5" />
                        </NeuIconButton>
                    </Dialog.Close>

                    <img
                        src={src}
                        alt={alt}
                        className="max-h-[90dvh] max-w-full touch-pinch-zoom object-contain"
                    />
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
