/**
 * pannellum ships no TypeScript definitions and attaches a plain global
 * (`window.pannellum`) rather than exporting anything — see
 * plan/phases/phase-06-photos-360.md M6.5. This declares just the surface
 * this app actually uses.
 */
export type PannellumViewerConfig = {
    type: 'equirectangular';
    panorama: string;
    autoLoad?: boolean;
    yaw?: number;
    pitch?: number;
    hfov?: number;
    compass?: boolean;
    showZoomCtrl?: boolean;
    showFullscreenCtrl?: boolean;
    orientationOnByDefault?: boolean;
};

export type PannellumViewer = {
    destroy: () => void;
    isLoaded: () => boolean;
    on: (
        event: string,
        callback: (...args: unknown[]) => void,
    ) => PannellumViewer;
    startOrientation: () => void;
    isOrientationSupported: () => boolean;
};

declare global {
    interface Window {
        pannellum?: {
            viewer: (
                container: HTMLElement | string,
                config: PannellumViewerConfig,
            ) => PannellumViewer;
        };
    }
}
