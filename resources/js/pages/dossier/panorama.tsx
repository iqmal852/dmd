import { Head, usePage } from '@inertiajs/react';
import { Compass } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { NeuButton } from '@/components/neu/neu-button';
import DossierLayout from '@/layouts/dossier/dossier-layout';
import { buildDossierNavItems } from '@/lib/dossier-nav';
import type { PannellumViewer } from '@/types/pannellum';

type PanoramaProp = {
    url: string;
    initialYaw: number;
    initialPitch: number;
    hfov: number;
};

type PageProps = {
    stationPublicId: string;
    stationCode: string;
    panorama: PanoramaProp;
};

function supportsDeviceOrientationPermission(): boolean {
    return (
        typeof DeviceOrientationEvent !== 'undefined' &&
        // iOS 13+ gates device orientation behind an explicit permission
        // prompt that must be triggered by a user gesture — see
        // plan/phases/phase-06-photos-360.md M6.5.
        typeof (
            DeviceOrientationEvent as unknown as {
                requestPermission?: () => Promise<'granted' | 'denied'>;
            }
        ).requestPermission === 'function'
    );
}

/**
 * The 360° panorama viewer — Picture1.png panel 4's "360° View" module.
 * Pannellum is lazy-loaded here only. See
 * plan/phases/phase-06-photos-360.md M6.5.
 */
export default function DossierPanorama() {
    const { branding } = usePage().props;
    const { stationPublicId, stationCode, panorama } =
        usePage<PageProps>().props;
    const containerRef = useRef<HTMLDivElement>(null);
    const viewerRef = useRef<PannellumViewer | null>(null);
    const [needsOrientationPrompt, setNeedsOrientationPrompt] = useState(false);

    useEffect(() => {
        if (!containerRef.current) {
            return;
        }

        let cancelled = false;

        void (async () => {
            await import('pannellum');
            await import('pannellum/build/pannellum.css');

            if (cancelled || !containerRef.current || !window.pannellum) {
                return;
            }

            const viewer = window.pannellum.viewer(containerRef.current, {
                type: 'equirectangular',
                panorama: panorama.url,
                autoLoad: true,
                yaw: panorama.initialYaw,
                pitch: panorama.initialPitch,
                hfov: panorama.hfov,
                compass: true,
                showZoomCtrl: true,
                showFullscreenCtrl: true,
            });

            viewerRef.current = viewer;
            setNeedsOrientationPrompt(supportsDeviceOrientationPermission());
        })();

        return () => {
            cancelled = true;
            viewerRef.current?.destroy();
            viewerRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [panorama.url]);

    async function enableOrientation() {
        const requestPermission = (
            DeviceOrientationEvent as unknown as {
                requestPermission: () => Promise<'granted' | 'denied'>;
            }
        ).requestPermission;

        const result = await requestPermission();

        if (result === 'granted') {
            viewerRef.current?.startOrientation();
        }

        setNeedsOrientationPrompt(false);
    }

    return (
        <DossierLayout
            operator={branding.operator}
            title={branding.title}
            navItems={buildDossierNavItems(stationPublicId, 'coordinates')}
        >
            <Head title={`360° View — ${stationCode}`} />

            <div className="space-y-4">
                <h1 className="text-lg font-bold">{stationCode} — 360° View</h1>

                {needsOrientationPrompt && (
                    <NeuButton
                        variant="ghost"
                        className="gap-2"
                        onClick={enableOrientation}
                    >
                        <Compass className="size-4" />
                        Enable motion controls
                    </NeuButton>
                )}

                {/*
                    Pannellum applies its own `.pnlm-container{height:100%}`
                    rule directly to the element passed to `viewer()`. That
                    rule shares this outer div's specificity and is injected
                    later (dynamic import), so it would win the cascade and
                    collapse a `h-[70dvh]` set on the same element down to
                    0 — the percentage has nothing definite to resolve
                    against. The explicit height lives on this wrapper
                    instead, and the ref'd child is left with no sizing
                    classes of its own for Pannellum's 100% to resolve
                    against. See plan/phases/phase-06-photos-360.md M6.5.
                */}
                <div className="bg-neu-surface-sunken shadow-neu-md h-[70dvh] w-full overflow-hidden rounded-[var(--radius-neu-lg)]">
                    <div ref={containerRef} className="size-full" />
                </div>
            </div>
        </DossierLayout>
    );
}
