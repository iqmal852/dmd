import { ShieldCheck } from 'lucide-react';
import * as React from 'react';

export type DossierLayoutProps = {
    operator: string;
    title: string;
    children: React.ReactNode;
};

/**
 * The public dossier's shell — sticky header carrying the brand lockup
 * (from config('dossier.branding'), never hard-coded) and the security
 * affordance shown in Picture1.png's header bar. Read-only: no forms, no
 * destructive actions, nothing implying editability. The bottom nav
 * (Overview/Coordinates/Files/Photos) is added in Phase 04 once there is
 * more than one screen to navigate between.
 */
export default function DossierLayout({
    operator,
    title,
    children,
}: DossierLayoutProps) {
    return (
        <div className="bg-neu-surface text-neu-ink min-h-screen">
            <header className="bg-neu-primary text-neu-on-primary shadow-neu-sm sticky top-0 z-10 flex items-center justify-between px-4 py-3">
                <span className="text-sm font-bold tracking-wide">
                    {operator}{' '}
                    <span className="font-normal opacity-80">| {title}</span>
                </span>
                <ShieldCheck className="size-5 opacity-90" aria-hidden="true" />
            </header>

            <main className="mx-auto max-w-5xl p-4">{children}</main>
        </div>
    );
}
