import { Form } from '@inertiajs/react';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuFormField } from '@/components/neu/neu-form-field';
import { NeuInput } from '@/components/neu/neu-input';
import { NeuSelect } from '@/components/neu/neu-select';
import { NeuTextarea } from '@/components/neu/neu-textarea';
import AdminSpecificationUpdateController from '@/actions/App/Http/Controllers/Admin/AdminSpecificationUpdateController';
import type { AdminSpecification } from '@/types/admin';

type Props = {
    stationPublicId: string;
    specification: AdminSpecification | null;
};

const QC_STATUS_OPTIONS = [
    { value: 'pending', label: 'Pending' },
    { value: 'verified', label: 'Verified' },
    { value: 'rejected', label: 'Rejected' },
];

/**
 * plan/phases/phase-08-admin-qr.md M8.6.
 */
export function SpecificationTab({ stationPublicId, specification }: Props) {
    return (
        <NeuCard className="p-5">
            <Form
                {...AdminSpecificationUpdateController.form({
                    station: stationPublicId,
                })}
                className="space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <NeuFormField
                                label="Observation Method"
                                htmlFor="observation_method"
                                error={errors.observation_method}
                            >
                                <NeuInput
                                    id="observation_method"
                                    name="observation_method"
                                    defaultValue={
                                        specification?.observationMethod ?? ''
                                    }
                                    invalid={!!errors.observation_method}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Observation Minutes"
                                htmlFor="observation_minutes"
                                error={errors.observation_minutes}
                            >
                                <NeuInput
                                    id="observation_minutes"
                                    name="observation_minutes"
                                    type="number"
                                    defaultValue={
                                        specification?.observationMinutes ?? ''
                                    }
                                    invalid={!!errors.observation_minutes}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Satellite Count"
                                htmlFor="satellite_count"
                                error={errors.satellite_count}
                            >
                                <NeuInput
                                    id="satellite_count"
                                    name="satellite_count"
                                    type="number"
                                    min={0}
                                    max={60}
                                    defaultValue={
                                        specification?.satelliteCount ?? ''
                                    }
                                    invalid={!!errors.satellite_count}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="PDOP Max"
                                htmlFor="pdop_max"
                                error={errors.pdop_max}
                            >
                                <NeuInput
                                    id="pdop_max"
                                    name="pdop_max"
                                    inputMode="decimal"
                                    defaultValue={specification?.pdopMax ?? ''}
                                    invalid={!!errors.pdop_max}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Elevation Cutoff (deg)"
                                htmlFor="elevation_cutoff_deg"
                                error={errors.elevation_cutoff_deg}
                            >
                                <NeuInput
                                    id="elevation_cutoff_deg"
                                    name="elevation_cutoff_deg"
                                    type="number"
                                    min={0}
                                    max={90}
                                    defaultValue={
                                        specification?.elevationCutoffDeg ?? ''
                                    }
                                    invalid={!!errors.elevation_cutoff_deg}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Antenna Type"
                                htmlFor="antenna_type"
                                error={errors.antenna_type}
                            >
                                <NeuInput
                                    id="antenna_type"
                                    name="antenna_type"
                                    defaultValue={
                                        specification?.antennaType ?? ''
                                    }
                                    invalid={!!errors.antenna_type}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Antenna Height (m)"
                                htmlFor="antenna_height"
                                error={errors.antenna_height}
                            >
                                <NeuInput
                                    id="antenna_height"
                                    name="antenna_height"
                                    inputMode="decimal"
                                    defaultValue={
                                        specification?.antennaHeight ?? ''
                                    }
                                    invalid={!!errors.antenna_height}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Antenna Reference Point"
                                htmlFor="antenna_reference_point"
                                error={errors.antenna_reference_point}
                            >
                                <NeuInput
                                    id="antenna_reference_point"
                                    name="antenna_reference_point"
                                    defaultValue={
                                        specification?.antennaReferencePoint ??
                                        ''
                                    }
                                    invalid={!!errors.antenna_reference_point}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Horizontal RMS (mm)"
                                htmlFor="horizontal_rms_mm"
                                error={errors.horizontal_rms_mm}
                            >
                                <NeuInput
                                    id="horizontal_rms_mm"
                                    name="horizontal_rms_mm"
                                    inputMode="decimal"
                                    defaultValue={
                                        specification?.horizontalRmsMm ?? ''
                                    }
                                    invalid={!!errors.horizontal_rms_mm}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Vertical RMS (mm)"
                                htmlFor="vertical_rms_mm"
                                error={errors.vertical_rms_mm}
                            >
                                <NeuInput
                                    id="vertical_rms_mm"
                                    name="vertical_rms_mm"
                                    inputMode="decimal"
                                    defaultValue={
                                        specification?.verticalRmsMm ?? ''
                                    }
                                    invalid={!!errors.vertical_rms_mm}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="QC Status"
                                htmlFor="qc_status"
                                error={errors.qc_status}
                                required
                            >
                                <NeuSelect
                                    id="qc_status"
                                    name="qc_status"
                                    defaultValue={
                                        specification?.qcStatus ?? 'pending'
                                    }
                                    invalid={!!errors.qc_status}
                                    required
                                >
                                    {QC_STATUS_OPTIONS.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NeuSelect>
                            </NeuFormField>

                            <NeuFormField
                                label="Verified On"
                                htmlFor="verified_at"
                                error={errors.verified_at}
                            >
                                <NeuInput
                                    id="verified_at"
                                    name="verified_at"
                                    type="date"
                                    defaultValue={
                                        specification?.verifiedAt ?? ''
                                    }
                                    invalid={!!errors.verified_at}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Verified By"
                                htmlFor="verified_by"
                                error={errors.verified_by}
                            >
                                <NeuInput
                                    id="verified_by"
                                    name="verified_by"
                                    defaultValue={
                                        specification?.verifiedBy ?? ''
                                    }
                                    invalid={!!errors.verified_by}
                                />
                            </NeuFormField>
                        </div>

                        <NeuFormField
                            label="Remarks"
                            htmlFor="remarks"
                            error={errors.remarks}
                        >
                            <NeuTextarea
                                id="remarks"
                                name="remarks"
                                rows={3}
                                defaultValue={specification?.remarks ?? ''}
                                invalid={!!errors.remarks}
                            />
                        </NeuFormField>

                        <NeuButton
                            type="submit"
                            variant="primary"
                            disabled={processing}
                        >
                            Save Specification
                        </NeuButton>
                    </>
                )}
            </Form>
        </NeuCard>
    );
}
