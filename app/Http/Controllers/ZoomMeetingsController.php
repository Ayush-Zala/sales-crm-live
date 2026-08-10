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

        $rolesarr = \Illuminate\Support\Facades\Auth::user()->getRoleNames();
        $allowedUserIds = null;
        if (!$rolesarr->contains('Admin')) {
            if ($rolesarr->contains('Business Development Manager') || $rolesarr->contains('Business Development Team Lead')) {
                $allowedUserIds = User::where('reporting_authority_id', \Illuminate\Support\Facades\Auth::id())->pluck('id')->toArray();
                $allowedUserIds[] = \Illuminate\Support\Facades\Auth::id();
            } else {
                $allowedUserIds = [\Illuminate\Support\Facades\Auth::id()];
            }
        }

        $zoomMeetings = MeetingLog::join('users', 'meeting_logs.user_id', '=', 'users.id')
            ->select('meeting_logs.*', 'users.name as user_name')
            ->orderBy('meeting_logs.id', 'desc');

        if ($allowedUserIds !== null) {
            $zoomMeetings = $zoomMeetings->whereIn('meeting_logs.user_id', $allowedUserIds);
        }

        if ($userFilter) {
            $zoomMeetings = $zoomMeetings->where('meeting_logs.user_id', $userFilter);
        }

        if ($search) {
            $zoomMeetings = $zoomMeetings->where('meeting_logs.topic', 'like', '%' . $search . '%');
        }

        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::now()->subDays(6)->startOfDay();
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::now()->endOfDay();

        $zoomMeetings = $zoomMeetings->whereBetween('meeting_logs.start_time', [$startDate, $endDate]);

        $analytics = \App\Services\ZoomAnalyticsService::getMeetingAnalytics($startDate, $endDate, $search, $userFilter, $allowedUserIds);

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

        $usersQuery = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('users.id', 'users.name')
            ->whereIn('roles.id', [$sales_Exec_role_id, $bd_team_lead_role_id, $bd_manager_role_id])
            ->where('users.is_active', 1);

        if ($allowedUserIds !== null) {
            $usersQuery = $usersQuery->whereIn('users.id', $allowedUserIds);
        }

        $users = $usersQuery->get();


        return Inertia::render('ZoomMeetings/Index', [
            'zoomMeetings' => $zoomMeetings,
            'users' => $users,
            'analytics' => $analytics,
        ]);
    }
}
