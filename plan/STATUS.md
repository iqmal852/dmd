# Build Status Board

**Last updated:** 2026-09-08
**Current phase:** Phase 08 complete. Phase 09 (Hardening & Release) starting next.

Update this file every time a milestone is completed. See §3 of [`README.md`](README.md).

| Phase | Title | Milestones | Status | Gate passed | Tag |
|:-----:|-------|:----------:|--------|:-----------:|-----|
| 00 | [Foundation & Tooling](phases/phase-00-foundation.md) | 7/7 | ✅ Complete | 2026-09-07 | `phase-00-complete` |
| 01 | [Data Model & Domain](phases/phase-01-data-model.md) | 6/6 | ✅ Complete | 2026-09-07 | `phase-01-complete` |
| 02 | [Neumorphism Design System](phases/phase-02-design-system.md) | 4/6 (2 ongoing) | 🟡 In progress (deliberately spans through Phase 08) | — | — |
| 03 | [Dossier Shell & Access Control](phases/phase-03-dossier-shell.md) | 7/7 | ✅ Complete (2 DoD items unverified — no physical device) | 2026-09-07 | — |
| 04 | [Coordinates & Specs Modules](phases/phase-04-coordinates-specs.md) | 5/5 | ✅ Complete | 2026-09-07 | — |
| 05 | [Location Map](phases/phase-05-location-map.md) | 5/5 | ✅ Complete | 2026-09-07 | — |
| 06 | [Site Photos & 360°](phases/phase-06-photos-360.md) | 6/6 | ✅ Complete (1 DoD item unverified — no real photography) | 2026-09-07 | `phase-06-complete` |
| 07 | [As-Built Drawings & Downloads](phases/phase-07-asbuilt-files.md) | 5/5 | ✅ Complete (1 DoD item unverified — no real DWG/CAD software) | 2026-09-07 | `phase-07-complete` |
| 08 | [Admin Console & QR Generation](phases/phase-08-admin-qr.md) | 7/8 (1 optional, skipped) | ✅ Complete (1 DoD item unverified — no printer/phone) | 2026-09-08 | `phase-08-complete` |
| 09 | [Hardening & Release](phases/phase-09-hardening-release.md) | 0/7 | ⬜ Not started | — | — |

**Legend:** ⬜ Not started · 🟡 In progress · 🔴 Blocked · ✅ Complete

