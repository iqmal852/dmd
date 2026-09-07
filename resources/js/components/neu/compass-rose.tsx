export type CompassRoseProps = {
    bearing: number;
};

/**
 * Overlaid on the eye-level hero photo, rotated so N points at true north
 * relative to the shot — Picture1.png panel 4. Hidden entirely when a
 * photo has no bearing rather than shown as a decorative fake; see
 * plan/phases/phase-06-photos-360.md M6.4.
 */
export function CompassRose({ bearing }: CompassRoseProps) {
    return (
        <div
            className="bg-neu-surface/85 shadow-neu-sm relative flex size-16 items-center justify-center rounded-full backdrop-blur-sm"
            aria-hidden="true"
        >
            <div
                className="relative size-11"
                style={{ transform: `rotate(${-bearing}deg)` }}
            >
                <span className="text-neu-ink absolute top-0 left-1/2 -translate-x-1/2 text-[0.6rem] font-bold">
                    N
                </span>
                <span className="text-neu-ink-muted absolute bottom-0 left-1/2 -translate-x-1/2 text-[0.6rem] font-bold">
                    S
                </span>
                <span className="text-neu-ink-muted absolute top-1/2 left-0 -translate-y-1/2 text-[0.6rem] font-bold">
                    W
                </span>
                <span className="text-neu-ink-muted absolute top-1/2 right-0 -translate-y-1/2 text-[0.6rem] font-bold">
                    E
                </span>
                <svg viewBox="0 0 44 44" className="absolute inset-0">
                    <line
                        x1="22"
                        y1="8"
                        x2="22"
                        y2="36"
                        stroke="currentColor"
                        strokeWidth="1"
                        className="text-neu-ink-subtle"
                    />
                    <line
                        x1="8"
                        y1="22"
                        x2="36"
                        y2="22"
                        stroke="currentColor"
                        strokeWidth="1"
                        className="text-neu-ink-subtle"
                    />
                    <circle
                        cx="22"
                        cy="22"
                        r="3"
                        className="fill-neu-primary-bright"
                    />
                </svg>
            </div>
        </div>
    );
}
