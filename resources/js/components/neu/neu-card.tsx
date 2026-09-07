import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * The base Neumorphism surface: one background colour, depth carried
 * entirely by a paired light/dark shadow. See plan/03-design-system.md §1.
 */
export const neuCardVariants = cva('neu bg-neu-surface text-neu-ink', {
    variants: {
        variant: {
            raised: '',
            inset: '',
            flat: '',
        },
        depth: {
            sm: '',
            md: '',
            lg: '',
        },
        radius: {
            sm: 'rounded-[var(--radius-neu-sm)]',
            md: 'rounded-[var(--radius-neu-md)]',
            lg: 'rounded-[var(--radius-neu-lg)]',
            pill: 'rounded-[var(--radius-neu-pill)]',
        },
    },
    compoundVariants: [
        { variant: 'raised', depth: 'sm', class: 'shadow-neu-sm' },
        { variant: 'raised', depth: 'md', class: 'shadow-neu-md' },
        { variant: 'raised', depth: 'lg', class: 'shadow-neu-lg' },
        { variant: 'inset', depth: 'sm', class: 'shadow-neu-inset-sm' },
        { variant: 'inset', depth: ['md', 'lg'], class: 'shadow-neu-inset-md' },
    ],
    defaultVariants: {
        variant: 'raised',
        depth: 'md',
        radius: 'lg',
    },
});

export type NeuCardProps = React.HTMLAttributes<HTMLDivElement> &
    VariantProps<typeof neuCardVariants>;

export const NeuCard = React.forwardRef<HTMLDivElement, NeuCardProps>(
    ({ className, variant, depth, radius, ...props }, ref) => (
        <div
            ref={ref}
            className={cn(
                neuCardVariants({ variant, depth, radius }),
                className,
            )}
            {...props}
        />
    ),
);
NeuCard.displayName = 'NeuCard';
