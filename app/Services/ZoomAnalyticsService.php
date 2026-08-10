<?php

namespace App\Services;

use App\Models\CallLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ZoomAnalyticsService
{
    /**
     * Get aggregated Zoom call analytics for a given period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $search
     * @param string|null $directionFilter 'inbound' or 'outbound'
     * @param int|null $userFilter
     * @return array
     */
    public static function getAnalytics(Carbon $startDate, Carbon $endDate, $search = null, $directionFilter = null, $userFilter = null)
    {
        $zoomCallLogs = CallLog::join('users', 'call_logs.user_id', '=', 'users.id')
            ->select('call_logs.*', 'users.name as user_name');

        // Apply filters to current period
        if ($search) {
            $zoomCallLogs->where(function ($q) use ($search) {
                $q->where('users.name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_number', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.callee_number', 'like', '%' . $search . '%');
            });
        }
        if ($directionFilter == "outbound") {
            $zoomCallLogs->where('call_logs.direction', 'outbound');
        } elseif ($directionFilter == "inbound") {
            $zoomCallLogs->where('call_logs.direction', 'inbound');
        }
        if ($userFilter) {
            $zoomCallLogs->where('call_logs.user_id', $userFilter);
        }

        $analyticsQuery = clone $zoomCallLogs;
        $analyticsQuery->whereBetween('call_logs.start_time', [$startDate, $endDate]);

        // Current Period Metrics
        $totalCalls = (clone $analyticsQuery)->count();
        $answeredCalls = (clone $analyticsQuery)->whereIn('call_logs.result', ['connected', 'answered'])->count();
        $missedCalls = $totalCalls - $answeredCalls;
        $answerRate = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $avgCallDuration = (clone $analyticsQuery)->whereNotNull('call_logs.talk_time')->where('call_logs.talk_time', '>', 0)->avg(DB::raw('CAST(call_logs.talk_time AS UNSIGNED)'));
        $avgWaitTime = (clone $analyticsQuery)->whereNotNull('call_logs.wait_time')->where('call_logs.wait_time', '>', 0)->avg(DB::raw('CAST(call_logs.wait_time AS UNSIGNED)'));

        // Previous Period Metrics (Year-over-Year)
        $prevStartDate = $startDate->copy()->subYear();
        $prevEndDate = $endDate->copy()->subYear();

        $prevQuery = CallLog::join('users', 'call_logs.user_id', '=', 'users.id')
            ->whereBetween('call_logs.start_time', [$prevStartDate, $prevEndDate]);

        if ($search) {
            $prevQuery->where(function ($q) use ($search) {
                $q->where('users.name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_number', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.callee_number', 'like', '%' . $search . '%');
            });
        }
        if ($directionFilter == "outbound") {
            $prevQuery->where('call_logs.direction', 'outbound');
        } elseif ($directionFilter == "inbound") {
            $prevQuery->where('call_logs.direction', 'inbound');
        }
        if ($userFilter) {
            $prevQuery->where('call_logs.user_id', $userFilter);
        }

        $prevTotalCalls = (clone $prevQuery)->count();
        $prevAnsweredCalls = (clone $prevQuery)->whereIn('call_logs.result', ['connected', 'answered'])->count();
        $prevMissedCalls = $prevTotalCalls - $prevAnsweredCalls;
        $prevAnswerRate = $prevTotalCalls > 0 ? round(($prevAnsweredCalls / $prevTotalCalls) * 100, 1) : 0;
        $prevAvgCallDuration = (clone $prevQuery)->whereNotNull('call_logs.talk_time')->where('call_logs.talk_time', '>', 0)->avg(DB::raw('CAST(call_logs.talk_time AS UNSIGNED)'));
        $prevAvgWaitTime = (clone $prevQuery)->whereNotNull('call_logs.wait_time')->where('call_logs.wait_time', '>', 0)->avg(DB::raw('CAST(call_logs.wait_time AS UNSIGNED)'));

        $periodLabel = $prevStartDate->toDateString() === $prevEndDate->toDateString() 
            ? $prevStartDate->format('M j, Y') 
            : $prevStartDate->format('M j, Y') . ' - ' . $prevEndDate->format('M j, Y');

        // Chart Analytics
        $chartDataRaw = (clone $analyticsQuery)
            ->reorder()
            ->select(
                DB::raw('DATE(call_logs.start_time) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when call_logs.direction = \'inbound\' then 1 else 0 end) as inbound'),
                DB::raw('sum(case when call_logs.direction = \'outbound\' then 1 else 0 end) as outbound'),
                DB::raw('sum(case when call_logs.result in (\'connected\', \'answered\') then 1 else 0 end) as answered'),
                DB::raw('avg(CAST(call_logs.talk_time AS UNSIGNED)) as avg_duration')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $chartData = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            if ($chartDataRaw->has($dateString)) {
                $item = $chartDataRaw->get($dateString);
                $item->missed = $item->total - $item->answered;
                $item->avg_duration = round((float)$item->avg_duration);
                $chartData->push($item);
            } else {
                $chartData->push((object)[
                    'date' => $dateString,
                    'total' => 0,
                    'inbound' => 0,
                    'outbound' => 0,
                    'answered' => 0,
                    'missed' => 0,
                    'avg_duration' => 0
                ]);
            }
        }

        $topUsers = (clone $analyticsQuery)
            ->reorder()
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $hourlyVolume = (clone $analyticsQuery)
            ->reorder()
            ->select(DB::raw('HOUR(call_logs.start_time) as hour'), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $waitTimeDistribution = (clone $analyticsQuery)
            ->reorder()
            ->select(
                DB::raw('sum(case when CAST(call_logs.wait_time AS UNSIGNED) between 0 and 5 then 1 else 0 end) as bucket_0_5s'),
                DB::raw('sum(case when CAST(call_logs.wait_time AS UNSIGNED) > 5 and CAST(call_logs.wait_time AS UNSIGNED) <= 10 then 1 else 0 end) as bucket_5_10s'),
                DB::raw('sum(case when CAST(call_logs.wait_time AS UNSIGNED) > 10 and CAST(call_logs.wait_time AS UNSIGNED) <= 20 then 1 else 0 end) as bucket_10_20s'),
                DB::raw('sum(case when CAST(call_logs.wait_time AS UNSIGNED) > 20 and CAST(call_logs.wait_time AS UNSIGNED) <= 30 then 1 else 0 end) as bucket_20_30s'),
                DB::raw('sum(case when CAST(call_logs.wait_time AS UNSIGNED) > 30 then 1 else 0 end) as bucket_30s_plus')
            )
            ->first();

        $durationDistribution = (clone $analyticsQuery)
            ->reorder()
            ->select(
                DB::raw('sum(case when CAST(call_logs.talk_time AS UNSIGNED) between 0 and 30 then 1 else 0 end) as bucket_0_30s'),
                DB::raw('sum(case when CAST(call_logs.talk_time AS UNSIGNED) > 30 and CAST(call_logs.talk_time AS UNSIGNED) <= 60 then 1 else 0 end) as bucket_30s_1m'),
                DB::raw('sum(case when CAST(call_logs.talk_time AS UNSIGNED) > 60 and CAST(call_logs.talk_time AS UNSIGNED) <= 180 then 1 else 0 end) as bucket_1m_3m'),
                DB::raw('sum(case when CAST(call_logs.talk_time AS UNSIGNED) > 180 and CAST(call_logs.talk_time AS UNSIGNED) <= 300 then 1 else 0 end) as bucket_3m_5m'),
                DB::raw('sum(case when CAST(call_logs.talk_time AS UNSIGNED) > 300 and CAST(call_logs.talk_time AS UNSIGNED) <= 600 then 1 else 0 end) as bucket_5m_10m'),
                DB::raw('sum(case when CAST(call_logs.talk_time AS UNSIGNED) > 600 then 1 else 0 end) as bucket_10m_plus')
            )
            ->first();

        return [
            'current' => [
                'total_calls' => $totalCalls,
                'answered_calls' => $answeredCalls,
                'missed_calls' => $missedCalls,
                'answer_rate' => $answerRate,
                'avg_call_duration' => round((float)$avgCallDuration),
                'avg_wait_time' => round((float)$avgWaitTime),
            ],
            'previous' => [
                'total_calls' => $prevTotalCalls,
                'answered_calls' => $prevAnsweredCalls,
                'missed_calls' => $prevMissedCalls,
                'answer_rate' => $prevAnswerRate,
                'avg_call_duration' => round((float)$prevAvgCallDuration),
                'avg_wait_time' => round((float)$prevAvgWaitTime),
            ],
            'charts' => [
                'volume_over_time' => $chartData,
                'inbound_outbound' => [
                    'inbound' => (clone $analyticsQuery)->where('call_logs.direction', 'inbound')->count(),
                    'outbound' => (clone $analyticsQuery)->where('call_logs.direction', 'outbound')->count(),
                ],
                'top_users' => $topUsers,
                'hourly_volume' => $hourlyVolume,
                'wait_time_distribution' => $waitTimeDistribution,
                'duration_distribution' => $durationDistribution,
            ],
            'period_label' => $periodLabel,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]
        ];
    }
}
