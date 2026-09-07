# Batu — MySpatial Digital Monument Dossier

> **Batu** (Malay: *stone / marker*) — the working name for the MySpatial GCP Digital
> Monument Dossier. A surveyor scans the QR plate embedded in a physical GCP monument,
> and the browser opens a complete, read-only digital dossier for that station.

This folder is the **single source of truth for the build plan**. Everything is derived
from `../Picture1.png`, the only requirements artefact supplied for this project.

---

## 1. Read these first, in order

| # | Document | What it settles |
|---|----------|-----------------|
| 1 | [`00-product-spec.md`](00-product-spec.md) | What the product is, screen by screen, transcribed from `Picture1.png` |
| 2 | [`01-architecture.md`](01-architecture.md) | Stack, package choices, and the reasoning behind each (ADR-style) |
| 3 | [`02-data-model.md`](02-data-model.md) | PostgreSQL schema, models, relationships, casts |
| 4 | [`03-design-system.md`](03-design-system.md) | Neumorphism tokens, component inventory, mobile-first rules |
| 5 | [`04-env-configuration.md`](04-env-configuration.md) | Every `.env` key, including the configurable QR domain and access mode |

Then work the phases in [`phases/`](phases/) in order. Live progress lives in
[`STATUS.md`](STATUS.md).

---

## 2. Phase map

| Phase | Title | Ships |
|-------|-------|-------|
| [00](phases/phase-00-foundation.md) | Foundation & Tooling | Laravel 13 + Inertia 3 + React 19 + Postgres skeleton, Boost MCP, CI |
| [01](phases/phase-01-data-model.md) | Data Model & Domain | Migrations, models, factories, seeders, the 25 LPT2 stations |
| [02](phases/phase-02-design-system.md) | Neumorphism Design System | Tokens, primitives, kitchen-sink page, light + dark |
| [03](phases/phase-03-dossier-shell.md) | Dossier Shell & Access Control | `/d/{code}` route, `.env`-driven public/password gate, Overview screen |
| [04](phases/phase-04-coordinates-specs.md) | Coordinates & Specs Modules | WGS 84 / GDM 2000 / MyGEOID, GNSS observation, accuracy RMS |
| [05](phases/phase-05-location-map.md) | Location Map | Leaflet + Esri satellite, KM chainage markers, directions hand-off |
| [06](phases/phase-06-photos-360.md) | Site Photos & 360° | Photo carousel by type, Pannellum panorama viewer |
| [07](phases/phase-07-asbuilt-files.md) | As-Built Drawings & Downloads | DWG/PDF viewer + streamed, audited downloads |
| [08](phases/phase-08-admin-qr.md) | Admin Console & QR Generation | Single-admin auth, full CRUD, uploads, QR + printable plate sheets |
| [09](phases/phase-09-hardening-release.md) | Hardening & Release | Performance, a11y, security review, deploy runbook |

Phases 04–07 each add one of the four module tiles shown in `Picture1.png` panel 1.
They are deliberately independent of one another and can be reordered if a demo
needs a particular module early — but **03 must precede all of them** and **02 must
precede everything visual**.

---

## 3. Milestone protocol — read this, it is the process the user asked for

Every phase file contains a **Milestone table**. A milestone is the smallest unit of
work that is independently demonstrable and independently testable.

### 3.1 Statuses

| Symbol | Meaning |
|--------|---------|
| `[ ]` | Not started |
| `[~]` | In progress |
| `[x]` | Done — merged, tests green |
| `[!]` | Blocked — a `> **Blocked:**` note must be added under the table saying why |
| `[-]` | Skipped — allowed **only** for milestones tagged `[OPTIONAL]`; add a one-line reason in the Phase Log |

Milestones tagged **`[OPTIONAL]`** are value-adds that go beyond what `Picture1.png`
shows. Skipping one does not block a phase gate. Everything untagged is required.

### 3.2 When a milestone is completed you MUST, in the same commit:

1. Flip its checkbox to `[x]` in the phase file's Milestone table.
2. Fill in its **Date** column (`YYYY-MM-DD`) and **Evidence** column (test name,
   commit short SHA, or screenshot path under `plan/evidence/`).
3. Append a dated line to the **Phase Log** at the bottom of that phase file, saying
   what actually shipped and anything that deviated from the plan.
4. Update the phase's progress fraction in [`STATUS.md`](STATUS.md) (e.g. `3/6`).

### 3.3 When every milestone in a phase is `[x]`:

1. Run the phase's **Test Gate** verbatim. Every command must pass.
2. Paste the gate output summary into the phase's **Sign-Off** block and fill the date.
3. Set the phase row in [`STATUS.md`](STATUS.md) to `✅ Complete`.
4. Tag the repo: `git tag phase-0X-complete && git push --tags`.
5. Only then start the next phase.

> **Rule:** a phase is never "mostly done". If the Test Gate is red, the phase is
> open, and the next phase does not begin.

### 3.4 Deviating from the plan

The plan is expected to be wrong somewhere. When reality disagrees:
edit the phase file, add a `> **Deviation (YYYY-MM-DD):**` blockquote explaining the
change and why, then proceed. Do not silently diverge — the plan files are the record.

---

## 4. Testing philosophy

The user requirement is *"always tested after each phase"*. Concretely:

- **Pest 5** is the only test runner. Feature tests over unit tests; test behaviour
  through HTTP routes and Inertia responses, not internals.
- **Pest browser tests** (real Chromium) cover the mobile experience. Every public
  screen has at least one test asserting it renders at **390×844 (iPhone 14)** —
  because the poster's own mockups are phones and field crews are on phones.
- **Larastan level 6+** and **Pint** run in the same gate as the tests. Static analysis
  failures block a phase exactly like a failing test does.
- Every phase's Test Gate is a copy-pasteable block. If it is not automatable, it is
  written as a numbered **manual QA script** instead — never as "check it looks ok".

---

## 5. Conventions

- **PHP**: strict types, constructor property promotion, readonly DTOs, enums for all
  fixed vocabularies. Controllers stay thin — invokable single-action controllers for
  anything non-CRUD; Form Requests for all validation; API Resources / DTOs for all
  Inertia props. No business logic in models beyond relationships, casts, and scopes.
- **React**: TypeScript everywhere, function components, no class components. Pages in
  `resources/js/pages/`, shared UI in `resources/js/components/ui/`, feature components
  in `resources/js/components/dossier/`. Props typed from generated types.
- **Naming**: the domain object is a **Station** (a GCP monument). Not "point",
  not "marker", not "monument" in code — `Station` everywhere.
- **Commits**: Conventional Commits, scoped to the milestone, e.g.
  `feat(dossier): add coordinates module (M4.2)`.
- **Branches**: `phase-0X/mY-short-slug`, squash-merged into `main`.

---

## 6. Ground rules pinned from the brief

1. Web only. No native mobile app. **Mobile-first responsive is non-negotiable** —
   design at 390px, then scale up.
2. The QR payload domain is **configurable via `.env`** and never hard-coded.
3. Whether a dossier is public or password-gated is **configurable via `.env`**.
4. **PostgreSQL** is the database, in dev and in production. Not SQLite, not MySQL.
5. **Exactly one admin user**, provisioned from `.env` by a seeder. No registration
   route, no user management UI.
6. **Neumorphism** is the visual language, applied consistently to the public dossier
   *and* the admin console.
7. Use Laravel's own AI tooling — **Laravel Boost**, its **MCP server**, and the
   **Boost skills/guidelines** it installs — as the day-to-day development harness
   (see Phase 00, M0.4). The product itself has no AI features in v1.
