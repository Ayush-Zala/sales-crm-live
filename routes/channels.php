<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel auth is checked here before a client is allowed to subscribe
| to a private channel.
|
*/

/**
 * Global admin channel — only Admins and Super Admins can subscribe.
 */
Broadcast::channel('dashboard.global', function ($user) {
    return $user->hasAnyRole(['Admin', 'Super Admin']);
});

/**
 * Manager-specific channel — the manager themselves, or an Admin.
 * e.g. "dashboard.manager.51"
 */
Broadcast::channel('dashboard.manager.{managerId}', function ($user, $managerId) {
    // Admin/Super Admin can subscribe to any manager channel
    if ($user->hasAnyRole(['Admin', 'Super Admin'])) {
        return true;
    }

    // The manager can subscribe to their own channel
    return (int) $user->id === (int) $managerId;
});

/**
 * User-specific channel — for Sales Executives (their own data only).
 * e.g. "dashboard.user.95"
 */
Broadcast::channel('dashboard.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