**Total: 50 / 62 milestones** (Phase 02's M2.5/M2.6 will be re-counted once M2.3/M2.4 fully land in later phases) — 60 required, 2 fully `[OPTIONAL]` (M5.5, M8.8). Dark theme (M2.1), station switcher (M4.2) and clipboard (M4.4) are optional *parts* of otherwise required milestones.

---

## Open blockers

_None._ Phase 02 is intentionally left open (M2.3/M2.4 deferred, see its phase file's
"build just-in-time" rule) — this is not a blocker, it's the plan working as designed.
Phase 03 built `NeuTile`, `MetaChip`, and `DossierLayout` just-in-time as it needed
them; `NeuBottomNav` and the form/feedback primitives (M2.4) are still owed and will
land in Phase 04+.

**Not yet verified, flagged for before the QR plates are printed:** a real phone
scanning a real QR code, and the unlock flow inside WhatsApp/Telegram/WeChat/Facebook
in-app browsers on iOS and Android. No physical device is available in this
development environment — coverage today is a real-Chromium browser test suite
hitting the exact same URLs, which is a good proxy but not a substitute for the real
thing.

**Copy-to-clipboard (Phase 04/05, optional)** works when verified by hand in Chrome (a
"copied" toast appears and the OS clipboard receives the bare value), but the
automated browser test can only confirm the click handler runs without erroring —
Chromium's automation clipboard-permission model doesn't grant clipboard-write the
way a real user session does, so the test can't independently confirm the OS
clipboard content.

**"Navigate to station" (Phase 05, optional)** — the URL construction and button are
built and verified live in Chrome, but opening an actual native map app (Apple Maps /
Google Maps) cannot be exercised from a desktop browser and needs a real device to
fully confirm, same constraint as the QR/in-app-browser items above.

**360° panorama touch-drag (Phase 06)** — Pannellum's own drag/touch handling is
exercised indirectly (the WebGL canvas mounts with non-zero dimensions and no console
errors), but touch-drag gesture navigation itself isn't independently driven by the
desktop-Chromium browser test suite, same constraint as the other real-device items
above.

**Photo transfer size budget (Phase 06)** — the Definition of Done's "< 400 KB largest
image @ 390px" target still cannot be verified against real content: there is no GCP
monument photography available in this project, so every seeded photo is a flat-colour
GD placeholder that compresses to 2-3 KB as WebP regardless of pipeline settings.
Phase 08's admin upload flow now exists and would let a real photograph through the
same `preview` conversion — re-verify once one is actually uploaded.

**DWG round-trip (Phase 07)** — "a downloaded DWG opens correctly in CAD software" is
not verified: there is no real as-built survey drawing or CAD software available in
this environment. The seeded "DWG" is arbitrary bytes with a `.dwg` extension, enough
to exercise the pairing/download-headers logic but not a genuine file round-trip.
Verify with a real drawing before the QR plates are printed.

**Panorama aspect-ratio upload validation (Phase 06) and DWG-without-preview upload
rejection (Phase 07)** — now implemented in Phase 08's admin upload forms
(`StorePanoramaRequest`'s `dimensions:ratio=2/1` rule and `StoreDocumentRequest`'s
preview-required check). This item is resolved; kept here only as a pointer to
where the enforcement actually landed.

**Printed QR scan (Phase 08)** — "a generated QR scans successfully from a printed
sheet with a real phone" is not verified: no printer or phone is available in this
development environment. `khanamiryan/qrcode-detector-decoder` decodes the
generated PNG byte-for-byte back to the expected URL
(`tests/Feature/Admin/AdminQrTest.php`), the strongest available proxy, but a real
print-and-scan is a required manual check before the QR plates are ordered — same
category as the other real-device items above.

---

## Deviations from plan

| Date | Phase | Deviation | Why |
|------|-------|-----------|-----|
| 2026-09-07 | 00 | Local Postgres via Homebrew service, not `docker-compose.yml` | Docker daemon wasn't running in the dev environment; `docker-compose.yml` is still worth adding later for cross-machine parity, tracked as follow-up |
| 2026-09-07 | 00 | Kept Fortify's 2FA + passkeys (removed only registration/reset/verification, as planned) | Laravel 13's current starter kit ships them enabled by default; they're additive opt-in hardening for the single admin, not a requirement violation, and removing them means dropping migrations/columns for no stated need |
| 2026-09-07 | 00 | Quality tooling is the starter kit's own `composer test` + `vp check` (vite-plus), not hand-rolled ESLint/Prettier scripts | This is what Laravel's official starter kit ships today; layering a second toolchain on top would be redundant |
| 2026-09-07 | 00 | Larastan already at level 7, not the planned level 6 | Starter kit default; left as-is since it's strictly ahead of plan and closer to Phase 09's level-8 target |
| 2026-09-07 | 01 | Only `StationStatus`/`QcStatus` implement `HasColor`, not all six enums | `02-data-model.md` §8 (color only "where the UI shows a coloured pill") is more precise than this phase's milestone-table wording and was followed |
| 2026-09-07 | 01 | `download_logs.media_id` has no FK constraint yet | `spatie/laravel-medialibrary`'s `media` table doesn't exist until Phase 06/07; added as a follow-up migration then |
| 2026-09-07 | 01 | Removed `WithoutModelEvents` from `DatabaseSeeder` | It silently suppressed the `Station` model's ULID-assignment event, causing a `NOT NULL` violation on first seed — a real bug caught immediately by running the seeder, not a stylistic choice |
| 2026-09-07 | 02 | All Neumorphism tokens namespaced `--neu-*`/`--color-neu-*`; components live in `components/neu/`, not `components/ui/` | The starter kit already owns the shadcn token names and the `ui/` folder for the pre-existing auth/settings pages; namespacing avoids silently breaking them |
| 2026-09-07 | 02 | Several poster-literal colours adjusted (danger hex, and per-colour text foreground instead of uniform white) | White text on the poster's own green/amber/cyan fails AA outright (2.3-3.0:1); computed real contrast ratios before committing to any hex |
| 2026-09-07 | 02 | Installed `pestphp/pest-plugin-browser` + Playwright + Chromium, and added a `Browser` testsuite to `phpunit.xml` | Needed for real-browser a11y/overflow/tap-target testing; `phpunit.xml` had no `Browser` suite at all, so browser tests were silently not running under `composer test` — fixed alongside |
| 2026-09-07 | 02 | Dark theme built now rather than left `[OPTIONAL]` | The starter kit's existing `.dark` toggle made it nearly free to wire up alongside the light theme |
| 2026-09-07 | 03 | Unlock redirect uses a validated `redirect` query/form field, not session-flashed `intended_url` | Flash data survives exactly one request; this flow is GET-then-POST, so the flash would already be gone by the time the form submits |
| 2026-09-07 | 03 | `GeoFormatter::installedDate()` widened to accept `CarbonInterface`, not just `Carbon` | `Date::use(CarbonImmutable::class)` (already in the starter kit) means every Eloquent date cast is actually a `CarbonImmutable` — a real TypeError caught by manually curling the route before any test existed |
| 2026-09-07 | 03 | Module tiles (`NeuTile`) render with no `href` for now | Coordinates/Map/Photos/Files routes don't exist until Phases 04-07; each phase adds its own tile's real link when its route lands |
| 2026-09-07 | 04 | Copy-to-clipboard scoped to lat/lon only, no "copy all", no `execCommand` fallback | Every other value on the Coordinates screen has no raw/display distinction worth stripping; reused the starter kit's existing `useClipboard` hook + `sonner` toaster instead of new infrastructure |
| 2026-09-07 | 04 | `NeuBottomNav` is a fixed bottom bar at all breakpoints, no desktop top-tab variant (`NeuTabs`) yet | M4.5 only requires `NeuBottomNav`; `NeuTabs` is a separate, still-unbuilt component deferred as a later polish |
| 2026-09-07 | 04 | Station-switcher chevron (M4.2, optional) skipped | Row renders as static text, matching the plan's own stated fallback for skipping it |
| 2026-09-07 | 05 | Layer toggle uses two persistent tile layers + add/remove, not `setUrl()` on one | `setUrl()` doesn't update Leaflet's attribution control text — would have silently broken the "correct attribution per layer" requirement |
| 2026-09-07 | 05 | M5.4's tile math cross-checked against an independent Python implementation | A first-draft test used a guessed expected tile coordinate that was wrong; ground truth was computed a second way before being trusted |
| 2026-09-07 | 05 | M5.5 ("Navigate here") built in full rather than left optional-and-skipped | Cheap to add given Phase 04's clipboard infrastructure already existed; directly serves the field-crew persona the app is for |
| 2026-09-07 | 06 | Conversion chain orders `performOnCollections()`/`queued()`/`nonQueued()` before `fit()/format()/quality()` | Larastan resolves `Conversion`'s `@mixin ImageDriver` as leaving the fluent `Conversion` type after any manipulation call is chained first; reordering keeps the native methods' return type intact |
| 2026-09-07 | 06 | Pannellum's viewer container has its explicit height on an outer wrapper div, not the ref'd element Pannellum takes over | Pannellum's own CSS sets `.pnlm-container{height:100%}` directly on the element passed to `viewer()`; that collided with this app's `h-[70dvh]` utility on the same element (equal specificity, Pannellum's CSS loads later and wins), collapsing the viewer to 0 height — a real production bug, reproduced independently of React before being fixed |
| 2026-09-07 | 06 | Pinch-zoom in the photo lightbox relies on native browser pinch-to-zoom, not a custom gesture handler | Simpler, and free coverage for double-tap/two-finger-pan too, at the cost of not being a bespoke in-app zoom UI |
| 2026-09-07 | 06 | Test Gate assertion #5 (panorama aspect-ratio upload validation) not implemented | No upload path exists yet in this phase — media is attached only via the seeder's `addMedia()`, bypassing form validation entirely; belongs with Phase 08's real admin upload form |
| 2026-09-07 | 06 | `DatabaseSeeder` now also calls `DemoPhotoSeeder` | It existed but wasn't wired in; without it, a plain `db:seed` showed no photos/panorama despite the seeder being fully built |
| 2026-09-07 | 06 | Fixed a latent weakness in Phase 03's `OverviewTest` (empty `X-Inertia-Version` header masked a 409 conflict, making its assertions pass vacuously against empty content) | Found while writing the analogous Phase 06 photos test, which caught the same pattern failing for real; both tests now use a plain full-page `GET` instead |
| 2026-09-07 | 06→07 | Fixed the bottom nav's "Photos" tab and the Overview's "Site Photos"/"360° View" tiles, which had no `href` since Phase 06 built their routes and rendered permanently disabled regardless of data | `NeuTile`/`NeuBottomNav` treat a missing `href` as disabled by design (route doesn't exist yet); the routes existed, the wiring was just never added — caught while wiring Phase 07's own Overview tile and fixed as its own commit first |
| 2026-09-07 | 07 | `documents` media collection lives on the `local` disk, not the package-default `public` disk photos/panoramas use | As-built drawings must never be publicly reachable by URL (unlike photos, which are non-sensitive once a station is already unlocked); `local` has no `storage:link` target |
| 2026-09-07 | 07 | Every document URL (`previewUrl`, `downloadUrl`) is built via this app's own `route()` helper to gated controllers, never a medialibrary-generated disk URL | Consistent with the `local`-disk decision above — nothing about a document's storage location should ever leak into a URL a client can see |
| 2026-09-07 | 07 | `documents` gets no derived "preview" conversion (unlike photos) | The plan's own pairing model is two full-resolution media rows, not a resize; a downsized engineering drawing can hide the detail a field crew needs, and Ghostscript (needed for medialibrary's own PDF-to-image conversion) isn't installed anyway |
| 2026-09-07 | 07 | `DocumentData::isRenderableMime()` uses an explicit allowlist, not `str_starts_with($mime, 'image/')` | Real bug found while seeding a demo DWG: Symfony's mime guesser correctly identifies DWG's binary signature and reports its real registered mime, `image/vnd.dwg` — which the naive prefix check wrongly treated as browser-renderable |
| 2026-09-07 | 07 | Download/preview controllers are typed to return Symfony's `StreamedResponse`, not `BinaryFileResponse` | PHPStan caught the mismatch immediately — `Storage::disk()->download()`/`->response()` return `StreamedResponse` in this Laravel version regardless of disk driver |
| 2026-09-07 | 07 | Non-primary documents render as download-only compact rows with no preview (even when their mime is renderable) | Matches the plan's own wireframe (only the primary document gets the large card with a preview); a demo station was seeded with an image as its *primary* document specifically so the lightbox path still has a real scenario to exercise |
| 2026-09-08 | 08 | Removed the global `Route::bind('station', ...)` (Phase 03) in favour of default implicit binding + a new `EnsureStationIsPublished` middleware scoped only to the dossier route group | The global bind silently made every admin `{station}` route 404 for any not-yet-published station too — found immediately when the very first admin CRUD test tried to publish one |
| 2026-09-08 | 08 | `config('fortify.home')` changed from the starter kit's `/dashboard` to `/admin/stations` | The admin console is this app's only real authenticated destination; the starter kit's placeholder dashboard page is left in place but unreachable via normal navigation |
| 2026-09-08 | 08 | Built the Neumorphism form primitives (`NeuInput`/`NeuTextarea`/`NeuSelect`/`NeuFormField`/`NeuToggle`) Phase 02's M2.4 deferred | This is the first screen with enough forms to justify them, exactly as Phase 02 planned ("build just-in-time") |
| 2026-09-08 | 08 | `UniqueStationCode` is a standalone `Illuminate\Contracts\Validation\ValidationRule`, not a closure repeated in both form requests | One implementation shared by create and update, mirroring the database's own case-insensitive functional unique index |
| 2026-09-08 | 08 | QR plates are SVG data URIs in the print/sheet pages, not PNG | Vector art has no DPI ceiling — matters at real-world 50×50mm print dimensions per the plan's own "≥300 DPI effective resolution" requirement |
| 2026-09-08 | 08 | `CoordinateSetFormData`/`SpecificationFormData` are new raw-value DTOs, not a reuse of the existing display-formatted `CoordinateSetData`/`SpecificationData` | The public DTOs format values with units/thousands separators for read-only display; a numeric `<input>` needs the raw decimal string instead |
| 2026-09-08 | 08 | Photo/panorama/document upload forms use Inertia's `<Form>` component against a real `<input type="file">`, not `router.post()` with a manually-built `FormData` | The manual approach silently sent empty request bodies specifically under Pest's browser-testing plugin (confirmed as a test-harness quirk, not an app bug, by uploading a photo through the original version by hand in a real Chrome browser and watching it succeed) |
| 2026-09-08 | 08 | Dropped the planned client-side image-downscale-before-upload for photos | Doing it safely with a native `<Form>` submission needs replacing the file input's FileList via the DataTransfer API, real added complexity for a nice-to-have the Test Gate doesn't require; the server's 20 MB cap is the current safeguard |
| 2026-09-08 | 08 | M8.8 (CSV import/export + download audit viewer) skipped entirely | `DemoStationSeeder` (Phase 01) already satisfies the Definition of Done's "all 25 stations loaded," which is the plan's own explicitly-stated fallback for skipping this optional milestone |

---

## Decision log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-09-07 | Admin console built in Inertia + React, not Filament | Single stack, single visual language; Neumorphism applies to admin too |
| 2026-09-07 | Dossier access mode (`public` / `password`) driven by `.env` | Client wants to flip the whole deployment between open and gated without a code change |
| 2026-09-07 | Leaflet + Esri World Imagery for maps, Pannellum for 360° | No API key, no per-view billing, MIT-licensed, small mobile bundle |
| 2026-09-07 | AI tooling = Laravel Boost + MCP + Boost skills, **dev-side only** | User confirmed; no `laravel/ai` or `laravel/mcp` in the product for v1 |
| 2026-09-07 | Features beyond the poster kept but tagged `[OPTIONAL]` | Station switcher, clipboard, navigate-here, dark theme, CSV import + audit viewer can be skipped without failing a gate |
| 2026-09-07 | Phase 08 re-estimated 5 → 7 days | All-React admin with media manager is the largest phase; original estimate was optimistic |
