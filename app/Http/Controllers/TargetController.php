<?php

namespace App\Http\Controllers;

use App\Models\Target;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TargetController extends Controller
{
    public function getMonthData(Request $request)
    {
        // Get the data from the database of the time which is passed in request

        $rolesarr = new Collection(Auth::user()->getRoleNames());

        $month = strtoupper($request->month);
        $year = $request->year;
        $managerid = $request->manager;

        $time = $month . '-' . $year;

        if ($rolesarr->contains('Admin')) {
            $userdetail = User::select('users.id as userid', 'users.name as username')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('users.is_active', 1)
                ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                ->when($managerid, function ($query, $managerid) {
                    return $query->where('users.reporting_authority_id', $managerid);
                })
                ->get();


            $allTargets = [];  // Array to store all targets

            foreach ($userdetail as $user) {
                $targets = Target::select('users.name as name', 'targets.*')
                    ->join('users', 'users.id', '=', 'targets.user_id')
                    ->where('time', $time)
                    ->where('targets.user_id', $user->userid)
                    ->get();

                if ($targets->isEmpty()) {
                    $targets[] = [
                        'user_id' => $user->userid,
                        'name' => $user->username,
                        'target_achieved' => 0,
                        'target_value' => 0,
                        'time' => $time,
                    ];
                }

                // Add the user's targets to the allTargets array
                $allTargets = array_merge($allTargets, $targets->toArray());
            }

            // Now, check for any users in the targets table who are not in the userdetail list
            $existingUserIds = $userdetail->pluck('userid')->toArray();
            $additionalTargets = Target::select('users.name as name', 'targets.*')
                ->join('users', 'users.id', '=', 'targets.user_id')
                ->where('time', $time)
                ->whereNotIn('targets.user_id', $existingUserIds)
                ->when($managerid, function ($query, $managerid) {
                    return $query->where('users.reporting_authority_id', $managerid);
                })
                ->get();

            // Add the additional users from the targets table to the allTargets array
            $allTargets = array_merge($allTargets, $additionalTargets->toArray());
        } else {
            $userdetail = User::select('users.id as userid', 'users.name as username')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('users.reporting_authority_id', Auth::id())
                ->where('users.is_active', 1)
                ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                ->where('users.reporting_authority_id', $managerid)
                ->get();


            $allTargets = [];  // Array to store all targets

            foreach ($userdetail as $user) {
                $targets = Target::select('users.name as name', 'targets.*')
                    ->join('users', 'users.id', '=', 'targets.user_id')
                    ->where('time', $time)
                    ->where('targets.user_id', $user->userid)
                    ->get();

                if ($targets->isEmpty()) {
                    $targets[] = [
                        'user_id' => $user->userid,
                        'name' => $user->username,
                        'target_achieved' => 0,
                        'target_value' => 0,
                        'time' => $time,
                    ];
                }

                // Add the user's targets to the allTargets array
                $allTargets = array_merge($allTargets, $targets->toArray());
            }
        }

        return response()->json(["data" => $allTargets]);
    }

    public function getUsersWithTargets(Request $request)
    {
        // Get the data from the database of the time which is passed in request
        // $data = Target::join('users', 'users.id', '=', 'targets.user_id')
        //     ->select('users.name as name', 'targets.*')
        //     ->get();

        $rolesarr = new Collection(Auth::user()->getRoleNames());

        $month = strtoupper($request->month);
        $year = $request->year;

        $time = $month . '-' . $year;

        if ($rolesarr->contains('Admin')) {
            $data = DB::table('users')
                ->leftJoin('targets', function ($join) use ($time) {
                    $join->on('targets.user_id', '=', 'users.id')
                        ->where(DB::raw('UPPER(targets.time)'), '=', $time);
                })
                ->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->select(
                    'users.name',
                    'users.id as user_id',
                    DB::raw("COALESCE(UPPER(targets.time), '$time') as time"), // Default to $time if null
                    DB::raw('IFNULL(targets.target_value, 0) as target_value'),
                    DB::raw('IFNULL(targets.target_achieved, 0) as target_achieved')
                )
                ->where('users.is_active', 1)
                ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                ->get();

        } else {

            $data = DB::table('users')
                ->leftJoin('targets', function ($join) use ($time) {
                    $join->on('targets.user_id', '=', 'users.id')
                        ->where(DB::raw('UPPER(targets.time)'), '=', $time);
                })
                ->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->select(
                    'users.name',
                    'users.id as user_id',
                    DB::raw("COALESCE(UPPER(targets.time), '$time') as time"), // Default to $time if null
                    DB::raw('IFNULL(targets.target_value, 0) as target_value'),
                    DB::raw('IFNULL(targets.target_achieved, 0) as target_achieved')
                )
                ->where('users.is_active', 1)
                ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                ->where('users.reporting_authority_id', Auth::id())
                ->get();
        }

        // $data = DB::table('users')
        //     ->leftJoin('targets', function ($join) use ($time) {
        //         $join->on('targets.user_id', '=', 'users.id')
        //             ->where(DB::raw('UPPER(targets.time)'), '=', $time);
        //     })
        //     ->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        //     ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
        //     ->select(
        //         'users.name',
        //         'users.id as user_id',
        //         DB::raw('IFNULL(targets.target_value, 0) as target_value'),
        //         DB::raw('IFNULL(targets.target_achieved, 0) as target_achieved')
        //     )
        //     ->where('roles.name', 'Sales Executives')
        //     ->get();

        return response()->json(["targets" => $data]);
    }

    public function updateTarget(Request $request)
    {
        $time = $request->time;

        $target = Target::where('user_id', $request->user_id)
            ->where('time', $time)
            ->first();

        if (!$target) {
            // Create a new target
            $target = Target::create([
                'user_id' => $request->user_id,
                'time' => $time,
                'target_value' => $request->target_value,
                'target_achieved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(["message" => "Target created successfully"]);
        }

        // Update the target value and target achieved value
        $target = Target::where('user_id', $request->user_id)
            ->where('time', $time)
            ->update([
                'target_value' => $request->target_value,
                'updated_at' => now(),
            ]);

        return response()->json(["message" => "Target updated successfully"]);
    }
}
