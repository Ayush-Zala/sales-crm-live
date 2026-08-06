<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;
use Illuminate\Support\Collection;

class CalendarController extends Controller
{

    public $loggedinUser;

    public function __construct()
    {
        $this->loggedinUser = Auth::check() ? Auth::user()->name : null;
    }

    public function index()
    {
        $sel = Calendar::select(
            "calendars.id as id",
            "calendars.title as title",
            "calendars.start_date as start",
            "calendars.end_date as end",
            "calendars.description as description",
            "calendars.repeat_rule as rRule",
            "calendars.all_day as allDay",
            // "users.name",
            // "users.id as userid"
        )->join('users', 'calendars.created_by', '=', 'users.id')
            ->where('created_by', Auth::id())->get();

        return Inertia::render('Event/Events', ['calendardata' => $sel]);
    }

    public function Create(Request $request)
    {
        // validations
        // $request->validate([
        //     'title' => 'required|string',
        //     'start' => 'required|number',
        //     'end' => 'required|number',
        //     'description' => 'nullable|string',
        //     'allDay' => 'nullable|boolean',
        //     'color' => 'nullable|string',
        // ]);

        $ins = Calendar::Create([
            'title' => $request->title,
            'repeat_rule' => $request->rRule,
            'start_date' => $request->start,
            'end_date' => $request->end,
            'description' => $request->description,
            'all_day' => $request->allDay,
            'colors' => '#8E33FF',
            'timezone' => $request->timezone,
            'company_id' => $request->company_id,
            'client_id' => $request->client_id,
            'zoom_meeting' => $request->zoomMeeting,
            'zoom_meeting_id' => $request->zoom_meeting_id,
            'zoom_meeting_url' => $request->zoom_meeting_url,
            'zoom_meeting_details' => json_encode($request->zoom_meeting_details),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        if ($ins) {

            $ins->name = Auth::user()->name;

            $properties = [
                'attributes' => [
                    'title' => $request->title,
                    'repeat_rule' => $request->rRule,
                    'start_date' => $request->start,
                    'end_date' => $request->end,
                    'description' => $request->description,
                    'all_day' => $request->all_day,
                    'timezone' => $request->timezone,
                    'zoom_meeting_url' => $request->zoom_meeting_url,
                    'company_id' => $request->company_id,
                    'client_id' => $request->client_id,
                ]
            ];

            ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has created event",
                'subject_type' => 'App\Models\Calendar',
                'event' => 'created',
                'subject_id' => $ins->id,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response(["status" => 1, 'data' => $ins, 'message' => 'Event created successfully']);
        } else {
            return response(["status" => 0]);
        }
    }

    public function Getdata()
    {
        $sel = Calendar::select("title as text", "start_date as startDate", "end_date as endDate", "id as calendarid")->where('created_by', Auth::id())->get();
        return response($sel);
    }

    public function delete(Request $request)
    {
        // validations
        $request->validate([
            'id' => 'required|integer',
        ]);

        $event = Calendar::where('id', $request->id)->first();

        $del = Calendar::where('id', $request->id)->delete();

        if ($del) {
            $properties = [
                'attributes' => [
                    'id' => $request->id,
                    'name' => $event->title,
                    'description' => $event->description,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'repeat_rule' => $event->repeat_rule,
                    'all_day' => $event->all_day,
                ]
            ];

            ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has deleted event",
                'subject_type' => 'App\Models\Calendar',
                'event' => 'deleted',
                'subject_id' => $request->id,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response(["status" => 1, 'message' => 'Event deleted successfully']);
        } else {
            return response(["status" => 0, 'message' => 'Failed to delete the event']);
        }
    }

    public function update(Request $request)
    {
        // try {
        //     $request->validate([
        //         'calendarid' => 'required|exists:calendars,id',
        //         'start' => 'nullable|date',
        //         'end' => 'nullable|date',
        //         'title' => 'nullable|string',
        //         'description' => 'nullable|string',
        //         'rRule' => 'nullable|string',
        //         'allDay' => 'nullable|boolean',
        //         'color' => 'nullable|string',
        //     ]);
        // } catch (\Illuminate\Validation\ValidationException $th) {
        //     return response(["status" => 0, 'error' => $th->validator->errors()]);
        // }

        // dd($request->all());

        // Find the calendar entry by ID
        $calendar = Calendar::find($request->id);
        // $columns = $request->all();

        $calendar->update([
            'title' => $request->title,
            'repeat_rule' => $request->rRule,
            'start_date' => $request->start,
            'end_date' => $request->end,
            'description' => $request->description,
            'all_day' => $request->allDay,
            'timezone' => $request->timezone,
            'company_id' => $request->company_id,
            'client_id' => $request->client_id,
            'updated_by' => Auth::id(),
        ]);

        // if ($calendar) {
        //     // Loop through the request data
        //     //dd($columns);
        //     if (array_key_exists("zoomMeeting", $columns)) {
        //         $calendar->zoom_meeting = $columns['zoomMeeting'];

        //     }
        //     if (array_key_exists("zoomMeetingId", $columns)) {
        //         $calendar->zoom_meeting_id = $columns['zoom_meeting_id'];

        //     }
        //     if (array_key_exists("zoomMeeting", $columns)) {
        //         $calendar->zoom_meeting = $columns['zoomMeeting'];

        //     }
        //     foreach ($columns as $key => $value) {
        //         // Skip keys that are not in the model attributes or are not needed
        //         if (in_array($key, ['calendarid', 'id'])) {
        //             continue;
        //         }

        //         if ($key === 'description') {
        //             continue;
        //         }

        //         if ($key === 'rRule') {
        //             $calendar->repeat_rule = $value;
        //             continue;
        //         }

        //         if ($key === 'color') {
        //             $calendar->colors = $value;
        //             continue;
        //         }

        //         if ($key === 'allDay') {
        //             $calendar->all_day = $value;
        //             continue;
        //         }



        //         if ($key === 'zoomMeetingId') {
        //             // dd('test');
        //             // $calendar->zoom_meeting_id = $value !== "" ? $value : null;
        //             $calendar->zoom_meeting_id = null;
        //         }

        //         if ($key === 'zoomMeetingUrl') {
        //             // $calendar->zoom_meeting_url = $value;
        //         }

        //         if ($key === 'zoomMeetingDetails') {
        //             // $calendar->zoom_meeting_details = json_encode($value);
        //         }


        //         // If the key is startDate or endDate, handle date formatting
        //         if ($key === 'start' || $key === 'end') {

        //             if ($key === 'start') {

        //                 // if value is like this: 2021-09-01 00:00:00  then convert it to timestamp
        //                 // if (strpos($value, 'T') !== false) {
        //                 //     $dateString = strtotime($value);
        //                 //     $date = (new \DateTime())->setTimestamp($dateString);
        //                 //     //$calendar->start_date = $date->format('Y-m-d H:i:s');
        //                 // } else {
        //                 //     $dateString = $request->start / 1000;
        //                 //     $date = (new \DateTime())->setTimestamp($dateString);
        //                 //     //  $calendar->start_date = $date->format('Y-m-d H:i:s');
        //                 // }
        //             } else if ($key === 'end') {

        //                 // if value is like this: 2021-09-01 00:00:00  then convert it to timestamp
        //                 // if (strpos($value, 'T') !== false) {
        //                 //     $dateString = strtotime($value);
        //                 //     $date = (new \DateTime())->setTimestamp($dateString);
        //                 //     //    $calendar->end_date = $date->format('Y-m-d H:i:s');
        //                 // } else {
        //                 //     $dateString = $request->end / 1000;
        //                 //     $date = (new \DateTime())->setTimestamp($dateString);
        //                 //     //  $calendar->end_date = $date->format('Y-m-d H:i:s');
        //                 // }
        //             }




        //         } else {
        //             $calendar->$key = $value;
        //         }


        //     }

        //     $calendar->updated_by = Auth::id();

        // Save the updated entry
        if ($calendar) {

            $properties = [
                'attributes' => [
                    'title' => $calendar->title,
                    'repeat_rule' => $calendar->repeat_rule,
                    'start_date' => $calendar->start_date,
                    'end_date' => $calendar->end_date,
                    'description' => $calendar->description,
                    'all_day' => $calendar->all_day,
                    'timezone' => $calendar->timezone,
                ]
            ];

            ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has updated event",
                'subject_type' => 'App\Models\Calendar',
                'event' => 'updated',
                'subject_id' => $calendar->id,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response(["status" => 1, 'data' => $calendar, 'message' => 'Event updated successfully.']);

            // } else {
            //     return response(["status" => 0, "message" => "Failed to save the update."]);
            // }
        } else {
            // Handle the case where the calendar entry is not found
            return response(["status" => 0, "message" => "Calendar entry not found."]);
        }

    }

    public function getEventByRange(Request $request)
    {
        // Validate and parse the date range inputs from the request
        $startDate = $request->startDate; // Start date for the range
        $endDate = $request->endDate; // End date for the range
        $user = $request->user; // Get the authenticated user
        $reportingauthorityid = $request->manager;

        $rolesarr = new Collection(Auth::user()->getRoleNames());

        // Validate that start_date and end_date are provided
        if (!$startDate || !$endDate) {
            return response(["error" => "Please provide a valid date range."], 400);
        }

        if ($user) {
            // match reporting authority id (check that user has reporting authority id same as $reportingauthorityid) if not matched then return error message
            $rpaId = User::where('id', $user)->value('reporting_authority_id');
            if ($rpaId != $reportingauthorityid && $reportingauthorityid != 'all') {
                return response(["error" => "Please select a valid reporting authority."], 403);
            }
        }

        if ($rolesarr->contains('Business Development Manager')) {
            $events = Calendar::select(
                "calendars.id as id",
                "calendars.title as title",
                "calendars.start_date as start_date",
                "calendars.end_date as end_date",
                "calendars.description as description",
                "calendars.repeat_rule as repeat_rule",
                "calendars.all_day as all_day",
                "calendars.timezone as timezone",
                "calendars.company_id as company_id",
                "users.name as user_name",
                "users.id as userid",
                'companies.name as company_name',
            )
                ->join('users', 'users.id', '=', 'calendars.created_by')
                ->join('companies', 'companies.id', '=', 'calendars.company_id')
                ->where('users.reporting_authority_id', Auth::id())
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('calendars.start_date', [$startDate, $endDate])
                        ->orWhereBetween('calendars.end_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('calendars.start_date', '<=', $startDate)
                                ->where('calendars.end_date', '>=', $endDate);
                        });
                })->get();
        } else {
            // Query events within the specified date range
            $events = Calendar::select(
                "calendars.id as id",
                "calendars.title as title",
                "calendars.start_date as start_date",
                "calendars.end_date as end_date",
                "calendars.description as description",
                "calendars.repeat_rule as repeat_rule",
                "calendars.all_day as all_day",
                "calendars.timezone as timezone",
                "calendars.company_id as company_id",
                "users.name as user_name",
                "users.id as userid",
                'companies.name as company_name',
            )
                ->join('users', 'users.id', '=', 'calendars.created_by')
                ->join('companies', 'companies.id', '=', 'calendars.company_id')
                ->where(function ($query) use ($reportingauthorityid, $user) {
                    if ($user) {
                        // If a specific user is selected, show only that user's events
                        $query->where('users.id', $user);
                    } elseif ($reportingauthorityid !== 'all' && $reportingauthorityid !== null) {
                        // If only a manager is selected (no user), show all users under that manager
                        $query->where('users.reporting_authority_id', $reportingauthorityid);
                    }
                    // If 'all' is selected with no user, no filter — show all events
                })
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('calendars.start_date', [$startDate, $endDate])
                        ->orWhereBetween('calendars.end_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('calendars.start_date', '<=', $startDate)
                                ->where('calendars.end_date', '>=', $endDate);
                        });
                })->get();
        }

        return response(["data" => $events]);
    }

    public function searchCompany(Request $request)
    {
        $query = $request->input('company');
        $user = Auth::user(); // Get the authenticated user

        $companies = Company::query();

        $isSalesExecutive = in_array('Sales Executives', $user->roles->pluck('name')->toArray());

        $isBDM = in_array('Business Development Manager', $user->roles->pluck('name')->toArray());

        if ($isSalesExecutive) {
            // Sales Executive: Get only the companies assigned to this user
            $companies->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($isBDM) {
            // BDM: Get companies assigned to users who report to this user
            $companies->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('assign_by', $user->id);
            });
        }
        // Admin: No additional filtering needed; they see all companies

        // Apply search query
        $companies->where('name', 'LIKE', '%' . $query . '%');

        // Get results with necessary fields
        $result = $companies->take(10)->get(['id', 'name']);

        return response()->json($result);
    }

    public function searchUser(Request $request)
    {
        $query = $request->input('user');
        $user = Auth::user(); // Get the authenticated user
        $roles = $user->roles->pluck('name')->toArray(); // Convert roles to array of role names

        $users = User::query();

        if (in_array('Sales Executives', $roles)) {
            // Sales Executive: Get only the users assigned to this user
            $users->where('id', $user->id);
        } elseif (in_array('Business Development Manager', $roles)) {
            // BDM: Get users who report to this user
            $users->where('reporting_authority_id', $user->id);
        }
        // Admin: No additional filtering needed; they see all users

        // Apply search query
        $users->where('name', 'LIKE', '%' . $query . '%');

        // Get results with necessary fields
        $result = $users->select('id', 'name')->get();

        return response()->json($result);
    }

    public function getTodaysEvents(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user

        $events = Calendar::query();

        // Sales Executive: Get only the companies assigned to this user
        $events->where('created_by', $user->id);

        // Admin: No additional filtering needed; they see all companies

        // Apply search query
        $events->whereDate('start_date', Carbon::today());

        // Get results with necessary fields
        $result = $events->get();

        return response()->json($result);
    }
}
