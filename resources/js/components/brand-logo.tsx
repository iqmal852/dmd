import { cn } from '@/lib/utils';

type BrandLogoProps = {
    className?: string;
};

/**
 * The PLUS mark, on a white chip so it stays legible on the dark primary
 * header and in dark mode alike. Size it by height via `className`
 * (e.g. `h-8`); the width follows the logo's aspect ratio.
 */
export function BrandLogo({ className }: BrandLogoProps) {
    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-md bg-white p-1',
                className,
            )}
        >
            <img
                src="/plus-logo.svg.webp"
                alt="PLUS"
                className="h-full w-auto"
            />
        </span>
    );
}
