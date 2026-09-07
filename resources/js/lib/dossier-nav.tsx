import { Camera, FileText, MapPin, Home } from 'lucide-react';
import { coordinates, photos, show } from '@/routes/dossier';
import type { NeuBottomNavItem } from '@/components/neu/neu-bottom-nav';

export type DossierSection = 'overview' | 'coordinates' | 'files' | 'photos';

/**
 * Builds the persistent Overview/Coordinates/Files/Photos bar items shared
 * by every dossier page. Files has no `href` yet — its route lands in
 * Phase 07 — so it renders disabled, same convention as NeuTile. See
 * plan/phases/phase-04-coordinates-specs.md M4.5.
 */
export function buildDossierNavItems(
    stationPublicId: string,
    active: DossierSection,
): NeuBottomNavItem[] {
    return [
        {
            key: 'overview',
            label: 'Overview',
            icon: <Home className="size-5" />,
            href: show({ station: stationPublicId }).url,
            active: active === 'overview',
        },
        {
            key: 'coordinates',
            label: 'Coordinates',
            icon: <MapPin className="size-5" />,
            href: coordinates({ station: stationPublicId }).url,
            active: active === 'coordinates',
        },
        {
            key: 'files',
            label: 'Files',
            icon: <FileText className="size-5" />,
            active: active === 'files',
        },
        {
            key: 'photos',
            label: 'Photos',
            icon: <Camera className="size-5" />,
            href: photos({ station: stationPublicId }).url,
            active: active === 'photos',
        },
    ];
}
