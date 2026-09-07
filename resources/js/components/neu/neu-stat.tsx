import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuStatProps = React.HTMLAttributes<HTMLDivElement> & {
    label: string;
    value: React.ReactNode;
    unit?: string;
};

/**
 * Label / value / unit row — the workhorse of the Coordinates and Specs
 * screens (Picture1.png panel 2). Tabular numerals + nowrap so a column of
 * survey values reads as digits, not jittering proportional glyphs, and
 * never wraps mid-value. See plan/03-design-system.md §2.4.
 */
export const NeuStat = React.forwardRef<HTMLDivElement, NeuStatProps>(
    ({ label, value, unit, className, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                'flex items-baseline justify-between gap-4 py-2.5',
                'border-neu-surface-sunken border-b last:border-b-0',
                className,
            )}
            {...props}
        >
            <span className="text-neu-ink-muted text-sm">{label}</span>
            <span className="text-neu-ink text-right font-semibold whitespace-nowrap [font-variant-numeric:tabular-nums]">
                {value}
                {unit ? (
                    <span className="text-neu-ink-muted ml-1">{unit}</span>
                ) : null}
            </span>
        </div>
    ),
);
NeuStat.displayName = 'NeuStat';
