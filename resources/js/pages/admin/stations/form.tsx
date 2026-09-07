import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { DetailsTab } from '@/pages/admin/stations/partials/details-tab';
import { CoordinatesTab } from '@/pages/admin/stations/partials/coordinates-tab';
import { SpecificationTab } from '@/pages/admin/stations/partials/specification-tab';
import { PhotosTab } from '@/pages/admin/stations/partials/photos-tab';
import { PanoramaTab } from '@/pages/admin/stations/partials/panorama-tab';
import { DocumentsTab } from '@/pages/admin/stations/partials/documents-tab';
import { cn } from '@/lib/utils';
import type {
    AdminCoordinateSet,
    AdminDocument,
    AdminPanorama,
    AdminPhoto,
    AdminSpecification,
    AdminStationForm,
    Option,
} from '@/types/admin';

type PageProps = {
    station: AdminStationForm;
    coordinateSet: AdminCoordinateSet | null;
    specification: AdminSpecification | null;
    photos: AdminPhoto[];
    panorama: AdminPanorama | null;
    documents: AdminDocument[];
    directionOptions: Option[];
    statusOptions: Option[];
    photoTypeOptions: Option[];
    documentTypeOptions: Option[];
};

type Tab =
    | 'details'
    | 'coordinates'
    | 'specification'
    | 'photos'
    | 'panorama'
    | 'documents';

/**
 * The station create/edit screen — one page with tabs rather than five
 * separate round trips. Only "Details" is available until a station has
 * been created (its id is needed for every other tab's upload/update
 * endpoint). See plan/phases/phase-08-admin-qr.md M8.3/M8.6/M8.7.
 */
export default function AdminStationForm() {
    const props = usePage<PageProps>().props;
    const { station } = props;
    const isEditing = station.publicId !== null;
    const [tab, setTab] = useState<Tab>('details');

    const tabs: { key: Tab; label: string }[] = [
        { key: 'details', label: 'Details' },
        { key: 'coordinates', label: 'Coordinates' },
        { key: 'specification', label: 'Specification' },
        { key: 'photos', label: 'Photos' },
        { key: 'panorama', label: '360° Panorama' },
        { key: 'documents', label: 'Documents' },
    ];

    return (
        <AdminLayout active="stations">
            <Head title={isEditing ? `Edit ${station.code}` : 'New Station'} />

            <h1 className="mb-4 text-xl font-bold">
                {isEditing ? `Edit ${station.code}` : 'New Station'}
            </h1>

            <div className="mb-4 flex gap-2 overflow-x-auto">
                {tabs.map((t) => (
                    <button
                        key={t.key}
                        type="button"
                        disabled={!isEditing && t.key !== 'details'}
                        onClick={() => setTab(t.key)}
                        className={cn(
                            'neu shadow-neu-sm shrink-0 rounded-[var(--radius-neu-pill)] px-4 py-2 text-sm font-bold disabled:opacity-40',
                            tab === t.key
                                ? 'bg-neu-primary-bright text-neu-on-primary-bright'
                                : 'text-neu-ink-muted',
                        )}
                    >
                        {t.label}
                    </button>
                ))}
            </div>

            {tab === 'details' && (
                <DetailsTab
                    station={station}
                    directionOptions={props.directionOptions}
                    statusOptions={props.statusOptions}
                />
            )}
            {tab === 'coordinates' && station.publicId && (
                <CoordinatesTab
                    stationPublicId={station.publicId}
                    coordinateSet={props.coordinateSet}
                />
            )}
            {tab === 'specification' && station.publicId && (
                <SpecificationTab
                    stationPublicId={station.publicId}
                    specification={props.specification}
                />
            )}
            {tab === 'photos' && station.publicId && (
                <PhotosTab
                    stationPublicId={station.publicId}
                    photos={props.photos}
                    photoTypeOptions={props.photoTypeOptions}
                />
            )}
            {tab === 'panorama' && station.publicId && (
                <PanoramaTab
                    stationPublicId={station.publicId}
                    panorama={props.panorama}
                />
            )}
            {tab === 'documents' && station.publicId && (
                <DocumentsTab
                    stationPublicId={station.publicId}
                    documents={props.documents}
                    documentTypeOptions={props.documentTypeOptions}
                />
            )}
        </AdminLayout>
    );
}
