import { Head, usePage } from '@inertiajs/react';
import { Compass, Gauge, Mountain, Satellite } from 'lucide-react';
import { toast } from 'sonner';
import { NeuEmptyState } from '@/components/neu/neu-empty-state';
import { NeuGroup } from '@/components/neu/neu-group';
import { NeuPill } from '@/components/neu/neu-pill';
import { NeuStat } from '@/components/neu/neu-stat';
import type { NeuPillTone } from '@/components/neu/neu-pill';
import { useClipboard } from '@/hooks/use-clipboard';
import DossierLayout from '@/layouts/dossier/dossier-layout';
import { buildDossierNavItems } from '@/lib/dossier-nav';
import type { CoordinateSet, Specification } from '@/types/dossier';

type PageProps = {
    stationPublicId: string;
    stationCode: string;
    coordinateSet: CoordinateSet | null;
    specification: Specification | null;
};

// Every specification field is shown even when the survey record hasn't
// been filled in yet (or doesn't exist at all) — explicit user request:
// missing values read as "N/A" rather than the field disappearing.
const NA = 'N/A';

/**
 * Picture1.png panels 2a (Coordinates) and 2b (Specs & QC), one scrolling
 * page — see plan/phases/phase-04-coordinates-specs.md M4.2/M4.3.
 */
export default function DossierCoordinates() {
    const { branding } = usePage().props;
    const { stationPublicId, stationCode, coordinateSet, specification } =
        usePage<PageProps>().props;
    const [, copy] = useClipboard();

    async function copyRaw(label: string, value: string) {
        const ok = await copy(value);
        toast(ok ? `${label} copied` : 'Copy not supported on this device');
    }

    return (
        <DossierLayout
            operator={branding.operator}
            title={branding.title}
            navItems={buildDossierNavItems(stationPublicId, 'coordinates')}
        >
            <Head title={`Coordinates — ${stationCode}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-bold">{stationCode}</h1>
                </div>

                {coordinateSet ? (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <NeuGroup
                            title="WGS 84"
                            icon={<Satellite className="size-3.5" />}
                        >
                            <button
                                type="button"
                                className="w-full text-left"
                                onClick={() =>
                                    copyRaw(
                                        'Latitude',
                                        coordinateSet.latitudeRaw,
                                    )
                                }
                            >
                                <NeuStat
                                    label="Latitude"
                                    value={coordinateSet.latitude}
                                />
                            </button>
                            <button
                                type="button"
                                className="w-full text-left"
                                onClick={() =>
                                    copyRaw(
                                        'Longitude',
                                        coordinateSet.longitudeRaw,
                                    )
                                }
                            >
                                <NeuStat
                                    label="Longitude"
                                    value={coordinateSet.longitude}
                                />
                            </button>
                            <NeuStat
                                label="Ellipsoidal Height (h)"
                                value={coordinateSet.ellipsoidalHeight}
                            />
                        </NeuGroup>

                        <NeuGroup
                            title="GDM 2000"
                            icon={<Compass className="size-3.5" />}
                        >
                            <NeuStat
                                label="Easting (E)"
                                value={coordinateSet.easting}
                            />
                            <NeuStat
                                label="Northing (N)"
                                value={coordinateSet.northing}
                            />
                        </NeuGroup>

                        <NeuGroup
                            title="MyGEOID Height (Orthometric)"
                            icon={<Mountain className="size-3.5" />}
                        >
                            <NeuStat
                                label=""
                                value={coordinateSet.orthometricHeight}
                            />
                        </NeuGroup>
                    </div>
                ) : (
                    <NeuEmptyState message="Coordinates not yet recorded for this station." />
                )}

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <NeuGroup
                        title="GNSS Observation"
                        icon={<Satellite className="size-3.5" />}
                    >
                        <NeuStat
                            label="Method"
                            value={specification?.observationMethod ?? NA}
                        />
                        <NeuStat
                            label="Observation Time"
                            value={specification?.observationMinutes ?? NA}
                        />
                        <NeuStat
                            label="No. of Satellites"
                            value={specification?.satelliteCount ?? NA}
                        />
                        <NeuStat
                            label="PDOP (Max)"
                            value={specification?.pdopMax ?? NA}
                        />
                        <NeuStat
                            label="Elevation Cut-off"
                            value={specification?.elevationCutoff ?? NA}
                        />
                        <NeuStat
                            label="Antenna Type"
                            value={specification?.antennaType ?? NA}
                        />
                        <NeuStat
                            label="Antenna Height"
                            value={specification?.antennaHeight ?? NA}
                        />
                        <NeuStat
                            label="Antenna Point"
                            value={specification?.antennaReferencePoint ?? NA}
                        />
                    </NeuGroup>

                    <NeuGroup
                        title="Accuracy (RMS)"
                        icon={<Gauge className="size-3.5" />}
                    >
                        <NeuStat
                            label="Horizontal (XY)"
                            value={specification?.horizontalRms ?? NA}
                        />
                        <NeuStat
                            label="Vertical (Z)"
                            value={specification?.verticalRms ?? NA}
                        />
                        <div className="flex items-center justify-between py-2.5">
                            <span className="text-neu-ink-muted text-sm">
                                Status
                            </span>
                            <NeuPill
                                tone={
                                    (specification?.qcStatusColor as NeuPillTone) ??
                                    'ink-muted'
                                }
                            >
                                {specification?.qcStatus ?? NA}
                            </NeuPill>
                        </div>
                    </NeuGroup>
                </div>
            </div>
        </DossierLayout>
    );
}
