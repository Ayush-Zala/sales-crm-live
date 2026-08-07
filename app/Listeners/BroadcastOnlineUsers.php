<?php

namespace App\Listeners;

use App\Events\OnlineUsersUpdated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Models\Cache;
use App\Models\User;
use Illuminate\Support\Facades\Cache as FacadesCache;

class BroadcastOnlineUsers
{
    public function handle(Login|Logout $event): void
    {
        if ($event instanceof Logout) {
            FacadesCache::forget('user-is-online-' . $event->user->id);
        }

        if ($event instanceof Login) {
            $expiresAt = now()->addMinutes(1);
            FacadesCache::put('user-is-online-' . $event->user->id, true, $expiresAt);
        }

        // Count all active online cache keys (set by UserOnlineMiddleware)
        $onlineCount = Cache::where('key', 'like', 'user-is-online-%')->where('expiration', '>=', time())->count();
        $totalUsers  = User::where('is_active', 1)->count();

        broadcast(new OnlineUsersUpdated($onlineCount, $totalUsers))->toOthers();
    }
}
