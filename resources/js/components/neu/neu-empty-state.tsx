import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuEmptyStateProps = {
    icon?: React.ReactNode;
    message: string;
    action?: React.ReactNode;
    className?: string;
};

/**
 * Shown wherever a station is missing a record it hasn't been surveyed for
 * yet — e.g. no coordinate set, no specification. See
 * plan/03-design-system.md §3 and plan/phases/phase-04-coordinates-specs.md
 * M4.4.
 */
export function NeuEmptyState({
    icon,
    message,
    action,
    className,
}: NeuEmptyStateProps) {
    return (
        <div
            className={cn(
                'neu flex flex-col items-center gap-3 rounded-[var(--radius-neu-lg)]',
                'bg-neu-surface shadow-neu-inset-sm p-8 text-center',
                className,
            )}
        >
            {icon && (
                <span className="text-neu-ink-subtle" aria-hidden="true">
                    {icon}
                </span>
            )}
            <p className="text-neu-ink-muted text-sm">{message}</p>
            {action}
        </div>
    );
}
