import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Press state swaps raised -> inset shadow AND shifts font weight — depth is
 * never the only affordance (plan/03-design-system.md §1.1). A visible focus
 * ring is a real 2px outline, not a shadow change, so it survives
 * prefers-contrast:more where shadows are switched off entirely.
 */
export const neuButtonVariants = cva(
    [
        'neu inline-flex min-h-11 items-center justify-center gap-2',
        'rounded-[var(--radius-neu-md)] bg-neu-surface px-5 font-medium',
        'transition-[box-shadow,transform,font-weight] duration-150 ease-out',
        'shadow-neu-md active:shadow-neu-inset-sm active:font-semibold',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        'focus-visible:ring-neu-primary-bright focus-visible:ring-offset-neu-surface',
        'disabled:pointer-events-none disabled:opacity-50',
    ].join(' '),
    {
        variants: {
            variant: {
                primary: 'text-neu-on-primary-bright bg-neu-primary-bright',
                ghost: 'text-neu-ink',
                danger: 'text-neu-on-danger bg-neu-danger',
            },
        },
        defaultVariants: {
            variant: 'ghost',
        },
    },
);

export type NeuButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> &
    VariantProps<typeof neuButtonVariants>;

export const NeuButton = React.forwardRef<HTMLButtonElement, NeuButtonProps>(
    ({ className, variant, type = 'button', ...props }, ref) => (
        <button
            ref={ref}
            type={type}
            className={cn(neuButtonVariants({ variant }), className)}
            {...props}
        />
    ),
);
NeuButton.displayName = 'NeuButton';
