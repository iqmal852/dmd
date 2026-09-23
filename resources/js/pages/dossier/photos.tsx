import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, RotateCw } from 'lucide-react';
import { useRef, useState } from 'react';
import { CompassRose } from '@/components/neu/compass-rose';
import { NeuEmptyState } from '@/components/neu/neu-empty-state';
import { NeuIconButton } from '@/components/neu/neu-icon-button';
import { PhotoLightbox } from '@/components/dossier/photo-lightbox';
import DossierLayout from '@/layouts/dossier/dossier-layout';
import { buildDossierNavItems } from '@/lib/dossier-nav';
import { method360 as photos360 } from '@/routes/dossier/photos';
import type { Photo } from '@/types/dossier';

type PageProps = {
    stationPublicId: string;
    stationCode: string;
    photos: Photo[];
    hasPanorama: boolean;
};

/**
 * Picture1.png panel 4 — Site Photos & 360° View. See
 * plan/phases/phase-06-photos-360.md M6.3/M6.4/M6.6.
 */
export default function DossierPhotos() {
    const { branding } = usePage().props;
    const { stationPublicId, stationCode, photos, hasPanorama } =
        usePage<PageProps>().props;
    const carouselRef = useRef<HTMLDivElement>(null);
    const [heroIndex, setHeroIndex] = useState(0);
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

    const hero = photos[heroIndex];

    function scrollCarousel(direction: -1 | 1) {
        carouselRef.current?.scrollBy({
            left: direction * 160,
            behavior: 'smooth',
        });
    }

    if (photos.length === 0 && !hasPanorama) {
        return (
            <DossierLayout
                operator={branding.operator}
                title={branding.title}
                navItems={buildDossierNavItems(stationPublicId, 'photos')}
            >
                <Head title={`Photos — ${stationCode}`} />
                <NeuEmptyState message="No site photos have been uploaded for this station yet." />
            </DossierLayout>
        );
    }

    return (
        <DossierLayout
            operator={branding.operator}
            title={branding.title}
            navItems={buildDossierNavItems(stationPublicId, 'photos')}
        >
            <Head title={`Photos — ${stationCode}`} />

            <div className="space-y-4">
                {hero && (
                    <button
                        type="button"
                        className="shadow-neu-md relative block w-full overflow-hidden rounded-[var(--radius-neu-lg)]"
                        onClick={() => setLightboxIndex(heroIndex)}
                    >
                        <span className="bg-neu-surface/90 text-neu-ink absolute top-3 left-3 z-10 rounded-[var(--radius-neu-pill)] px-3 py-1 text-xs font-bold tracking-wide uppercase">
                            {hero.label}
                        </span>

                        {hero.bearing !== null && (
                            <span className="absolute top-3 right-3 z-10">
                                <CompassRose bearing={hero.bearing} />
                                <span className="sr-only">
                                    Photo taken facing {hero.bearing}°
                                </span>
                            </span>
                        )}

                        <img
                            src={hero.previewUrl}
                            srcSet={hero.srcset}
                            alt={hero.label}
                            width={hero.width}
                            height={hero.height}
                            style={{
                                backgroundImage: `url(${hero.placeholder})`,
                                backgroundSize: 'cover',
                            }}
                            className="aspect-4/3 w-full object-cover"
                            fetchPriority="high"
                            decoding="async"
                        />
                    </button>
                )}

                <div className="flex items-center gap-2">
                    <NeuIconButton
                        aria-label="Scroll thumbnails left"
                        onClick={() => scrollCarousel(-1)}
                    >
                        <ChevronLeft className="size-5" />
                    </NeuIconButton>

                    <div
                        ref={carouselRef}
                        className="flex flex-1 snap-x gap-3 overflow-x-auto scroll-smooth pb-1"
                    >
                        {photos.map((photo, i) => (
                            <button
                                key={photo.id}
                                type="button"
                                onClick={() => setHeroIndex(i)}
                                className="shadow-neu-sm relative shrink-0 snap-start overflow-hidden rounded-[var(--radius-neu-md)]"
                            >
                                <img
                                    src={photo.thumbUrl}
                                    alt={photo.label}
                                    loading="lazy"
                                    decoding="async"
                                    className="size-20 object-cover"
                                />
                                <span className="bg-neu-surface/90 text-neu-ink absolute inset-x-0 bottom-0 truncate px-1 py-0.5 text-center text-[0.6rem] font-bold">
                                    {photo.label}
                                </span>
                            </button>
                        ))}

                        {hasPanorama && (
                            <Link
                                href={
                                    photos360({ station: stationPublicId }).url
                                }
                                className="border-neu-violet bg-neu-violet text-neu-on-violet shadow-neu-sm relative flex size-20 shrink-0 snap-start flex-col items-center justify-center gap-1 rounded-[var(--radius-neu-md)] border-2"
                            >
                                <RotateCw className="size-5" />
                                <span className="text-[0.6rem] font-bold">
                                    360°
                                </span>
                            </Link>
                        )}
                    </div>

                    <NeuIconButton
                        aria-label="Scroll thumbnails right"
                        onClick={() => scrollCarousel(1)}
                    >
                        <ChevronRight className="size-5" />
                    </NeuIconButton>
                </div>
            </div>

            {lightboxIndex !== null && (
                <PhotoLightbox
                    photos={photos}
                    index={lightboxIndex}
                    onIndexChange={setLightboxIndex}
                    onClose={() => setLightboxIndex(null)}
                />
            )}
        </DossierLayout>
    );
}
