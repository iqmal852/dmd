<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Station;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Caches the public dossier's per-station read models (coordinates,
 * specification, photo/document counts, formatted display strings — the
 * expensive-to-recompute part of each page, not the HTTP response or the
 * access-gate decision itself). See plan/phases/phase-09-hardening-release.md
 * M9.2.
 *
 * Deliberately a version-counter, not cache tags: this app's default cache
 * store (`database`, so it works the same in a Redis-less deployment) has
 * no tag support. Every admin write path that touches a station, its
 * coordinates, specification, or media calls `bump()` at the end of the
 * request; a page read builds its cache key from `current()`, so a bumped
 * version simply makes every previously-cached key for that station
 * unreachable rather than needing them individually deleted. Falls back
 * to a short TTL on the version key itself, so a version bump that never
 * happens (a bug, or a write path this misses) can't pin stale data
 * forever — never a gated response is cached, only the formatted station
 * data a route already decided the visitor may see.
 */
final readonly class StationCache
{
    private const int VERSION_TTL_SECONDS = 3600;

    private const int DATA_TTL_SECONDS = 300;

    /**
     * The callback may return a Data object (or an array containing one,
     * or several nested) — StationSummaryData, CoordinateSetData, and
     * friends. Cache::remember would serialize that as-is, but the
     * `database` cache store's config('cache.serializable_classes')
     * defaults to `false`, which makes its unserialize() refuse *any*
     * object class, anywhere in the structure — so a plain DTO,
     * uncached, works fine, then 500s on every subsequent (cache-hit)
     * request. json_decode(json_encode(...)) reduces the result to
     * plain arrays/scalars before it ever reaches the cache — the exact
     * same shape Inertia would send to the browser as JSON regardless,
     * since every Data class here holds only display-ready primitives
     * (see each class's own docblock) and every caller either passes the
     * result straight through to Inertia::render() or destructures it
     * with array access, never `instanceof` or `->property` on the
     * cached value itself.
     *
     * @param  Closure(): mixed  $callback
     */
    public static function remember(Station $station, string $key, Closure $callback): mixed
    {
        $cacheKey = sprintf(
            'station:%s:v%d:%s',
            $station->public_id,
            self::currentVersion($station),
            $key,
        );

        return Cache::remember(
            $cacheKey,
            self::DATA_TTL_SECONDS,
            fn () => json_decode(json_encode($callback()) ?: 'null', true),
        );
    }

    /**
     * Called at the end of every admin write to a station or anything
     * that belongs to it (coordinates, specification, photos, panorama,
     * documents) — see the Admin*Controller classes.
     */
    public static function bump(Station $station): void
    {
        $key = self::versionKey($station);

        // Cache::increment() on a driver where the key has never been set
        // is ambiguous (some drivers create it at 0 first, meaning this
        // bump would be silently absorbed) — Cache::add() only writes if
        // the key is genuinely absent, so the increment below always has
        // something real to act on. The version must only ever go up,
        // never reset: a reset could make a fresher write share a cache
        // key with stale data still sitting in the store under that same
        // now-reused version number.
        Cache::add($key, 1, self::VERSION_TTL_SECONDS);
        Cache::increment($key);
    }

    private static function currentVersion(Station $station): int
    {
        return (int) Cache::remember(
            self::versionKey($station),
            self::VERSION_TTL_SECONDS,
            fn () => 1,
        );
    }

    private static function versionKey(Station $station): string
    {
        return "station:{$station->public_id}:version";
    }
}
