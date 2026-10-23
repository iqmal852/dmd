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
