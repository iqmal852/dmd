import * as React from 'react';
import { cn } from '@/lib/utils';

export type MetaChipProps = {
    icon: React.ReactNode;
    label: string;
    value: string;
};

/**
 * One cell of the Highway / KM / Section / Direction row on the Overview
 * screen (Picture1.png panel 1 title block). The row itself scrolls
 * horizontally on mobile and becomes a 4-column grid at >=640px — see
 * plan/03-design-system.md §4.
 */
export function MetaChip({ icon, label, value }: MetaChipProps) {
    return (
        <div
            className={cn(
                'neu flex shrink-0 snap-start items-center gap-2 rounded-[var(--radius-neu-md)]',
                'bg-neu-surface shadow-neu-sm px-4 py-2',
            )}
        >
            <span className="text-neu-ink-muted" aria-hidden="true">
                {icon}
            </span>
            <span className="flex flex-col leading-tight">
                <span className="text-neu-ink-muted text-[0.65rem] font-semibold tracking-wide uppercase">
                    {label}
                </span>
                <span className="text-neu-ink text-sm font-bold whitespace-nowrap">
                    {value}
                </span>
            </span>
        </div>
    );
}
