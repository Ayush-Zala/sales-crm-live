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
    private function getDateRangeForFilter($filter, $request)
    {
        $currentStart = Carbon::now()->startOfMonth();
        $currentEnd = Carbon::now()->endOfMonth();
        $prevStart = null;
        $prevEnd = null;

        if ($filter == 'today') {
            $currentStart = Carbon::today();
            $currentEnd = Carbon::today()->endOfDay();
            $prevStart = Carbon::yesterday()->startOfDay();
            $prevEnd = Carbon::yesterday()->endOfDay();
        } elseif ($filter == 'yesterday') {
            $currentStart = Carbon::yesterday();
            $currentEnd = Carbon::yesterday()->endOfDay();
            $prevStart = Carbon::today()->subDays(2)->startOfDay();
            $prevEnd = Carbon::today()->subDays(2)->endOfDay();
        } elseif ($filter == 'last_week') {
            $currentStart = Carbon::today()->subDays(7)->startOfDay();
            $currentEnd = Carbon::today()->endOfDay();
            $prevStart = Carbon::today()->subDays(14)->startOfDay();
            $prevEnd = Carbon::today()->subDays(7)->endOfDay();
        } elseif ($filter == 'this_month') {
            $currentStart = Carbon::now()->startOfMonth();
            $currentEnd = Carbon::now()->endOfMonth();
            $prevStart = Carbon::now()->subMonth()->startOfMonth();
            $prevEnd = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($filter == 'life_time') {
            $earliestRecord = Disposition::min('updated_at');
            $currentStart = $earliestRecord ? Carbon::parse($earliestRecord) : Carbon::today();
            $currentEnd = Carbon::now()->endOfDay();
            $prevStart = null;
            $prevEnd = null;
        } elseif ($filter == 'custom') {
            $currentStart = $request->startDateFilter ? Carbon::parse($request->startDateFilter)->startOfDay() : Carbon::now()->startOfMonth();
            $currentEnd = $request->endDateFilter ? Carbon::parse($request->endDateFilter)->endOfDay() : Carbon::now()->endOfMonth();
            $diffInDays = $currentStart->diffInDays($currentEnd) + 1;
            $prevStart = $currentStart->copy()->subDays($diffInDays)->startOfDay();
            $prevEnd = $currentStart->copy()->subDays(1)->endOfDay();
        } else {
            $prevStart = Carbon::now()->subMonth()->startOfMonth();
            $prevEnd = Carbon::now()->subMonth()->endOfMonth();
        }

        return [$currentStart, $currentEnd, $prevStart, $prevEnd];
    }

    private function getAnalyticsDateRangeForFilter($filter, $request)
    {
        $currentStart = Carbon::now()->startOfMonth();
        $currentEnd = Carbon::now()->endOfMonth();
        $prevStart = null;
        $prevEnd = null;

        if ($filter == 'today') {
            $currentStart = Carbon::today();
            $currentEnd = Carbon::today()->endOfDay();
            $prevStart = Carbon::yesterday()->startOfDay();
            $prevEnd = Carbon::yesterday()->endOfDay();
        } elseif ($filter == 'yesterday') {
            $currentStart = Carbon::yesterday();
            $currentEnd = Carbon::yesterday()->endOfDay();
            $prevStart = Carbon::today()->subDays(2)->startOfDay();
            $prevEnd = Carbon::today()->subDays(2)->endOfDay();
        } elseif ($filter == 'last_week') {
            $currentStart = Carbon::today()->subDays(7)->startOfDay();
            $currentEnd = Carbon::today()->endOfDay();
            $prevStart = Carbon::today()->subDays(14)->startOfDay();
            $prevEnd = Carbon::today()->subDays(7)->endOfDay();
        } elseif ($filter == 'this_month') {
            $currentStart = Carbon::now()->startOfMonth();
            $currentEnd = Carbon::now()->endOfMonth();
            $prevStart = Carbon::now()->subMonth()->startOfMonth();
            $prevEnd = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($filter == 'life_time') {
            $earliestRecord = Disposition::min('updated_at');
            $currentStart = $earliestRecord ? Carbon::parse($earliestRecord) : Carbon::today();
            $currentEnd = Carbon::now()->endOfDay();
            $prevStart = null;
            $prevEnd = null;
        } elseif ($filter == 'custom') {
            $currentStart = $request->analytics_start ? Carbon::parse($request->analytics_start)->startOfDay() : Carbon::now()->startOfMonth();
            $currentEnd = $request->analytics_end ? Carbon::parse($request->analytics_end)->endOfDay() : Carbon::now()->endOfMonth();
            $diffInDays = $currentStart->diffInDays($currentEnd) + 1;
            $prevStart = $currentStart->copy()->subDays($diffInDays)->startOfDay();
            $prevEnd = $currentStart->copy()->subDays(1)->endOfDay();
        } else {
            $prevStart = Carbon::now()->subMonth()->startOfMonth();
            $prevEnd = Carbon::now()->subMonth()->endOfMonth();
        }

        return [$currentStart, $currentEnd, $prevStart, $prevEnd];
    }

        public function index(Request $request)
    {
        $id = \Auth::id();
        $rolesarr = new \Illuminate\Support\Collection(\Auth::user()->getRoleNames());

        $detailClosure = function () use ($request, $id, $rolesarr) {
            $onlineUsers = 0;
            $events = [];

            $filter = $request->filter;
            
            if ($filter == 'custom') {
                $request->merge([
                    'startDateFilter' => $request->startDateFilter ?? '1970-01-01',
                    'endDateFilter' => $request->endDateFilter ?? \Carbon\Carbon::now()->toDateString()
                ]);
            }

            if (($rolesarr->contains('Admin')) || $rolesarr->contains('Business Development Manager')) {
                if ($rolesarr->contains('Admin')) {
                    $userdetail = \App\Models\User::select('users.id as userid', 'users.name as username')
                        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('users.is_active', 1)
                        ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                        ->get();

                    $existingUserIds = $userdetail->pluck('userid')->toArray();
                    $additionalTargets = \App\Models\Target::select('users.name as name', 'targets.*')
                        ->join('users', 'users.id', '=', 'targets.user_id')
                        ->where('time', strtoupper(date('M-Y')))
                        ->whereNotIn('targets.user_id', $existingUserIds)
                        ->get();

                    $allTargets = [];
                    $allTargets = array_merge($allTargets, $additionalTargets->toArray());

                    $saleStatusId = \App\Models\DispositionStatus::where('name', 'Sale')->first()->id;

                    if ($filter == 'today') {
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereBetween('updated_at', [\Carbon\Carbon::today(), \Carbon\Carbon::today()->endOfDay()])
                            ->count();
                    } elseif ($filter == 'yesterday') {
                        $yesterdayStart = \Carbon\Carbon::yesterday();
                        $yesterdayEnd = \Carbon\Carbon::yesterday()->endOfDay();
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereBetween('updated_at', [$yesterdayStart, $yesterdayEnd])
                            ->count();
                    } elseif ($filter == 'last_week') {
                        $weekday = \Carbon\Carbon::today()->subDays(7)->startOfDay();
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereBetween('updated_at', [$weekday, \Carbon\Carbon::today()->endOfDay()])
                            ->count();
                    } elseif ($filter == 'this_month') {
                        $monthday = \Carbon\Carbon::now()->startOfMonth();
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereBetween('updated_at', [$monthday, \Carbon\Carbon::today()->endOfDay()])
                            ->count();
                    } elseif ($filter == 'custom') {
                        $from = \Carbon\Carbon::parse($request->startDateFilter)->startOfDay();
                        $to = \Carbon\Carbon::parse($request->endDateFilter)->endOfDay();
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereBetween('updated_at', [$from, $to])
                            ->count();
                    } else {
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)->count();
                    }

                    $onlineUsersCount = \App\Models\Cache::where('key', 'like', 'user-is-online-%')->where('expiration', '>=', time())->count();
                    $onlineUsers = $onlineUsersCount;
                    $totalUsers = \App\Models\User::where('is_active', 1)->count(); 

                    $totalassign = cache()->remember('total_active_assign_companies', 3600, function() {
                        return \App\Models\AssignCompanies::distinct('company_id')
                            ->where("is_active", 1)
                            ->count();
                    });
                    $unassign = max(0, cache()->remember('total_clients_count', 3600, function() {
                        return \App\Models\Client::count();
                    }) - $totalassign);

                    $existingUserIds = $userdetail->pluck('userid')->toArray();
                    $monthTargets = \App\Models\Target::select('users.name as name', 'targets.*')
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
                                'time' => strtoupper(\Carbon\Carbon::now()->format('M-Y')),
                            ];
                        }
                    }

                    $today = \Carbon\Carbon::today()->toDateString();
                    $last_7_days = \Carbon\Carbon::today()->subDays(7)->toDateString();

                    $events = \App\Models\Calendar::select(
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
                    $userdetail = \App\Models\User::select('users.id as userid', 'users.name as username')
                        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('users.reporting_authority_id', $id)
                        ->where('users.is_active', 1)
                        ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                        ->get();

                    $saleStatusId = \App\Models\DispositionStatus::where('name', 'Sale')->first()->id;

                    if ($filter == 'today') {
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                            ->whereDate('updated_at', \Carbon\Carbon::today())
                            ->count();
                    } elseif ($filter == 'yesterday') {
                        $yesterday = date("Y-m-d", strtotime("-1 days"));
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                            ->whereDate('updated_at', $yesterday)
                            ->count();
                    } elseif ($filter == 'last_week') {
                        $lastWeekStart = \Carbon\Carbon::today()->subDays(7)->startOfDay();
                        $lastWeekEnd = \Carbon\Carbon::today()->endOfDay();

                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                            ->whereBetween('updated_at', [$lastWeekStart, $lastWeekEnd])
                            ->count();
                    } elseif ($filter == 'this_month') {
                        $firstDayOfThisMonth = date("Y-m-01");
                        $today = date("Y-m-d");

                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                            ->whereDate('updated_at', '>=', $firstDayOfThisMonth)
                            ->whereDate('updated_at', '<=', $today)
                            ->count();
                    } elseif ($filter == 'custom') {
                        $from = $request->startDateFilter;
                        $to = $request->endDateFilter;
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                            ->where([['updated_at', '>=', $from], ['updated_at', '<=', $to]])
                            ->count();
                    } else {
                        $totalsalesmade = \App\Models\Disposition::where('status_id', $saleStatusId)
                            ->whereIn('user_id', $userdetail->pluck('userid')->toArray())
                            ->count();
                    }

                    $onlineUsers = \App\Models\Cache::where(function ($query) use ($userdetail, $id) {
                        $userIds = $userdetail->pluck('userid')->toArray();
                        $userIds[] = $id; 

                        foreach ($userIds as $userId) {
                            $query->orWhere('key', 'like', "user-is-online-$userId");
                        }
                    })->where('expiration', '>=', time())->count();

                    $totalUsers = \App\Models\User::where('is_active', 1)
                        ->where(function($q) use ($id) {
                            $q->where('reporting_authority_id', $id)
                              ->orWhere('id', $id);
                        })
                        ->count(); 

                    $totalassign = cache()->remember('total_assign_manager_' . $id, 3600, function() use ($id) {
                        return \App\Models\AssignCompanies::distinct('company_id')
                            ->where('assign_by', $id)
                            ->where("is_active", 1)
                            ->count();
                    });
                    $unassign = max(0, cache()->remember('total_clients_count', 3600, function() {
                        return \App\Models\Client::count();
                    }) - $totalassign);

                    $existingUserIds = $userdetail->pluck('userid')->toArray();
                    $monthTargets = \App\Models\Target::select('users.name as name', 'targets.*')
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
                                'time' => strtoupper(\Carbon\Carbon::now()->format('M-Y')),
                            ];
                        }
                    }

                    $today = \Carbon\Carbon::today()->toDateString();
                    $last_7_days = \Carbon\Carbon::today()->subDays(7)->toDateString();

                    $events = \App\Models\Calendar::select(
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
                        ->where('users.reporting_authority_id', \Auth::id())
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

                $detail['name'] = \Auth::user()->name;
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
                $userdetail = \App\Models\User::find($id);
                $totalassignuser = \App\Models\AssignCompanies::where('user_id', $id)->distinct('company_id')->count();

                $totalcalls = \App\Models\Disposition::where('user_id', $id)->count();
                $calltoday = \App\Models\Disposition::where('user_id', $id)
                    ->whereBetween('updated_at', [\Carbon\Carbon::today(), \Carbon\Carbon::today()->endOfDay()])->count();
                $totalZoomCalls = \App\Models\CallLog::where('user_id', $id)->count();

                $dispostatus = \App\Models\DispositionStatus::select('id', 'name')->get();
                
                $detailarr = [];
                foreach ($dispostatus as $dispo) {
                    $detailarr["today"][$dispo->name] = \App\Models\Disposition::where('user_id', $id)
                        ->where('status_id', $dispo->id)->whereBetween('updated_at', [\Carbon\Carbon::today(), \Carbon\Carbon::today()->endOfDay()])
                        ->count();

                    $detailarr["yesterday"][$dispo->name] = \App\Models\Disposition::where('user_id', $id)
                        ->where('status_id', $dispo->id)->whereBetween('updated_at', [\Carbon\Carbon::yesterday(), \Carbon\Carbon::yesterday()->endOfDay()])
                        ->count();
                }

                $targetCount = \App\Models\Target::where('user_id', $id)->where('time', strtoupper(date('M-Y')))->count();

                if ($targetCount > 0) {
                    $target = \App\Models\Target::where('user_id', $id)->where('time', strtoupper(date('M-Y')))->get();
                    $targetValue = $target[0]->target_value;
                    $targetAchieved = $target[0]->target_achieved;

                    if (!empty($target) && isset($target[0]) && $targetValue > 0) {
                        $target_percentage = round(($target[0]->target_achieved / $targetValue) * 100, 2);
                    } else {
                        $target_percentage = 0;
                    }

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

                $today = \Carbon\Carbon::today()->toDateString();

                $events = \App\Models\Calendar::select(
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
                    'target_month' => $target[0]->time ?? strtoupper(\Carbon\Carbon::now()->format('M-Y')),
                    'today_events' => $events,
                    'targets' => $target,
                ];
            }

            $filter = $request->filter ?? 'life_time';
            list($currentStart, $currentEnd, $prevStart, $prevEnd) = $this->getDateRangeForFilter($filter, $request);
            $saleStatusId = \App\Models\DispositionStatus::where('name', 'Sale')->first()->id;

            $isAdmin = $rolesarr->contains('Admin') || $rolesarr->contains('Super Admin');
            $userIdList = [];
            if (!$isAdmin) {
                if (isset($userdetail) && $userdetail instanceof \Illuminate\Support\Collection) {
                    $userIdList = $userdetail->pluck('userid')->toArray();
                } else {
                    $userIdList = [$id];
                }
            }

            // Generate independent Sparkline data for Top KPI Cards (using $filter dates)
            $kpiDailyDispositionsQuery = \App\Models\Disposition::select(\DB::raw('DATE(updated_at) as date'), \DB::raw('COUNT(*) as total'))
                ->whereBetween('updated_at', [$currentStart, $currentEnd])
                ->groupBy('date');

            $kpiDailyZoomCallsQuery = \App\Models\CallLog::select(\DB::raw('DATE(start_time) as date'), \DB::raw('COUNT(DISTINCT caller_number) as total'))
                ->whereBetween('start_time', [$currentStart, $currentEnd])
                ->groupBy('date');
                
            $kpiDailySalesQuery = \App\Models\Disposition::select(\DB::raw('DATE(updated_at) as date'), \DB::raw('COUNT(*) as total'))
                ->where('status_id', $saleStatusId)
                ->whereBetween('updated_at', [$currentStart, $currentEnd])
                ->groupBy('date');

            if (!$isAdmin) {
                $kpiDailyDispositionsQuery->whereIn('user_id', $userIdList);
                $kpiDailyZoomCallsQuery->whereIn('user_id', $userIdList);
                $kpiDailySalesQuery->whereIn('user_id', $userIdList);
            }

            $kpiDailyDispositions = $kpiDailyDispositionsQuery->pluck('total', 'date');
            $kpiDailyZoomCalls = $kpiDailyZoomCallsQuery->pluck('total', 'date');
            $kpiDailySales = $kpiDailySalesQuery->pluck('total', 'date');

            $kpiSparklineData = [];
            $kpiCurrentDate = $currentStart->copy();
            $kpiDiffDays = $currentStart->diffInDays($currentEnd);
            
            if ($kpiDiffDays > 60) {
                $kpiCurrentMonth = $currentStart->copy()->startOfMonth();
                while ($kpiCurrentMonth <= $currentEnd) {
                    $crmCalls = 0; $zoomCalls = 0; $salesCount = 0;
                    $monthEnd = $kpiCurrentMonth->copy()->endOfMonth();
                    $tempDate = $kpiCurrentMonth->copy();
                    while ($tempDate <= min($monthEnd, $currentEnd)) {
                        $dateStr = $tempDate->toDateString();
                        $crmCalls += $kpiDailyDispositions->get($dateStr, 0);
                        $zoomCalls += $kpiDailyZoomCalls->get($dateStr, 0);
                        $salesCount += $kpiDailySales->get($dateStr, 0);
                        $tempDate->addDay();
                    }
                    
                    $kpiSparklineData[] = [
                        'date' => $kpiCurrentMonth->format('M Y'),
                        'total_calls' => $zoomCalls + $crmCalls,
                        'zoom_calls' => $zoomCalls,
                        'sales' => $salesCount
                    ];
                    $kpiCurrentMonth->addMonth();
                }
            } else {
                while ($kpiCurrentDate <= min(\Carbon\Carbon::today(), $currentEnd)) {
                    $dateStr = $kpiCurrentDate->toDateString();
                    $crmCalls = $kpiDailyDispositions->get($dateStr, 0);
                    $zoomCalls = $kpiDailyZoomCalls->get($dateStr, 0);
                    
                    $kpiSparklineData[] = [
                        'date' => $kpiCurrentDate->format('M d'),
                        'total_calls' => $zoomCalls + $crmCalls,
                        'zoom_calls' => $zoomCalls,
                        'sales' => $kpiDailySales->get($dateStr, 0)
                    ];
                    $kpiCurrentDate->addDay();
                }
            }

            // 2. Trends for KPI Cards
            $thisPeriodSalesQuery = \App\Models\Disposition::where('status_id', $saleStatusId)->whereBetween('updated_at', [$currentStart, $currentEnd]);
            $prevPeriodSalesQuery = \App\Models\Disposition::where('status_id', $saleStatusId)->whereBetween('updated_at', [$prevStart, $prevEnd]);
            $thisPeriodZoomQuery = \App\Models\CallLog::whereBetween('start_time', [$currentStart, $currentEnd]);
            $prevPeriodZoomQuery = \App\Models\CallLog::whereBetween('start_time', [$prevStart, $prevEnd]);
            $thisPeriodCrmQuery = \App\Models\Disposition::whereBetween('updated_at', [$currentStart, $currentEnd]);
            $prevPeriodCrmQuery = \App\Models\Disposition::whereBetween('updated_at', [$prevStart, $prevEnd]);

            if (!$isAdmin) {
                $thisPeriodSalesQuery->whereIn('user_id', $userIdList);
                $prevPeriodSalesQuery->whereIn('user_id', $userIdList);
                $thisPeriodZoomQuery->whereIn('user_id', $userIdList);
                $prevPeriodZoomQuery->whereIn('user_id', $userIdList);
                $thisPeriodCrmQuery->whereIn('user_id', $userIdList);
                $prevPeriodCrmQuery->whereIn('user_id', $userIdList);
            }

            $thisPeriodSales = $thisPeriodSalesQuery->count();
            $prevPeriodSales = $prevStart ? $prevPeriodSalesQuery->count() : 0;
            $salesTrend = $prevStart ? ($prevPeriodSales > 0 ? round((($thisPeriodSales - $prevPeriodSales) / $prevPeriodSales) * 100, 1) : ($thisPeriodSales > 0 ? 100 : 0)) : null;
            
            $thisPeriodZoom = $thisPeriodZoomQuery->distinct('caller_number')->count();
            $prevPeriodZoom = $prevStart ? $prevPeriodZoomQuery->distinct('caller_number')->count() : 0;
            $zoomTrend = $prevStart ? ($prevPeriodZoom > 0 ? round((($thisPeriodZoom - $prevPeriodZoom) / $prevPeriodZoom) * 100, 1) : ($thisPeriodZoom > 0 ? 100 : 0)) : null;

            $thisPeriodCrm = $thisPeriodCrmQuery->count();
            $prevPeriodCrm = $prevStart ? $prevPeriodCrmQuery->count() : 0;
            
            $crmTrend = $prevStart ? ($prevPeriodCrm > 0 ? round((($thisPeriodCrm - $prevPeriodCrm) / $prevPeriodCrm) * 100, 1) : ($thisPeriodCrm > 0 ? 100 : 0)) : null;
            
            $thisPeriodTotalCalls = $thisPeriodZoom + $thisPeriodCrm;
            $prevPeriodTotalCalls = $prevPeriodZoom + $prevPeriodCrm;
            $totalCallsTrend = $prevStart ? ($prevPeriodTotalCalls > 0 ? round((($thisPeriodTotalCalls - $prevPeriodTotalCalls) / $prevPeriodTotalCalls) * 100, 1) : ($thisPeriodTotalCalls > 0 ? 100 : 0)) : null;
            
            $thisPeriodAssign = \App\Models\AssignCompanies::distinct('company_id')
                ->where('assign_by', $id)->where('is_active', 1)
                ->whereBetween('created_at', [$currentStart, $currentEnd])->count();
            $prevPeriodAssign = $prevStart ? \App\Models\AssignCompanies::distinct('company_id')
                ->where('assign_by', $id)->where('is_active', 1)
                ->whereBetween('created_at', [$prevStart, $prevEnd])->count() : 0;
            
            $assignTrend = $prevStart ? ($prevPeriodAssign > 0 ? round((($thisPeriodAssign - $prevPeriodAssign) / $prevPeriodAssign) * 100, 1) : ($thisPeriodAssign > 0 ? 100 : 0)) : null;

            $thisPeriodCompany = \App\Models\Company::whereBetween('created_at', [$currentStart, $currentEnd])->count();
            $prevPeriodCompany = $prevStart ? \App\Models\Company::whereBetween('created_at', [$prevStart, $prevEnd])->count() : 0;
            
            $thisPeriodUnassigned = max(0, $thisPeriodCompany - $thisPeriodAssign);
            $prevPeriodUnassigned = max(0, $prevPeriodCompany - $prevPeriodAssign);
            $unassignedTrend = $prevStart ? ($prevPeriodUnassigned > 0 ? round((($thisPeriodUnassigned - $prevPeriodUnassigned) / $prevPeriodUnassigned) * 100, 1) : ($thisPeriodUnassigned > 0 ? 100 : 0)) : null;

            $detail['kpiSparklineData'] = $kpiSparklineData;
            $trendLabelMap = [
                'today' => 'vs yesterday',
                'yesterday' => 'vs previous day',
                'last_week' => 'vs previous week',
                'this_month' => 'vs last month',
                'last_month' => 'vs previous month',
                'this_year' => 'vs last year',
                'custom' => 'vs previous period',
                'life_time' => ''
            ];
            
            $detail['trends'] = [
                'sales' => $salesTrend,
                'zoom' => $zoomTrend,
                'total' => $totalCallsTrend,
                'assigned' => $assignTrend,
                'unassigned' => $unassignedTrend,
                'label' => $trendLabelMap[$filter] ?? 'vs previous'
            ];

            return $detail;
        };

        $analyticsOverviewClosure = function () use ($request, $id, $rolesarr) {
            $analyticsFilter = $request->analytics_filter ?? 'this_month';
            list($analyticsStart, $analyticsEnd, $analyticsPrevStart, $analyticsPrevEnd) = $this->getAnalyticsDateRangeForFilter($analyticsFilter, $request);
            $saleStatusId = \App\Models\DispositionStatus::where('name', 'Sale')->first()->id;

            $isAdmin = $rolesarr->contains('Admin') || $rolesarr->contains('Super Admin');
            $userIdList = [];
            if (!$isAdmin) {
                if ($rolesarr->contains('Business Development Manager')) {
                    $userdetail = \App\Models\User::select('users.id as userid')
                        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('users.reporting_authority_id', $id)
                        ->where('users.is_active', 1)
                        ->whereIn('roles.name', ['Sales Executives', 'Business Development Team Lead'])
                        ->get();
                    $userIdList = $userdetail->pluck('userid')->toArray();
                } else {
                    $userIdList = [$id];
                }
            }

            // 1. Daily Analytics for Area Chart
            $dailyDispositionsQuery = \App\Models\Disposition::select(\DB::raw('DATE(updated_at) as date'), \DB::raw('COUNT(*) as total'))
                ->whereBetween('updated_at', [$analyticsStart, $analyticsEnd])
                ->groupBy('date');
            
            $dailyZoomCallsQuery = \App\Models\CallLog::select(\DB::raw('DATE(start_time) as date'), \DB::raw('COUNT(DISTINCT caller_number) as total'))
                ->whereBetween('start_time', [$analyticsStart, $analyticsEnd])
                ->groupBy('date');

            $dailySalesQuery = \App\Models\Disposition::select(\DB::raw('DATE(updated_at) as date'), \DB::raw('COUNT(*) as total'))
                ->where('status_id', $saleStatusId)
                ->whereBetween('updated_at', [$analyticsStart, $analyticsEnd])
                ->groupBy('date');

            if (!$isAdmin) {
                $dailyDispositionsQuery->whereIn('user_id', $userIdList);
                $dailyZoomCallsQuery->whereIn('user_id', $userIdList);
                $dailySalesQuery->whereIn('user_id', $userIdList);
            }

            $dailyDispositions = $dailyDispositionsQuery->pluck('total', 'date');
            $dailyZoomCalls = $dailyZoomCallsQuery->pluck('total', 'date');
            $dailySales = $dailySalesQuery->pluck('total', 'date');

            $analyticsOverview = [];
            $currentDate = $analyticsStart->copy();
            $diffDays = $analyticsStart->diffInDays($analyticsEnd);
            
            if ($diffDays > 60) {
                $currentMonth = $analyticsStart->copy()->startOfMonth();
                while ($currentMonth <= $analyticsEnd) {
                    $monthStr = $currentMonth->format('Y-m');
                    $crmCalls = 0; $zoomCalls = 0; $salesCount = 0;
                    
                    $monthEnd = $currentMonth->copy()->endOfMonth();
                    $tempDate = $currentMonth->copy();
                    while ($tempDate <= min($monthEnd, $analyticsEnd)) {
                        $dateStr = $tempDate->toDateString();
                        $crmCalls += $dailyDispositions->get($dateStr, 0);
                        $zoomCalls += $dailyZoomCalls->get($dateStr, 0);
                        $salesCount += $dailySales->get($dateStr, 0);
                        $tempDate->addDay();
                    }
                    
                    $analyticsOverview[] = [
                        'date' => $currentMonth->format('M Y'),
                        'zoom_calls' => $zoomCalls, 
                        'crm_calls' => $crmCalls,
                        'total_calls' => $zoomCalls + $crmCalls,
                        'sales' => $salesCount
                    ];
                    $currentMonth->addMonth();
                }
            } else {
                while ($currentDate <= min(\Carbon\Carbon::today(), $analyticsEnd)) {
                    $dateStr = $currentDate->toDateString();
                    $crmCalls = $dailyDispositions->get($dateStr, 0);
                    $zoomCalls = $dailyZoomCalls->get($dateStr, 0);
                    
                    $analyticsOverview[] = [
                        'date' => $currentDate->format('M d'),
                        'zoom_calls' => $zoomCalls, 
                        'crm_calls' => $crmCalls,
                        'total_calls' => $zoomCalls + $crmCalls,
                        'sales' => $dailySales->get($dateStr, 0)
                    ];
                    $currentDate->addDay();
                }
            }

            // Analytics Overview Trends
            $analyticsThisPeriodSalesQuery = \App\Models\Disposition::where('status_id', $saleStatusId)->whereBetween('updated_at', [$analyticsStart, $analyticsEnd]);
            $analyticsPrevPeriodSalesQuery = \App\Models\Disposition::where('status_id', $saleStatusId)->whereBetween('updated_at', [$analyticsPrevStart, $analyticsPrevEnd]);
            $analyticsThisPeriodZoomQuery = \App\Models\CallLog::whereBetween('start_time', [$analyticsStart, $analyticsEnd]);
            $analyticsPrevPeriodZoomQuery = \App\Models\CallLog::whereBetween('start_time', [$analyticsPrevStart, $analyticsPrevEnd]);
            $analyticsThisPeriodCrmQuery = \App\Models\Disposition::whereBetween('updated_at', [$analyticsStart, $analyticsEnd]);
            $analyticsPrevPeriodCrmQuery = \App\Models\Disposition::whereBetween('updated_at', [$analyticsPrevStart, $analyticsPrevEnd]);

            if (!$isAdmin) {
                $analyticsThisPeriodSalesQuery->whereIn('user_id', $userIdList);
                $analyticsPrevPeriodSalesQuery->whereIn('user_id', $userIdList);
                $analyticsThisPeriodZoomQuery->whereIn('user_id', $userIdList);
                $analyticsPrevPeriodZoomQuery->whereIn('user_id', $userIdList);
                $analyticsThisPeriodCrmQuery->whereIn('user_id', $userIdList);
                $analyticsPrevPeriodCrmQuery->whereIn('user_id', $userIdList);
            }

            $analyticsThisPeriodSales = $analyticsThisPeriodSalesQuery->count();
            $analyticsPrevPeriodSales = $analyticsPrevStart ? $analyticsPrevPeriodSalesQuery->count() : 0;
            $analyticsSalesTrend = $analyticsPrevStart ? ($analyticsPrevPeriodSales > 0 ? round((($analyticsThisPeriodSales - $analyticsPrevPeriodSales) / $analyticsPrevPeriodSales) * 100, 1) : ($analyticsThisPeriodSales > 0 ? 100 : 0)) : null;
            
            $analyticsThisPeriodZoom = $analyticsThisPeriodZoomQuery->distinct('caller_number')->count();
            $analyticsPrevPeriodZoom = $analyticsPrevStart ? $analyticsPrevPeriodZoomQuery->distinct('caller_number')->count() : 0;
            $analyticsZoomTrend = $analyticsPrevStart ? ($analyticsPrevPeriodZoom > 0 ? round((($analyticsThisPeriodZoom - $analyticsPrevPeriodZoom) / $analyticsPrevPeriodZoom) * 100, 1) : ($analyticsThisPeriodZoom > 0 ? 100 : 0)) : null;

            $analyticsThisPeriodCrm = $analyticsThisPeriodCrmQuery->count();
            $analyticsPrevPeriodCrm = $analyticsPrevStart ? $analyticsPrevPeriodCrmQuery->count() : 0;
            $analyticsCrmTrend = $analyticsPrevStart ? ($analyticsPrevPeriodCrm > 0 ? round((($analyticsThisPeriodCrm - $analyticsPrevPeriodCrm) / $analyticsPrevPeriodCrm) * 100, 1) : ($analyticsThisPeriodCrm > 0 ? 100 : 0)) : null;
            
            $analyticsThisPeriodTotalCalls = $analyticsThisPeriodZoom + $analyticsThisPeriodCrm;
            $analyticsPrevPeriodTotalCalls = $analyticsPrevPeriodZoom + $analyticsPrevPeriodCrm;
            $analyticsTotalCallsTrend = $analyticsPrevStart ? ($analyticsPrevPeriodTotalCalls > 0 ? round((($analyticsThisPeriodTotalCalls - $analyticsPrevPeriodTotalCalls) / $analyticsPrevPeriodTotalCalls) * 100, 1) : ($analyticsThisPeriodTotalCalls > 0 ? 100 : 0)) : null;

            return [
                'dailyData' => $analyticsOverview,
                'trends' => [
                    'sales' => $analyticsSalesTrend,
                    'zoom' => $analyticsZoomTrend,
                    'crm' => $analyticsCrmTrend,
                    'total' => $analyticsTotalCallsTrend
                ]
            ];
        };

        return Inertia::render('Dashboard', [
            'detail' => fn () => $detailClosure(),
            'analyticsOverview' => fn () => $analyticsOverviewClosure(),
            'reportData' => function () use ($request, $id, $rolesarr) {
                if (($rolesarr->contains('Admin')) || $rolesarr->contains('Business Development Manager')) {
                    $managersList = \App\Models\User::select('users.reporting_authority_id', 'managers.name as manager_name')
                        ->join('users as managers', 'managers.id', '=', 'users.reporting_authority_id')
                        ->distinct()
                        ->where('users.reporting_authority_id', '=', \Auth::id())
                        ->whereNotExists(function ($query) {
                            $query->select(\DB::raw(1))
                                ->from('model_has_roles')
                                ->where('model_has_roles.role_id', '=', 1)
                                ->whereRaw('model_has_roles.model_id = users.reporting_authority_id');
                        })
                        ->get();
                    if ($rolesarr->contains('Admin')) {
                        $managersList = \App\Models\User::select('reporting_authority_id', \DB::raw('(SELECT name FROM users as managers WHERE managers.id = users.reporting_authority_id) as manager_name'))
                            ->distinct()
                            ->whereNotNull('reporting_authority_id')
                            ->whereNotExists(function ($query) {
                                $query->select(\DB::raw(1))
                                    ->from('model_has_roles')
                                    ->where('model_has_roles.role_id', '=', 1)
                                    ->whereRaw('model_has_roles.model_id = users.reporting_authority_id');
                            })
                            ->get();
                    }
                    return $this->getReportDataManager($managersList);
                }
                return [];
            }
        ]);
    }public function getReportData(Request $request)
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
                    ->where('status_id', $dispo->id)->whereDate('updated_at', Carbon::today())
                    ->count();

                $total += $detailarr[$dispo->name];

                $detailarr['Total'] = $total;
            } elseif ($duration == 'yesterday') {
                $yesterday = date("Y-m-d", strtotime("-1 days"));
                $detailarr[$dispo->name] = Disposition::where('user_id', $userid)
                    ->where('status_id', $dispo->id)->whereDate('updated_at', $yesterday)
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
        $managerIds = collect($managersList)->pluck('reporting_authority_id')->filter()->toArray();
        if (empty($managerIds)) return [];

        sort($managerIds);
        $cacheKey = 'report_data_manager_' . md5(json_encode($managerIds));

        return cache()->remember($cacheKey, 3600, function() use ($managersList, $managerIds) {
            $team = [];

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
        });
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
                    ->whereDate('dispositions.updated_at', Carbon::today())
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'yesterday') {
                // get all the sales with details like company name, user name, status name, updated_at
                $yesterday = date("Y-m-d", strtotime("-1 days"));

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->whereDate('dispositions.updated_at', $yesterday)
                    ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                    ->join('users', 'users.id', '=', 'dispositions.user_id')
                    ->join('disposition_statuses', 'disposition_statuses.id', '=', 'dispositions.status_id')
                    ->select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'disposition_statuses.name as status_name')
                    ->get();
            } else if ($filter == 'last_week') {
                // get all the sales with details like company name, user name, status name, updated_at
                $lastWeekStart = Carbon::today()->subDays(7)->startOfDay();
                $lastWeekEnd = Carbon::today()->endOfDay();

                $sales = Disposition::where('dispositions.status_id', $statusId)
                    ->whereBetween('dispositions.updated_at', [$lastWeekStart, $lastWeekEnd])
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
                    ->whereDate('dispositions.updated_at', Carbon::today())
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
                    ->whereDate('dispositions.updated_at', $yesterday)
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
                ->whereDate('dispositions.updated_at', Carbon::today())
                ->get();
        } else if ($filter == "yesterday") {
            $yesterday = date("Y-m-d", strtotime("-1 days"));
            $dispositionDetails = Disposition::select('dispositions.*', 'companies.name as company_name', 'users.name as user_name', 'clients.fname as client_fname', 'clients.lname as client_lname', 'companies.id as company_id')
                ->join('companies', 'companies.id', '=', 'dispositions.company_id')
                ->join('users', 'users.id', '=', 'dispositions.user_id')
                ->join('clients', 'clients.id', '=', 'dispositions.client_id')
                ->where('status_id', $disposition)
                ->where('user_id', $userId)
                ->whereDate('dispositions.updated_at', $yesterday)
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
