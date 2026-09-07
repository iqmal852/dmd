import { Link } from '@inertiajs/react';
import * as React from 'react';
import { cn } from '@/lib/utils';

export type NeuBottomNavItem = {
    key: string;
    label: string;
    icon: React.ReactNode;
    href?: string;
    active?: boolean;
};

export type NeuBottomNavProps = {
    items: NeuBottomNavItem[];
};

/**
 * The persistent Overview / Coordinates / Files / Photos bar from
 * Picture1.png panel 2's phone mockups. Fixed at the bottom on every
 * breakpoint for now — a desktop-specific top tab strip (NeuTabs) is a
 * later refinement, not required by
 * plan/phases/phase-04-coordinates-specs.md M4.5. An item with no `href`
 * (its route doesn't exist yet) renders disabled rather than linking to a
 * 404 — same convention as NeuTile.
 */
export function NeuBottomNav({ items }: NeuBottomNavProps) {
    return (
        <nav
            className={cn(
                'fixed inset-x-0 bottom-0 z-10 flex justify-around',
                'bg-neu-surface shadow-neu-lg',
                'pb-[env(safe-area-inset-bottom)]',
            )}
            aria-label="Dossier sections"
        >
            {items.map((item) => {
                const disabled = !item.href;
                const className = cn(
                    'flex min-h-14 min-w-14 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium',
                    item.active
                        ? 'text-neu-primary-bright'
                        : 'text-neu-ink-muted',
                    disabled && 'pointer-events-none opacity-40',
                );

                if (disabled) {
                    return (
                        <span
                            key={item.key}
                            className={className}
                            aria-disabled="true"
                        >
                            {item.icon}
                            {item.label}
                        </span>
                    );
                }

                return (
                    <Link
                        key={item.key}
                        href={item.href as string}
                        className={className}
                        aria-current={item.active ? 'page' : undefined}
                    >
                        {item.icon}
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}
