<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

app()->singleton(Schedule::class, function ($app) {
    return tap(new Schedule, function ($schedule) {
$schedule->command('app:call-log')
            ->dailyAt('06:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping()
            ->runInBackground();
    });
});

// app(Schedule::class)->command('app:call-log')->everyMinute();
