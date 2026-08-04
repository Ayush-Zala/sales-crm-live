<?php

namespace App\Http\Controllers;

use App\Models\AssignCompanies;
use App\Models\Cache;
use App\Models\Calendar;
use App\Models\CallLog;
use App\Models\ClientPhone;
use App\Models\ClientsPhone;
use App\Models\Company;
use App\Models\CompanyPhone;
use App\Models\Disposition;
use App\Models\DispositionStatus;
use App\Models\Notification;
use App\Models\Target;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Auth;
use DB;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $id = Auth::id();
        $rolesarr = new Collection(Auth::user()->getRoleNames());
        $onlineUsers = 0;
        $events = [];

        $filter = $request->filter;

        if (($rolesarr->contains('Admin')) || $rolesarr->contains('Business Development Manager')) {
            if ($rolesarr->contains('Admin')) {
                $userdetail = User::select('users.id as userid', 'users.name as username')
                    ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('users.is_active', 1)
                    ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                    ->get();

                // Now, check for any users in the targets table who are not in the userdetail list
                $existingUserIds = $userdetail->pluck('userid')->toArray();
                $additionalTargets = Target::select('users.name as name', 'targets.*')
                    ->join('users', 'users.id', '=', 'targets.user_id')
                    ->where('time', strtoupper(date('M-Y')))
                    ->whereNotIn('targets.user_id', $existingUserIds)
                    ->get();

                // Add the additional users from the targets table to the allTargets array
                $allTargets = [];
                $allTargets = array_merge($allTargets, $additionalTargets->toArray());

                $saleStatusId = DispositionStatus::where('name', 'Sale')->first()->id;

                if ($filter == 'today') {
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                        ->count();
                } elseif ($filter == 'yesterday') {
                    $yesterday = date("Y-m-d", strtotime("-1 days"));
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->where('updated_at', 'like', '%' . $yesterday . '%')
                        ->count();
                } elseif ($filter == 'last_week') {
                    $weekday = date("Y-m-d", strtotime("-7 days"));
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->where([['updated_at', '>=', $weekday], ['updated_at', '<=', date("Y-m-d")]])
                        ->count();
                } elseif ($filter == 'this_month') {
                    $monthday = date("Y-m-d", strtotime("-30 days"));
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->where([['updated_at', '>=', $monthday], ['updated_at', '<=', date("Y-m-d")]])
                        ->count();
                } elseif ($filter == 'custom') {
                    $from = $request->startDateFilter;
                    $to = $request->endDateFilter;
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->where([['updated_at', '>=', $from], ['updated_at', '<=', $to]])
                        ->count();
                } else {
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)->count();
                }

                // get count of all online users from Cache
                // $onlineUsers = Cache::get('online-users', []);
                $onlineUsersCount = Cache::where('key', 'like', 'user-is-online-%')->count();
                $onlineUsers = max(0, $onlineUsersCount - 1);
                $totalUsers = User::where('is_active', 1)->count(); // Get the total number of active users

                $totalassign = AssignCompanies::distinct('company_id')
                    ->where("is_active", 1)
                    ->count();
                $unassign = Company::count() - $totalassign;

                $managersList = User::select('reporting_authority_id', DB::raw('(SELECT name FROM users as managers WHERE managers.id = users.reporting_authority_id) as manager_name'))
                    ->distinct()
                    ->whereNotNull('reporting_authority_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('model_has_roles')
                            ->where('model_has_roles.role_id', '=', 1)
                            ->whereRaw('model_has_roles.model_id = users.reporting_authority_id');
                    })
                    ->get();

                $reportData = $this->getReportDataManager($managersList);

                foreach ($userdetail as $key => $val) {
                    $targets = Target::select('users.name as name', 'targets.*')
                        ->join('users', 'users.id', '=', 'targets.user_id')
                        ->where('time', strtoupper(date('M-Y')))
                        ->where('targets.user_id', $val->userid)
                        ->get();

                    if ($targets->isEmpty()) {
                        $targets[] = [
                            'user_id' => $val->userid,
                            'name' => $val->username,
                            'target_achieved' => 0,
                            'target_value' => 0,
                            'time' => strtoupper(Carbon::now()->format('M-Y')),
                        ];
                    }

                    // Add the user's targets to the allTargets array
                    $allTargets = array_merge($allTargets, $targets->toArray());

                    $today = Carbon::today()->toDateString();
                    $last_7_days = Carbon::today()->subDays(7)->toDateString();

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
                        ->where(function ($query) use ($today, $last_7_days) {
                            $query->whereBetween('calendars.start_date', [$today, $last_7_days])
                                ->orWhereBetween('calendars.end_date', [$today, $last_7_days])
                                ->orWhere(function ($query) use ($today, $last_7_days) {
                                    $query->where('calendars.start_date', '<=', $today)
                                        ->where('calendars.end_date', '>=', $last_7_days);
                                });
                        })
                        ->get();
                }
            } else {
                $userdetail = User::select('users.id as userid', 'users.name as username')
                    ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('users.reporting_authority_id', $id)
                    ->where('users.is_active', 1)
                    ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                    ->get();

                // total sales made from Disposition table of all the users who have reporting authority id of this user
                $saleStatusId = DispositionStatus::where('name', 'Sale')->first()->id;

                if ($filter == 'today') {
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                        ->where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                        ->count();
                } elseif ($filter == 'yesterday') {
                    $yesterday = date("Y-m-d", strtotime("-1 days"));
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                        ->where('updated_at', 'like', '%' . $yesterday . '%')
                        ->count();
                } elseif ($filter == 'last_week') {
                    $lastWeekStart = date("Y-m-d", strtotime("last week monday"));
                    $lastWeekEnd = date("Y-m-d", strtotime("last week sunday"));

                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                        ->where('updated_at', '>=', $lastWeekStart)
                        ->where('updated_at', '<=', $lastWeekEnd)
                        ->count();
                } elseif ($filter == 'this_month') {
                    // Get the first day of the current month
                    $firstDayOfThisMonth = date("Y-m-01");
                    // Today's date
                    $today = date("Y-m-d");

                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                        // Using whereDate to compare only the date part
                        ->whereDate('updated_at', '>=', $firstDayOfThisMonth)
                        ->whereDate('updated_at', '<=', $today)
                        ->count();
                } elseif ($filter == 'custom') {
                    $from = $request->startDateFilter;
                    $to = $request->endDateFilter;
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                        ->where([['updated_at', '>=', $from], ['updated_at', '<=', $to]])
                        ->count();
                } else {
                    $totalsalesmade = Disposition::where('status_id', $saleStatusId)
                        ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                        ->count();
                }

                // get count of all online users from Cache of all the users who have reporting authority id of this user
                $onlineUsers = Cache::where(function ($query) use ($userdetail) {
                    $userIds = $userdetail->pluck('userid')->toArray();

                    foreach ($userIds as $userId) {
                        $query->orWhere('key', 'like', "user-is-online-$userId");
                    }
                })->count();

                $totalUsers = User::where('is_active', 1)
                    ->where('reporting_authority_id', $id)
                    ->count(); // Get the total number of active users

                // totalassign where user_id is in the userdetail
                $totalassign = AssignCompanies::distinct('company_id')
                    ->where('assign_by', $id)
                    ->where("is_active", 1)
                    ->count();
                $unassign = Company::count() - $totalassign;

                $managersList = User::select('users.reporting_authority_id', 'managers.name as manager_name')
                    ->join('users as managers', 'managers.id', '=', 'users.reporting_authority_id')
                    ->distinct()
                    ->where('users.reporting_authority_id', '=', Auth::id())
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('model_has_roles')
                            ->where('model_has_roles.role_id', '=', 1)
                            ->whereRaw('model_has_roles.model_id = users.reporting_authority_id');
                    })
                    ->get();

                $reportData = $this->getReportDataManager($managersList);

                foreach ($userdetail as $key => $val) {
                    $targets = Target::select('users.name as name', 'targets.*')
                        ->join('users', 'users.id', '=', 'targets.user_id')
                        ->where('time', strtoupper(date('M-Y')))
                        ->where('targets.user_id', $val->userid)
                        ->get();

                    if ($targets->isEmpty()) {
                        $targets[] = [
                            'user_id' => $val->userid,
                            'name' => $val->username,
                            'target_achieved' => 0,
                            'target_value' => 0,
                            'time' => strtoupper(Carbon::now()->format('M-Y')),
                        ];
                    }

                    // Add the user's targets to the allTargets array
                    $allTargets = [];
                    $allTargets = array_merge($allTargets, $targets->toArray());

                    $today = Carbon::today()->toDateString();
                    $last_7_days = Carbon::today()->subDays(7)->toDateString();

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
                        ->where(function ($query) use ($today, $last_7_days) {
                            $query->whereBetween('calendars.start_date', [$today, $last_7_days])
                                ->orWhereBetween('calendars.end_date', [$today, $last_7_days])
                                ->orWhere(function ($query) use ($today, $last_7_days) {
                                    $query->where('calendars.start_date', '<=', $today)
                                        ->where('calendars.end_date', '>=', $last_7_days);
                                });
                        })
                        ->get();
                }
            }

            $detail['name'] = Auth::user()->name;
            $detail['user_id'] = $id;
            $detail['totalassigned'] = $totalassign;
            $detail['unassigned'] = $unassign;
            $detail['online_users'] = $onlineUsers;
            $detail['total_users'] = $totalUsers;
            $detail['user_detail'] = $userdetail;
            $detail['today_events'] = $events;
            $detail['targets'] = $allTargets ?? [];
            $detail['total_sales_made'] = $totalsalesmade;

        } else {
            // get user reports if user is not admin or business development manager from user, assignCompanies, DispositionStatus and Disposition database tables
            $userdetail = User::find($id);
            $totalassignuser = AssignCompanies::where('user_id', $id)->distinct('company_id')->count();

            $totalcalls = Disposition::where('user_id', $id)->count();
            $calltoday = Disposition::where('user_id', $id)
                ->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')->count();
            $totalZoomCalls = CallLog::where('user_id', $id)->count();

            $dispostatus = DispositionStatus::select('id', 'name')->get();

            foreach ($dispostatus as $dispo) {
                $detailarr["today"][$dispo->name] = Disposition::where('user_id', $id)
                    ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                    ->count();

                $detailarr["yesterday"][$dispo->name] = Disposition::where('user_id', $id)
                    ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . date("Y-m-d", strtotime("-1 days")) . '%')
                    ->count();
            }

            // get all target of this month of user from Target table and count percentage of target achieved by user
            $targetCount = Target::where('user_id', $id)->where('time', 'like', '%' . strtoupper(date('M-Y')) . '%')->count();

            if ($targetCount > 0) {
                $target = Target::where('user_id', $id)->where('time', 'like', '%' . strtoupper(date('M-Y')) . '%')->get();

                $targetValue = $target[0]->target_value;

                $targetAchieved = $target[0]->target_achieved;

                // count percentage of target achieved by user
                if (!empty($target) && isset($target[0]) && $targetValue > 0) {
                    $target_percentage = round(($target[0]->target_achieved / $targetValue) * 100, 2);
                    // $target_achieved = $target[0]->target_achieved ?? 0; // Ensure target_achieved exists
                    // $target_percentage = $targetValue > 0
                    //     ? round(($target_achieved / $targetValue) * 100, 2)
                    //     : 0; // Prevent division by zero
                } else {
                    $target_percentage = 0;
                }

                // add username to target
                $target[0]->name = $userdetail->name;

            } else {
                $target_percentage = 0;
                $targetValue = 0;
                $targetAchieved = 0;
                $target[] = [
                    "ID" => "",
                    "user_id" => $id,
                    "name" => $userdetail->name,
                    "time" => date('M-Y'),
                    "target_achieved" => 0,
                    "target_value" => 0,
                    "created_at" => "2024-10-15T17:52:17.000000Z",
                    "updated_at" => "2024-10-15T17:53:02.000000Z"
                ];
            }

            $today = Carbon::today()->toDateString();

            $events = Calendar::select(
                "calendars.id as id",
                "calendars.title as title",
                "calendars.start_date as start_date",
                "calendars.end_date as end_date",
                "calendars.description as description",
                "calendars.repeat_rule as repeat_rule",
                "calendars.all_day as all_day",
                "calendars.timezone as timezone",
                "calendars.id as company_id",
                "users.name as user_name",
                "users.id as userid",
                'companies.name as company_name',
            )
                ->join('users', 'users.id', '=', 'calendars.created_by')
                ->join('companies', 'companies.id', '=', 'calendars.company_id')
                ->where('calendars.created_by', $id)
                ->where(function ($query) use ($today) {
                    $query->whereDate('calendars.start_date', $today)
                        ->orWhereDate('calendars.end_date', $today);
                })
                ->get();

            $detail = [
                'name' => $userdetail->name,
                'user_id' => $id,
                'total_call_made' => $totalcalls,
                'total_zoom_calls' => $totalZoomCalls,
                'today_call_made' => $calltoday,
                'totalassigned' => $totalassignuser,
                'detail_report' => $detailarr,
                'target_percentage' => $target_percentage,
                'target_value' => $targetValue,
                'target_achieved' => $targetAchieved,
                'target_month' => $target[0]->time ?? strtoupper(Carbon::now()->format('M-Y')),
                'today_events' => $events,
                'targets' => $target,
            ];
        }

        return Inertia::render('Dashboard', ['detail' => $detail, 'reportData' => $reportData ?? []]);
    }

    public function getReportData(Request $request)
    {
        // get report data from userid and duration from disposition, disposition_status and users database tables according to the disposition status
        $userid = $request->userid;
        $duration = $request->duration;

        $dispostatus = DispositionStatus::select('id', 'name')->get();

        $total = 0;

        // TODO: Make date timezone in EST timezone

        foreach ($dispostatus as $dispo) {
            if ($duration == 'life') {
                $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                    ->where('status_id', $dispo->id)->count();

                $total += $detailarr[$dispo->name];

                $detailarr['Total'] = $total;
            } elseif ($duration == 'today') {
                $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                    ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                    ->count();

                $total += $detailarr[$dispo->name];

                $detailarr['Total'] = $total;
            } elseif ($duration == 'yesterday') {
                $yesterday = date("Y-m-d", strtotime("-1 days"));
                $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                    ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . $yesterday . '%')
                    ->count();

                $total += $detailarr[$dispo->name];

                $detailarr['Total'] = $total;
            } elseif ($duration == 'last_7_day') {
                $weekday = date("Y-m-d", strtotime("-7 days"));
                $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                    ->where('status_id', $dispo->id)->where([['updated_at', '>=', $weekday], ['updated_at', '<=', date("Y-m-d")]])
                    ->count();

                $total += $detailarr[$dispo->name];

                $detailarr['Total'] = $total;
            } elseif ($duration == 'last_30_day') {
                $monthday = date("Y-m-d", strtotime("-30 days"));
                $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                    ->where('status_id', $dispo->id)->where([['updated_at', '>=', $monthday], ['updated_at', '<=', date("Y-m-d")]])
                    ->count();

                $total += $detailarr[$dispo->name];

                $detailarr['Total'] = $total;
            }
        }

        return response()->json(['reportData' => $detailarr]);
    }

    public function getReportDataManager($managersList)
    {
        $team = [];
        foreach ($managersList as $key => $manager) {
            $teamlist = User::select('id', 'name')->where('reporting_authority_id', $manager->reporting_authority_id)->where('is_active', 1)->get();
            foreach ($teamlist as $val) {
                // get report data from userid and duration from disposition, disposition_status and users database tables according to the disposition status
                $userid = $val->id;
                //  $duration = $request->duration;
                $duration = 'life';
                $dispostatus = DispositionStatus::select('id', 'name')->get();

                $total = 0;
                $totsale = 0;

                // get zoom calls from method of getZoomCalls from zoom controller
                $zoomController = new \App\Http\Controllers\ZoomController();
                $zoomAndCRMCalls = $zoomController->getZoomAndCRMCalls($userid);

                foreach ($dispostatus as $dispo) {
                    if ($duration == 'life') {
                        $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                            ->where('status_id', $dispo->id)->count();

                        $total += $detailarr[$dispo->name];

                        if ($dispo->name == 'Sale') {
                            $totsale += $detailarr[$dispo->name];
                        }

                        $detailarr['Total'] = $total;
                        $detailarr['Total_Sale'] = $totsale;
                    } elseif ($duration == 'today') {
                        $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                            ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                            ->count();

                        $total += $detailarr[$dispo->name];
                        if ($dispo->name == 'Sale') {
                            $totsale += $detailarr[$dispo->name];
                        }

                        $detailarr['Total_Sale'] = $totsale;
                        $detailarr['Total'] = $total;
                    } elseif ($duration == 'yesterday') {
                        $yesterday = date("Y-m-d", strtotime("-1 days"));
                        $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                            ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . $yesterday . '%')
                            ->count();

                        $total += $detailarr[$dispo->name];
                        // $total += $detailarr[$dispo->name];
                        if ($dispo->name == 'Sale') {
                            $totsale += $detailarr[$dispo->name];
                        }

                        $detailarr['Total_Sale'] = $totsale;

                        $detailarr['Total'] = $total;
                    } elseif ($duration == 'last_7_day') {
                        $weekday = date("Y-m-d", strtotime("-7 days"));
                        $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                            ->where('status_id', $dispo->id)->where([['updated_at', '>=', $weekday], ['updated_at', '<=', date("Y-m-d")]])
                            ->count();

                        $total += $detailarr[$dispo->name];

                        //  $total += $detailarr[$dispo->name];
                        if ($dispo->name == 'Sale') {
                            $totsale += $detailarr[$dispo->name];
                        }

                        $detailarr['Total_Sale'] = $totsale;

                        $detailarr['Total'] = $total;
                    } elseif ($duration == 'last_30_day') {
                        $monthday = date("Y-m-d", strtotime("-30 days"));
                        $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                            ->where('status_id', $dispo->id)->where([['updated_at', '>=', $monthday], ['updated_at', '<=', date("Y-m-d")]])
                            ->count();

                        $total += $detailarr[$dispo->name];

                        $detailarr['Total'] = $total;
                        if ($dispo->name == 'Sale') {
                            $totsale += $detailarr[$dispo->name];
                        }

                        $detailarr['Total_Sale'] = $totsale;
                    }
                }
                $salesexe[] = ['name' => $val->name, 'id' => $val->id, 'totalCall' => ($total + $zoomAndCRMCalls['zoom_api']), 'totalSales' => $totsale, 'zoomCalls' => $zoomAndCRMCalls['zoom_api'], 'crmCalls' => $zoomAndCRMCalls['call_salecrm']];
            }
            $team['manager'][] = [
                'name' => $manager->manager_name,
                'id' => $manager->reporting_authority_id,
                'team' => $salesexe
            ];
            $salesexe = [];
        }

        return $team;
    }

    public function getReportDataForAdminManager(Request $request)
    {
        // Input parameters
        $managerId = $request->userid;
        $duration = $request->duration;

        // Get manager's name
        $managerName = User::where('id', $managerId)->first()->name;

        // Get the active team list for the manager
        $teamlist = User::select('id', 'name')
            ->where('reporting_authority_id', $managerId)
            ->where('is_active', 1)
            ->get();

        $salesexe = []; // To store team data

        foreach ($teamlist as $val) {
            $userid = $val->id;
            $dispostatus = DispositionStatus::select('id', 'name')->get();
            $total = 0; // Initialize total for this user
            $detailarr = [];

            foreach ($dispostatus as $dispo) {
                if ($duration == 'life') {
                    $count = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)
                        ->count();
                } elseif ($duration == 'today') {
                    $count = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)
                        ->whereDate('updated_at', today())
                        ->count();
                } elseif ($duration == 'yesterday') {
                    $count = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)
                        ->whereDate('updated_at', now()->subDay())
                        ->count();
                } elseif ($duration == 'last_7_day') {
                    $count = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)
                        ->whereBetween('updated_at', [now()->subDays(7), now()])
                        ->count();
                } elseif ($duration == 'last_30_day') {
                    $count = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)
                        ->whereBetween('updated_at', [now()->subDays(30), now()])
                        ->count();
                } else {
                    $count = 0;
                }

                // Accumulate counts
                $detailarr[$dispo->name] = $count;
                $total += $count;
            }

            // Add total to detail array
            $detailarr['Total'] = $total;

            // Add team data for the user
            $salesexe[] = [
                'id' => $val->id,
                'name' => $val->name,
                'totalCall' => $total,
            ];
        }

        // Prepare final team structure
        $team = [
            'reportData' => [
                'manager' => [
                    [
                        'name' => $managerName,
                        'id' => $managerId,
                        'team' => $salesexe,
                    ],
                ],
            ],
        ];

        return response()->json($team);
    }

    public function getReportDataManagers(Request $request)
    {
        $team = [];
        //foreach ($managersList as $key => $manager) {
        $teamlist = User::select('id', 'name')->where('reporting_authority_id', $request->userid)->where('is_active', 1)->get();

        foreach ($teamlist as $val) {
            // get report data from userid and duration from disposition, disposition_status and users database tables according to the disposition status
            $userid = $val->id;
            $duration = $request->duration;
            // $duration = 'life';
            $dispostatus = DispositionStatus::select('id', 'name')->get();

            $total = 0;
            $zoomtotal = 0;
            $salecrmtotal = 0;
            $totalSales = 0;
            $cleanedNumbersArray = 0;
            $cleanedNumbersArray2 = 0;


            foreach ($dispostatus as $dispo) {
                if ($duration == 'life') {
                    $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)->count();

                    $total += $detailarr[$dispo->name];

                    ////////////////////////////////////////////

                    $sel = CallLog::select('caller_number')->where('user_id', $userid)->pluck('caller_number');
                    $zoomnumber = array_unique($sel->toArray());

                    $disposition_new = Disposition::select('phone')->where('user_id', $userid)->pluck('phone')->toArray();

                    $cleanedNumbers = array_map(function ($number) {
                        $number11 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number11);
                    }, $zoomnumber);

                    // If you need to convert to an array
                    $cleanedNumbersArray = $cleanedNumbers;

                    $cleanedNumbers2 = array_map(function ($number) {
                        $number112 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number112);
                    }, $disposition_new);

                    // If you need to convert to an array
                    $cleanedNumbersArray2 = $cleanedNumbers2;
                    $salecrmtotal = count($disposition_new);
                    if ((count($disposition_new)) > (count($cleanedNumbersArray))) {

                        $zoomtotal = count($disposition_new) - count(value: $cleanedNumbersArray);
                    } else {

                        $zoomtotal = count($cleanedNumbersArray) - count(value: $disposition_new);
                    }
                    if ($salecrmtotal < 0) {
                        $salecrmtotal = 0;
                    }
                    if ($zoomtotal < 0) {
                        $zoomtotal = 0;
                    }
                    /////////////////////////////////////////

                    // $detailarr['Total'] = $total;
                    $detailarr['Total'] = $zoomtotal + $salecrmtotal;

                    // //////////////

                    $totalSales = Disposition::where('user_id', $val)
                        ->join('disposition_statuses', 'dispositions.status_id', '=', 'disposition_statuses.id')
                        ->where('disposition_statuses.name', 'Sale')
                        ->count();


                    $detailarr['Total_Sale'] = $totalSales;

                } elseif ($duration == 'today') {
                    $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')
                        ->count();

                    $total += $detailarr[$dispo->name];

                    ////////////////////////////////////////////

                    $sel = CallLog::select('caller_number')->where('user_id', $userid)
                        ->Where('start_time', 'like', '%' . date("Y-m-d") . '%')->pluck('caller_number');
                    $zoomnumber = array_unique($sel->toArray());

                    $disposition_new = Disposition::select('phone')->where('user_id', $userid)->Where('updated_at', 'like', '%' . date("Y-m-d") . '%')->pluck('phone')->toArray();

                    $cleanedNumbers = array_map(function ($number) {
                        $number11 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number11);
                    }, $zoomnumber);

                    // If you need to convert to an array
                    $cleanedNumbersArray = $cleanedNumbers;

                    $cleanedNumbers2 = array_map(function ($number) {
                        $number112 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number112);
                    }, $disposition_new);

                    // If you need to convert to an array
                    // $cleanedNumbersArray2 = $cleanedNumbers2;
                    // $zoomtotal += count($cleanedNumbersArray);
                    // $salecrmtotal += count($cleanedNumbersArray2);



                    $cleanedNumbersArray2 = $cleanedNumbers2;
                    $salecrmtotal = count($disposition_new);
                    if ((count($disposition_new)) > (count($cleanedNumbersArray))) {

                        $zoomtotal = count($disposition_new) - count(value: $cleanedNumbersArray);
                    } else {

                        $zoomtotal = count($cleanedNumbersArray) - count(value: $disposition_new);
                    }
                    if ($salecrmtotal < 0) {
                        $salecrmtotal = 0;
                    }
                    if ($zoomtotal < 0) {
                        $zoomtotal = 0;
                    }


                    $detailarr['Total'] = $zoomtotal + $salecrmtotal;
                    /////////////////////////////////////////

                    // //////////////

                    $totalSales = Disposition::where('user_id', $val)
                        ->join('disposition_statuses', 'dispositions.status_id', '=', 'disposition_statuses.id')
                        ->where('disposition_statuses.name', 'Sale')
                        ->where('dispositions.updated_at', 'like', '%' . date("Y-m-d") . '%')
                        ->count();

                    $detailarr['Total_Sale'] = $totalSales;

                } elseif ($duration == 'yesterday') {
                    $yesterday = date("Y-m-d", strtotime("-1 days"));
                    $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)->Where('updated_at', 'like', '%' . $yesterday . '%')
                        ->count();

                    $total += $detailarr[$dispo->name];

                    //   $detailarr['Total'] = $total;

                    ////////////////////////////////////////////


                    $sel = CallLog::select('caller_number')->where('user_id', $userid)
                        ->Where('start_time', 'like', '%' . $yesterday . '%')->pluck('caller_number');
                    $zoomnumber = array_unique($sel->toArray());

                    $disposition_new = Disposition::select('phone')->where('user_id', $userid)->Where('updated_at', 'like', '%' . $yesterday . '%')->pluck('phone')->toArray();
                    $cleanedNumbers = array_map(function ($number) {


                        $number11 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number11);
                    }, $zoomnumber);

                    // If you need to convert to an array
                    $cleanedNumbersArray = $cleanedNumbers;

                    $cleanedNumbers2 = array_map(function ($number) {
                        $number112 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number112);
                    }, $disposition_new);

                    // If you need to convert to an array
                    // $cleanedNumbersArray2 = $cleanedNumbers2;
                    // $zoomtotal += count($cleanedNumbersArray);
                    // $salecrmtotal += count($cleanedNumbersArray2);

                    $cleanedNumbersArray2 = $cleanedNumbers2;
                    $salecrmtotal = count($disposition_new);
                    if ((count($disposition_new)) > (count($cleanedNumbersArray))) {

                        $zoomtotal = count($disposition_new) - count(value: $cleanedNumbersArray);
                    } else {

                        $zoomtotal = count($cleanedNumbersArray) - count(value: $disposition_new);
                    }
                    if ($salecrmtotal < 0) {
                        $salecrmtotal = 0;
                    }
                    if ($zoomtotal < 0) {
                        $zoomtotal = 0;
                    }


                    $detailarr['Total'] = $zoomtotal + $salecrmtotal;
                    /////////////////////////////////////////

                    // //////////////

                    $totalSales = Disposition::where('user_id', $val)
                        ->join('disposition_statuses', 'dispositions.status_id', '=', 'disposition_statuses.id')
                        ->where('disposition_statuses.name', 'Sale')
                        ->where('dispositions.updated_at', 'like', '%' . $yesterday . '%')
                        ->count();

                    $detailarr['Total_Sale'] = $totalSales;
                } elseif ($duration == 'last_7_day') {
                    $weekday = date("Y-m-d", strtotime("-7 days"));
                    $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)->where([['updated_at', '>=', $weekday], ['updated_at', '<=', date("Y-m-d")]])
                        ->count();

                    $total += $detailarr[$dispo->name];

                    // $detailarr['Total'] = $total;




                    ////////////////////////////////////////////


                    $sel = CallLog::select('caller_number')->where('user_id', $userid)
                        ->where([['start_time', '>=', $weekday], ['start_time', '<=', date("Y-m-d")]])->pluck('caller_number');
                    $zoomnumber = array_unique($sel->toArray());

                    $disposition_new = Disposition::select('phone')->where('user_id', $userid)->where([['updated_at', '>=', $weekday], ['updated_at', '<=', date("Y-m-d")]])->pluck('phone')->toArray();
                    $cleanedNumbers = array_map(function ($number) {
                        $number11 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number11);
                    }, $zoomnumber);

                    // If you need to convert to an array
                    $cleanedNumbersArray = $cleanedNumbers;

                    $cleanedNumbers2 = array_map(function ($number) {
                        $number112 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number112);
                    }, $disposition_new);

                    // If you need to convert to an array
                    // $cleanedNumbersArray2 = $cleanedNumbers2;
                    // $zoomtotal += count($cleanedNumbersArray);
                    // $salecrmtotal += count($cleanedNumbersArray2);


                    $cleanedNumbersArray2 = $cleanedNumbers2;
                    $salecrmtotal = count($disposition_new);
                    if ((count($disposition_new)) > (count($cleanedNumbersArray))) {

                        $zoomtotal = count($disposition_new) - count(value: $cleanedNumbersArray);
                    } else {

                        $zoomtotal = count($cleanedNumbersArray) - count(value: $disposition_new);
                    }
                    if ($salecrmtotal < 0) {
                        $salecrmtotal = 0;
                    }
                    if ($zoomtotal < 0) {
                        $zoomtotal = 0;
                    }

                    $detailarr['Total'] = $zoomtotal + $salecrmtotal;
                    /////////////////////////////////////////

                    // //////////////

                    $totalSales = Disposition::where('user_id', $val)
                        ->join('disposition_statuses', 'dispositions.status_id', '=', 'disposition_statuses.id')
                        ->where('disposition_statuses.name', 'Sale')
                        ->where('dispositions.updated_at', '>=', $weekday)
                        ->where('dispositions.updated_at', '<=', date("Y-m-d"))
                        ->count();

                    $detailarr['Total_Sale'] = $totalSales;


                } elseif ($duration == 'last_30_day') {
                    $monthday = date("Y-m-d", strtotime("-30 days"));
                    $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                        ->where('status_id', $dispo->id)->where([['updated_at', '>=', $monthday], ['updated_at', '<=', date("Y-m-d")]])
                        ->count();

                    $total += $detailarr[$dispo->name];




                    ////////////////////////////////////////////


                    $sel = CallLog::select('caller_number')->where('user_id', $userid)
                        ->where([['start_time', '>=', $monthday], ['start_time', '<=', date("Y-m-d")]])->pluck('caller_number');
                    $zoomnumber = array_unique($sel->toArray());

                    $disposition_new = Disposition::select('phone')->where('user_id', $userid)->where([['updated_at', '>=', $monthday], ['updated_at', '<=', date("Y-m-d")]])->pluck('phone')->toArray();
                    $cleanedNumbers = array_map(function ($number) {


                        $number11 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number11);
                    }, $zoomnumber);

                    // If you need to convert to an array
                    $cleanedNumbersArray = $cleanedNumbers;

                    $cleanedNumbers2 = array_map(function ($number) {


                        $number112 = str_replace("+1", "", $number);
                        return preg_replace('/[^0-9]/', '', $number112);
                    }, $disposition_new);

                    // If you need to convert to an array
                    // $cleanedNumbersArray2 = $cleanedNumbers2;
                    // $zoomtotal += count($cleanedNumbersArray);
                    // $salecrmtotal += count($cleanedNumbersArray2);


                    $cleanedNumbersArray2 = $cleanedNumbers2;
                    $salecrmtotal = count($disposition_new);
                    if ((count($disposition_new)) > (count($cleanedNumbersArray))) {

                        $zoomtotal = count($disposition_new) - count(value: $cleanedNumbersArray);
                    } else {

                        $zoomtotal = count($cleanedNumbersArray) - count(value: $disposition_new);
                    }
                    if ($salecrmtotal < 0) {
                        $salecrmtotal = 0;
                    }
                    if ($zoomtotal < 0) {
                        $zoomtotal = 0;
                    }
                    $detailarr['Total'] = $zoomtotal + $salecrmtotal;
                    /////////////////////////////////////////

                    // //////////////

                    $totalSales = Disposition::where('user_id', $val)
                        ->join('disposition_statuses', 'dispositions.status_id', '=', 'disposition_statuses.id')
                        ->where('disposition_statuses.name', 'Sale')
                        ->where('dispositions.updated_at', '>=', $monthday)
                        ->where('dispositions.updated_at', '<=', date("Y-m-d"))
                        ->count();

                    $detailarr['Total_Sale'] = $totalSales;

                }
            }
            $salesexe[] = ['name' => $val->name, 'id' => $val->id, 'totalCall' => ($zoomtotal + $salecrmtotal), 'zoomCalls' => $zoomtotal, 'crmCalls' => $salecrmtotal, 'totalSales' => $totalSales];

            //                $team['manager'][$manager->manager_name][$manager->reporting_authority_id][] = ['user_id' => $val->id, 'name' => $val->name, 'reportData' => $detailarr];
        }
        $users = User::find($request->userid);
        $team['manager'][] = [
            'name' => $users->name,
            'id' => $request->userid,
            'team' => $salesexe
        ];

        return response()->json(['reportData' => $team]);
    }

    public function getTotalSalesDetails(Request $request)
    {
        $filter = $request->filter;
        $statusId = DispositionStatus::where('name', 'Sale')->first()->id;

        $userRole = Auth::user()->getRoleNames();

        if ($userRole->contains('Admin')) {
            if ($filter == 'today') {
                // get all the sales with details like company name, user name, status name, updated_at
                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where('dispositions.updated_at', 'like', '%' . date("Y-m-d") . '%')
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'yesterday') {
                // get all the sales with details like company name, user name, status name, updated_at
                $yesterday = date("Y-m-d", strtotime("-1 days"));

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where('dispositions.updated_at', 'like', '%' . $yesterday . '%')
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'last_week') {
                // get all the sales with details like company name, user name, status name, updated_at
                $lastWeekStart = date("Y-m-d", strtotime("last week monday"));
                $lastWeekEnd = date("Y-m-d", strtotime("last week sunday"));

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where('dispositions.updated_at', '>=', $lastWeekStart)
                    ->where('dispositions.updated_at', '<=', $lastWeekEnd)
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'this_month') {
                // Get the first day of the current month
                $firstDayOfThisMonth = date("Y-m-01");
                // Today's date
                $today = date("Y-m-d");

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->whereDate('dispositions.updated_at', '>=', $firstDayOfThisMonth)
                    ->whereDate('dispositions.updated_at', '<=', $today)
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'life_time') {
                // get all the sales with details like company name, user name, status name, updated_at
                $sales = Disposition::where('status_id', $statusId)
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'custom') {
                $from = $request->startDateFilter;
                $to = $request->endDateFilter;

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where([['dispositions.updated_at', '>=', $from], ['disposition.updated_at', '<=', $to]])
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            }
        } else {
            if ($filter == 'today') {
                // get all the sales with details like company name, user name, status name, updated_at
                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where('dispositions.updated_at', 'like', '%' . date("Y-m-d") . '%')
                    ->where('users.reporting_authority_id', Auth::id())
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'yesterday') {
                // get all the sales with details like company name, user name, status name, updated_at
                $yesterday = date("Y-m-d", strtotime("-1 days"));

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where('dispositions.updated_at', 'like', '%' . $yesterday . '%')
                    ->where('users.reporting_authority_id', Auth::id())
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'last_week') {
                // get all the sales with details like company name, user name, status name, updated_at
                $lastWeekStart = date("Y-m-d", strtotime("last week monday"));
                $lastWeekEnd = date("Y-m-d", strtotime("last week sunday"));

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where('dispositions.updated_at', '>=', $lastWeekStart)
                    ->where('dispositions.updated_at', '<=', $lastWeekEnd)
                    ->where('users.reporting_authority_id', Auth::id())
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'this_month') {
                // Get the first day of the current month
                $firstDayOfThisMonth = date("Y-m-01");
                // Today's date
                $today = date("Y-m-d");

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->whereDate('dispositions.updated_at', '>=', $firstDayOfThisMonth)
                    ->whereDate('dispositions.updated_at', '<=', $today)
                    ->where('users.reporting_authority_id', Auth::id())
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'life_time') {
                // get all the sales with details like company name, user name, status name, updated_at
                $sales = Disposition::where('status_id', $statusId)
                    ->where('users.reporting_authority_id', Auth::id())
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'custom') {
                $from = $request->startDateFilter;
                $to = $request->endDateFilter;

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->where([['dispositions.updated_at', '>=', $from], ['disposition.updated_at', '<=', $to]])
                    ->where('users.reporting_authority_id', Auth::id())
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            }
        }

        return response()->json(['sales' => $sales]);
    }

    public function getTotalCallsDetails(Request $request)
    {
        $filter = $request->filter;
        $dispositionStatus = DispositionStatus::all();

        if ($filter == 'life_time') {
            foreach ($dispositionStatus as $status) {
                $callsCount[$status->name] = Disposition::where('status_id', $status->id)
                    ->where('user_id', Auth::id())
                    ->count();
            }

            $callsCount['Total'] = Disposition::where('user_id', Auth::id())->count();
        }

        return response()->json(['calls' => $callsCount]);
    }

    public function getSalesDetailsByDateRange(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $statusId = DispositionStatus::where('name', 'Sale')->first()->id;

        $sales = Disposition::where('dispositions.status_id', $statusId)
            ->whereDate('dispositions.updated_at', '>=', $startDate)
            ->whereDate('dispositions.updated_at', '<=', $endDate)
            ->join('companies', 'companies.id', '=', 'dispositions.company_id')
            ->join('users', 'users.id', '=', 'dispositions.user_id')
            ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
            ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
            ->get();

        return response()->json(['sales' => $sales]);
    }

    public function getDispositionCallDetails(Request $request)
    {
        $dispositionName = $request->dispositionName;
        $userId = $request->userId;
        $filter = $request->filter;

        $disposition = DispositionStatus::where('name', $dispositionName)->value('id');

        if ($filter == "today") {
            $dispositionDetails = Disposition::select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'clients.fname as client_fname', 'clients.lname as client_lname', 'companies.id as company_id')
                ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                ->join('users', 'users.id', '=', 'dispositions.user_id')
                ->join('clients', 'clients.id', '=', 'dispositions.client_id')
                ->where('status_id', $disposition)
                ->where('user_id', $userId)
                ->where('dispositions.updated_at', 'like', '%' . date("Y-m-d") . '%')
                ->get();
        } else if ($filter == "yesterday") {
            $yesterday = date("Y-m-d", strtotime("-1 days"));
            $dispositionDetails = Disposition::select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'clients.fname as client_fname', 'clients.lname as client_lname', 'companies.id as company_id')
                ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                ->join('users', 'users.id', '=', 'dispositions.user_id')
                ->join('clients', 'clients.id', '=', 'dispositions.client_id')
                ->where('status_id', $disposition)
                ->where('user_id', $userId)
                ->where('dispositions.updated_at', 'like', '%' . $yesterday . '%')
                ->get();
        } else if ($filter == "last_7_days") {
            $lastWeekStart = date("Y-m-d", strtotime("last week monday"));
            $lastWeekEnd = date("Y-m-d", strtotime("last week sunday"));
            $dispositionDetails = Disposition::select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'clients.fname as client_fname', 'clients.lname as client_lname', 'companies.id as company_id')
                ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                ->join('users', 'users.id', '=', 'dispositions.user_id')
                ->join('clients', 'clients.id', '=', 'dispositions.client_id')
                ->where('status_id', $disposition)
                ->where('user_id', $userId)
                ->where('dispositions.updated_at', '>=', $lastWeekStart)
                ->where('dispositions.updated_at', '<=', $lastWeekEnd)
                ->get();
        } else if ($filter == "last_30_days") {
            $lastMonthStart = date("Y-m-d", strtotime("last month"));
            $lastMonthEnd = date("Y-m-d", strtotime("last month last day"));
            $dispositionDetails = Disposition::select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'clients.fname as client_fname', 'clients.lname as client_lname', 'companies.id as company_id')
                ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                ->join('users', 'users.id', '=', 'dispositions.user_id')
                ->join('clients', 'clients.id', '=', 'dispositions.client_id')
                ->where('status_id', $disposition)
                ->where('user_id', $userId)
                ->where('dispositions.updated_at', '>=', $lastMonthStart)
                ->where('dispositions.updated_at', '<=', $lastMonthEnd)
                ->get();
        } else if ($filter == "life") {
            $dispositionDetails = Disposition::select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'clients.fname as client_fname', 'clients.lname as client_lname', 'companies.id as company_id')
                ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                ->join('users', 'users.id', '=', 'dispositions.user_id')
                ->join('clients', 'clients.id', '=', 'dispositions.client_id')
                ->where('status_id', $disposition)
                ->where('user_id', $userId)
                ->get();
        }

        return response()->json(['dispositionDetails' => $dispositionDetails]);
    }

    public function getDispositionCallDetailsForSalesExec(Request $request)
    {
        $dispositionName = $request->dispositionName;
        $userId = Auth::id();
        $filter = $request->filter;

        $disposition = DispositionStatus::where('name', $dispositionName)->value('id');

        if ($filter == "life_time") {
            $dispositionDetails = Disposition::select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'clients.fname as client_fname', 'clients.lname as client_lname', 'companies.id as company_id')
                ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                ->join('users', 'users.id', '=', 'dispositions.user_id')
                ->join('clients', 'clients.id', '=', 'dispositions.client_id')
                ->where('status_id', $disposition)
                ->where('user_id', $userId)
                ->get();
        }

        return response()->json(['dispositionDetails' => $dispositionDetails]);
    }

    public function matchNumber(Request $request)
    {
        $sel = CallLog::select('caller_number')->pluck('caller_number');
        $zoomnumber = array_unique($sel->toArray());
        // $sel1 = ClientsPhone::select('phone')->pluck('phone');
        // $zoomnumber1 = array_unique($sel1->toArray());
        // $sel2 = CompanyPhone::select('phone')->pluck('phone');
        // $zoomnumber2 = array_unique($sel2->toArray());
        // $companyphone11 = array_merge($zoomnumber1, $zoomnumber2);
        // $countcompanyphone = count($companyphone11);
        $disposition = Disposition::select('phone')->where('user_id', 95)->pluck('phone')->toArray();
        $cleanedNumbers = array_map(function ($number) {


            $number11 = str_replace("+1", "", $number);
            return preg_replace('/[^0-9]/', '', $number11);
        }, $zoomnumber);

        // If you need to convert to an array
        $cleanedNumbersArray = $cleanedNumbers;

        $cleanedNumbers2 = array_map(function ($number) {


            $number112 = str_replace("+1", "", $number);
            return preg_replace('/[^0-9]/', '', $number112);
        }, $disposition);

        // If you need to convert to an array
        $cleanedNumbersArray2 = $cleanedNumbers2;


        $companyData = array_diff($cleanedNumbersArray2, $cleanedNumbersArray);
        return ['call_salecrm' => count($disposition), 'zoom_api' => count($cleanedNumbersArray)];


    }

    public function getSalesExecutiveDispositionDetails(Request $request)
    {
        $userId = Auth::id();

        $dispositionStatuses = DispositionStatus::all();

        $dispositionDetails = [];

        foreach ($dispositionStatuses as $dispositionStatus) {
            $disposition = Disposition::where('user_id', $userId)
                ->where('status_id', $dispositionStatus->id)
                ->get();

            $dispositionDetails[] = [
                'disposition_name' => $dispositionStatus->name,
                'disposition_count' => count($disposition),
                'disposition' => $disposition
            ];
        }

        // get total disposition count and merge with disposition details array in the last index
        $totalDispositionCount = Disposition::where('user_id', $userId)->count();

        $dispositionDetails[] = [
            'disposition_name' => 'Total',
            'disposition_count' => $totalDispositionCount
        ];

        return response()->json(['dispositionDetails' => $dispositionDetails]);
    }
}
