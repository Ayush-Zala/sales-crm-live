<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\UserActivity::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \LaravelEG\Laravel\Middleware\UserOnlineMiddleware::class,
        ])->validateCsrfTokens(except: [
            '*',
            '/api-data1',
        ]);
    })

 ->withSchedule(function (Schedule $schedule) {
        $schedule->command('app:call-log')->cron('0 */8 * * *');
$schedule->command('app:zoomrecord')->cron('0 */8 * * *');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
 $schedule->command('app:call-log')->hourly();

