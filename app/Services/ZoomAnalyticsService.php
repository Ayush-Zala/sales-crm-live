<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\MeetingLog;
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
     * @param array|null $allowedUserIds
     * @return array
     */
    public static function getAnalytics(Carbon $startDate, Carbon $endDate, $search = null, $directionFilter = null, $userFilter = null, $allowedUserIds = null)
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
        if ($allowedUserIds !== null) {
            $zoomCallLogs->whereIn('call_logs.user_id', $allowedUserIds);
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

        // Previous Period Metrics (Period-over-Period)
        $diffInDays = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($diffInDays + 1);
        $prevEndDate = $startDate->copy()->subDay();

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
        if ($allowedUserIds !== null) {
            $prevQuery->whereIn('call_logs.user_id', $allowedUserIds);
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

    /**
     * Get aggregated Zoom Meeting analytics for a given period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $search
     * @param int|null $userFilter
     * @param array|null $allowedUserIds
     * @return array
     */
    public static function getMeetingAnalytics(Carbon $startDate, Carbon $endDate, $search = null, $userFilter = null, $allowedUserIds = null)
    {
        $zoomMeetings = MeetingLog::join('users', 'meeting_logs.user_id', '=', 'users.id')
            ->select('meeting_logs.*', 'users.name as user_name');

        // Apply filters to current period
        if ($search) {
            $zoomMeetings->where('meeting_logs.topic', 'like', '%' . $search . '%');
        }
        if ($userFilter) {
            $zoomMeetings->where('meeting_logs.user_id', $userFilter);
        }
        if ($allowedUserIds !== null) {
            $zoomMeetings->whereIn('meeting_logs.user_id', $allowedUserIds);
        }

        $analyticsQuery = clone $zoomMeetings;
        $analyticsQuery->whereBetween('meeting_logs.start_time', [$startDate, $endDate]);

        // Current Period Metrics
        $totalMeetings = (clone $analyticsQuery)->count();
        $totalParticipants = (clone $analyticsQuery)->sum(DB::raw('JSON_LENGTH(participants)'));
        $totalMeetingHours = (clone $analyticsQuery)->sum(DB::raw('CAST(duration AS UNSIGNED)'));
        $avgParticipants = (clone $analyticsQuery)->avg(DB::raw('JSON_LENGTH(participants)'));
        $avgDuration = (clone $analyticsQuery)->avg(DB::raw('CAST(duration AS UNSIGNED)'));

        // Previous Period Metrics (Period-over-Period)
        $diffInDays = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($diffInDays + 1);
        $prevEndDate = $startDate->copy()->subDay();

        $prevQuery = MeetingLog::join('users', 'meeting_logs.user_id', '=', 'users.id')
            ->whereBetween('meeting_logs.start_time', [$prevStartDate, $prevEndDate]);

        if ($search) {
            $prevQuery->where('meeting_logs.topic', 'like', '%' . $search . '%');
        }
        if ($userFilter) {
            $prevQuery->where('meeting_logs.user_id', $userFilter);
        }
        if ($allowedUserIds !== null) {
            $prevQuery->whereIn('meeting_logs.user_id', $allowedUserIds);
        }

        $prevTotalMeetings = (clone $prevQuery)->count();
        $prevTotalParticipants = (clone $prevQuery)->sum(DB::raw('JSON_LENGTH(participants)'));
        $prevTotalMeetingHours = (clone $prevQuery)->sum(DB::raw('CAST(duration AS UNSIGNED)'));
        $prevAvgParticipants = (clone $prevQuery)->avg(DB::raw('JSON_LENGTH(participants)'));
        $prevAvgDuration = (clone $prevQuery)->avg(DB::raw('CAST(duration AS UNSIGNED)'));

        $periodLabel = $prevStartDate->toDateString() === $prevEndDate->toDateString() 
            ? $prevStartDate->format('M j, Y') 
            : $prevStartDate->format('M j, Y') . ' - ' . $prevEndDate->format('M j, Y');

        // Chart Data
        $meetingsOverTimeRaw = (clone $analyticsQuery)
            ->select(
                DB::raw('DATE(meeting_logs.start_time) as date'),
                DB::raw('count(*) as total_meetings'),
                DB::raw('SUM(JSON_LENGTH(participants)) as total_participants'),
                DB::raw('SUM(CAST(duration AS UNSIGNED)) as total_duration')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
            
        $meetingsOverTime = collect();
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            if ($meetingsOverTimeRaw->has($dateStr)) {
                $meetingsOverTime->push($meetingsOverTimeRaw->get($dateStr));
            } else {
                $meetingsOverTime->push([
                    'date' => $dateStr,
                    'total_meetings' => 0,
                    'total_participants' => 0,
                    'total_duration' => 0
                ]);
            }
            $currentDate->addDay();
        }

        $meetingTypeDistribution = (clone $analyticsQuery)
            ->select(
                'meeting_logs.type',
                DB::raw('count(*) as total')
            )
            ->groupBy('meeting_logs.type')
            ->get()
            ->map(function ($item) {
                $label = 'Other';
                if ($item->type == 1) $label = 'Instant Meeting';
                elseif ($item->type == 2) $label = 'Scheduled Meeting';
                elseif ($item->type == 3) $label = 'Recurring Meeting';
                elseif ($item->type == 4) $label = 'Personal Meeting Room';
                elseif ($item->type == 8) $label = 'Recurring Fixed Time';
                
                return [
                    'label' => $label,
                    'total' => $item->total
                ];
            });

        // Group same labels together if multiple types map to 'Other'
        $groupedTypeDistribution = collect();
        foreach ($meetingTypeDistribution as $item) {
            $existing = $groupedTypeDistribution->firstWhere('label', $item['label']);
            if ($existing) {
                $existing['total'] += $item['total'];
                $groupedTypeDistribution = $groupedTypeDistribution->map(function($i) use ($existing) {
                    if ($i['label'] == $existing['label']) return $existing;
                    return $i;
                });
            } else {
                $groupedTypeDistribution->push($item);
            }
        }

        $topHosts = (clone $analyticsQuery)
            ->select(
                'users.name as host_name',
                DB::raw('count(*) as total_meetings')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_meetings')
            ->limit(5)
            ->get();

        $meetingsByTimeOfDay = (clone $analyticsQuery)
            ->select(
                DB::raw('HOUR(start_time) as hour'),
                DB::raw('count(*) as total')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
            
        $hourlyDistribution = collect();
        for ($i = 0; $i < 24; $i++) {
            $record = $meetingsByTimeOfDay->firstWhere('hour', $i);
            $hourlyDistribution->push([
                'hour' => $i,
                'total' => $record ? $record->total : 0
            ]);
        }

        $meetingDurationDistributionRaw = (clone $analyticsQuery)
            ->select(
                DB::raw("CASE
                    WHEN CAST(duration AS UNSIGNED) <= 10 THEN '0 - 10 min'
                    WHEN CAST(duration AS UNSIGNED) <= 20 THEN '10 - 20 min'
                    WHEN CAST(duration AS UNSIGNED) <= 30 THEN '20 - 30 min'
                    WHEN CAST(duration AS UNSIGNED) <= 60 THEN '30 - 60 min'
                    ELSE '60+ min'
                END as duration_bucket"),
                DB::raw('count(*) as total')
            )
            ->groupBy('duration_bucket')
            ->get();
            
        $durationBuckets = ['0 - 10 min', '10 - 20 min', '20 - 30 min', '30 - 60 min', '60+ min'];
        $meetingDurationDistribution = collect();
        foreach ($durationBuckets as $bucket) {
            $record = $meetingDurationDistributionRaw->firstWhere('duration_bucket', $bucket);
            $meetingDurationDistribution->push([
                'bucket' => $bucket,
                'total' => $record ? $record->total : 0
            ]);
        }

        return [
            'current' => [
                'total_meetings' => $totalMeetings,
                'total_participants' => (int)$totalParticipants,
                'total_meeting_hours' => (int)$totalMeetingHours, // in minutes, formatted on frontend
                'avg_participants' => round((float)$avgParticipants, 1),
                'avg_duration' => round((float)$avgDuration, 1), // in minutes
            ],
            'previous' => [
                'total_meetings' => $prevTotalMeetings,
                'total_participants' => (int)$prevTotalParticipants,
                'total_meeting_hours' => (int)$prevTotalMeetingHours,
                'avg_participants' => round((float)$prevAvgParticipants, 1),
                'avg_duration' => round((float)$prevAvgDuration, 1),
            ],
            'charts' => [
                'meetings_over_time' => $meetingsOverTime,
                'meeting_type_distribution' => $groupedTypeDistribution->values()->all(),
                'top_hosts' => $topHosts,
                'hourly_distribution' => $hourlyDistribution,
                'duration_distribution' => $meetingDurationDistribution,
            ],
            'period_label' => $periodLabel,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]
        ];
    }
}
