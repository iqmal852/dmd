import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuInputProps = React.InputHTMLAttributes<HTMLInputElement> & {
    invalid?: boolean;
};

/**
 * The shared Neumorphism input surface — an inset shadow rather than a
 * border, extracted from the pattern originally used one-off on the
 * unlock screen (plan/phases/phase-03-dossier-shell.md M3.3). See
 * plan/phases/phase-08-admin-qr.md, which is the first screen with enough
 * forms to justify the shared primitive Phase 02's M2.4 deferred.
 */
export const NeuInput = React.forwardRef<HTMLInputElement, NeuInputProps>(
    ({ className, invalid, ...props }, ref) => (
        <input
            ref={ref}
            className={cn(
                'neu bg-neu-surface text-neu-ink shadow-neu-inset-sm w-full rounded-[var(--radius-neu-md)] px-4 py-2.5 outline-none',
                'placeholder:text-neu-ink-subtle disabled:cursor-not-allowed disabled:opacity-50',
                'focus-visible:ring-neu-primary-bright focus-visible:ring-2',
                invalid && 'ring-neu-danger ring-2',
                className,
            )}
            {...props}
        />
    ),
);
NeuInput.displayName = 'NeuInput';
