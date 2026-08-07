<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public $loggedinUser;

    public function __construct()
    {
        $this->loggedinUser = Auth::check() ? Auth::user()->name : null;
    }


    public function index(Request $request)
    {
        $search = $request->search;
        $perPage = $request->per_page ?? 50;

        $roles = Role::query()->when($search, function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('guard_name', 'like', '%' . $search . '%');
        })->orderBy('id', 'asc')
            ->paginate($perPage);

        return Inertia::render('Role/Index', [
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        // validations
        $request->validate([
            'role_name' => 'required|string|unique:roles,name',
        ]);

        $role = DB::table('roles')->insert([
            'name' => $request->role_name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $roleDetails = DB::table('roles')
            ->select('id', 'name')
            ->where('name', $request->role_name)
            ->first();

        if (isset($request->currentPermissions) && count($request->currentPermissions) > 0) {
            foreach ($request->currentPermissions as $key => $val) {
                RoleHasPermission::create([
                    'permission_id' => $val,
                    'role_id' => $roleDetails->id
                ]);
            }
        }

        $properties = [
            'attributes' => [
                'id' => $roleDetails->id,
                'name' => $request->role_name,
            ],
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has created a role",
            'subject_type' => 'App\Models\Role',
            'event' => 'created',
            'subject_id' => $role,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::user()->id,
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    public function getGroupPermissions()
    {
        $groupPermissions = DB::table('group_has_permissions')
            ->join('groups', 'group_has_permissions.group_id', '=', 'groups.id')
            ->join('permissions', 'group_has_permissions.permission_id', '=', 'permissions.id')
            ->select('groups.name as group_name', 'permissions.name as permission_name', 'permissions.id as permission_id', 'groups.id as group_id')
            ->get();

        return $groupPermissions;
    }

    public function createView()
    {
        $permissions = Group::with('permissions:id,name')->get(['id', 'name'])->map(function ($group) {
            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'permissions' => $group->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ];
                }),
            ];
        });

        return Inertia::render('Role/CreateRole', ['permissions' => $permissions]);
    }

    public function edit(Request $request)
    {
        $role_id = $request->id;

        $role = Role::find($role_id)->toArray();

        $permissions = Group::with('permissions:id,name')->get(['id', 'name'])->map(function ($group) {
            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'permissions' => $group->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ];
                }),
            ];
        });

        $currenpremissions = RoleHasPermission::where('role_id', $role_id)->pluck('permission_id')->toArray();

        return Inertia::render('Role/EditRole', ["role" => $role, 'permissions' => $permissions, "current_permissions" => $currenpremissions]);
    }

    public function update(Request $request)
    {
        // validations
        // $request->validate([
        //     'role_id' => 'required|integer',
        //     'role_name' => 'required|string',
        // ]);


        RoleHasPermission::where('role_id', $request->role_id)->delete();

        Role::find($request->role_id)->update([
            'name' => $request->name,
        ]);

        if (isset($request->currentPermissions) && count($request->currentPermissions) > 0) {
            foreach ($request->currentPermissions as $key => $val) {
                RoleHasPermission::create([
                    'permission_id' => $val,
                    'role_id' => $request->role_id
                ]);
            }
        }

        $properties = [
            'attributes' => [
                'id' => $request->role_id,
                'name' => $request->name,
            ],
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has updated a role",
            'subject_type' => 'App\Models\Role',
            'event' => 'updated',
            'subject_id' => $request->role_id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::user()->id,
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now()
        ]);


        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function getRoleDetails($id)
    {
        $role = DB::table('roles as r')
            ->select('r.id', 'r.name')
            ->where('r.id', $id)
            ->first();

        $groupPermissions = $this->getGroupPermissions();

        $permissionsByRole = DB::table('role_has_permissions as rhp')
            ->join('permissions as p', 'p.id', '=', 'rhp.permission_id')
            ->select('p.id', 'p.name')
            ->where('rhp.role_id', $id)
            ->get();

        // add field to groupPermissions to check if permission is assigned to role
        foreach ($groupPermissions as $groupPermission) {
            $groupPermission->assigned = false;
            foreach ($permissionsByRole as $permissionByRole) {
                if ($groupPermission->permission_id == $permissionByRole->id) {
                    $groupPermission->assigned = true;
                }
            }
        }

        return Inertia::render('Role/ViewRole', ['role' => $role, 'permissions' => $groupPermissions]);
    }

    public function addPermissionsToRole(Request $request)
    {
        // validations
        // $request->validate([
        //     'role_id' => 'required|integer',
        //     'permissions' => 'required|array',
        // ]);

        foreach ($request->permissions as $permission) {
            $rolehaspermissions = DB::table('role_has_permissions')->insert([
                'permission_id' => $permission,
                'role_id' => $request->role_id
            ]);

            $properties = [
                'attributes' => [
                    'role_id' => $request->role_id,
                    'permission_id' => $permission,
                    'permission_name' => Permission::find($permission)->name,
                ],
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has added permissions to role",
                'subject_type' => 'App\Models\RoleHasPermission',
                'event' => 'created',
                'subject_id' => $rolehaspermissions,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::user()->id,
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'Permissions added to role successfully']);
    }

    public function deletePermissionsFromRole(Request $request)
    {
        // validations
        // $request->validate([
        //     'role_id' => 'required|integer',
        //     'permissions' => 'required|array',
        // ]);

        foreach ($request->permissions as $permission) {

            $oldPermission = DB::table('permissions')
                ->where('id', $permission)
                ->first();

            $rolehaspermissions = DB::table('role_has_permissions')
                ->where('permission_id', $permission)
                ->where('role_id', $request->role_id)
                ->delete();

            $properties = [
                'attributes' => [
                    'role_id' => $request->role_id,
                    'permission_id' => $permission,
                    'permission_name' => $oldPermission->name,
                ],
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has deleted permissions from role",
                'subject_type' => 'App\Models\RoleHasPermission',
                'event' => 'deleted',
                'subject_id' => $rolehaspermissions,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::user()->id,
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'Permissions deleted from role successfully']);
    }
}
