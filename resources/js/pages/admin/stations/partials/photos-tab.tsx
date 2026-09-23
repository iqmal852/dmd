import { Form, router } from '@inertiajs/react';
import { Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
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

type SelectedFile = {
    file: File;
    label: string;
    previewUrl: string;
};

/**
 * plan/phases/phase-08-admin-qr.md M8.7. A single `<input type="file"
 * multiple>` carries every selected file to the server natively in one
 * request — no DataTransfer/synthetic-FormData trickery (that category
 * of approach broke uploads under Pest's browser-testing plugin before,
 * see AdminEndToEndTest's own docblock). Reading `e.target.files` here
 * is only ever to drive the live thumbnail/label preview below; the
 * input's own FileList is never replaced or reconstructed, so the
 * actual submission is exactly what the browser already does natively
 * for a multi-file field.
 *
 * No fixed photo type/category (a free-text label only) and no cap on
 * how many photos a station may carry — explicit user request.
 * Reselecting the file input replaces the whole preview — there's no
 * per-file "remove before upload," since doing that without touching
 * the native FileList needs the same DataTransfer trickery this
 * deliberately avoids.
 */
export function PhotosTab({ stationPublicId, photos }: Props) {
    const [selected, setSelected] = useState<SelectedFile[]>([]);

    function revokePreviews(items: SelectedFile[]) {
        items.forEach((item) => URL.revokeObjectURL(item.previewUrl));
    }

    function handleFilesChosen(fileList: FileList | null) {
        revokePreviews(selected);

        const files = fileList ? Array.from(fileList) : [];
        setSelected(
            files.map((file) => ({
                file,
                label: '',
                previewUrl: URL.createObjectURL(file),
            })),
        );
    }

    function clearSelection() {
        revokePreviews(selected);
        setSelected([]);
    }

    function setLabelAt(index: number, label: string) {
        setSelected((current) =>
            current.map((item, i) =>
                i === index ? { ...item, label } : item,
            ),
        );
    }

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
                <h2 className="mb-1 font-bold">Add Photos</h2>
                <p className="text-neu-ink-muted mb-4 text-sm">
                    Select one or more photos, label each one, then upload
                    them all at once.
                </p>
                <Form
                    {...AdminPhotoStoreController.form({
                        station: stationPublicId,
                    })}
                    resetOnSuccess
                    onSuccess={() => {
                        toast(
                            selected.length > 1
                                ? `${selected.length} photos uploaded`
                                : 'Photo uploaded',
                        );
                        clearSelection();
                    }}
                >
                    {({ processing, errors }) => (
                        <div className="space-y-4">
                            <NeuFormField
                                label="Photos"
                                htmlFor="photo-files"
                                error={errors.files}
                                hint="JPEG, PNG, or WebP — select multiple at once"
                            >
                                <input
                                    id="photo-files"
                                    name="files[]"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    required
                                    className="text-sm"
                                    onChange={(e) =>
                                        handleFilesChosen(e.target.files)
                                    }
                                />
                            </NeuFormField>

                            {selected.length > 0 && (
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {selected.map((item, index) => (
                                        <div
                                            key={index}
                                            className="neu shadow-neu-sm flex items-center gap-3 rounded-[var(--radius-neu-md)] p-2"
                                        >
                                            <img
                                                src={item.previewUrl}
                                                alt=""
                                                className="size-14 shrink-0 rounded-[var(--radius-neu-sm)] object-cover"
                                            />
                                            <div className="min-w-0 flex-1">
                                                <p className="text-neu-ink-subtle truncate text-xs">
                                                    {item.file.name}
                                                </p>
                                                <NeuInput
                                                    placeholder="Label (optional)"
                                                    name="labels[]"
                                                    value={item.label}
                                                    onChange={(e) =>
                                                        setLabelAt(
                                                            index,
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="mt-1 py-1 text-sm"
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <NeuButton
                                type="submit"
                                variant="primary"
                                className="gap-2"
                                disabled={processing || selected.length === 0}
                            >
                                <Upload className="size-4" />
                                {processing
                                    ? 'Uploading…'
                                    : selected.length > 1
                                      ? `Upload ${selected.length} Photos`
                                      : 'Upload'}
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
