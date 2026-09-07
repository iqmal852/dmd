import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuTextareaProps =
    React.TextareaHTMLAttributes<HTMLTextAreaElement> & {
        invalid?: boolean;
    };

export const NeuTextarea = React.forwardRef<
    HTMLTextAreaElement,
    NeuTextareaProps
>(({ className, invalid, ...props }, ref) => (
    <textarea
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
));
NeuTextarea.displayName = 'NeuTextarea';
