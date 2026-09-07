import { Form, router } from '@inertiajs/react';
import { Trash2, Upload } from 'lucide-react';
import { toast } from 'sonner';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuFormField } from '@/components/neu/neu-form-field';
import { NeuInput } from '@/components/neu/neu-input';
import AdminPanoramaStoreController from '@/actions/App/Http/Controllers/Admin/AdminPanoramaStoreController';
import { destroy } from '@/routes/admin/stations/panorama';
import type { AdminPanorama } from '@/types/admin';

type Props = {
    stationPublicId: string;
    panorama: AdminPanorama | null;
};

/**
 * plan/phases/phase-08-admin-qr.md M8.7. The 2:1 aspect ratio is enforced
 * server-side (StorePanoramaRequest's `dimensions:ratio=2/1` rule) — the
 * error surfaces through the normal Inertia `<Form>` error slot rather
 * than a separate client-side pre-check, which would need reading the
 * file's dimensions before submit for a courtesy check the server already
 * gives a clear message for. Deviation from the plan's "dragging a live
 * Pannellum preview" for initial yaw/pitch: plain numeric inputs instead,
 * to avoid embedding a second Pannellum instance (with its own
 * drag-to-set-view interaction) in the admin console for a value an admin
 * can just as easily type after viewing the public page once.
 */
export function PanoramaTab({ stationPublicId, panorama }: Props) {
    function deletePanorama() {
        if (!panorama) {
            return;
        }

        router.delete(
            destroy({ station: stationPublicId, media: panorama.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => toast('Panorama deleted'),
            },
        );
    }

    return (
        <div className="space-y-4">
            {panorama && (
                <NeuCard className="flex items-center gap-4 p-4">
                    <img
                        src={panorama.url}
                        alt="Current panorama"
                        className="h-20 w-40 rounded-[var(--radius-neu-sm)] object-cover"
                    />
                    <div className="flex-1 text-sm">
                        <p>
                            Yaw {panorama.initialYaw}° · Pitch{' '}
                            {panorama.initialPitch}° · HFOV {panorama.hfov}°
                        </p>
                    </div>
                    <NeuButton
                        variant="danger"
                        className="gap-2"
                        onClick={deletePanorama}
                    >
                        <Trash2 className="size-4" />
                        Delete
                    </NeuButton>
                </NeuCard>
            )}

            <NeuCard className="p-5">
                <h2 className="mb-4 font-bold">
                    {panorama ? 'Replace Panorama' : 'Upload Panorama'}
                </h2>

                <Form
                    {...AdminPanoramaStoreController.form({
                        station: stationPublicId,
                    })}
                    resetOnSuccess
                    onSuccess={() => toast('Panorama uploaded')}
                >
                    {({ processing, errors }) => (
                        <div className="space-y-4">
                            <NeuFormField
                                label="File (2:1 equirectangular)"
                                htmlFor="panorama-file"
                                error={errors.file}
                            >
                                <input
                                    id="panorama-file"
                                    name="file"
                                    type="file"
                                    accept="image/jpeg,image/webp"
                                    required
                                    className="text-sm"
                                />
                            </NeuFormField>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <NeuFormField
                                    label="Initial Yaw"
                                    htmlFor="initial_yaw"
                                >
                                    <NeuInput
                                        id="initial_yaw"
                                        name="initial_yaw"
                                        type="number"
                                        defaultValue={panorama?.initialYaw ?? 0}
                                    />
                                </NeuFormField>
                                <NeuFormField
                                    label="Initial Pitch"
                                    htmlFor="initial_pitch"
                                >
                                    <NeuInput
                                        id="initial_pitch"
                                        name="initial_pitch"
                                        type="number"
                                        defaultValue={
                                            panorama?.initialPitch ?? 0
                                        }
                                    />
                                </NeuFormField>
                                <NeuFormField label="HFOV" htmlFor="hfov">
                                    <NeuInput
                                        id="hfov"
                                        name="hfov"
                                        type="number"
                                        defaultValue={panorama?.hfov ?? 100}
                                    />
                                </NeuFormField>
                            </div>

                            <NeuButton
                                type="submit"
                                variant="primary"
                                className="gap-2"
                                disabled={processing}
                            >
                                <Upload className="size-4" />
                                {processing ? 'Uploading…' : 'Upload'}
                            </NeuButton>
                        </div>
                    )}
                </Form>
            </NeuCard>
        </div>
    );
}
