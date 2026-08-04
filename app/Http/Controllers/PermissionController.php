<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class PermissionController extends Controller
{

    public $user;

    public function __construct()
    {
        $this->user = Auth::check() ? Auth::user()->name : null;
    }

    public function index(Request $request)
    {
        $search = $request->search;

        $permissions = DB::table('permissions as p')
            ->select(
                'p.id',
                'p.name',
                'p.guard_name',
                'p.created_at',
                'p.updated_at',
            )
            ->where(function ($query) use ($search) {
                $query->where('p.name', 'like', '%' . $search . '%')
                    ->orWhere('p.guard_name', 'like', '%' . $search . '%');
            })
            ->groupBy(
                'p.id',
                'p.name',
                'p.guard_name',
                'p.created_at',
                'p.updated_at',
            );

        if ($request->per_page == null) {
            $permissions = $permissions->paginate(50);
        } else {
            $permissions = $permissions->paginate($request->per_page);
        }

        return Inertia::render('Permission/Index', [
            'permissions' => $permissions
        ]);
    }

    public function getPermissions()
    {
        $permissions = DB::table('permissions as p')->select('p.id', 'p.name')->get();

        return response()->json(['permissions' => $permissions]);
    }

    public function getPermissionsByRole(Request $request)
    {
        // validations
        // $request->validate([
        //     'role_id' => 'required|integer'
        // ]);

        $permissions = DB::table('permissions as p')->select('p.id', 'p.name')->get();

        $permissionsByRole = DB::table('role_has_permissions as rhp')
            ->join('permissions as p', 'p.id', '=', 'rhp.permission_id')
            ->select('p.id', 'p.name')
            ->where('rhp.role_id', $request->role_id)
            ->get();


        return response()->json(['permissions' => $permissions, 'permissionsByRole' => $permissionsByRole], 201);
    }

    public function create()
    {
        $groups = DB::table('groups as g')->select('g.id', 'g.name')->get();

        return response()->json(['groups' => $groups]);
    }

    public function store(Request $request)
    {
        // validations
        // $request->validate([
        //     'permission_name' => 'required|string',
        //     'group_id' => 'required|integer',
        // ]);

        $permission = Permission::create([
            'name' => $request->permission_name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $groupHasPermission = DB::table('group_has_permissions')->insert([
            'group_id' => $request->group_id,
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $properties = [
            'attributes' => [
                'name' => $request->permission_name,
                'group_id' => $request->group_id,
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->user . " has created permission",
            'subject_type' => 'App\Models\Permission',
            'event' => 'created',
            'subject_id' => $permission->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Permission created successfully');
    }

    public function edit(Request $request)
    {
        // validations
        $request->validate([
            'permission_id' => 'required|integer'
        ]);

        // Get permission details with group
        $permission = DB::table('permissions as p')
            ->join('group_has_permissions as ghp', 'ghp.permission_id', '=', 'p.id')
            ->join('groups as g', 'g.id', '=', 'ghp.group_id')
            ->select('p.id', 'p.name', 'g.id as group_id')
            ->where('p.id', $request->permission_id)
            ->first();

        $groups = DB::table('groups as g')->select('g.id', 'g.name')->get();

        return response()->json(['permission' => $permission, 'groups' => $groups]);
    }

    public function update(Request $request)
    {
        // validations
        // $request->validate([
        //     'permission_id' => 'required|integer',
        //     'permission_name' => 'required|string',
        //     'group_id' => 'required|integer',
        // ]);

        $oldPermission = DB::table('group_has_permissions')
            ->join('permissions as p', 'group_has_permissions.permission_id', '=', 'p.id')
            ->join('groups as g', 'group_has_permissions.group_id', '=', 'g.id')
            ->where('group_has_permissions.permission_id', $request->permission_id)
            ->select('p.id as permission_id', 'p.name as permission_name', 'g.id as group_id', 'g.name as group_name')
            ->first();


        $permission = DB::table('permissions')
            ->where('id', $request->permission_id)
            ->update([
                'name' => $request->permission_name,
                'guard_name' => 'web',
                'updated_at' => now()
            ]);

        $groupHasPermission = DB::table('group_has_permissions')
            ->where('permission_id', $request->permission_id)
            ->update([
                'group_id' => $request->group_id,
                'updated_at' => now()
            ]);

        $properties = [
            'old' => [
                'name' => $oldPermission->permission_name,
                'group_id' => $oldPermission->group_id,
                'group_name' => $oldPermission->group_name,
            ],
            'attributes' => [
                'name' => $request->permission_name,
                'group_id' => $request->group_id,
                'group_name' => DB::table('groups')->where('id', $request->group_id)->value('name'),
            ]
        ];


        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->user . " has updated permission",
            'subject_type' => 'App\Models\Permission',
            'event' => 'updated',
            'subject_id' => $request->permission_id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Permission updated successfully');
    }

    public function cachePermissions()
    {
        // $permissions = ['read', 'write', 'delete'];
        // $role = ['admin' => $permissions, 'user' => 'read', 'guest' => $permissions];
        // Cache::put('user_permission', $permissions);
        // Cache::put('user_role', $role);

        // $value = Cache::get('user_role');


        return response()->json(['role' => Auth::user()->getRoleNames(), 'permissions' => Auth::user()->getAllPermissions()]);
    }
}
