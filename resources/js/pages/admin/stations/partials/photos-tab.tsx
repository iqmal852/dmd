import { Form, router } from '@inertiajs/react';
import { Trash2, Upload } from 'lucide-react';
import { toast } from 'sonner';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuFormField } from '@/components/neu/neu-form-field';
import { NeuIconButton } from '@/components/neu/neu-icon-button';
import { NeuInput } from '@/components/neu/neu-input';
import AdminPhotoStoreController from '@/actions/App/Http/Controllers/Admin/AdminPhotoStoreController';
import { destroy, update } from '@/routes/admin/stations/photos';
import type { AdminPhoto } from '@/types/admin';

type Props = {
    stationPublicId: string;
    photos: AdminPhoto[];
};

/**
 * plan/phases/phase-08-admin-qr.md M8.7. One file per submission — the
 * admin repeats the action for a multi-file selection — via Inertia's
 * `<Form>` component, the same pattern used throughout this admin console
 * (and the public unlock form). Deviation: no client-side downscale of
 * oversized images before upload. That needs replacing the file input's
 * FileList via the DataTransfer API before a native form submission,
 * which is real added complexity for a nice-to-have the Test Gate doesn't
 * require; the server-side 20 MB cap (StorePhotoRequest) is the actual
 * safeguard against an enormous upload today.
 *
 * No fixed photo type/category (a free-text label only) and no cap on how
 * many photos a station may carry — explicit user request.
 */
export function PhotosTab({ stationPublicId, photos }: Props) {
    function updatePhoto(photo: AdminPhoto, changes: Partial<AdminPhoto>) {
        router.patch(
            update({ station: stationPublicId, media: photo.id }).url,
            {
                bearing: changes.bearing ?? photo.bearing,
                label: changes.label ?? photo.label,
            },
            { preserveScroll: true, onSuccess: () => toast('Photo updated') },
        );
    }

    function deletePhoto(photo: AdminPhoto) {
        router.delete(
            destroy({ station: stationPublicId, media: photo.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => toast('Photo deleted'),
            },
        );
    }

    return (
        <div className="space-y-4">
            <NeuCard className="p-5">
                <h2 className="mb-4 font-bold">Add Photo</h2>
                <Form
                    {...AdminPhotoStoreController.form({
                        station: stationPublicId,
                    })}
                    resetOnSuccess
                    onSuccess={() => toast('Photo uploaded')}
                >
                    {({ processing, errors }) => (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <NeuFormField
                                label="File"
                                htmlFor="photo-file"
                                error={errors.file}
                                className="sm:col-span-4"
                            >
                                <input
                                    id="photo-file"
                                    name="file"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                    className="text-sm"
                                />
                            </NeuFormField>
                            <NeuFormField
                                label="Label"
                                htmlFor="label"
                                hint="Optional"
                                error={errors.label}
                                className="sm:col-span-2"
                            >
                                <NeuInput id="label" name="label" />
                            </NeuFormField>
                            <NeuFormField
                                label="Bearing (0-359)"
                                htmlFor="bearing"
                                hint="Optional"
                                error={errors.bearing}
                            >
                                <NeuInput
                                    id="bearing"
                                    name="bearing"
                                    type="number"
                                    min={0}
                                    max={359}
                                />
                            </NeuFormField>
                            <NeuButton
                                type="submit"
                                variant="primary"
                                className="gap-2 sm:col-span-4 sm:w-fit"
                                disabled={processing}
                            >
                                <Upload className="size-4" />
                                {processing ? 'Uploading…' : 'Upload'}
                            </NeuButton>
                        </div>
                    )}
                </Form>
            </NeuCard>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {photos.map((photo) => (
                    <NeuCard key={photo.id} className="flex gap-3 p-3">
                        <img
                            src={photo.thumbUrl}
                            alt=""
                            className="size-20 shrink-0 rounded-[var(--radius-neu-sm)] object-cover"
                        />
                        <div className="flex-1 space-y-2">
                            <NeuInput
                                placeholder="Label"
                                defaultValue={photo.label ?? ''}
                                onBlur={(e) =>
                                    updatePhoto(photo, {
                                        label: e.target.value || null,
                                    })
                                }
                                className="py-1.5 text-sm"
                            />
                            <div className="flex items-center gap-2">
                                <NeuInput
                                    type="number"
                                    min={0}
                                    max={359}
                                    placeholder="Bearing"
                                    defaultValue={photo.bearing ?? ''}
                                    onBlur={(e) =>
                                        updatePhoto(photo, {
                                            bearing: e.target.value
                                                ? Number(e.target.value)
                                                : null,
                                        })
                                    }
                                    className="py-1.5 text-sm"
                                />
                                <NeuIconButton
                                    aria-label={`Delete photo`}
                                    onClick={() => deletePhoto(photo)}
                                >
                                    <Trash2 className="size-4" />
                                </NeuIconButton>
                            </div>
                        </div>
                    </NeuCard>
                ))}
            </div>

            {photos.length === 0 && (
                <p className="text-neu-ink-muted text-sm">
                    No photos uploaded yet.
                </p>
            )}
        </div>
    );
}
