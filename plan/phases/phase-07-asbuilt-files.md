# Phase 07 — As-Built Drawings & Downloads

| | |
|---|---|
| **Status** | ✅ Complete |
| **Depends on** | Phase 03 |
| **Estimate** | 2 days |
| **Tag on completion** | `phase-07-complete` |

## Goal

Poster panel 3: view the official as-built drawing on screen, and download the original
file. Plus the audit trail that makes "Secure & Read-Only" a verifiable claim rather than
a slogan.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M7.1** | `documents` media collection, `DocumentType` enum, DWG-plus-preview pairing rule | 2026-09-07 | `tests/Feature/Data/DocumentDataTest.php` |
| [x] | **M7.2** | `DocumentData` DTO + `/d/{code}/files` listing | 2026-09-07 | `tests/Feature/Dossier/FilesTest.php` |
| [x] | **M7.3** | Inline preview: PDF via `<object>`/`<iframe>`, image via zoomable viewer | 2026-09-07 | `plan/evidence/phase-07/phase-07-files-{mobile,desktop}.png` |
| [x] | **M7.4** | Gated, streamed download controller with correct headers | 2026-09-07 | `tests/Feature/Dossier/FilesTest.php` |
| [x] | **M7.5** | `download_logs` written asynchronously; retention pruning scheduled | 2026-09-07 | `tests/Feature/Dossier/FilesTest.php` |

---

## Milestone detail

### M7.1 — The DWG problem, solved explicitly

A browser cannot render `.dwg`. The poster shows a drawing on screen *and* a "Download
As-Built Drawing" button — so every as-built document is a **pair**:

| Artefact | Purpose | Formats |
|----------|---------|---------|
| **Original** | The downloadable deliverable | `.dwg`, `.dxf`, `.pdf` |
| **Preview** | What renders on screen | `.pdf` or `.png`/`.webp` |

Modelled as two media rows in the `documents` collection; the original carries a
`preview_media_id` custom property. When the original is already a PDF it is its own
preview and no second file is needed.

Validation rule enforced in Phase 08's admin form: uploading a `.dwg` without a preview is
rejected with "A DWG cannot be displayed in a browser — attach a PDF or image preview."

Custom properties: `document_type`, `title`, `revision`, `is_primary`, `preview_media_id`.
Exactly one `is_primary` per station — the target of the big download button.

### M7.2 — Files listing

`/d/{code}/files`, component `dossier/files`. The primary as-built document is a large
`NeuCard` at the top (title, revision, file type badge, size, a `Download` `NeuButton`,
and a preview thumbnail), followed by any other documents as compact rows.

```php
final readonly class DocumentData
{
    public function __construct(
        public string $id,             // signed/opaque, not the media PK
        public string $type,           // 'as_built'
        public string $typeLabel,      // 'As-Built Drawing'
        public string $title,
        public ?string $revision,
        public string $extension,      // 'DWG'
        public string $size,           // '2.4 MB', pre-formatted
        public bool $isPrimary,
        public ?string $previewUrl,
        public string $downloadUrl,
    ) {}
}
```

Empty state when a station has no documents: "As-built drawing not yet uploaded for this
station." — with the station code, so a crew can report it.

### M7.3 — Inline preview

- **PDF**: `<object type="application/pdf">` with a "Open in new tab" fallback for iOS
  Safari, which renders only the first page inline. Do **not** bundle PDF.js in v1 — it is
  ~1 MB and native rendering is adequate for a single-sheet drawing.
- **Image**: the same zoomable viewer used by the photo lightbox (pinch, double-tap,
  `←`/`→`), because an engineering drawing is unreadable at 390px without zoom.
- Preview is lazy: it loads when scrolled into view, not on page load.

### M7.4 — Download controller

```php
Route::get('{station}/files/{media}/download', DownloadDocumentController::class)
    ->middleware([EnsureDossierUnlocked::class])
    ->scopeBindings()                       // media must belong to this station
    ->name('dossier.files.download');
```

