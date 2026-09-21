import { Head, usePage } from '@inertiajs/react';
import { Layers, Locate, Minus, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import type { Map as LeafletMap, Marker, TileLayer } from 'leaflet';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuIconButton } from '@/components/neu/neu-icon-button';
import { NeuStat } from '@/components/neu/neu-stat';
import { useClipboard } from '@/hooks/use-clipboard';
import DossierLayout from '@/layouts/dossier/dossier-layout';
import { buildDossierNavItems } from '@/lib/dossier-nav';
import type { StationMap } from '@/types/dossier';

type PageProps = {
    stationPublicId: string;
    stationCode: string;
    station: StationMap | null;
    map: {
        satelliteUrl: string;
        satelliteAttribution: string;
        streetUrl: string;
        streetAttribution: string;
        defaultZoom: number;
        maxZoom: number;
    };
};

const PIN_SVG = (color: string) => `
    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="48" viewBox="0 0 36 48">
        <path d="M18 0C8.06 0 0 8.06 0 18c0 13.5 18 30 18 30s18-16.5 18-30C36 8.06 27.94 0 18 0z" fill="${color}"/>
        <circle cx="18" cy="18" r="7" fill="white"/>
    </svg>
`;

/**
 * Picture1.png panel 2c — the Location Map. Leaflet is lazy-loaded here
 * only; the Overview screen's preview never pays for it. See
 * plan/phases/phase-05-location-map.md M5.1/M5.2.
 */
