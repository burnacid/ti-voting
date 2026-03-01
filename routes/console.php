<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of games older than 7 days to run daily at 2 AM
Schedule::command('games:cleanup')->dailyAt('02:00')->name('cleanup-old-games')->withoutOverlapping();