`scopeBindings()` is the important bit: without it, `/d/{stationA}/files/{mediaOfStationB}/download`
would serve another station's file after unlocking only station A. A test asserts a **404**
for that cross-station case.

Response: `Storage::disk(...)->download()` (streamed, never `file_get_contents`), with

```
Content-Disposition: attachment; filename="LPT2-GCP-015-as-built-revA.dwg"
Content-Type: <real mime, from the stored media record>
X-Content-Type-Options: nosniff
Cache-Control: private, max-age=0, no-store
```

Filenames are built from the station code + document type + revision, sanitised with
`Str::slug` on each part — never from user-supplied filenames, which is how path traversal
and header injection get in.

**Original files are never publicly reachable.** The media disk is private; there is no
`storage:link` for `documents`. Every byte goes through this controller.

### M7.5 — Audit

An event fires on successful download; a **queued** listener writes the `download_logs`
row so the response is never delayed. Stores `station_id`, `media_id`,
`hash_hmac('sha256', $ip, config('app.key'))`, truncated UA, and `downloaded_at`.
Never the raw IP.

Scheduled in `routes/console.php`:

```php
Schedule::command('model:prune', ['--model' => [DownloadLog::class]])->daily();
```

Toggle with `DOWNLOAD_LOG_ENABLED=false` for deployments where even hashed logging is
unwanted.

---

## Test Gate

```bash
php artisan test --filter='Document|File|Download|Dossier'
php artisan test --filter=Browser
```

Assertions that must exist:

1. `GET /d/{public_id}/files` → 200, component `dossier/files`; props are `DocumentData`
   only — no disk paths, no absolute filesystem paths, no raw media models.
2. Download returns 200 with `Content-Disposition: attachment` and the sanitised filename.
3. **Cross-station download → 404** (the `scopeBindings` test).
4. Download is behind the access gate: password mode + no unlock → 302, zero bytes served.
5. A download writes exactly one `download_logs` row, with a 64-char `ip_hash` and no raw
   IP anywhere in the row.
6. `DOWNLOAD_LOG_ENABLED=false` writes no row but still serves the file.
7. `model:prune` deletes logs older than the retention window and keeps newer ones.
8. Uploading a `.dwg` with no preview fails validation (unit test on the rule).
9. A station with no documents renders the empty state.
10. **Browser, 390×844:** the primary document card renders, the download button is
    ≥ 44px, and a PDF preview is visible or the "open in new tab" fallback is shown.
11. **Security:** requesting a media file's public URL directly returns 403/404.

---

## Definition of Done

