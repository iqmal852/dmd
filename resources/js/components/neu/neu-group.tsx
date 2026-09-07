import * as React from 'react';
import { NeuCard } from '@/components/neu/neu-card';
import { cn } from '@/lib/utils';

export type NeuGroupProps = React.HTMLAttributes<HTMLDivElement> & {
    title: string;
    icon?: React.ReactNode;
};

/**
 * A titled group of NeuStats, e.g. "WGS 84 (GPS)", "ACCURACY (RMS)" — see
 * Picture1.png panel 2.
 */
export const NeuGroup = React.forwardRef<HTMLDivElement, NeuGroupProps>(
    ({ title, icon, className, children, ...props }, ref) => (
        <NeuCard ref={ref} className={cn('p-4', className)} {...props}>
            <h3 className="text-neu-ink-muted mb-2 flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase">
                {icon}
                {title}
            </h3>
            <div>{children}</div>
        </NeuCard>
    ),
);
NeuGroup.displayName = 'NeuGroup';
