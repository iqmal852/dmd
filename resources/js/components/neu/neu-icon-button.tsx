import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuIconButtonProps =
    React.ButtonHTMLAttributes<HTMLButtonElement> & {
        'aria-label': string;
    };

/**
 * Icon-only button, always >=44x44 (map controls, carousel arrows — see
 * plan/03-design-system.md §4). `aria-label` is required at the type level
 * since there is no visible text label to fall back on.
 */
export const NeuIconButton = React.forwardRef<
    HTMLButtonElement,
    NeuIconButtonProps
>(({ className, type = 'button', ...props }, ref) => (
    <button
        ref={ref}
        type={type}
        className={cn(
            'neu inline-flex h-11 w-11 items-center justify-center',
            'bg-neu-surface text-neu-ink rounded-[var(--radius-neu-md)]',
            'shadow-neu-sm transition-[box-shadow] duration-150 ease-out',
            'active:shadow-neu-inset-sm',
            'focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
            'focus-visible:ring-neu-primary-bright focus-visible:ring-offset-neu-surface',
            'disabled:pointer-events-none disabled:opacity-50',
            className,
        )}
        {...props}
    />
));
NeuIconButton.displayName = 'NeuIconButton';
