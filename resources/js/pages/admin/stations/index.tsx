import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowUpDown,
    Camera,
    FileText,
    MapPin,
    Plus,
    QrCode,
    RotateCw,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { NeuButton } from '@/components/neu/neu-button';
import { NeuCard } from '@/components/neu/neu-card';
import { NeuInput } from '@/components/neu/neu-input';
import { NeuPill } from '@/components/neu/neu-pill';
import { NeuSelect } from '@/components/neu/neu-select';
import { NeuToggle } from '@/components/neu/neu-toggle';
import AdminLayout from '@/layouts/admin/admin-layout';
import { create, edit, index, publish, qr } from '@/routes/admin/stations';
import { show } from '@/routes/dossier';
import type { AdminStationListItem, Option, Paginated } from '@/types/admin';
import type { NeuPillTone } from '@/components/neu/neu-pill';

type PageProps = {
    stations: Paginated<AdminStationListItem>;
    filters: { search: string; highway: string; status: string; sort: string };
    highways: string[];
    statusOptions: Option[];
};

function MediaBadges({ station }: { station: AdminStationListItem }) {
    return (
        <div className="text-neu-ink-muted flex items-center gap-3 text-xs">
            <span
                className={
                    station.hasCoordinates ? 'text-neu-accent' : undefined
                }
                title="Coordinates"
            >
                <MapPin className="size-4" />
            </span>
            <span className="flex items-center gap-0.5">
                <Camera className="size-4" />
                {station.photoCount}
            </span>
            <span
                className={station.hasPanorama ? 'text-neu-violet' : undefined}
                title="360° panorama"
            >
                <RotateCw className="size-4" />
            </span>
            <span className="flex items-center gap-0.5">
                <FileText className="size-4" />
                {station.documentCount}
            </span>
        </div>
    );
}

/**
 * plan/phases/phase-08-admin-qr.md M8.2.
 */
