<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {

        // return [
        //     ...parent::share($request),
        //     'auth' => [
        //         'user' => $request->user(),
        //         // 'roles' => $request->user() ? $request->user()->roles->pluck('name') : null,
        //         // 'permissions' => $request->user() ? $request->user()->getPermissionsViaRoles()->pluck('name') : [],
        //     ],
        // ];

        return array_merge(parent::share($request), [
            'auth' => [
                // Share user basic details, without roles and permissions
                'user' => $request->user() ? $request->user()->only(['id', 'name', 'email', 'reporting_authority_id', 'email_verified_at', 'is_active', 'created_at', 'updated_at']) : null,

                // Share user roles as an array of role names
                'roles' => $request->user() ? $request->user()->roles->pluck('name') : [],

                // Share permissions as an array of permission names
                'permissions' => $request->user() ? $request->user()->getAllPermissions()->pluck('name') : [],
            ],

            // Add flash messages
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
