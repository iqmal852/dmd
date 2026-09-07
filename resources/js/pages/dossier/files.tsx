import { Head, usePage } from '@inertiajs/react';
import { Download, ExternalLink, FileText } from 'lucide-react';
import { useState } from 'react';
import { DocumentLightbox } from '@/components/dossier/document-lightbox';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuEmptyState } from '@/components/neu/neu-empty-state';
import { NeuIconButton } from '@/components/neu/neu-icon-button';
import { NeuPill } from '@/components/neu/neu-pill';
import DossierLayout from '@/layouts/dossier/dossier-layout';
import { buildDossierNavItems } from '@/lib/dossier-nav';
import type { Document } from '@/types/dossier';

type PageProps = {
    stationPublicId: string;
    stationCode: string;
    documents: Document[];
};

function DocumentPreview({ document }: { document: Document }) {
    const [lightboxOpen, setLightboxOpen] = useState(false);

    if (document.previewKind === 'pdf') {
        return (
            <div className="space-y-2">
                <object
                    data={document.previewUrl ?? undefined}
                    type="application/pdf"
                    className="bg-neu-surface-sunken h-[60dvh] w-full rounded-[var(--radius-neu-md)]"
                    aria-label={`${document.title} preview`}
                >
                    <p className="text-neu-ink-muted p-4 text-sm">
                        This browser can't display the PDF inline.
                    </p>
                </object>
                <a
                    href={document.previewUrl ?? undefined}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-neu-link inline-flex items-center gap-1 text-sm font-medium"
                >
                    <ExternalLink className="size-4" />
                    Open in new tab (some browsers only show the first page
                    inline)
                </a>
            </div>
        );
    }

    if (document.previewKind === 'image') {
        return (
            <>
                <button
                    type="button"
                    className="block w-full overflow-hidden rounded-[var(--radius-neu-md)]"
                    onClick={() => setLightboxOpen(true)}
                >
                    <img
                        src={document.previewUrl ?? undefined}
                        alt={`${document.title} preview`}
                        className="w-full object-contain"
                    />
                </button>
                {lightboxOpen && (
                    <DocumentLightbox
                        src={document.previewUrl ?? ''}
                        alt={`${document.title} preview`}
                        onClose={() => setLightboxOpen(false)}
                    />
                )}
            </>
        );
    }

    return (
        <div className="text-neu-ink-muted bg-neu-surface-sunken flex h-32 items-center justify-center rounded-[var(--radius-neu-md)] text-sm">
            No preview available for this file type.
        </div>
    );
}

function PrimaryDocumentCard({ document }: { document: Document }) {
    return (
        <NeuCard className="space-y-4 p-5">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 className="text-lg font-bold">{document.title}</h2>
                    <p className="text-neu-ink-muted text-sm">
                        {document.typeLabel}
                        {document.revision && ` · Rev. ${document.revision}`}
                    </p>
                </div>
                <NeuPill tone="info">{document.extension}</NeuPill>
            </div>

            <DocumentPreview document={document} />

            <div className="flex items-center justify-between gap-3">
                <span className="text-neu-ink-muted text-sm">
                    {document.size}
                </span>
                <a href={document.downloadUrl}>
                    <NeuButton variant="primary" className="gap-2">
                        <Download className="size-4" />
                        Download As-Built Drawing
                    </NeuButton>
                </a>
            </div>
        </NeuCard>
    );
}

function DocumentRow({ document }: { document: Document }) {
    return (
        <NeuCard
            variant="flat"
            className="flex items-center justify-between gap-3 p-3"
        >
            <div className="flex min-w-0 items-center gap-3">
                <FileText className="text-neu-ink-subtle size-5 shrink-0" />
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium">
                        {document.title}
                    </p>
                    <p className="text-neu-ink-muted text-xs">
                        {document.typeLabel} · {document.extension} ·{' '}
                        {document.size}
                    </p>
                </div>
            </div>
            <a href={document.downloadUrl}>
                <NeuIconButton aria-label={`Download ${document.title}`}>
                    <Download className="size-4" />
                </NeuIconButton>
            </a>
        </NeuCard>
    );
}

/**
 * Picture1.png panel 3 — As-Built Drawing viewer and downloads. See
 * plan/phases/phase-07-asbuilt-files.md M7.2/M7.3.
 */
export default function DossierFiles() {
    const { branding } = usePage().props;
    const { stationPublicId, stationCode, documents } =
        usePage<PageProps>().props;

    const primary = documents.find((document) => document.isPrimary);
    const others = documents.filter((document) => document !== primary);

    return (
        <DossierLayout
            operator={branding.operator}
            title={branding.title}
            navItems={buildDossierNavItems(stationPublicId, 'files')}
        >
            <Head title={`Files — ${stationCode}`} />

            <div className="space-y-4">
                {documents.length === 0 ? (
                    <NeuEmptyState
                        icon={<FileText className="size-8" />}
                        message={`As-built drawing not yet uploaded for station ${stationCode}.`}
                    />
                ) : (
                    <>
                        {primary && <PrimaryDocumentCard document={primary} />}

                        {others.length > 0 && (
                            <div className="space-y-2">
                                {others.map((document) => (
                                    <DocumentRow
                                        key={document.id}
                                        document={document}
                                    />
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </DossierLayout>
    );
}
