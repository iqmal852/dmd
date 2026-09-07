/** Mirrors App\Data\QuickViewData exactly. */
export type QuickView = {
    latitude: string;
    longitude: string;
    ellipsoidalHeight: string;
    easting: string;
    northing: string;
    orthometricHeight: string;
};

/** Mirrors App\Data\ModuleAvailability exactly. */
export type ModuleAvailability = {
    hasCoordinates: boolean;
    hasSpecification: boolean;
    photoCount: number;
    hasPanorama: boolean;
    documentCount: number;
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
    modules: ModuleAvailability;
};
