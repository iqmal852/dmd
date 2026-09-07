import { Form, router } from '@inertiajs/react';
import { Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuFormField } from '@/components/neu/neu-form-field';
import { NeuInput } from '@/components/neu/neu-input';
import { NeuPill } from '@/components/neu/neu-pill';
import { NeuSelect } from '@/components/neu/neu-select';
import AdminDocumentStoreController from '@/actions/App/Http/Controllers/Admin/AdminDocumentStoreController';
import { destroy, update } from '@/routes/admin/stations/documents';
import type { AdminDocument, Option } from '@/types/admin';

type Props = {
    stationPublicId: string;
    documents: AdminDocument[];
    documentTypeOptions: Option[];
};

const RENDERABLE_MIMES = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
    'image/svg+xml',
];

/**
 * plan/phases/phase-08-admin-qr.md M8.7. A DWG/DXF upload requires a
 * preview file in the same request — enforced server-side
 * (StoreDocumentRequest); this component's `needsPreview` state is only a
 * same-page hint, not the enforcement point.
 */
export function DocumentsTab({
    stationPublicId,
    documents,
    documentTypeOptions,
}: Props) {
    const [needsPreview, setNeedsPreview] = useState(false);

    function setPrimary(document: AdminDocument) {
        router.patch(
            update({ station: stationPublicId, media: document.id }).url,
            {
                document_type: document.documentType,
                title: document.title,
                revision: document.revision,
                is_primary: true,
            },
            {
                preserveScroll: true,
                onSuccess: () => toast('Primary document updated'),
            },
        );
    }

    function deleteDocument(document: AdminDocument) {
        router.delete(
            destroy({ station: stationPublicId, media: document.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => toast('Document deleted'),
            },
        );
    }

    return (
        <div className="space-y-4">
            <NeuCard className="p-5">
                <h2 className="mb-4 font-bold">Add Document</h2>

                <Form
                    {...AdminDocumentStoreController.form({
                        station: stationPublicId,
                    })}
                    resetOnSuccess
                    onSuccess={() => {
                        setNeedsPreview(false);
                        toast('Document uploaded');
                    }}
                >
                    {({ processing, errors }) => (
                        <div className="space-y-4">
                            <NeuFormField
                                label="File"
                                htmlFor="document-file"
                                error={errors.file}
                            >
                                <input
                                    id="document-file"
                                    name="file"
                                    type="file"
                                    required
                                    onChange={(e) => {
                                        const mime =
                                            e.target.files?.[0]?.type ?? '';
                                        setNeedsPreview(
                                            mime !== '' &&
                                                !RENDERABLE_MIMES.includes(
                                                    mime,
                                                ),
                                        );
                                    }}
                                    className="text-sm"
                                />
                            </NeuFormField>

                            {needsPreview && (
                                <NeuFormField
                                    label="Preview File (required — this file type can't be displayed in a browser)"
                                    htmlFor="document-preview-file"
                                    hint="Attach a PDF or image so the Files screen has something to render inline."
                                    error={errors.preview_file}
                                >
                                    <input
                                        id="document-preview-file"
                                        name="preview_file"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        className="text-sm"
                                    />
                                </NeuFormField>
                            )}

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <NeuFormField
                                    label="Document Type"
                                    htmlFor="document_type"
                                    error={errors.document_type}
                                >
                                    <NeuSelect
                                        id="document_type"
                                        name="document_type"
                                        defaultValue={
                                            documentTypeOptions[0]?.value
                                        }
                                    >
                                        {documentTypeOptions.map((option) => (
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
                                    label="Title"
                                    htmlFor="title"
                                    error={errors.title}
                                >
                                    <NeuInput
                                        id="title"
                                        name="title"
                                        required
                                    />
                                </NeuFormField>
                                <NeuFormField
                                    label="Revision"
                                    htmlFor="revision"
                                    hint="Optional"
                                    error={errors.revision}
                                >
                                    <NeuInput id="revision" name="revision" />
                                </NeuFormField>
                            </div>

                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="is_primary"
                                    value="1"
                                    className="size-4"
                                />
                                Set as the primary document (the big download
                                button)
                            </label>

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

            <div className="space-y-2">
                {documents.map((document) => (
                    <NeuCard
                        key={document.id}
                        className="flex items-center justify-between gap-3 p-3"
                    >
                        <div className="min-w-0">
                            <p className="truncate text-sm font-medium">
                                {document.title}
                                {document.isPrimary && (
                                    <NeuPill tone="accent" className="ml-2">
                                        Primary
                                    </NeuPill>
                                )}
                            </p>
                            <p className="text-neu-ink-muted text-xs">
                                {document.documentType} · {document.extension} ·{' '}
                                {document.size}
                                {!document.hasPreview && ' · No preview'}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {!document.isPrimary && (
                                <NeuButton
                                    variant="ghost"
                                    onClick={() => setPrimary(document)}
                                >
                                    Make Primary
                                </NeuButton>
                            )}
                            <NeuButton
                                variant="danger"
                                onClick={() => deleteDocument(document)}
                            >
                                <Trash2 className="size-4" />
                            </NeuButton>
                        </div>
                    </NeuCard>
                ))}
            </div>

            {documents.length === 0 && (
                <p className="text-neu-ink-muted text-sm">
                    No documents uploaded yet.
                </p>
            )}
        </div>
    );
}
