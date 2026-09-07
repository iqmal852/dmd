import { ShieldCheck } from 'lucide-react';
import * as React from 'react';
import {
    NeuBottomNav,
    type NeuBottomNavItem,
} from '@/components/neu/neu-bottom-nav';

export type DossierLayoutProps = {
    operator: string;
    title: string;
    navItems?: NeuBottomNavItem[];
    children: React.ReactNode;
};

/**
 * The public dossier's shell — sticky header carrying the brand lockup
 * (from config('dossier.branding'), never hard-coded) and the security
 * affordance shown in Picture1.png's header bar, plus the persistent
 * Overview/Coordinates/Files/Photos bar once there's more than one screen
 * to navigate between. Read-only: no forms, no destructive actions,
 * nothing implying editability.
 */
export default function DossierLayout({
    operator,
    title,
    navItems,
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

            <main
                className={`mx-auto max-w-5xl p-4 ${navItems ? 'pb-20' : ''}`}
            >
                {children}
            </main>

            {navItems && <NeuBottomNav items={navItems} />}
        </div>
    );
}