export default function DossierMap() {
    const { branding } = usePage().props;
    const { stationPublicId, stationCode, station, map } =
        usePage<PageProps>().props;
    const containerRef = useRef<HTMLDivElement>(null);
    const mapRef = useRef<LeafletMap | null>(null);
    const markerRef = useRef<Marker | null>(null);
    const satelliteLayerRef = useRef<TileLayer | null>(null);
    const streetLayerRef = useRef<TileLayer | null>(null);
    const [layer, setLayer] = useState<'satellite' | 'street'>('satellite');
    const [, copy] = useClipboard();

    async function copyCoordinates() {
        if (!station) {
            return;
        }

        const ok = await copy(`${station.latitude}, ${station.longitude}`);
        toast(ok ? 'Coordinates copied' : 'Copy not supported on this device');
    }

    useEffect(() => {
        if (!station || !containerRef.current) {
            return;
        }

        let cancelled = false;

        void (async () => {
            const L = (await import('leaflet')).default;
            await import('leaflet/dist/leaflet.css');

            if (cancelled || !containerRef.current) {
                return;
            }

            const leafletMap = L.map(containerRef.current, {
                center: [station.latitude, station.longitude],
                zoom: map.defaultZoom,
                scrollWheelZoom: false,
                zoomControl: false,
                attributionControl: true,
            });

            leafletMap.on('click', () => leafletMap.scrollWheelZoom.enable());

            // Two persistent layers, toggled via add/remove (not setUrl) so
            // Leaflet's attribution control correctly swaps text — Esri and
            // OSM both require their attribution to be shown, never removed.
            const satelliteLayer = L.tileLayer(map.satelliteUrl, {
                attribution: map.satelliteAttribution,
                maxZoom: map.maxZoom,
            }).addTo(leafletMap);

            const streetLayer = L.tileLayer(map.streetUrl, {
                attribution: map.streetAttribution,
                maxZoom: map.maxZoom,
            });

            const icon = L.divIcon({
                html: PIN_SVG('var(--neu-primary-bright, #1f6fd0)'),
                className: '',
                iconSize: [36, 48],
                iconAnchor: [18, 48],
            });

            const marker = L.marker([station.latitude, station.longitude], {
                icon,
            })
                .addTo(leafletMap)
                .bindTooltip(station.code, {
                    permanent: true,
                    direction: 'right',
                    offset: [8, -24],
                });

            mapRef.current = leafletMap;
            markerRef.current = marker;
            satelliteLayerRef.current = satelliteLayer;
            streetLayerRef.current = streetLayer;
        })();

        return () => {
            cancelled = true;
            mapRef.current?.remove();
            mapRef.current = null;
            satelliteLayerRef.current = null;
            streetLayerRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [station?.latitude, station?.longitude]);

    function switchLayer(next: 'satellite' | 'street') {
        if (
            !mapRef.current ||
            !satelliteLayerRef.current ||
            !streetLayerRef.current
        ) {
            return;
        }

        const [from, to] =
            next === 'satellite'
                ? [streetLayerRef.current, satelliteLayerRef.current]
                : [satelliteLayerRef.current, streetLayerRef.current];

        mapRef.current.removeLayer(from);
        mapRef.current.addLayer(to);
        setLayer(next);
    }

    function recentre() {
        if (mapRef.current && station) {
            mapRef.current.setView(
                [station.latitude, station.longitude],
                map.defaultZoom,
            );
        }
    }

    return (
        <DossierLayout
            operator={branding.operator}
            title={branding.title}
            navItems={buildDossierNavItems(stationPublicId, 'coordinates')}
        >
            <Head title={`Map — ${stationCode}`} />

            <div className="space-y-4">
                <h1 className="text-lg font-bold">{stationCode}</h1>

                {station ? (
                    <>
                        <div className="shadow-neu-md relative h-[60dvh] overflow-hidden rounded-[var(--radius-neu-lg)]">
                            <div ref={containerRef} className="h-full w-full" />

                            <div className="absolute top-3 right-3 z-[400]">
                                <NeuIconButton
                                    aria-label="Toggle map layer"
                                    onClick={() =>
                                        switchLayer(
                                            layer === 'satellite'
                                                ? 'street'
                                                : 'satellite',
                                        )
                                    }
                                >
                                    <Layers className="size-5" />
                                </NeuIconButton>
                            </div>

                            <div className="absolute right-3 bottom-3 z-[400] flex flex-col gap-2">
                                <NeuIconButton
                                    aria-label="Zoom in"
                                    onClick={() => mapRef.current?.zoomIn()}
                                >
                                    <Plus className="size-5" />
                                </NeuIconButton>
                                <NeuIconButton
                                    aria-label="Zoom out"
                                    onClick={() => mapRef.current?.zoomOut()}
                                >
                                    <Minus className="size-5" />
                                </NeuIconButton>
                                <NeuIconButton
                                    aria-label="Recentre map"
                                    onClick={recentre}
                                >
                                    <Locate className="size-5" />
                                </NeuIconButton>
                            </div>
                        </div>

                        <NeuCard className="grid grid-cols-2 gap-x-6 p-4">
                            <NeuStat label="Highway" value={station.highway} />
                            <NeuStat label="KM" value={station.km} />
                            <NeuStat
                                label="Direction"
                                value={station.direction}
                            />
                            <NeuStat
                                label="Section"
                                value={station.section ?? '—'}
                            />
                            <NeuStat
                                label="Monument Type"
                                value={station.monumentType}
                            />
                            <NeuStat
                                label="Installed"
                                value={station.installedAt ?? '—'}
                            />
                        </NeuCard>

                        <div className="space-y-2">
                            <NeuButton
                                variant="ghost"
                                className="w-full"
                                onClick={copyCoordinates}
                            >
                                Copy coordinates
                            </NeuButton>
                            <p className="text-neu-ink-muted text-center text-xs">
                                This monument may sit on a live highway
                                shoulder. Park safely and stay alert to traffic.
                            </p>
                        </div>
                    </>
                ) : (
                    <NeuCard className="text-neu-ink-muted p-8 text-center text-sm">
                        Coordinates not yet recorded for this station — the map
                        cannot be shown.
                    </NeuCard>
                )}
            </div>
        </DossierLayout>
    );
}
