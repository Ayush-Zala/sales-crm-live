<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $roles = Auth::user()->roles->pluck('name')->toArray();

        $isAdmin = in_array('Admin', $roles);
        $isBDM = in_array('Business Development Manager', $roles);
        $isBDTL = in_array('Business Development Team Lead', $roles);


        if ($isBDM) {
            // get all the users under the BDM
            $users = User::where('reporting_authority_id', $userId)->pluck('id')->toArray();

            // all the logs of the user where the reporting authority is loggedin user
            $activity = DB::table('activity_logs as a')
                ->select('a.id', 'a.description', 'a.subject_type', 'a.event', 'a.subject_id', 'a.properties', 'a.created_at', 'a.updated_at', 'u.name')
                ->leftJoin('users as u', 'u.id', '=', 'a.causer_id')
                ->whereIn('a.causer_id', array_merge([$userId], $users))
                ->orderBy('a.id', 'desc');
        } else if ($isBDTL) {

            // get reporting authority id of the user
            $reportingAuthorityId = User::where('id', $userId)->pluck('reporting_authority_id')->first();

            // get all the users under the BDM
            $users = User::where('reporting_authority_id', $reportingAuthorityId)->pluck('id');

            // all the logs of the user where the reporting authority is loggedin user
            $activity = DB::table('activity_logs as a')
                ->select('a.id', 'a.description', 'a.subject_type', 'a.event', 'a.subject_id', 'a.properties', 'a.created_at', 'a.updated_at', 'u.name')
                ->leftJoin('users as u', 'u.id', '=', 'a.causer_id')
                ->whereIn('a.causer_id', $users)
                ->orderBy('a.id', 'desc');

        } else {

            $activity = DB::table('activity_logs as a')
                ->select('a.id', 'a.description', 'a.subject_type', 'a.event', 'a.subject_id', 'a.properties', 'a.created_at', 'a.updated_at', 'u.name')
                ->leftJoin('users as u', 'u.id', '=', 'a.causer_id')
                ->orderBy('a.id', 'desc');

        }

        if ($request->per_page) {
            $activity = $activity->paginate($request->per_page);
        } else {
            $activity = $activity->paginate(10);
        }
        return Inertia::render('Logs/Index', [
            'activities' => $activity
        ]);
    }
}
