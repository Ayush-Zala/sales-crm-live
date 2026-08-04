<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Jubaer\Zoom\Zoom;

class EventController extends Controller
{
    public function index(Request $request)
    {
        // Get authenticated user's ID
        $user = auth()->user();
        $userId = Auth::id();
        $zoom = new Zoom();

        // Get the roles of the authenticated user
        $roles = $user->roles->pluck('name')->toArray(); // Convert roles to array of role names

        // Check if the user is Admin or Business Development Manager
        $isAdminOrBDM = in_array('Admin', $roles) || in_array('Business Development Manager', $roles);

        // Get the month parameter from the request, default to current month
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Get the userId parameter from the request if searching for a specific user
        $searchUserId = $request->input('user');

        $calendardata = Calendar::select(
            "calendars.title as title",
            "calendars.start_date as start",
            "calendars.end_date as end",
            "calendars.id as id",
            'calendars.phone as phone',
            'calendars.company_id as companyId',
            "companies.name as companyName",
            "calendars.zoom_meeting as zoomMeeting",
            'calendars.zoom_meeting_id as zoomMeetingId',
            "calendars.zoom_meeting_url as zoomMeetingUrl",
            "calendars.zoom_meeting_details as zoomMeetingDetails",
            "calendars.repeat_rule as rRule",
            "calendars.all_day as allDay",
            "calendars.description as description",
            'calendars.timezone as timezone',
            "users.name",
            "users.id as userid"
        )->join('users', 'calendars.created_by', '=', 'users.id')
            ->leftJoin('companies', 'calendars.company_id', '=', 'companies.id')
            ->whereMonth('calendars.start_date', $month) // Filter events for the selected month
            ->whereYear('calendars.start_date', $year);


        // Apply filtering based on user roles
        if (!$isAdminOrBDM) {
            // If the user is not Admin or Business Development Manager, filter by created_by
            $calendardata = $calendardata->where('created_by', $userId);
        }

        // If a user ID is provided, filter the events by userId
        if ($searchUserId) {
            $calendardata = $calendardata->where('users.id', $searchUserId);
        }

        $calendardata = $calendardata->get();

        // Get the zoom meetings
        $meetings = $zoom->getAllMeeting();

        return Inertia::render('Event/Index', [
            'calendardata' => $calendardata,
            'meetings' => $meetings,
            'selectedMonth' => $month,
            'selectedYear' => $year,
        ]);
    }

    public function version2()
    {
        $sel = Calendar::select("title as text", "start_date as startDate", "end_date as endDate")->where('created_by', Auth::id())->get();

        return Inertia::render('Event/Events', ['calendardata' => $sel]);
    }
}
