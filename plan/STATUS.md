# Build Status Board

**Last updated:** 2026-09-07
**Current phase:** Phase 05 complete. Phase 06 (Site Photos & 360°) starting next.

Update this file every time a milestone is completed. See §3 of [`README.md`](README.md).

| Phase | Title | Milestones | Status | Gate passed | Tag |
|:-----:|-------|:----------:|--------|:-----------:|-----|
| 00 | [Foundation & Tooling](phases/phase-00-foundation.md) | 7/7 | ✅ Complete | 2026-09-07 | `phase-00-complete` |
| 01 | [Data Model & Domain](phases/phase-01-data-model.md) | 6/6 | ✅ Complete | 2026-09-07 | `phase-01-complete` |
| 02 | [Neumorphism Design System](phases/phase-02-design-system.md) | 4/6 (2 ongoing) | 🟡 In progress (deliberately spans through Phase 08) | — | — |
| 03 | [Dossier Shell & Access Control](phases/phase-03-dossier-shell.md) | 7/7 | ✅ Complete (2 DoD items unverified — no physical device) | 2026-09-07 | — |
| 04 | [Coordinates & Specs Modules](phases/phase-04-coordinates-specs.md) | 5/5 | ✅ Complete | 2026-09-07 | — |
| 05 | [Location Map](phases/phase-05-location-map.md) | 5/5 | ✅ Complete | 2026-09-07 | — |
| 06 | [Site Photos & 360°](phases/phase-06-photos-360.md) | 0/6 | ⬜ Not started | — | — |
| 07 | [As-Built Drawings & Downloads](phases/phase-07-asbuilt-files.md) | 0/5 | ⬜ Not started | — | — |
| 08 | [Admin Console & QR Generation](phases/phase-08-admin-qr.md) | 0/8 (1 optional) | ⬜ Not started | — | — |
| 09 | [Hardening & Release](phases/phase-09-hardening-release.md) | 0/7 | ⬜ Not started | — | — |

**Legend:** ⬜ Not started · 🟡 In progress · 🔴 Blocked · ✅ Complete

**Total: 32 / 62 milestones** (Phase 02's M2.5/M2.6 will be re-counted once M2.3/M2.4 fully land in later phases) — 60 required, 2 fully `[OPTIONAL]` (M5.5, M8.8). Dark theme (M2.1), station switcher (M4.2) and clipboard (M4.4) are optional *parts* of otherwise required milestones.

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
