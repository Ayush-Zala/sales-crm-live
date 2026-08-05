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

                $existingUserIds = $userdetail->pluck('userid')->toArray();
                $monthTargets = Target::select('users.name as name', 'targets.*')
                    ->join('users', 'users.id', '=', 'targets.user_id')
                    ->where('time', strtoupper(date('M-Y')))
                    ->whereIn('targets.user_id', $existingUserIds)
                    ->get()
                    ->keyBy('user_id');

                foreach ($userdetail as $val) {
                    if ($monthTargets->has($val->userid)) {
                        $allTargets[] = $monthTargets->get($val->userid)->toArray();
                    } else {
                        $allTargets[] = [
                            'user_id' => $val->userid,
                            'name' => $val->username,
                            'target_achieved' => 0,
                            'target_value' => 0,
                            'time' => strtoupper(Carbon::now()->format('M-Y')),
                        ];
                    }
                }

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

                $existingUserIds = $userdetail->pluck('userid')->toArray();
                $monthTargets = Target::select('users.name as name', 'targets.*')
                    ->join('users', 'users.id', '=', 'targets.user_id')
                    ->where('time', strtoupper(date('M-Y')))
                    ->whereIn('targets.user_id', $existingUserIds)
                    ->get()
                    ->keyBy('user_id');

                $allTargets = [];
                foreach ($userdetail as $val) {
                    if ($monthTargets->has($val->userid)) {
                        $allTargets[] = $monthTargets->get($val->userid)->toArray();
                    } else {
                        $allTargets[] = [
                            'user_id' => $val->userid,
                            'name' => $val->username,
                            'target_achieved' => 0,
                            'target_value' => 0,
                            'time' => strtoupper(Carbon::now()->format('M-Y')),
                        ];
                    }
                }

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
        $managerIds = collect($managersList)->pluck('reporting_authority_id')->filter()->toArray();
        if (empty($managerIds)) return [];

        $allTeamMembers = User::select('id', 'name', 'reporting_authority_id')
            ->whereIn('reporting_authority_id', $managerIds)
            ->where('is_active', 1)
            ->get();
            
        $userIds = $allTeamMembers->pluck('id')->toArray();
        if (empty($userIds)) return [];

        $saleStatusId = DispositionStatus::where('name', 'Sale')->value('id');

        $dispoCounts = Disposition::whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'user_id');

        $dispoSalesCounts = Disposition::whereIn('user_id', $userIds)
            ->where('status_id', $saleStatusId)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'user_id');

        $zoomCounts = CallLog::whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(DISTINCT caller_number) as total'))
            ->pluck('total', 'user_id');

        // Group members by manager
        $membersByManager = $allTeamMembers->groupBy('reporting_authority_id');

        foreach ($managersList as $manager) {
            $managerTeam = $membersByManager->get($manager->reporting_authority_id, collect([]));
            $salesexe = [];
            
            foreach ($managerTeam as $val) {
                $userid = $val->id;
                
                $salecrmtotal = $dispoCounts->get($userid, 0);
                $totalSales = $dispoSalesCounts->get($userid, 0);
                $uniqueZoomCalls = $zoomCounts->get($userid, 0);
                
                $zoomtotal = abs($salecrmtotal - $uniqueZoomCalls);
                
                $salesexe[] = [
                    'name' => $val->name, 
                    'id' => $val->id, 
                    'totalCall' => ($zoomtotal + $salecrmtotal), 
                    'totalSales' => $totalSales,
                    'zoomCalls' => $zoomtotal, 
                    'crmCalls' => $salecrmtotal
                ];
            }
            
            $managerName = isset($manager->manager_name) ? $manager->manager_name : (isset($manager->name) ? $manager->name : 'Unknown');
            $team['manager'][] = [
                'name' => $managerName,
                'id' => $manager->reporting_authority_id,
                'team' => $salesexe
            ];
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
        $managerId = $request->userid;
        $teamlist = User::select('id', 'name')->where('reporting_authority_id', $managerId)->where('is_active', 1)->get();
        $userIds = $teamlist->pluck('id')->toArray();
        if (empty($userIds)) return response()->json(['reportData' => []]);

        $duration = $request->duration;
        $saleStatusId = DispositionStatus::where('name', 'Sale')->value('id');

        $dispositionQuery = Disposition::whereIn('user_id', $userIds);
        $dispoSalesQuery = Disposition::whereIn('user_id', $userIds)->where('status_id', $saleStatusId);
        $callLogQuery = CallLog::whereIn('user_id', $userIds);

        if ($duration == 'today') {
            $dispositionQuery->whereDate('updated_at', Carbon::today());
            $dispoSalesQuery->whereDate('updated_at', Carbon::today());
            $callLogQuery->whereDate('start_time', Carbon::today());
        } elseif ($duration == 'yesterday') {
            $yesterday = Carbon::yesterday();
            $dispositionQuery->whereDate('updated_at', $yesterday);
            $dispoSalesQuery->whereDate('updated_at', $yesterday);
            $callLogQuery->whereDate('start_time', $yesterday);
        } elseif ($duration == 'last_7_day') {
            $weekday = Carbon::today()->subDays(7);
            $dispositionQuery->where('updated_at', '>=', $weekday);
            $dispoSalesQuery->where('updated_at', '>=', $weekday);
            $callLogQuery->where('start_time', '>=', $weekday);
        } elseif ($duration == 'last_30_day') {
            $monthday = Carbon::today()->subDays(30);
            $dispositionQuery->where('updated_at', '>=', $monthday);
            $dispoSalesQuery->where('updated_at', '>=', $monthday);
            $callLogQuery->where('start_time', '>=', $monthday);
        }

        $dispoCounts = $dispositionQuery->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as total'))->pluck('total', 'user_id');
            
        $dispoSalesCounts = $dispoSalesQuery->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as total'))->pluck('total', 'user_id');

        $zoomCounts = $callLogQuery->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(DISTINCT caller_number) as total'))->pluck('total', 'user_id');

        $salesexe = [];
        foreach ($teamlist as $val) {
            $userid = $val->id;
            
            $salecrmtotal = $dispoCounts->get($userid, 0);
            $totalSales = $dispoSalesCounts->get($userid, 0);
            $uniqueZoomCalls = $zoomCounts->get($userid, 0);
            
            $zoomtotal = abs($salecrmtotal - $uniqueZoomCalls);
            
            $salesexe[] = [
                'name' => $val->name, 
                'id' => $val->id, 
                'totalCall' => ($zoomtotal + $salecrmtotal), 
                'zoomCalls' => $zoomtotal, 
                'crmCalls' => $salecrmtotal, 
                'totalSales' => $totalSales
            ];
        }

        $manager = User::find($managerId);
        $team['manager'][] = [
            'name' => $manager ? $manager->name : 'Unknown',
            'id' => $managerId,
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
