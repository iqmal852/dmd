# Phase 06 — Site Photos & 360° View

| | |
|---|---|
| **Status** | ⬜ Not started |
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
| [ ] | **M6.1** | Media Library installed; `photos` / `panoramas` collections, conversions, responsive images | | |
| [ ] | **M6.2** | `PhotoData` DTO with `srcset`, blur placeholder, type, bearing, caption | | |
| [ ] | **M6.3** | `/d/{code}/photos` — hero photo + type-labelled carousel with `‹` `›` and swipe | | |
| [ ] | **M6.4** | Compass rose overlay driven by the photo's `bearing` | | |
| [ ] | **M6.5** | Pannellum 360° viewer, lazy-loaded, at `/d/{code}/photos/360` | | |
| [ ] | **M6.6** | Full-screen lightbox, pinch-zoom, keyboard nav, empty states | | |

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

- [ ] All four photo types from the poster render with their exact captions.
- [ ] Compass rose reflects real bearing data and hides when absent.
- [ ] 360° panorama is navigable by drag on desktop and touch on mobile.
- [ ] Largest image transferred on the photos route is < 400 KB on a 390px viewport.
- [ ] Media library and Pannellum add nothing to the entry bundle.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | |
| **Result** | |
| **Media Library version used** | |
| **Transfer size @ 390px** | |
| **Screenshots** | `plan/evidence/phase-06/` |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
