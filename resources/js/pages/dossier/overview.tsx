import { Head, Link, usePage } from '@inertiajs/react';
import {
    Building2,
    Compass,
    Hash,
    MapPin,
    MapPinned,
    Signpost,
} from 'lucide-react';
import { MetaChip } from '@/components/neu/meta-chip';
import { map as mapRoute } from '@/routes/dossier';
import { NeuGroup } from '@/components/neu/neu-group';
import { NeuPill } from '@/components/neu/neu-pill';
import { NeuStat } from '@/components/neu/neu-stat';
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

    // Only show the Facility chip when it says something the Direction
    // chip doesn't already — the source data sets facility_type to a bare
    // "NB"/"SB"/"EB"/"WB" for plain directional points, which would just
    // repeat Direction verbatim.
    const facilityLabel =
        station.facilityType &&
        !['NB', 'SB', 'EB', 'WB'].includes(station.facilityType)
            ? station.facilityType
            : null;

    return (
        <DossierLayout
            operator={branding.operator}
            title={branding.title}
            navItems={buildDossierNavItems(station.publicId, 'overview')}
        >
            <Head title={`GCP Station ${station.code}`} />

            <div className="mx-auto max-w-2xl space-y-6">
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

                {(station.gcpReference ||
                    station.location ||
                    facilityLabel) && (
                    <div className="-mx-1 flex snap-x scrollbar-none gap-2 overflow-x-auto px-1 pb-1 sm:grid sm:grid-cols-3 sm:overflow-visible">
                        {station.gcpReference && (
                            <MetaChip
                                icon={<Hash className="size-4" />}
                                label="GCP Reference"
                                value={station.gcpReference}
                            />
                        )}
                        {station.location && (
                            <MetaChip
                                icon={<MapPinned className="size-4" />}
                                label="Location"
                                value={station.location}
                            />
                        )}
                        {facilityLabel && (
                            <MetaChip
                                icon={<Building2 className="size-4" />}
                                label="Facility"
                                value={facilityLabel}
                            />
                        )}
                    </div>
                )}

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
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <NeuGroup title="WGS 84">
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
                        <NeuGroup title="GDM 2000">
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
        </DossierLayout>
    );
}
