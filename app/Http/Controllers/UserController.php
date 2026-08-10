<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AssignCompanies;
use App\Models\Company;
use App\Models\Disposition;
use App\Models\DispositionStatus;
use App\Models\Group;
use App\Models\ModelHasPermission;
use App\Models\RoleHasPermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public $filter;
    public $loggedinUser;

    public function __construct()
    {
        $this->loggedinUser = Auth::check() ? Auth::user()->name : null;
    }

    public function index(Request $request)
    {
        $this->filter = $request->filter;

        $rolesarr = new Collection(Auth::user()->getRoleNames());

        if ($rolesarr->contains('admin') || $rolesarr->contains('Admin')) {

            $query = DB::table('users as u1')
                ->select('u1.id', 'u1.name', 'u1.email', 'u1.is_active', 'u1.reporting_authority_id', 'u2.name as reporting_authority_name')
                ->leftJoin('users as u2', 'u1.reporting_authority_id', '=', 'u2.id')
                ->where(function ($q) use ($request) {
                    $q->where('u1.name', 'like', '%' . $request->search . '%')
                        ->orWhere('u1.email', 'like', '%' . $request->search . '%');
                });
        } else {

            $query = DB::table('users as u1')
                ->select(
                    'u1.id',
                    'u1.name',
                    'u1.email',
                    'u1.is_active',
                    'u1.reporting_authority_id',
                    'u2.name as reporting_authority_name'
                )
                ->leftJoin('users as u2', 'u1.reporting_authority_id', '=', 'u2.id')
                ->where('u1.reporting_authority_id', Auth::id()) // Ensure this is applied first
                ->where(function ($q) use ($request) {
                    $q->where('u1.name', 'like', '%' . $request->search . '%')
                        ->orWhere('u1.email', 'like', '%' . $request->search . '%');
                }); // Group the OR conditions
        }

        if ($request->filter == 'active') {
            $query->where('u1.is_active', '=', 1);
        } else if ($request->filter == 'inactive') {
            $query->where('u1.is_active', 0);
        } elseif ($request->filter === 'online_users_only') {
            $query->whereRaw('u1.id IN (SELECT user_id FROM sessions)');
        }


        if ($request->per_page == null) {
            $user = $query->paginate(50);
        } else {
            $user = $query->paginate($request->per_page);
        }

        $user->getCollection()->transform(function ($user) {
            $isOnline = DB::table('cache')->select('key')->where('key', '=', 'user-is-online-' . $user->id)->where('value', '=', 'b:1;')->get(); // Add the isOnline field

            $user->isOnline = count($isOnline) > 0 ? true : false;
            return $user;
        });

        $user->getCollection()->transform(function ($user) {
            $user->role = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', '=', $user->id)
                ->value('roles.name');

            return $user;
        });

        // get total assign companies to user
        $user->getCollection()->transform(function ($user) {
            $user->assigned_accounts = AssignCompanies::where('user_id', $user->id)
                ->where('is_active', true)
                ->distinct('company_id')
                ->count();

            return $user;
        });

        return Inertia::render('User/Index', ['users' => $user]);
    }

    public function getUserByRole(Request $request)
    {
        $rolesarr = new Collection(Auth::user()->getRoleNames());

        if ($rolesarr->contains('admin') || $rolesarr->contains('Admin')) {

            $query = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('users.id', 'users.name')
                ->whereIn('roles.name', (array) $request->role)
                ->where('users.is_active', true)
                ->orderBy('users.name', 'asc')
                ->get();

        } else {

            $query = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('users.id', 'users.name')
                ->whereIn('roles.name', (array) $request->role)
                ->where('users.reporting_authority_id', Auth::id())
                ->where('users.is_active', true)
                ->orderBy('users.name', 'asc')
                ->get();
        }

        // add field isOnline for each user
        $query = $query->map(function ($user) {
            $isOnline = DB::table('cache')->select('key')->where(
                'key',
                '=',
                'user-is-online-' . $user->id
            )->where('value', '=', 'b:1;')->exists(); // Use 'exists()' for better performance

            // Add the isOnline field
            $user->isOnline = $isOnline ? true : false;

            return $user;
        });

        return $query;
    }

    public function getGroupPermissions()
    {
        // $groupPermissions = DB::table('group_has_permissions')
        //     ->join('groups', 'group_has_permissions.group_id', '=', 'groups.id')
        //     ->join('permissions', 'group_has_permissions.permission_id', '=', 'permissions.id')
        //     ->select('groups.name as group_name', 'permissions.name as permission_name', 'permissions.id as permission_id', 'groups.id as group_id')
        //     ->get();

        $groupPermissions = Group::with('permissions:id,name')->get(['id', 'name'])->map(function ($group) {
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

        return $groupPermissions;
    }

    public function getReportingAuthorities()
    {
        // $reportingAuthoritiesRoles = DB::table('roles')->select('name')
        //     ->where('name', '=', 'admin')
        //     ->orWhere('name', 'like', '%manager')
        //     ->orWhere('name', 'like', '%team-lead')
        //     ->get();

        // get roles id where name ends with manager, team-lead, admin from roles table
        $reportingAuthoritiesRoles = DB::table('roles')->select('id')
            ->where('name', '=', 'admin')
            ->orWhere('name', 'like', '%manager')
            ->orWhere('name', 'like', '%team-lead')
            ->get();

        $user = User::select('users.id', 'users.name', 'roles.name as role')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('model_has_roles.role_id', $reportingAuthoritiesRoles->pluck('id')->toArray())
            ->where('is_active', true)
            ->get();

        $user = array_map(function ($u) {
            return $u;
        }, $user->toArray());

        return $user;
    }

    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::with('permissions:id,name')->get(['id', 'name']);

        $groupPermissions = $this->getGroupPermissions();

        // get list of roles from roles table where name ends with manager, team-lead, admin
        $reportingAuthorities = $this->getReportingAuthorities();

        return Inertia::render('User/CreateUser', ['roles' => $roles, 'groupPermissions' => $groupPermissions, 'reportingAuthorities' => $reportingAuthorities]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'reportingAuthority' => 'nullable|integer',
            'roleName' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $user = User::create([
            'name' => $request->username, // changed from username to name
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'reporting_authority_id' => $request->reportingAuthority,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($user) {
            $user->assignRole($request->roleName);
            $user->givePermissionTo($request->permissions);

            $properties = [
                'attributes' => [
                    'name' => $request->username,
                    'email' => $request->email,
                ],
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has created user",
                'subject_type' => 'App\Models\User',
                'event' => 'created',
                'subject_id' => $user->id,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
        }

        return response()->json(['message' => 'User not created'], 400);

    }

    public function getuserdetails(Request $request)
    {
        $user = User::find($request->id);
        $roles = DB::table('roles')->select('name')->get();

        return ['user' => $user, 'roles' => $roles];
    }

    public function getUsersByAuthorityId()
    {
        // get id of logged in user
        $id = Auth::id();

        $isAdmin = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', '=', $id)
            ->where('roles.name', '=', 'admin')
            ->exists();

        // if id is admin then get the users which has role Sales Executive
        if ($isAdmin) {
            $users = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'Sales Executives')
                ->where('users.is_active', true)
                ->select('users.id', 'users.name')
                ->get();

            return $users;
        } else {
            $users = User::select('id', 'name')
                ->where('reporting_authority_id', $id)
                ->where('is_active', true)
                ->get();

            return $users;
        }
    }

    public function edit($id)
    {
        $user = User::find($id);
        $roles = DB::table('roles')->select('name')->get();
        $groupPermissions = $this->getGroupPermissions();
        $reportingAuthorities = $this->getReportingAuthorities();

        // get user role
        $user->role = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', '=', $id)
            ->value('roles.name');

        $roleId = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', '=', $id)
            ->value('roles.id');

        $rolePermissions = RoleHasPermission::where('role_id', $roleId)->pluck('permission_id')->toArray();

        $userPermissions = ModelHasPermission::where('model_id', $id)->pluck('permission_id')->toArray();

        return Inertia::render('User/EditUser', ['user' => $user, 'roles' => $roles, 'groupPermissions' => $groupPermissions, 'reportingAuthorities' => $reportingAuthorities, 'userPermissions' => $userPermissions, 'rolePermissions' => $rolePermissions]);
    }

    public function update(Request $request)
    {
        // validations
        $request->validate([
            'user_id' => 'required|integer',
            'name' => 'required|string',
            'email' => 'required|email',
            'reportingAuthority' => 'nullable|integer',
            'roleName' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $user = User::find($request->user_id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->reporting_authority_id = $request->reportingAuthority;
        $user->updated_at = now();

        if ($user) {
            $oldRole = $user->getRoleNames();

            if ($oldRole->count() > 0) {
                $oldRoles = $oldRole[0];
                $user->removeRole($oldRoles);
            }

            $user->save();

            $user->assignRole($request->roleName);
            $user->syncPermissions($request->permissions ?? []);

            $properties = [
                'attributes' => [
                    'name' => $request->name,
                    'email' => $request->email,
                ],
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has updated user",
                'subject_type' => 'App\Models\User',
                'event' => 'updated',
                'subject_id' => $request->user_id,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'User updated successfully');
        } else {
            return redirect()->back()->with('error', 'User not updated');
        }
    }

    public function toggleActiveStatus(Request $request)
    {
        // validations
        $request->validate([
            'id' => 'required|integer',
            'is_active' => 'required|boolean',
        ]);

        try {
            // Unassign all companies of user if user is inactive
            if ($request->is_active == false) {
                $unassigned = AssignCompanies::where('user_id', $request->id)
                    ->update(['is_active' => false]);

                // Update the Company table, unassigning the user
                $companyUpdate = Company::where('assign_to', $request->id)
                    ->update(['assign_to' => null, 'assign_by' => null]);

            }

            $user = User::where('id', $request->id)->first(); // Retrieve the user first

            // If no user is found, throw an exception
            if (!$user) {
                throw new \Exception("User not found.");
            }

            // Perform the update
            $user->update([
                'is_active' => $request->is_active ? 1 : 0,
            ]);

            $properties = [
                'attributes' => [
                    'user_id' => $request->id,
                    'user_name' => $user->name,
                    'is_active' => $request->is_active,
                ],
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has updated user status",
                'subject_type' => 'App\Models\User',
                'event' => 'updated',
                'subject_id' => $request->id,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'User status updated'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            // Return error response
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteuser(Request $request)
    {
        // validations
        $request->validate([
            'id' => 'required|integer',
        ]);

        $user = User::find($request->id);
        $user->delete();

        $properties = [
            'attributes' => [
                'user_id' => $request->id,
                'user_name' => $user->name,
            ],
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has deleted user",
            'subject_type' => 'App\Models\User',
            'event' => 'deleted',
            'subject_id' => $request->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'User deleted successfully'], 201);
    }

    public function report($id)
    {
        $userdetail = User::find($id);
        $totalassignuser = AssignCompanies::where('user_id', $id)->distinct('company_id')->count();
        $totalassign = AssignCompanies::distinct('company_id')->count();
        $dispositionList = DispositionStatus::select('id', 'name')->get();
        $unassign = $totalassign - $totalassignuser;
        $yesterday = date("Y-m-d", strtotime("-1 days"));
        $weekday = date("Y-m-d", strtotime("-7 days"));
        $monthday = date("Y-m-d", strtotime("-30 days"));

        $call = Disposition::where('user_id', $id)->count();
        $calltoday = Disposition::where('user_id', $id)
            ->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')->count();

        $dispostatus = DispositionStatus::select('id', 'name')->get();
        $detilarr = [];
        foreach ($dispostatus as $dispo) {
            $detailarr['life'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->count();

            $detailarr['today'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                ->count();

            $detailarr['yesterday'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . $yesterday . '%')
                ->count();

            $detailarr['last_7_day'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->where([['updated_at', '>=', $weekday], ['updated_at', '<=', date("Y-m-d")]])
                ->count();

            $detailarr['last_30_day'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->where([['updated_at', '>=', $monthday], ['updated_at', '<=', date("Y-m-d")]])
                ->count();
        }

        $detail = [
            'name' => $userdetail->name,
            'user_id' => $id,
            'disposition_list' => $dispositionList,
            'total_call_made' => $call,
            'today_call_made' => $calltoday,
            'unassigned' => $unassign,
            'total_assigned' => $totalassignuser,
            'detail_report' => $detailarr
        ];

        return Inertia::render('User/Report', ['report' => $detail]);
    }

    public function customreport($id)
    {
        $userdetail = User::find($id);

        $yesterday = date("Y-m-d", strtotime("-1 days"));
        $weekday = date("Y-m-d", strtotime("-7 days"));
        $monthday = date("Y-m-d", strtotime("-30 days"));

        $dispostatus = DispositionStatus::select('id', 'name')->get();
        $detilarr = [];


        foreach ($dispostatus as $dispo) {
            $detailarr['life'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->count();
            $detailarr['today'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                ->count();
            $detailarr['yesterday'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . $yesterday . '%')
                ->count();

            $detailarr['last_7_day'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->where([['updated_at', '>=', $weekday], ['updated_at', '<=', date("Y-m-d")]])
                ->count();
            $detailarr['last_30_day'][$dispo->name] = Disposition::where('user_id', $id)
                ->where('status_id', $dispo->id)->where([['updated_at', '>=', $monthday], ['updated_at', '<=', date("Y-m-d")]])
                ->count();
        }

        $detail = [
            'name' => $userdetail->name,
            'user_id' => $id,
            'detail_report' => $detailarr
        ];

        return response()->json($detail);
    }

    public function updateUserPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json(['message' => 'Password updated successfully'], 201);

        } catch (\Throwable $e) {
            // Return a generic error message
            return response()->json(['message' => 'Failed to update password. Please try again.'], 500);
        }
    }

    public function unassignAllCompaniesOfUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer',
            ]);

            $user = User::find($validatedData['user_id']);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $unassigned = AssignCompanies::where('user_id', $validatedData['user_id'])
                ->update(['is_active' => false]);

            Company::where('assign_to', $validatedData['user_id'])
                ->update(['assign_to' => null], ['assign_by' => null]);

            if ($unassigned === 0) {
                return response()->json(['error' => 'No companies were found to unassign for this user'], 404);
            }

            return response()->json(['message' => 'All companies are unassigned successfully'], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Invalid data', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An unexpected error occurred', 'error' => $e->getMessage()], 500);
        }
    }
}