- [x] The as-built drawing can be read on a phone screen (zoomable) and downloaded intact
      — verified for PDF (native pinch-zoom) and image (lightbox) previews; a real
      curl download round-tripped as a valid PDF (`file` reports "PDF document,
      version 1.4, 1 pages").
- [ ] A downloaded DWG opens correctly in CAD software — **not verified**: there is no
      real DWG file or CAD software available in this environment. The seeded "DWG" is
      arbitrary bytes with a `.dwg` extension, sufficient to exercise the pairing and
      download-headers logic but not an actual round-trip. Verify with a real file
      before go-live.
- [x] No file is reachable without passing the access gate — `documents` lives on the
      `local` disk (no `storage:link` target); both `files.preview` and
      `files.download` sit behind `EnsureDossierUnlocked`; a direct `/storage/...`
      guess 403/404s.
- [x] Downloads are audited without storing personal data — `LogDocumentDownload` is a
      queued listener storing only `hash_hmac('sha256', $ip, config('app.key'))`,
      verified end-to-end against a real dev queue worker (not just `QUEUE_CONNECTION=sync`
      in tests).

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | Pass — 213/213 (160 Unit+Feature, 53 Browser), Pint clean, PHPStan level 7 clean, `npm run check`/`types:check` clean, `npm run build` clean |
| **DWG round-trip verified** | No — see Definition of Done note above |
| **Screenshots** | `plan/evidence/phase-07/` |
| **Commit / tag** | `phase-07-complete` |

## Phase Log

- 2026-09-07 — M7.1 done: `documents` media collection added to `Station`, on the
  `local` disk (not the package-default `public` disk photos/panoramas use) — as-built
  drawings are never publicly reachable by URL. `DocumentType` enum already existed
  from Phase 01. The DWG-plus-preview pairing is two media rows: the original (any
  type) carries `document_type`/`title`/`revision`/`is_primary`, and — only when the
  original can't be rendered — a `preview_media_id` custom property pointing at a
  second, separately-uploaded row. A row with no `document_type` is a preview-only
  companion and is filtered out of the Files listing and the Overview's document count.
- 2026-09-07 — Real bug found and fixed while seeding the demo DWG: Symfony's mime
  guesser correctly identifies a DWG file's binary signature (`AC10...`) and reports
  its real registered mime type, `image/vnd.dwg` — which a naive `str_starts_with($mime,
  'image/')` renderability check wrongly treated as a browser-renderable image. Fixed
  by replacing that check with an explicit allowlist of genuinely renderable mimes
  (`DocumentData::isRenderableMime()`, shared with `PreviewDocumentController`), rather
  than trusting the `image/` prefix.
- 2026-09-07 — M7.2 done: `DocumentData` DTO and `DossierFilesController` built. Every
  URL on the DTO (`previewUrl`, `downloadUrl`) points at this app's own gated
  controllers, never a medialibrary-generated disk URL. Unit-tested in
  `tests/Feature/Data/DocumentDataTest.php`.
- 2026-09-07 — M7.3 done: `/d/{code}/files` built — a large primary-document card
  (PDF via `<object>` with an always-visible "Open in new tab" link, since iOS Safari
  renders the `<object>` successfully but only shows the first page) plus compact rows
  for other documents. Deviation: only the *primary* document gets an inline preview;
  non-primary documents are download-only rows with no preview, per the plan's own
  wireframe — the image lightbox therefore only had a scenario to exercise once a demo
  station was seeded with an image as its *primary* document.
- 2026-09-07 — M7.4 done: `DownloadDocumentController` and `PreviewDocumentController`,
  both bound via `{station}/{media:uuid}` + `scopeBindings()` so a media row from
  another station 404s. Filenames are `Str::slug()`-sanitised per part
  (`{station-code}-{document-type}-rev-{revision}.{ext}`), never from user input.
  Streams via `Storage::disk()->download()`/`->response()` (Symfony `StreamedResponse`,
  not `BinaryFileResponse` — PHPStan caught the wrong return-type hint immediately).
- 2026-09-07 — M7.5 done: `DocumentDownloaded` event + `LogDocumentDownload` (a
  `ShouldQueue` listener) write the audit row, gated by
  `config('dossier.downloads.log_enabled')`. `model:prune` scheduled daily in
  `routes/console.php` against `DownloadLog`'s existing `prunable()` scope (built in
  Phase 01). Verified against a real `php artisan queue:work` in dev, not just the
  test suite's `QUEUE_CONNECTION=sync` — confirmed the job actually queues (visible in
  the `jobs` table) and, once processed, writes a row with a 64-char hash and no raw IP.
- 2026-09-07 — Found and fixed two bugs left over from Phase 06 while wiring this
  phase's own Overview tile: the bottom nav's "Photos" tab and the Overview's "Site
  Photos"/"360° View" tiles had never been given an `href`, so both rendered
  permanently disabled regardless of whether photo/panorama data existed (`NeuTile` and
  `NeuBottomNav` both treat a missing `href` as disabled). Fixed as a separate commit
  before starting this phase's own work, with a browser regression test; the same
  wiring was then extended to the As-Built tile and Files nav item.
