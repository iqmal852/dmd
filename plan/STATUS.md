# Build Status Board

**Last updated:** 2026-09-07
**Current phase:** Phase 00 — Foundation & Tooling (milestones complete, gate green, tag pending)

Update this file every time a milestone is completed. See §3 of [`README.md`](README.md).

| Phase | Title | Milestones | Status | Gate passed | Tag |
|:-----:|-------|:----------:|--------|:-----------:|-----|
| 00 | [Foundation & Tooling](phases/phase-00-foundation.md) | 7/7 | 🟡 Gate green, tag pending | 2026-09-07 | — |
| 01 | [Data Model & Domain](phases/phase-01-data-model.md) | 0/6 | ⬜ Not started | — | — |
| 02 | [Neumorphism Design System](phases/phase-02-design-system.md) | 0/6 | ⬜ Not started | — | — |
| 03 | [Dossier Shell & Access Control](phases/phase-03-dossier-shell.md) | 0/7 | ⬜ Not started | — | — |
| 04 | [Coordinates & Specs Modules](phases/phase-04-coordinates-specs.md) | 0/5 | ⬜ Not started | — | — |
| 05 | [Location Map](phases/phase-05-location-map.md) | 0/5 (1 optional) | ⬜ Not started | — | — |
| 06 | [Site Photos & 360°](phases/phase-06-photos-360.md) | 0/6 | ⬜ Not started | — | — |
| 07 | [As-Built Drawings & Downloads](phases/phase-07-asbuilt-files.md) | 0/5 | ⬜ Not started | — | — |
| 08 | [Admin Console & QR Generation](phases/phase-08-admin-qr.md) | 0/8 (1 optional) | ⬜ Not started | — | — |
| 09 | [Hardening & Release](phases/phase-09-hardening-release.md) | 0/7 | ⬜ Not started | — | — |

**Legend:** ⬜ Not started · 🟡 In progress · 🔴 Blocked · ✅ Complete

**Total: 7 / 62 milestones** — 60 required, 2 fully `[OPTIONAL]` (M5.5, M8.8). Dark theme (M2.1), station switcher (M4.2) and clipboard (M4.4) are optional *parts* of otherwise required milestones.

---

## Open blockers

_None._ Phase 00's CI fix (Postgres service container added to `.github/workflows/tests.yml`) is committed locally and simulated successfully but not yet pushed/tagged — no blocker, just the next action.

---

## Deviations from plan

| Date | Phase | Deviation | Why |
|------|-------|-----------|-----|
| 2026-09-07 | 00 | Local Postgres via Homebrew service, not `docker-compose.yml` | Docker daemon wasn't running in the dev environment; `docker-compose.yml` is still worth adding later for cross-machine parity, tracked as follow-up |
| 2026-09-07 | 00 | Kept Fortify's 2FA + passkeys (removed only registration/reset/verification, as planned) | Laravel 13's current starter kit ships them enabled by default; they're additive opt-in hardening for the single admin, not a requirement violation, and removing them means dropping migrations/columns for no stated need |
| 2026-09-07 | 00 | Quality tooling is the starter kit's own `composer test` + `vp check` (vite-plus), not hand-rolled ESLint/Prettier scripts | This is what Laravel's official starter kit ships today; layering a second toolchain on top would be redundant |
| 2026-09-07 | 00 | Larastan already at level 7, not the planned level 6 | Starter kit default; left as-is since it's strictly ahead of plan and closer to Phase 09's level-8 target |

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
