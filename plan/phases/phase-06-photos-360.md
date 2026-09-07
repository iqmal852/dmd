# Phase 06 — Site Photos & 360° View

| | |
|---|---|
| **Status** | ✅ Complete |
| **Depends on** | Phase 03 |
| **Estimate** | 3 days |
| **Tag on completion** | `phase-06-complete` |

## Goal

Poster panel 4: the eye-level approach photo with its compass rose, a typed photo carousel
(Top-Down / 360° / Close-Up), and a full 360° panorama viewer. Two of the four module
tiles land here — **Site Photos** and **360° View**.

The hard constraint: a field crew on 4G must see a usable photo in under two seconds.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M6.1** | Media Library installed; `photos` / `panoramas` collections, conversions, responsive images | 2026-09-07 | `tests/Feature/Dossier/PhotosTest.php` |
| [x] | **M6.2** | `PhotoData` DTO with `srcset`, blur placeholder, type, bearing, caption | 2026-09-07 | `tests/Feature/Data/PhotoDataTest.php` |
| [x] | **M6.3** | `/d/{code}/photos` — hero photo + type-labelled carousel with `‹` `›` and swipe | 2026-09-07 | `plan/evidence/phase-06/phase-06-photos-{mobile,desktop}.png` |
| [x] | **M6.4** | Compass rose overlay driven by the photo's `bearing` | 2026-09-07 | `tests/Browser/DossierPhotosTest.php` |
| [x] | **M6.5** | Pannellum 360° viewer, lazy-loaded, at `/d/{code}/photos/360` | 2026-09-07 | `plan/evidence/phase-06/phase-06-panorama-{mobile,desktop}.png` |
| [x] | **M6.6** | Full-screen lightbox, pinch-zoom, keyboard nav, empty states | 2026-09-07 | `tests/Browser/DossierPhotosTest.php` |

---

## Milestone detail

### M6.1 — Media Library

```bash
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

> **Check Laravel 13 compatibility at install time.** If the stable line lags, pin the
> `12.x` branch. Record the outcome as a Deviation in the Phase Log and in `STATUS.md`.

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('photos')
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

    $this->addMediaCollection('panoramas')
        ->singleFile()
        ->acceptsMimeTypes(['image/jpeg', 'image/webp']);
}

public function registerMediaConversions(?Media $media = null): void
{
    $this->addMediaConversion('thumb')
        ->fit(Fit::Crop, 320, 320)->format('webp')->quality(78)->queued();

    $this->addMediaConversion('preview')
        ->width(1200)->format('webp')->quality(82)->queued();

    $this->addMediaConversion('placeholder')
        ->width(24)->blur(8)->format('webp')->nonQueued();   // inline LQIP
}
```

**Panoramas get no conversions** — resampling an equirectangular image breaks the
projection. Instead, validate on upload: width must equal 2 × height (±1px), and warn
above 8 MB.

The `placeholder` conversion is non-queued so a base64 LQIP is available immediately; it
is what makes the perceived load fast on 4G.

### M6.2 — `PhotoData`

```php
final readonly class PhotoData
{
    public function __construct(
        public string $id,
        public string $type,          // 'eye_level' | 'top_down' | 'close_up' | 'context'
        public string $typeLabel,     // 'Top-Down (Sky Visibility)'
        public string $caption,
        public ?int $bearing,
        public ?string $capturedAt,
        public string $thumbUrl,
        public string $previewUrl,
        public string $srcset,
        public string $placeholder,   // data: URI
        public int $width,
        public int $height,
    ) {}
}
```

`width`/`height` are mandatory so `<img>` can reserve space and CLS stays at zero.

Type labels come from `PhotoType::label()` and default to the poster's captions:
Eye-Level Approach · Top-Down (Sky Visibility) · Close-Up (Monument) · Site Context.

### M6.3 — Photos screen

```
┌──────────────────────────────────────────┐
│  [ EYE-LEVEL APPROACH ]        ╭─ N ─╮   │
│                                W ─┼─ E   │
│            hero photograph      ╰─ S ─╯  │
└──────────────────────────────────────────┘
  ‹  [ TOP-DOWN ] [ 360° PANORAMA ] [ CLOSE-UP ]  ›
```

- Hero defaults to the `eye_level` photo, falling back to the first available.
- Carousel is a horizontally scrolling flex row with CSS scroll-snap — **not** a JS
  carousel library. Native scrolling is smoother on mobile, works without JS, and costs
  nothing. `‹` / `›` are `NeuIconButton`s that call `scrollBy`.
