<?php

namespace App\Http\Controllers;

use App\Models\MeetingLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ZoomMeetingsController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->filter;
        $userFilter = $request->user;
        $search = $request->search;

        $zoomMeetings = MeetingLog::join('users', 'meeting_logs.user_id', '=', 'users.id')
            ->select('meeting_logs.*', 'users.name as user_name')
            ->orderBy('meeting_logs.id', 'desc');

        if ($userFilter) {
            $zoomMeetings = $zoomMeetings->where('meeting_logs.user_id', $userFilter);
        }

        if ($search) {
            $zoomMeetings = $zoomMeetings->where('meeting_logs.topic', 'like', '%' . $search . '%');
        }


        if ($request->per_page) {
            $zoomMeetings = $zoomMeetings->paginate($request->per_page)->withQueryString();
        } else {
            $zoomMeetings = $zoomMeetings->paginate(25)->withQueryString();
        }


        // get users list where role is Sales Executives or Business Development Team Lead. role id is defined in model_has_roles table in database
        $sales_Exec_role_id = DB::table('roles')->select('id')
            ->where('name', 'Sales Executives')
            ->value('id');

        $bd_team_lead_role_id = DB::table('roles')->select('id')
            ->where('name', 'Business Development Team Lead')
            ->value('id');

        $bd_manager_role_id = DB::table('roles')->select('id')
            ->where('name', 'Business Development Manager')
            ->value('id');

        $users = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('users.id', 'users.name')
            ->whereIn('roles.id', [$sales_Exec_role_id, $bd_team_lead_role_id, $bd_manager_role_id])
            ->where('users.is_active', 1)
            ->get();


        return Inertia::render('ZoomMeetings/Index', [
            'zoomMeetings' => $zoomMeetings,
            'users' => $users,
        ]);
    }
}
