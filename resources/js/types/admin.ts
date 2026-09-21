/** Mirrors App\Data\Admin\StationListItemData exactly. */
export type AdminStationListItem = {
    publicId: string;
    code: string;
    highway: string;
    section: string | null;
    km: string;
    status: string;
    statusColor: string;
    isPublished: boolean;
    hasCoordinates: boolean;
    photoCount: number;
    hasPanorama: boolean;
    documentCount: number;
};

export type Option = { value: string; label: string };

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

/** Mirrors App\Data\Admin\StationFormData exactly. */
export type AdminStationForm = {
    publicId: string | null;
    code: string;
    gcpReference: string | null;
    highway: string;
    section: string | null;
    location: string | null;
    km: string;
    direction: string;
    facilityType: string | null;
    monumentType: string;
    installedAt: string | null;
    status: string;
    description: string | null;
    hasAccessPassword: boolean;
    isPublished: boolean;
};

/** Mirrors App\Data\Admin\CoordinateSetFormData exactly. */
export type AdminCoordinateSet = {
    latitude: string;
    longitude: string;
    ellipsoidalHeight: string;
    easting: string;
    northing: string;
    zone: string | null;
    orthometricHeight: string;
    geoidModel: string;
    epoch: string | null;
    computedAt: string | null;
};

/** Mirrors App\Data\Admin\SpecificationFormData exactly. */
export type AdminSpecification = {
    observationMethod: string | null;
    observationMinutes: number | null;
    satelliteCount: number | null;
    pdopMax: string | null;
    elevationCutoffDeg: number | null;
    antennaType: string | null;
    antennaHeight: string | null;
    antennaReferencePoint: string | null;
    horizontalRmsMm: string | null;
    verticalRmsMm: string | null;
    qcStatus: string;
    verifiedAt: string | null;
    verifiedBy: string | null;
    remarks: string | null;
};

/** Mirrors App\Data\Admin\PhotoFormData exactly. */
export type AdminPhoto = {
    id: string;
    type: string;
    bearing: number | null;
    caption: string | null;
    thumbUrl: string;
};

/** Mirrors App\Data\Admin\PanoramaFormData exactly. */
export type AdminPanorama = {
    id: string;
    url: string;
    initialYaw: number;
    initialPitch: number;
    hfov: number;
};

/** Mirrors App\Data\Admin\DocumentFormData exactly. */
export type AdminDocument = {
    id: string;
    documentType: string;
    title: string;
    revision: string | null;
    isPrimary: boolean;
    extension: string;
    size: string;
    hasPreview: boolean;
};

/** Mirrors App\Data\Admin\PlateData exactly. */
export type AdminPlate = {
    publicId: string;
    code: string;
    highway: string;
    qrDataUri: string;
};
