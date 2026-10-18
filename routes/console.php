<?php

use App\Models\LogEntry;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of games older than 7 days to run daily at 2 AM
Schedule::command('games:cleanup')->dailyAt('02:00')->name('cleanup-old-games')->withoutOverlapping();



// Delete log entries older than LOG_DATABASE_DAYS (default 30 days)
Schedule::command('model:prune', ['--model' => [LogEntry::class]])->dailyAt('02:30')->name('prune-log-entries')->withoutOverlapping();
