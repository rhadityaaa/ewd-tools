<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule untuk mengecek periode expired setiap 5 menit
Schedule::command('periods:check-expired')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Alternatif: Jalankan setiap jam
// Schedule::command('periods:check-expired')->hourly();

// Alternatif: Jalankan setiap hari pada jam 00:00
// Schedule::command('periods:check-expired')->daily();
