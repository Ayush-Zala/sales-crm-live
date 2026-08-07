<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Cache as FacadesCache;

class UserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (FacadesAuth::check()) {
            $expiresAt = now()->addMinutes(1); /* keep online for 1 min */
            FacadesCache::put('user-is-online-' . FacadesAuth::user()->id, true, $expiresAt);

            // Check if online count has changed to broadcast update dynamically
            $currentOnlineCount = \App\Models\Cache::where('key', 'like', 'user-is-online-%')->where('expiration', '>=', time())->count();
            $lastKnownCount = FacadesCache::get('last-known-online-count', -1);
            
            if ($currentOnlineCount !== $lastKnownCount) {
                FacadesCache::put('last-known-online-count', $currentOnlineCount, now()->addMinutes(10));
                $totalUsers = \App\Models\User::where('is_active', 1)->count();
                broadcast(new \App\Events\OnlineUsersUpdated($currentOnlineCount, $totalUsers));
            }

            /* last seen */
            //  User::where('id', FacadesAuth::user()->id)->update(['last_seen' => now()]);
        }
        return $next($request);
    }
}
