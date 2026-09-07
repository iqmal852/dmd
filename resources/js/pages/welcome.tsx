import { Head, Link, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    Cloud,
    Compass,
    Download,
    Eye,
    Gauge,
    Link as LinkIcon,
    QrCode,
    ShieldCheck,
    Unlock,
} from 'lucide-react';
import { NeuCard } from '@/components/neu/neu-card';
import { dashboard, login } from '@/routes';

const HOW_IT_WORKS = [
    { step: 1, label: 'SCAN', sub: 'QR Code', icon: QrCode },
    { step: 2, label: 'CONNECT', sub: 'Secure Link', icon: LinkIcon },
    { step: 3, label: 'ACCESS', sub: 'Digital Dossier', icon: Unlock },
    { step: 4, label: 'VIEW', sub: 'Data & Photos', icon: Eye },
    { step: 5, label: 'DOWNLOAD', sub: 'As-Built / Reports', icon: Download },
] as const;

const KEY_BENEFITS = [
    { label: 'Faster', sub: 'Field Verification', icon: Gauge },
    { label: 'Accurate', sub: '& Consistent', icon: CheckCircle2 },
    { label: 'Secure', sub: '& Read-Only', icon: ShieldCheck },
    { label: 'Always', sub: 'Accessible', icon: Cloud },
    { label: 'Supports', sub: 'HAIM Vision', icon: Compass },
] as const;

/**
 * What someone sees if they type the bare domain instead of scanning a
 * plate — Picture1.png panels 5 & 6. See
 * plan/phases/phase-03-dossier-shell.md M3.7.
 */
export default function Welcome() {
    const { auth, branding } = usePage().props;

    return (
        <div className="bg-neu-surface text-neu-ink min-h-screen">
            <Head title="Welcome" />

            <header className="bg-neu-primary text-neu-on-primary shadow-neu-sm flex items-center justify-between px-4 py-3">
                <span className="text-sm font-bold tracking-wide">
                    {branding.operator}{' '}
                    <span className="font-normal opacity-80">
                        | {branding.title}
                    </span>
                </span>
                <Link
                    href={auth.user ? dashboard() : login()}
                    className="text-sm font-medium underline-offset-4 hover:underline"
                >
                    {auth.user ? 'Dashboard' : 'Log in'}
                </Link>
            </header>

            <main className="mx-auto max-w-5xl space-y-12 p-6">
                <section className="space-y-2 text-center">
                    <p className="text-neu-primary-bright text-sm font-bold tracking-widest uppercase">
                        Smart Monument — Physical → Digital
                    </p>
                    <h1 className="text-3xl font-bold">
                        Scan. Connect. Access.
                    </h1>
                    <p className="text-neu-ink-muted mx-auto max-w-xl">
                        Every GCP monument carries a QR plate. Scan it to open
                        its complete digital dossier — coordinates, specs,
                        photos, and as-built drawings.
                    </p>
                </section>

                <section
                    aria-labelledby="how-it-works-heading"
                    className="space-y-4"
                >
                    <h2
                        id="how-it-works-heading"
                        className="text-center text-lg font-bold"
                    >
                        How It Works
                    </h2>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
                        {HOW_IT_WORKS.map(
                            ({ step, label, sub, icon: Icon }) => (
                                <NeuCard
                                    key={step}
                                    className="flex flex-col items-center gap-2 p-4 text-center"
                                >
                                    <span className="bg-neu-primary-bright text-neu-on-primary-bright flex size-8 items-center justify-center rounded-[var(--radius-neu-pill)] text-sm font-bold">
                                        {step}
                                    </span>
                                    <Icon
                                        className="text-neu-primary-bright size-6"
                                        aria-hidden="true"
                                    />
                                    <span className="text-sm font-bold">
                                        {label}
                                    </span>
                                    <span className="text-neu-ink-muted text-xs">
                                        {sub}
                                    </span>
                                </NeuCard>
                            ),
                        )}
                    </div>
                </section>

                <section
                    aria-labelledby="key-benefits-heading"
                    className="space-y-4"
                >
                    <h2
                        id="key-benefits-heading"
                        className="text-center text-lg font-bold"
                    >
                        Key Benefits
                    </h2>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
                        {KEY_BENEFITS.map(({ label, sub, icon: Icon }) => (
                            <NeuCard
                                key={label}
                                className="flex flex-col items-center gap-2 p-4 text-center"
                            >
                                <Icon
                                    className="text-neu-accent size-6"
                                    aria-hidden="true"
                                />
                                <span className="text-sm font-bold">
                                    {label}
                                </span>
                                <span className="text-neu-ink-muted text-xs">
                                    {sub}
                                </span>
                            </NeuCard>
                        ))}
                    </div>
                </section>

                <footer className="border-neu-surface-sunken text-neu-ink-muted flex flex-wrap items-center justify-center gap-x-6 gap-y-2 border-t pt-6 text-center text-xs">
                    <span>25 GCP LPT2 Coverage</span>
                    <span>WGS 84 / GDM 2000 / MyGEOID</span>
                    <span>≤10mm XY Accuracy, ≤15mm Z</span>
                    <span>As-Built Drawings</span>
                    <span>Site Photos &amp; 360° Context</span>
                    <span>Secure &amp; Password Protected</span>
                    <span className="text-neu-ink font-bold">
                        Powered by {branding.operator}
                    </span>
                </footer>
            </main>
        </div>
    );
}