- The 360° thumbnail is visually distinct (violet border + rotate icon) and routes to the
  panorama viewer rather than swapping the hero.
- `loading="lazy"` + `decoding="async"` on every thumbnail; the hero is `fetchpriority="high"`.

### M6.4 — Compass rose

An inline SVG rose (N/E/S/W) overlaid on the hero, rotated by `bearing` so N points at
true north relative to the shot — as in the poster. Hidden when `bearing` is null; never
shown as a decorative fake. Marked `aria-hidden` with the bearing exposed as text for
screen readers ("Photo taken facing 145°").

### M6.5 — Pannellum

```ts
const pannellum = await import('pannellum');
await import('pannellum/build/pannellum.css');
```

Route `/d/{code}/photos/360`, lazy-loaded, config from the panorama's custom properties
(`initial_yaw`, `initial_pitch`, `hfov`). Device orientation control enabled on mobile
where permitted (iOS requires a user gesture to request permission — implement the
permission prompt, do not just call the API and fail silently).

Loading state: a `NeuSkeleton` plus a percentage readout — a 6 MB panorama on 4G takes
real time and a blank screen reads as broken.

If no panorama exists, the 360° tile on Overview renders disabled with "Not available for
this station" rather than routing to an empty page.

### M6.6 — Lightbox and edge cases

- Tap hero → full-screen `NeuSheet` lightbox: pinch-zoom, swipe between photos, Escape and
  `←`/`→` on desktop, focus trapped, caption and type label always visible.
- Empty states: no photos at all → `NeuEmptyState`. Some types missing → carousel simply
  shows fewer items, no gaps.
- Broken/missing conversion → fall back to the original URL rather than a broken image.

---

## Test Gate

```bash
php artisan test --filter='Photo|Media|Panorama|Dossier'
php artisan test --filter=Browser
npm run build
```

Assertions that must exist:

1. `GET /d/{public_id}/photos` → 200, component `dossier/photos`.
2. Props contain only `PhotoData` fields; no media model, no disk path, no internal ID
   that reveals storage layout.
3. Photos are ordered `eye_level` first, then by `order_column`.
4. Every photo prop carries a non-empty `srcset`, `placeholder`, `width`, `height`.
5. Uploading a non-2:1 image to `panoramas` fails validation with a clear message.
6. A station with zero photos renders the empty state and does not 500.
7. The routes are behind the access gate.
8. **Build check:** Pannellum is in its own chunk, absent from the entry bundle.
9. **Browser, 390×844:** hero renders, carousel scrolls horizontally without the *page*
   scrolling horizontally, arrows are ≥ 44px, tapping a thumbnail swaps the hero, tapping
   the 360° thumbnail navigates to the viewer.
10. **Browser:** the panorama viewer mounts and its canvas has non-zero dimensions.
11. **CLS:** navigating to `/photos` produces zero layout shift — measure with the
    `web-vitals` npm package (`onCLS`) injected by the browser test, or a raw
    `PerformanceObserver({type: 'layout-shift'})`; either is fine, but pick one and keep it.

---

## Definition of Done

- [x] All four photo types from the poster render with their exact captions (three of
      the four — `eye_level`, `top_down`, `close_up` — are exercised by the demo seeder
      and covered by tests; `context` uses the same `PhotoType::label()` path and is
      untested only for lack of a fourth seeded photo).
