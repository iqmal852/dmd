import { Link, usePage } from '@inertiajs/react';
import { LogOut, QrCode, Rows3 } from 'lucide-react';
import * as React from 'react';
import { logout } from '@/routes';
import { index } from '@/routes/admin/stations';
import { sheet } from '@/routes/admin/qr';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

export type AdminSection = 'stations' | 'qr-sheet';

export type AdminLayoutProps = {
    active?: AdminSection;
    children: React.ReactNode;
};

/**
 * The admin console's shell — same Neumorphism system as the public
 * dossier, but desktop-first: a horizontal tab strip rather than a bottom
 * nav, since managing 25 stations is realistically desk work even though
 * the console must still be usable on a phone. See
 * plan/phases/phase-08-admin-qr.md.
 */
export default function AdminLayout({ active, children }: AdminLayoutProps) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <div className="bg-neu-surface text-neu-ink min-h-screen">
            <header className="bg-neu-primary text-neu-on-primary shadow-neu-sm sticky top-0 z-10 flex items-center justify-between px-4 py-3">
                <span className="text-sm font-bold tracking-wide">
                    MySpatial{' '}
                    <span className="font-normal opacity-80">| Admin</span>
                </span>

                <div className="flex items-center gap-3 text-sm">
                    <span className="hidden opacity-90 sm:inline">
                        {auth.user.name}
                    </span>
                    <Link
                        href={logout()}
                        as="button"
                        className="inline-flex items-center gap-1.5 opacity-90 hover:opacity-100"
                    >
                        <LogOut className="size-4" aria-hidden="true" />
                        Log out
                    </Link>
                </div>
            </header>

            <nav className="bg-neu-surface -mx-1 flex snap-x gap-2 overflow-x-auto px-1 py-3 sm:mx-0 sm:px-4">
                <Link
                    href={index().url}
                    className={cn(
                        'neu shadow-neu-sm inline-flex shrink-0 items-center gap-2 rounded-[var(--radius-neu-pill)] px-4 py-2 text-sm font-bold',
                        active === 'stations'
                            ? 'bg-neu-primary-bright text-neu-on-primary-bright'
                            : 'text-neu-ink-muted',
                    )}
                >
                    <Rows3 className="size-4" aria-hidden="true" />
                    Stations
                </Link>
                <Link
                    href={sheet().url}
                    className={cn(
                        'neu shadow-neu-sm inline-flex shrink-0 items-center gap-2 rounded-[var(--radius-neu-pill)] px-4 py-2 text-sm font-bold',
                        active === 'qr-sheet'
                            ? 'bg-neu-primary-bright text-neu-on-primary-bright'
                            : 'text-neu-ink-muted',
                    )}
                >
                    <QrCode className="size-4" aria-hidden="true" />
                    QR Sheet
                </Link>
            </nav>

            <main className="mx-auto max-w-6xl p-4">{children}</main>
        </div>
    );
}
