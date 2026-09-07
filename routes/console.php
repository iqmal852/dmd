<?php

use App\Models\DownloadLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Retention window is config('dossier.downloads.retention_days') via
// DownloadLog::prunable(). See plan/phases/phase-07-asbuilt-files.md M7.5.
Schedule::command('model:prune', ['--model' => [DownloadLog::class]])->daily();

// plan/phases/phase-09-hardening-release.md M9.4. Where this actually
// lands is config('backup.disk') — local by default, an off-site disk
// (e.g. S3) in production; see docs/DEPLOYMENT.md.
Schedule::command('batu:backup')->daily()->onOneServer();
