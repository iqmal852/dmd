import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Token names match App\Contracts\HasColor::color() exactly (see
 * app/Enums/StationStatus.php, app/Enums/QcStatus.php) so a status pill is
 * one line at the call site: <NeuPill tone={station.statusColor}>{station.status}</NeuPill>.
 * Each tone pairs with ONE fixed foreground chosen for >=4.5:1 contrast —
 * see the comment above --color-neu-on-* in resources/css/app.css for why
 * that foreground isn't uniformly white.
 */
const TONE_CLASSES = {
    primary: 'bg-neu-primary text-neu-on-primary',
    'primary-bright': 'bg-neu-primary-bright text-neu-on-primary-bright',
    accent: 'bg-neu-accent text-neu-on-accent',
    info: 'bg-neu-info text-neu-on-info',
    violet: 'bg-neu-violet text-neu-on-violet',
    warning: 'bg-neu-warning text-neu-on-warning',
    danger: 'bg-neu-danger text-neu-on-danger',
    'ink-muted': 'bg-neu-surface-sunken text-neu-ink',
} as const;

export type NeuPillTone = keyof typeof TONE_CLASSES;

export type NeuPillProps = React.HTMLAttributes<HTMLSpanElement> & {
    tone: NeuPillTone;
};

export const NeuPill = React.forwardRef<HTMLSpanElement, NeuPillProps>(
    ({ tone, className, ...props }, ref) => (
        <span
            ref={ref}
            className={cn(
                'inline-flex items-center rounded-[var(--radius-neu-pill)]',
                'px-3 py-1 text-xs font-bold tracking-wide uppercase',
                TONE_CLASSES[tone],
                className,
            )}
            {...props}
        />
    ),
);
NeuPill.displayName = 'NeuPill';
