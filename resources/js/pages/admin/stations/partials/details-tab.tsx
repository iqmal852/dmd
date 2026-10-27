import { Form } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuFormField } from '@/components/neu/neu-form-field';
import { NeuInput } from '@/components/neu/neu-input';
import { NeuPill } from '@/components/neu/neu-pill';
import { NeuSelect } from '@/components/neu/neu-select';
import { NeuTextarea } from '@/components/neu/neu-textarea';
import AdminStationStoreController from '@/actions/App/Http/Controllers/Admin/AdminStationStoreController';
import AdminStationUpdateController from '@/actions/App/Http/Controllers/Admin/AdminStationUpdateController';
import type { AdminStationForm, Option } from '@/types/admin';
import { useState } from 'react';

type Props = {
    station: AdminStationForm;
    directionOptions: Option[];
    statusOptions: Option[];
};

/**
 * plan/phases/phase-08-admin-qr.md M8.3.
 */
export function DetailsTab({
    station,
    directionOptions,
    statusOptions,
}: Props) {
    const isEditing = station.publicId !== null;
    const [clearPassword, setClearPassword] = useState(false);

    const formProps = isEditing
        ? AdminStationUpdateController.form({
              station: station.publicId as string,
          })
        : AdminStationStoreController.form();

    return (
        <NeuCard className="p-5">
            <Form {...formProps} className="space-y-5">
                {({ processing, errors }) => (
                    <>
                        {isEditing && (
                            <>
                                {/*
                                  * The warning below is about the Public ID
                                  * only, not the editable form beneath it, so
                                  * it lives in its own read-only panel with a
                                  * divider after it.
                                  */}
                                <section
                                    aria-labelledby="public-id-heading"
                                    className="bg-neu-surface-sunken space-y-3 rounded-[var(--radius-neu-md)] p-4"
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <h3
                                            id="public-id-heading"
                                            className="text-neu-ink-muted text-sm font-medium"
                                        >
                                            Public ID
                                        </h3>
                                        <NeuPill tone="ink-muted">
                                            Read-only
                                        </NeuPill>
                                    </div>
                                    <p className="bg-neu-primary text-neu-on-primary rounded-[var(--radius-neu-md)] px-3 py-2 font-mono text-base font-semibold tracking-wide break-all select-all">
                                        {station.publicId}
                                    </p>
                                    <div
                                        role="alert"
                                        className="bg-neu-warning text-neu-on-warning flex items-start gap-2 rounded-[var(--radius-neu-md)] p-3 text-sm"
                                    >
                                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                        <span>
                                            <strong className="font-semibold">
                                                Warning:
                                            </strong>{' '}
                                            Changing this Public ID would
                                            invalidate every QR plate already
                                            installed in the field — it cannot
                                            be edited here.
                                        </span>
                                    </div>
                                </section>

                                <div
                                    role="separator"
                                    className="bg-neu-surface-sunken h-0.5 rounded-full"
                                />

                                <h3 className="text-neu-ink text-sm font-semibold">
                                    Station details
                                </h3>
                            </>
                        )}

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <NeuFormField
                                label="Code"
                                htmlFor="code"
                                error={errors.code}
                                required
                            >
                                <NeuInput
                                    id="code"
                                    name="code"
                                    defaultValue={station.code}
                                    invalid={!!errors.code}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="GCP Reference"
                                htmlFor="gcp_reference"
                                error={errors.gcp_reference}
                                hint="The physical survey point ID used in the field (e.g. GP91, TBM03)."
                            >
                                <NeuInput
                                    id="gcp_reference"
                                    name="gcp_reference"
                                    defaultValue={station.gcpReference ?? ''}
                                    invalid={!!errors.gcp_reference}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Highway"
                                htmlFor="highway"
                                error={errors.highway}
                                required
                            >
                                <NeuInput
                                    id="highway"
                                    name="highway"
                                    defaultValue={station.highway}
                                    invalid={!!errors.highway}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Section"
                                htmlFor="section"
                                error={errors.section}
                            >
                                <NeuInput
                                    id="section"
                                    name="section"
                                    defaultValue={station.section ?? ''}
                                    invalid={!!errors.section}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Location"
                                htmlFor="location"
                                error={errors.location}
                                hint="State or named link road (e.g. KEDAH, or DL1- Dengkil NB)."
                            >
                                <NeuInput
                                    id="location"
                                    name="location"
                                    defaultValue={station.location ?? ''}
                                    invalid={!!errors.location}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="KM"
                                htmlFor="km"
                                error={errors.km}
                                required
                            >
                                <NeuInput
                                    id="km"
                                    name="km"
                                    type="number"
                                    step="0.001"
                                    inputMode="decimal"
                                    defaultValue={station.km}
                                    invalid={!!errors.km}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Direction"
                                htmlFor="direction"
                                error={errors.direction}
                                required
                            >
                                <NeuSelect
                                    id="direction"
                                    name="direction"
                                    defaultValue={station.direction}
                                    invalid={!!errors.direction}
                                    required
                                >
                                    <option value="" disabled>
                                        Select…
                                    </option>
                                    {directionOptions.map((option) => (
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
                                label="Facility Type"
                                htmlFor="facility_type"
                                error={errors.facility_type}
                                hint="What's physically here, if not just a direction (e.g. Toll Plaza, Interchange, RSA NB)."
                            >
                                <NeuInput
                                    id="facility_type"
                                    name="facility_type"
                                    defaultValue={station.facilityType ?? ''}
                                    invalid={!!errors.facility_type}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Monument Type"
                                htmlFor="monument_type"
                                error={errors.monument_type}
                                required
                            >
                                <NeuInput
                                    id="monument_type"
                                    name="monument_type"
                                    defaultValue={station.monumentType}
                                    invalid={!!errors.monument_type}
                                    required
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Installed"
                                htmlFor="installed_at"
                                error={errors.installed_at}
                            >
                                <NeuInput
                                    id="installed_at"
                                    name="installed_at"
                                    type="date"
                                    defaultValue={station.installedAt ?? ''}
                                    invalid={!!errors.installed_at}
                                />
                            </NeuFormField>

                            <NeuFormField
                                label="Status"
                                htmlFor="status"
                                error={errors.status}
                                required
                            >
                                <NeuSelect
                                    id="status"
                                    name="status"
                                    defaultValue={station.status}
                                    invalid={!!errors.status}
                                    required
                                >
                                    <option value="" disabled>
                                        Select…
                                    </option>
                                    {statusOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NeuSelect>
                            </NeuFormField>
                        </div>

                        <NeuFormField
                            label="Description"
                            htmlFor="description"
                            error={errors.description}
                        >
                            <NeuTextarea
                                id="description"
                                name="description"
                                rows={3}
                                defaultValue={station.description ?? ''}
                                invalid={!!errors.description}
                            />
                        </NeuFormField>

                        <NeuFormField
                            label="Per-station Password"
                            htmlFor="access_password"
                            error={errors.access_password}
                            hint={
                                station.hasAccessPassword
                                    ? 'This station has its own password. Leave blank to keep it unchanged.'
                                    : 'Leave blank to defer to the deployment-wide access mode.'
                            }
                        >
                            <NeuInput
                                id="access_password"
                                name="access_password"
                                type="password"
                                autoComplete="new-password"
                                invalid={!!errors.access_password}
                                disabled={clearPassword}
                            />
                        </NeuFormField>

                        {isEditing && station.hasAccessPassword && (
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="clear_access_password"
                                    checked={clearPassword}
                                    onChange={(event) =>
                                        setClearPassword(event.target.checked)
                                    }
                                    className="size-4"
                                />
                                Clear the per-station password
                            </label>
                        )}

                        <NeuButton
                            type="submit"
                            variant="primary"
                            disabled={processing}
                        >
                            {isEditing ? 'Save Changes' : 'Create Station'}
                        </NeuButton>
                    </>
                )}
            </Form>
        </NeuCard>
    );
}
