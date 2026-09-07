import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { AlertTriangle } from 'lucide-react';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuFormField } from '@/components/neu/neu-form-field';
import { NeuInput } from '@/components/neu/neu-input';
import AdminStationCoordinatesUpdateController from '@/actions/App/Http/Controllers/Admin/AdminStationCoordinatesUpdateController';
import type { AdminCoordinateSet } from '@/types/admin';

type Props = {
    stationPublicId: string;
    coordinateSet: AdminCoordinateSet | null;
};

const MALAYSIA_LAT = [0.5, 7.5];
const MALAYSIA_LON = [99, 120];

/**
 * plan/phases/phase-08-admin-qr.md M8.6. The Malaysia range check is a
 * warning, not a validation error — the app must not refuse valid data
 * because someone deployed it on a different project — but a swapped
 * lat/lon is the single most common survey data-entry error, and catching
 * it at entry saves a site visit.
 */
export function CoordinatesTab({ stationPublicId, coordinateSet }: Props) {
    const [lat, setLat] = useState(coordinateSet?.latitude ?? '');
    const [lon, setLon] = useState(coordinateSet?.longitude ?? '');

    const latNum = parseFloat(lat);
    const lonNum = parseFloat(lon);
    const latOutOfRange =
        !isNaN(latNum) &&
        (latNum < MALAYSIA_LAT[0] || latNum > MALAYSIA_LAT[1]);
    const lonOutOfRange =
        !isNaN(lonNum) &&
        (lonNum < MALAYSIA_LON[0] || lonNum > MALAYSIA_LON[1]);

    return (
        <NeuCard className="p-5">
            <Form
                {...AdminStationCoordinatesUpdateController.form({
                    station: stationPublicId,
                })}
                className="space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        {(latOutOfRange || lonOutOfRange) && (
                            <div className="bg-neu-warning/20 text-neu-ink flex items-start gap-2 rounded-[var(--radius-neu-md)] p-3 text-sm">
                                <AlertTriangle className="text-neu-warning mt-0.5 size-4 shrink-0" />
                                <span>
                                    These coordinates fall outside
                                    Malaysia&rsquo;s typical range — double
                                    check latitude and longitude aren&rsquo;t
                                    swapped. This is a warning only; you can
                                    still save.
                                </span>
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <NeuFormField
                                label="Latitude (WGS 84)"
                                htmlFor="latitude"
                                error={errors.latitude}
                                hint={
                                    !isNaN(latNum)
                                        ? `Renders as "${latNum} °"`
                                        : undefined
                                }
                                required
                            >
                                <NeuInput
                                    id="latitude"
                                    name="latitude"
                                    inputMode="decimal"
                                    value={lat}
                                    onChange={(e) => setLat(e.target.value)}
                                    invalid={!!errors.latitude}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Longitude (WGS 84)"
                                htmlFor="longitude"
                                error={errors.longitude}
                                hint={
                                    !isNaN(lonNum)
                                        ? `Renders as "${lonNum} °"`
                                        : undefined
                                }
                                required
                            >
                                <NeuInput
                                    id="longitude"
                                    name="longitude"
                                    inputMode="decimal"
                                    value={lon}
                                    onChange={(e) => setLon(e.target.value)}
                                    invalid={!!errors.longitude}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Ellipsoidal Height (h)"
                                htmlFor="ellipsoidal_height"
                                error={errors.ellipsoidal_height}
                                required
                            >
                                <NeuInput
                                    id="ellipsoidal_height"
                                    name="ellipsoidal_height"
                                    inputMode="decimal"
                                    defaultValue={
                                        coordinateSet?.ellipsoidalHeight ?? ''
                                    }
                                    invalid={!!errors.ellipsoidal_height}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Easting (E, GDM 2000)"
                                htmlFor="easting"
                                error={errors.easting}
                                required
                            >
                                <NeuInput
                                    id="easting"
                                    name="easting"
                                    inputMode="decimal"
                                    defaultValue={coordinateSet?.easting ?? ''}
                                    invalid={!!errors.easting}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Northing (N, GDM 2000)"
                                htmlFor="northing"
                                error={errors.northing}
                                required
                            >
                                <NeuInput
                                    id="northing"
                                    name="northing"
                                    inputMode="decimal"
                                    defaultValue={coordinateSet?.northing ?? ''}
                                    invalid={!!errors.northing}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Zone"
                                htmlFor="zone"
                                error={errors.zone}
                            >
                                <NeuInput
                                    id="zone"
                                    name="zone"
                                    defaultValue={coordinateSet?.zone ?? ''}
                                    invalid={!!errors.zone}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Orthometric Height (MyGEOID)"
                                htmlFor="orthometric_height"
                                error={errors.orthometric_height}
                                required
                            >
                                <NeuInput
                                    id="orthometric_height"
                                    name="orthometric_height"
                                    inputMode="decimal"
                                    defaultValue={
                                        coordinateSet?.orthometricHeight ?? ''
                                    }
                                    invalid={!!errors.orthometric_height}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Geoid Model"
                                htmlFor="geoid_model"
                                error={errors.geoid_model}
                                required
                            >
                                <NeuInput
                                    id="geoid_model"
                                    name="geoid_model"
                                    defaultValue={
                                        coordinateSet?.geoidModel ?? 'MyGEOID'
                                    }
                                    invalid={!!errors.geoid_model}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Epoch"
                                htmlFor="epoch"
                                error={errors.epoch}
                            >
                                <NeuInput
                                    id="epoch"
                                    name="epoch"
                                    defaultValue={coordinateSet?.epoch ?? ''}
                                    invalid={!!errors.epoch}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Computed On"
                                htmlFor="computed_at"
                                error={errors.computed_at}
                            >
                                <NeuInput
                                    id="computed_at"
                                    name="computed_at"
                                    type="date"
                                    defaultValue={
                                        coordinateSet?.computedAt ?? ''
                                    }
                                    invalid={!!errors.computed_at}
                                />
                            </NeuFormField>
                        </div>

                        <NeuButton
                            type="submit"
                            variant="primary"
                            disabled={processing}
                        >
                            Save Coordinates
                        </NeuButton>
                    </>
                )}
            </Form>
        </NeuCard>
    );
}
