<?php

namespace App\Providers;

use App\Listeners\BroadcastOnlineUsers;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Broadcast online user count on login/logout
        Event::listen(Login::class, BroadcastOnlineUsers::class);
        Event::listen(Logout::class, BroadcastOnlineUsers::class);
    }
}
