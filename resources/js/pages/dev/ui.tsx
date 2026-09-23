import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuGroup } from '@/components/neu/neu-group';
import { NeuIconButton } from '@/components/neu/neu-icon-button';
import { NeuPill, type NeuPillTone } from '@/components/neu/neu-pill';
import { NeuStat } from '@/components/neu/neu-stat';
import { useAppearance } from '@/hooks/use-appearance';

const PILL_TONES: NeuPillTone[] = [
    'primary',
    'primary-bright',
    'accent',
    'info',
    'violet',
    'warning',
    'danger',
    'ink-muted',
];

const WIDTHS = [
    { label: '390px (mobile)', className: 'max-w-[390px]' },
    { label: '768px (tablet)', className: 'max-w-3xl' },
    { label: '1280px (desktop)', className: 'max-w-5xl' },
] as const;

/**
 * Kitchen sink for every Neumorphism primitive. Local-only — see
 * routes/web.php and plan/phases/phase-02-design-system.md M2.6. This page
 * is the visual reference and the target for Phase 02's browser tests.
 */
export default function DevUi() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [width, setWidth] = useState<(typeof WIDTHS)[number]>(WIDTHS[2]);

    return (
        <>
            <Head title="Neumorphism kitchen sink" />

            <div className="bg-neu-surface text-neu-ink min-h-screen p-6">
                <div className={`mx-auto space-y-8 ${width.className}`}>
                    <header className="flex flex-wrap items-center justify-between gap-4">
                        <h1 className="text-2xl font-bold">
                            Neumorphism kitchen sink
                        </h1>

                        <div className="flex flex-wrap items-center gap-2">
                            {WIDTHS.map((w) => (
                                <NeuButton
                                    key={w.label}
                                    variant={
                                        w.label === width.label
                                            ? 'primary'
                                            : 'ghost'
                                    }
                                    onClick={() => setWidth(w)}
                                    className="px-3 text-xs"
                                >
                                    {w.label}
                                </NeuButton>
                            ))}
                            <NeuButton
                                variant="ghost"
                                onClick={() =>
                                    updateAppearance(
                                        resolvedAppearance === 'dark'
                                            ? 'light'
                                            : 'dark',
                                    )
                                }
                                className="px-3 text-xs"
                            >
                                {resolvedAppearance === 'dark'
                                    ? 'Light'
                                    : 'Dark'}{' '}
                                mode
                            </NeuButton>
                        </div>
                    </header>

                    <section
                        aria-labelledby="cards-heading"
                        className="space-y-3"
                    >
                        <h2
                            id="cards-heading"
                            className="text-sm font-bold uppercase"
                        >
                            NeuCard — variant x depth
                        </h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            {(['raised', 'inset', 'flat'] as const).map(
                                (variant) => (
                                    <div key={variant} className="space-y-3">
                                        {(['sm', 'md', 'lg'] as const).map(
                                            (depth) => (
                                                <NeuCard
                                                    key={depth}
                                                    variant={variant}
                                                    depth={depth}
                                                    className="p-4 text-sm"
                                                >
                                                    {variant} / {depth}
                                                </NeuCard>
                                            ),
                                        )}
                                    </div>
                                ),
                            )}
                        </div>
                    </section>

                    <section
                        aria-labelledby="buttons-heading"
                        className="space-y-3"
                    >
                        <h2
                            id="buttons-heading"
                            className="text-sm font-bold uppercase"
                        >
                            NeuButton / NeuIconButton
                        </h2>
                        <div className="flex flex-wrap items-center gap-3">
                            <NeuButton variant="primary">Primary</NeuButton>
                            <NeuButton variant="ghost">Ghost</NeuButton>
                            <NeuButton variant="danger">Danger</NeuButton>
                            <NeuButton variant="primary" disabled>
                                Disabled
                            </NeuButton>
                            <NeuIconButton aria-label="Zoom in">
                                +
                            </NeuIconButton>
                            <NeuIconButton aria-label="Zoom out">
                                −
                            </NeuIconButton>
                        </div>
                    </section>

                    <section
                        aria-labelledby="pills-heading"
                        className="space-y-3"
                    >
                        <h2
                            id="pills-heading"
                            className="text-sm font-bold uppercase"
                        >
                            NeuPill — every tone
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {PILL_TONES.map((tone) => (
                                <NeuPill key={tone} tone={tone}>
                                    {tone}
                                </NeuPill>
                            ))}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <NeuPill tone="accent">ACTIVE</NeuPill>
                            <NeuPill tone="accent">VERIFIED</NeuPill>
                        </div>
                    </section>

                    <section
                        aria-labelledby="stats-heading"
                        className="space-y-3"
                    >
                        <h2
                            id="stats-heading"
                            className="text-sm font-bold uppercase"
                        >
                            NeuGroup / NeuStat — LPT2-GCP-015 reference values
                        </h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <NeuGroup title="WGS 84">
                                <NeuStat
                                    label="Latitude"
                                    value="4.27412582"
                                    unit="°"
                                />
                                <NeuStat
                                    label="Longitude"
                                    value="103.43658211"
                                    unit="°"
                                />
                                <NeuStat
                                    label="Ellipsoidal Height (h)"
                                    value="128.346"
                                    unit="m"
                                />
                            </NeuGroup>
                            <NeuGroup title="GDM 2000">
                                <NeuStat
                                    label="Easting (E)"
                                    value="428,765.212"
                                    unit="m"
                                />
                                <NeuStat
                                    label="Northing (N)"
                                    value="472,318.678"
                                    unit="m"
                                />
                            </NeuGroup>
                            <NeuGroup title="MyGEOID Height (Orthometric)">
                                <NeuStat label="" value="112.436" unit="m" />
                            </NeuGroup>
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