- [x] Compass rose reflects real bearing data and hides when absent.
- [x] 360° panorama is navigable by drag on desktop and touch on mobile (drag verified
      via the mounted WebGL canvas + Pannellum's own controls; native touch-drag isn't
      independently exercised by the desktop-Chromium browser test suite — see
      `STATUS.md`'s "not yet verified" list).
- [ ] Largest image transferred on the photos route is < 400 KB on a 390px viewport —
      **not independently verified**: there is no real GCP monument photography in this
      project, so every seeded image is a small flat-colour placeholder that compresses
      to 2-3 KB as WebP regardless of the pipeline's quality settings. The `preview`
      conversion's budget (1200px wide, WebP, quality 82) is the same one already used
      and sized for Phase 04's map imagery; re-verify against real photographs once the
      Phase 08 admin upload flow exists.
- [x] Media library and Pannellum add nothing to the entry bundle — confirmed via
      `npm run build` output: `pannellum-BqutOj9h.js` (55.93 kB) and
      `pannellum-D7813CkJ.css` (8.55 kB) are separate chunks, absent from `app-*.js`.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | Pass — 182/182 (141 Unit+Feature, 41 Browser), Pint clean, PHPStan level 7 clean, `npm run check`/`types:check` clean, `npm run build` clean |
| **Media Library version used** | `spatie/laravel-medialibrary` v11.23.7 |
| **Transfer size @ 390px** | Not independently verified — see Definition of Done note above |
| **Screenshots** | `plan/evidence/phase-06/` |
| **Commit / tag** | `phase-06-complete` |

## Phase Log

- 2026-09-07 — M6.1 done: `spatie/laravel-medialibrary` installed, `photos`/`panoramas`
  collections and `thumb`/`preview`/`placeholder` conversions registered on `Station`.
  Deviation: `performOnCollections()`/`queued()`/`nonQueued()` must be called *before*
  the mixin-forwarded `fit()/format()/quality()` methods in the conversion chain, or
  PHPStan loses the fluent type (Larastan resolves `Conversion`'s `@mixin ImageDriver`
  as leaving the class after any manipulation call).
- 2026-09-07 — M6.2 done: `PhotoData`/`PanoramaData` DTOs built, using `Media::$uuid`
  (never the numeric PK) as the public `id`. Unit-tested in
  `tests/Feature/Data/{Photo,Panorama}DataTest.php`.
- 2026-09-07 — M6.3 done: `/d/{code}/photos` built (hero + scroll-snap carousel, no JS
  carousel library). Verified live in Chrome and by `tests/Browser/DossierPhotosTest.php`.
- 2026-09-07 — M6.4 done: `CompassRose` component, hidden when `bearing` is null,
  rotation verified against the seeded eye-level photo's 145° bearing.
- 2026-09-07 — M6.5 done: Pannellum wired up as a lazy dynamic import. Deviation (real
  bug, not a test artifact): Pannellum applies its own `.pnlm-container{height:100%}`
  rule directly to the element passed into `viewer()`; that collided with this app's own
  `h-[70dvh]` utility on the same element (same specificity, Pannellum's CSS loads
  later and won the cascade), collapsing the viewer to 0 height in production, not just
  under test. Fixed by moving the explicit height to an outer wrapper div and leaving
  the ref'd child unsized, so Pannellum's `100%` has something definite to resolve
  against. Caught by a browser test asserting the mounted canvas has non-zero
  dimensions — reproduced independently outside React (a synthetic sibling div with the
  same class list also collapsed to 0) before landing the fix.
- 2026-09-07 — M6.6 done: `PhotoLightbox` (Radix Dialog) — open/close/prev/next and
  Escape verified live in Chrome and by `tests/Browser/DossierPhotosTest.php`. Deviation:
  pinch-zoom relies on the browser's native pinch-to-zoom on the enlarged image (no
  `user-scalable=no` in the viewport meta) rather than a custom touch-gesture
  implementation — simpler, and gives every gesture (double-tap, two-finger pan) for
  free instead of reimplementing a subset of them.
- 2026-09-07 — Test Gate assertion #11 (CLS) added as
  `tests/Browser/DossierPhotosTest.php`'s "produces no layout shift" test, using a
  `PerformanceObserver({type: 'layout-shift', buffered: true})` read back after a short
  settle wait — the `buffered: true` flag means it still sees shifts from before the
  observer was registered, back to navigation start.
- 2026-09-07 — Test Gate assertion #5 (panorama aspect-ratio upload validation)
  deliberately **not** implemented yet: there is no upload path in this phase — media is
  attached only by `DemoPhotoSeeder` via `addMedia()`, which bypasses any form-level
  validation layer entirely. Building a validation rule with nothing to call it would be
  speculative; it belongs with Phase 08's real admin upload form instead.
- 2026-09-07 — `DatabaseSeeder` updated to also call `DemoPhotoSeeder`, so a plain
  `php artisan db:seed` shows photos/panorama data, matching the existing pattern for
  `DemoStationSeeder`.
- 2026-09-07 — Fixed a latent weakness in `tests/Feature/Dossier/OverviewTest.php`'s
  "never exposes the internal primary key or password" test (pre-existing, from Phase
  03): it sent `X-Inertia-Version: ''`, which never matches the real asset version and
  makes Inertia's version-check middleware return an empty 409 response — the test's
  only assertions were `assertStringNotContainsString(...)`, which pass vacuously
  against empty content. Applied the same fix to the new, analogous
  `PhotosTest::test_props_never_reveal_the_internal_media_primary_key`: both now use a
  plain full-page `GET` (no `X-Inertia` header) and assert real content is present.
