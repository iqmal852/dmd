import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuFormFieldProps = {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    className?: string;
    children: React.ReactNode;
};

/**
 * Label + control + error/hint, shared by every admin form field. See
 * plan/phases/phase-08-admin-qr.md M8.3/M8.6.
 */
export function NeuFormField({
    label,
    htmlFor,
    error,
    hint,
    required,
    className,
    children,
}: NeuFormFieldProps) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <label
                htmlFor={htmlFor}
                className="text-neu-ink-muted flex items-center gap-1 text-sm font-medium"
            >
                {label}
                {required && (
                    <span className="text-neu-danger" aria-hidden="true">
                        *
                    </span>
                )}
            </label>
            {children}
            {error ? (
                <p className="text-neu-danger text-sm font-medium">{error}</p>
            ) : (
                hint && <p className="text-neu-ink-subtle text-xs">{hint}</p>
            )}
        </div>
    );
}
