import { Link } from '@inertiajs/react';
import * as React from 'react';
import { cn } from '@/lib/utils';

const TILE_COLOR_CLASSES = {
    blue: 'bg-neu-primary-bright text-neu-on-primary-bright',
    green: 'bg-neu-accent text-neu-on-accent',
    cyan: 'bg-neu-info text-neu-on-info',
    violet: 'bg-neu-violet text-neu-on-violet',
} as const;

export type NeuTileColor = keyof typeof TILE_COLOR_CLASSES;

export type NeuTileProps = {
    color: NeuTileColor;
    icon: React.ReactNode;
    label: string;
    href?: string;
    disabled?: boolean;
    prefetch?: boolean;
};

/**
 * A module tile from Picture1.png panel 1 — Coordinates (blue), As-Built
 * (green), Site Photos (cyan), 360° View (violet). These four colours are
 * the primary wayfinding cue and belong permanently to these four modules —
 * see plan/03-design-system.md §2.2.
 *
 * `href` is optional: a module whose route doesn't exist yet (Phases 04-07
 * add them one at a time) renders as a non-interactive, disabled-looking
 * tile rather than a link to a 404.
 */
export function NeuTile({
    color,
    icon,
    label,
    href,
    disabled,
    prefetch,
}: NeuTileProps) {
    const isDisabled = disabled || !href;

    const content = (
        <>
            <span className="text-2xl" aria-hidden="true">
                {icon}
            </span>
            <span className="text-sm font-bold">{label}</span>
        </>
    );

    const className = cn(
        'neu shadow-neu-md flex min-h-24 flex-col items-center justify-center gap-2 rounded-[var(--radius-neu-lg)] p-4 transition-[box-shadow] duration-150 ease-out',
        TILE_COLOR_CLASSES[color],
        isDisabled
            ? 'pointer-events-none opacity-50'
            : 'active:shadow-neu-inset-sm focus-visible:ring-neu-ink focus-visible:ring-offset-neu-surface focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
    );

    if (isDisabled) {
        return (
            <div className={className} aria-disabled="true">
                {content}
            </div>
        );
    }

    return (
        <Link href={href} prefetch={prefetch} className={className}>
            {content}
        </Link>
    );
}
