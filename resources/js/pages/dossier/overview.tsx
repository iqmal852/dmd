import { Head, Link, usePage } from '@inertiajs/react';
import {
    Camera,
    Compass,
    FileText,
    MapPin,
    RotateCw,
    Signpost,
} from 'lucide-react';
import { MetaChip } from '@/components/neu/meta-chip';
import { coordinates, files, map as mapRoute, photos } from '@/routes/dossier';
import { method360 } from '@/routes/dossier/photos';
import { NeuGroup } from '@/components/neu/neu-group';
import { NeuPill } from '@/components/neu/neu-pill';
import { NeuStat } from '@/components/neu/neu-stat';
import { NeuTile } from '@/components/neu/neu-tile';
import type { NeuPillTone } from '@/components/neu/neu-pill';
import DossierLayout from '@/layouts/dossier/dossier-layout';
import { buildDossierNavItems } from '@/lib/dossier-nav';
import type { StationSummary } from '@/types/dossier';

type PageProps = {
    station: StationSummary;
};

/**
 * The screen a scanned QR code opens — Picture1.png panel 1. See
 * plan/phases/phase-03-dossier-shell.md M3.6.
 */
export default function DossierOverview() {
    const { station, branding } = usePage<PageProps>().props;

    return (
        <DossierLayout
            operator={branding.operator}
            title={branding.title}
            navItems={buildDossierNavItems(station.publicId, 'overview')}
        >
            <Head title={`GCP Station ${station.code}`} />

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="space-y-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h1 className="text-xl font-bold">
                            GCP STATION: {station.code}
                        </h1>
                        <NeuPill tone={station.statusColor as NeuPillTone}>
                            {station.status}
                        </NeuPill>
                    </div>

                    <div className="-mx-1 flex snap-x scrollbar-none gap-2 overflow-x-auto px-1 pb-1 sm:grid sm:grid-cols-4 sm:overflow-visible">
                        <MetaChip
                            icon={<Signpost className="size-4" />}
                            label="Highway"
                            value={station.highway}
                        />
                        <MetaChip
                            icon={<MapPin className="size-4" />}
                            label="KM"
                            value={station.km}
                        />
                        <MetaChip
                            icon={<Compass className="size-4" />}
                            label="Section"
                            value={station.section ?? '—'}
                        />
                        <MetaChip
                            icon={<Compass className="size-4" />}
                            label="Direction"
                            value={station.direction}
                        />
                    </div>

                    {station.mapPreview && (
                        <Link
                            href={mapRoute({ station: station.publicId }).url}
                            className="neu shadow-neu-md block overflow-hidden rounded-[var(--radius-neu-lg)]"
                        >
                            <div className="bg-neu-surface-sunken relative h-40 w-full overflow-hidden">
                                <img
                                    src={station.mapPreview.tileUrl}
                                    alt=""
                                    className="h-full w-full object-cover"
                                    loading="lazy"
                                    width={256}
                                    height={256}
                                />
                                <span
                                    className="text-neu-primary-bright absolute -translate-x-1/2 -translate-y-full"
                                    style={{
                                        left: `${station.mapPreview.pinLeftPercent}%`,
                                        top: `${station.mapPreview.pinTopPercent}%`,
                                    }}
                                    aria-hidden="true"
                                >
                                    <MapPin className="size-7" />
                                </span>
                                <span className="bg-neu-surface/90 text-neu-ink-muted absolute top-2 left-2 rounded-[var(--radius-neu-pill)] px-2 py-0.5 text-[0.65rem] font-bold">
                                    {station.mapPreview.kmBefore}
                                </span>
                                <span className="bg-neu-surface/90 text-neu-ink-muted absolute top-2 right-2 rounded-[var(--radius-neu-pill)] px-2 py-0.5 text-[0.65rem] font-bold">
                                    {station.mapPreview.kmAfter}
                                </span>
                            </div>
                        </Link>
                    )}

                    {station.quickView ? (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-1">
                            <NeuGroup title="WGS 84 (GPS)">
                                <NeuStat
                                    label="Latitude"
                                    value={station.quickView.latitude}
                                />
                                <NeuStat
                                    label="Longitude"
                                    value={station.quickView.longitude}
                                />
                                <NeuStat
                                    label="Ellipsoidal Height (h)"
                                    value={station.quickView.ellipsoidalHeight}
                                />
                            </NeuGroup>
                            <NeuGroup title="GDM 2000 (TM)">
                                <NeuStat
                                    label="Easting (E)"
                                    value={station.quickView.easting}
                                />
                                <NeuStat
                                    label="Northing (N)"
                                    value={station.quickView.northing}
                                />
                            </NeuGroup>
                            <NeuGroup title="MyGEOID Height (Ortho)">
                                <NeuStat
                                    label=""
                                    value={station.quickView.orthometricHeight}
                                />
                            </NeuGroup>
                        </div>
                    ) : (
                        <p className="text-neu-ink-muted text-sm">
                            Coordinates not yet recorded for this station.
                        </p>
                    )}
                </div>

                <div className="space-y-3">
                    <div className="grid grid-cols-2 gap-4">
                        <NeuTile
                            color="blue"
                            icon={<MapPin />}
                            label="Coordinates"
                            href={
                                coordinates({ station: station.publicId }).url
                            }
                            prefetch
                            disabled={!station.modules.hasCoordinates}
                        />
                        <NeuTile
                            color="green"
                            icon={<FileText />}
                            label="As-Built"
                            href={files({ station: station.publicId }).url}
                            prefetch
                            disabled={station.modules.documentCount === 0}
                        />
                        <NeuTile
                            color="cyan"
                            icon={<Camera />}
                            label="Site Photos"
                            href={photos({ station: station.publicId }).url}
                            prefetch
                            disabled={station.modules.photoCount === 0}
                        />
                        <NeuTile
                            color="violet"
                            icon={<RotateCw />}
                            label="360° View"
                            href={method360({ station: station.publicId }).url}
                            disabled={!station.modules.hasPanorama}
                        />
                    </div>
                    <p className="text-neu-ink-muted text-center text-xs">
                        Tap any module for full details
                    </p>
                </div>
            </div>
        </DossierLayout>
    );
}
