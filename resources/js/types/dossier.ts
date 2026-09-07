/** Mirrors App\Data\QuickViewData exactly. */
export type QuickView = {
    latitude: string;
    longitude: string;
    ellipsoidalHeight: string;
    easting: string;
    northing: string;
    orthometricHeight: string;
};

/** Mirrors App\Data\MapPreviewData exactly. */
export type MapPreview = {
    tileUrl: string;
    attribution: string;
    pinLeftPercent: number;
    pinTopPercent: number;
    kmBefore: string;
    kmAfter: string;
};

/** Mirrors App\Data\ModuleAvailability exactly. */
export type ModuleAvailability = {
    hasCoordinates: boolean;
    hasSpecification: boolean;
    photoCount: number;
    hasPanorama: boolean;
    documentCount: number;
};

/** Mirrors App\Data\CoordinateSetData exactly. */
export type CoordinateSet = {
    latitude: string;
    longitude: string;
    ellipsoidalHeight: string;
    easting: string;
    northing: string;
    zone: string | null;
    orthometricHeight: string;
    geoidModel: string;
    epoch: string | null;
    latitudeRaw: string;
    longitudeRaw: string;
};

/** Mirrors App\Data\SpecificationData exactly. */
export type Specification = {
    observationMethod: string | null;
    observationMinutes: string | null;
    satelliteCount: number | null;
    pdopMax: string | null;
    elevationCutoff: string | null;
    antennaType: string | null;
    antennaHeight: string | null;
    antennaReferencePoint: string | null;
    horizontalRms: string | null;
    verticalRms: string | null;
    qcStatus: string;
    qcStatusColor: string;
};

/** Mirrors App\Data\PhotoData exactly. */
export type Photo = {
    id: string;
    type: string;
    typeLabel: string;
    caption: string;
    bearing: number | null;
    capturedAt: string | null;
    thumbUrl: string;
    previewUrl: string;
    srcset: string;
    placeholder: string;
    width: number;
    height: number;
};

/** Mirrors App\Data\DocumentData exactly. */
export type Document = {
    id: string;
    type: string;
    typeLabel: string;
    title: string;
    revision: string | null;
    extension: string;
    size: string;
    isPrimary: boolean;
    previewUrl: string | null;
    previewKind: 'pdf' | 'image' | 'none';
    downloadUrl: string;
};

/** Mirrors App\Data\StationMapData exactly. */
export type StationMap = {
    code: string;
    latitude: number;
    longitude: number;
    highway: string;
    km: string;
    direction: string;
    section: string | null;
    monumentType: string;
    installedAt: string | null;
};

/** Mirrors App\Data\StationSummaryData exactly. */
export type StationSummary = {
    publicId: string;
    code: string;
    highway: string;
    section: string | null;
    km: string;
    direction: string;
    monumentType: string;
    installedAt: string | null;
    status: string;
    statusColor: string;
    quickView: QuickView | null;
    mapPreview: MapPreview | null;
    modules: ModuleAvailability;
};
