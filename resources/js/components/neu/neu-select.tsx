import { ChevronDown } from 'lucide-react';
import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuSelectProps = React.SelectHTMLAttributes<HTMLSelectElement> & {
    invalid?: boolean;
};

/**
 * A native `<select>` rather than a custom listbox — full keyboard/screen
 * reader behaviour for free, and there is no requirement in
 * plan/phases/phase-08-admin-qr.md for multi-select or rich option
 * rendering that would justify the extra weight of a custom component.
 */
export const NeuSelect = React.forwardRef<HTMLSelectElement, NeuSelectProps>(
    ({ className, invalid, children, ...props }, ref) => (
        <div className="relative">
            <select
                ref={ref}
                className={cn(
                    'neu bg-neu-surface text-neu-ink shadow-neu-inset-sm w-full appearance-none rounded-[var(--radius-neu-md)] px-4 py-2.5 pr-10 outline-none',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    'focus-visible:ring-neu-primary-bright focus-visible:ring-2',
                    invalid && 'ring-neu-danger ring-2',
                    className,
                )}
                {...props}
            >
                {children}
            </select>
            <ChevronDown
                className="text-neu-ink-muted pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2"
                aria-hidden="true"
            />
        </div>
    ),
);
NeuSelect.displayName = 'NeuSelect';