export default function AdminStationIndex() {
    const { stations, filters, highways, statusOptions } =
        usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);

    function applyFilters(next: Partial<typeof filters>) {
        router.get(
            index().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function togglePublished(station: AdminStationListItem, checked: boolean) {
        router.patch(
            publish({ station: station.publicId }).url,
            { is_published: checked },
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast(
                        checked ? 'Station published' : 'Station unpublished',
                    ),
            },
        );
    }

    function sortLink(column: string) {
        return (
            <button
                type="button"
                onClick={() => applyFilters({ sort: column })}
                className={`inline-flex items-center gap-1 ${filters.sort === column ? 'text-neu-ink' : 'text-neu-ink-muted'}`}
            >
                {column.toUpperCase()}
                <ArrowUpDown className="size-3" />
            </button>
        );
    }

    return (
        <AdminLayout active="stations">
            <Head title="Stations" />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-xl font-bold">Stations</h1>
                <Link href={create().url}>
                    <NeuButton variant="primary" className="gap-2">
                        <Plus className="size-4" />
                        New Station
                    </NeuButton>
                </Link>
            </div>

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <NeuInput
                    placeholder="Search by code…"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            applyFilters({ search });
                        }
                    }}
                    onBlur={() => applyFilters({ search })}
                />
                <NeuSelect
                    value={filters.highway}
                    onChange={(event) =>
                        applyFilters({ highway: event.target.value })
                    }
                >
                    <option value="">All highways</option>
                    {highways.map((highway) => (
                        <option key={highway} value={highway}>
                            {highway}
                        </option>
                    ))}
                </NeuSelect>
                <NeuSelect
                    value={filters.status}
                    onChange={(event) =>
                        applyFilters({ status: event.target.value })
                    }
                >
                    <option value="">All statuses</option>
                    {statusOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </NeuSelect>
            </div>

            {/* Desktop table */}
            <NeuCard className="hidden overflow-x-auto p-0 md:block">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="text-neu-ink-muted border-b border-black/5 text-xs tracking-wide uppercase">
                            <th className="px-4 py-3">{sortLink('code')}</th>
                            <th className="px-4 py-3">{sortLink('highway')}</th>
                            <th className="px-4 py-3">{sortLink('km')}</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Media</th>
                            <th className="px-4 py-3">Published</th>
                            <th className="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {stations.data.map((station) => (
                            <tr
                                key={station.publicId}
                                className="border-b border-black/5 last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {station.code}
                                </td>
                                <td className="px-4 py-3">{station.highway}</td>
                                <td className="px-4 py-3">{station.km}</td>
                                <td className="px-4 py-3">
                                    <NeuPill
                                        tone={
                                            station.statusColor as NeuPillTone
                                        }
                                    >
                                        {station.status}
                                    </NeuPill>
                                </td>
                                <td className="px-4 py-3">
                                    <MediaBadges station={station} />
                                </td>
                                <td className="px-4 py-3">
                                    <NeuToggle
                                        checked={station.isPublished}
                                        onChange={(checked) =>
                                            togglePublished(station, checked)
                                        }
                                        aria-label={`Publish ${station.code}`}
                                    />
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-3 text-xs font-bold">
                                        <Link
                                            href={
                                                edit({
                                                    station: station.publicId,
                                                }).url
                                            }
                                            className="text-neu-link"
                                        >
                                            Edit
                                        </Link>
                                        <Link
                                            href={
                                                qr({
                                                    station: station.publicId,
                                                }).url
                                            }
                                            className="text-neu-link inline-flex items-center gap-1"
                                        >
                                            <QrCode className="size-3.5" />
                                            QR
                                        </Link>
                                        <Link
                                            href={
                                                show({
                                                    station: station.publicId,
                                                }).url
                                            }
                                            className="text-neu-ink-muted"
                                        >
                                            View
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </NeuCard>

            {/* Mobile cards */}
            <div className="space-y-3 md:hidden">
                {stations.data.map((station) => (
                    <NeuCard key={station.publicId} className="space-y-3 p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="font-bold">{station.code}</p>
                                <p className="text-neu-ink-muted text-xs">
                                    {station.highway} · KM {station.km}
                                </p>
                            </div>
                            <NeuPill tone={station.statusColor as NeuPillTone}>
                                {station.status}
                            </NeuPill>
                        </div>
                        <MediaBadges station={station} />
                        <div className="flex items-center justify-between">
                            <NeuToggle
                                checked={station.isPublished}
                                onChange={(checked) =>
                                    togglePublished(station, checked)
                                }
                                aria-label={`Publish ${station.code}`}
                            />
                            <div className="flex items-center gap-4 text-sm font-bold">
                                <Link
                                    href={
                                        edit({ station: station.publicId }).url
                                    }
                                    className="text-neu-link"
                                >
                                    Edit
                                </Link>
                                <Link
                                    href={qr({ station: station.publicId }).url}
                                    className="text-neu-link"
                                >
                                    QR
                                </Link>
                            </div>
                        </div>
                    </NeuCard>
                ))}
            </div>

            {stations.data.length === 0 && (
                <p className="text-neu-ink-muted py-12 text-center text-sm">
                    No stations match these filters.
                </p>
            )}

            {stations.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm">
                    <NeuButton
                        variant="ghost"
                        disabled={!stations.prev_page_url}
                        onClick={() =>
                            stations.prev_page_url &&
                            router.get(stations.prev_page_url)
                        }
                    >
                        Previous
                    </NeuButton>
                    <span className="text-neu-ink-muted">
                        Page {stations.current_page} of {stations.last_page}
                    </span>
                    <NeuButton
                        variant="ghost"
                        disabled={!stations.next_page_url}
                        onClick={() =>
                            stations.next_page_url &&
                            router.get(stations.next_page_url)
                        }
                    >
                        Next
                    </NeuButton>
                </div>
            )}
        </AdminLayout>
    );
}
