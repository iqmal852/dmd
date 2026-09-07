import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuToggleProps = {
    checked: boolean;
    onChange: (checked: boolean) => void;
    disabled?: boolean;
    'aria-label': string;
    className?: string;
};

/**
 * A physical-feeling on/off switch — the "published" toggle on the station
 * index (plan/phases/phase-08-admin-qr.md M8.2). The track is always
 * `shadow-neu-inset-sm` (recessed); the thumb is always `shadow-neu-sm`
 * (raised) — depth never depends on state, colour does.
 */
export function NeuToggle({
    checked,
    onChange,
    disabled,
    className,
    ...props
}: NeuToggleProps) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={cn(
                'neu shadow-neu-inset-sm relative inline-flex h-7 w-12 shrink-0 items-center rounded-[var(--radius-neu-pill)] transition-colors',
                checked ? 'bg-neu-accent' : 'bg-neu-surface-sunken',
                'disabled:pointer-events-none disabled:opacity-50',
                'focus-visible:ring-neu-primary-bright focus-visible:ring-offset-neu-surface focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                className,
            )}
            {...props}
        >
            <span
                className={cn(
                    'shadow-neu-sm bg-neu-surface inline-block size-5 transform rounded-full transition-transform',
                    checked ? 'translate-x-6' : 'translate-x-1',
                )}
            />
        </button>
    );
}
