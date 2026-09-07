# Phase 07 — As-Built Drawings & Downloads

| | |
|---|---|
| **Status** | ⬜ Not started |
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
| [ ] | **M7.1** | `documents` media collection, `DocumentType` enum, DWG-plus-preview pairing rule | | |
| [ ] | **M7.2** | `DocumentData` DTO + `/d/{code}/files` listing | | |
| [ ] | **M7.3** | Inline preview: PDF via `<object>`/`<iframe>`, image via zoomable viewer | | |
| [ ] | **M7.4** | Gated, streamed download controller with correct headers | | |
| [ ] | **M7.5** | `download_logs` written asynchronously; retention pruning scheduled | | |

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

- [ ] The as-built drawing can be read on a phone screen (zoomable) and downloaded intact.
- [ ] A downloaded DWG opens correctly in CAD software — **verify with a real file once**.
- [ ] No file is reachable without passing the access gate.
- [ ] Downloads are audited without storing personal data.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | |
| **Result** | |
| **DWG round-trip verified** | |
| **Screenshots** | `plan/evidence/phase-07/` |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
