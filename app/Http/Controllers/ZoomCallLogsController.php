<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\User;
use App\Models\ZoomApi;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Session;

class ZoomCallLogsController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->filter;
        $userFilter = $request->user;
        $search = $request->search;

        $zoomCallLogs = CallLog::join('users', 'call_logs.user_id', '=', 'users.id')
            ->select('call_logs.*', 'users.name as user_name')
            ->orderBy('call_logs.start_time', 'desc');


        if ($search) {
            $zoomCallLogs = $zoomCallLogs->where(function ($q) use ($search) {
                $q->where('users.name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_number', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.callee_number', 'like', '%' . $search . '%');
            });
        }

        if ($filter == "outbound") {
            $zoomCallLogs = $zoomCallLogs->where('call_logs.direction', 'outbound');
        } elseif ($filter == "inbound") {
            $zoomCallLogs = $zoomCallLogs->where('call_logs.direction', 'inbound');
        }

        if ($userFilter) {
            $zoomCallLogs = $zoomCallLogs->where('call_logs.user_id', $userFilter);
        }

        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::now()->subDays(6)->startOfDay();
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::now()->endOfDay();

        $zoomCallLogs = $zoomCallLogs->whereBetween('call_logs.start_time', [$startDate, $endDate]);

        // --- Calculate Analytics for Current Period ---
        $analyticsQuery = clone $zoomCallLogs;
        
        $totalCalls = (clone $analyticsQuery)->count();
        $answeredCalls = (clone $analyticsQuery)->whereIn('call_logs.result', ['connected', 'answered'])->count();
        $missedCalls = $totalCalls - $answeredCalls;
        $answerRate = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $avgCallDuration = (clone $analyticsQuery)->whereNotNull('call_logs.talk_time')->where('call_logs.talk_time', '>', 0)->avg(DB::raw('CAST(call_logs.talk_time AS UNSIGNED)'));
        $avgWaitTime = (clone $analyticsQuery)->whereNotNull('call_logs.wait_time')->where('call_logs.wait_time', '>', 0)->avg(DB::raw('CAST(call_logs.wait_time AS UNSIGNED)'));

        // --- Calculate Analytics for Previous Period ---
        $prevStartDate = $startDate->copy()->subYear();
        $prevEndDate = $endDate->copy()->subYear();

        $prevQuery = CallLog::join('users', 'call_logs.user_id', '=', 'users.id')
            ->whereBetween('call_logs.start_time', [$prevStartDate, $prevEndDate]);

        if ($search) {
            $prevQuery = $prevQuery->where(function ($q) use ($search) {
                $q->where('users.name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_name', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.calle_email', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.caller_number', 'like', '%' . $search . '%')
                  ->orWhere('call_logs.callee_number', 'like', '%' . $search . '%');
            });
        }
        if ($filter == "outbound") {
            $prevQuery = $prevQuery->where('call_logs.direction', 'outbound');
        } elseif ($filter == "inbound") {
            $prevQuery = $prevQuery->where('call_logs.direction', 'inbound');
        }
        if ($userFilter) {
            $prevQuery = $prevQuery->where('call_logs.user_id', $userFilter);
        }

        $prevTotalCalls = (clone $prevQuery)->count();
        $prevAnsweredCalls = (clone $prevQuery)->whereIn('call_logs.result', ['connected', 'answered'])->count();
        $prevMissedCalls = $prevTotalCalls - $prevAnsweredCalls;
        $prevAnswerRate = $prevTotalCalls > 0 ? round(($prevAnsweredCalls / $prevTotalCalls) * 100, 1) : 0;
        $prevAvgCallDuration = (clone $prevQuery)->whereNotNull('call_logs.talk_time')->where('call_logs.talk_time', '>', 0)->avg(DB::raw('CAST(call_logs.talk_time AS UNSIGNED)'));
        $prevAvgWaitTime = (clone $prevQuery)->whereNotNull('call_logs.wait_time')->where('call_logs.wait_time', '>', 0)->avg(DB::raw('CAST(call_logs.wait_time AS UNSIGNED)'));

        $periodLabel = $prevStartDate->toDateString() === $prevEndDate->toDateString() 
            ? $prevStartDate->format('M j') 
            : $prevStartDate->format('M j') . ' - ' . $prevEndDate->format('M j');

        // --- Chart Analytics ---
        $chartData = (clone $analyticsQuery)
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
            ->map(function ($item) {
                // Formatting for the frontend
                $item->missed = $item->total - $item->answered;
                $item->avg_duration = round((float)$item->avg_duration);
                return $item;
            });

        $periodLabel = $prevStartDate->toDateString() === $prevEndDate->toDateString() 
            ? $prevStartDate->format('M j, Y') 
            : $prevStartDate->format('M j, Y') . ' - ' . $prevEndDate->format('M j, Y');

        // --- Advanced Chart Analytics ---
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

        $analytics = [
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

        if ($request->per_page) {
            $zoomCallLogs = $zoomCallLogs->paginate($request->per_page);
        } else {
            $zoomCallLogs = $zoomCallLogs->paginate(25);
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


        return Inertia::render('ZoomCallLogs/Index', [
            'zoomCallLogs' => $zoomCallLogs,
            'users' => $users,
            'analytics' => $analytics,
        ]);
    }

    public function getDownloadUrl(Request $request)
    {
        $zoom_token = $this->downloadurls($request->callId);

        return response()->json($zoom_token, 200);
    }

    public function newTokenGenerate($userid)
    {
        $zoomCredentials = ZoomApi::select('*')
            ->where('user_id', $userid)
            ->first();

        $curl1 = curl_init();
        $credentials1 = base64_encode($zoomCredentials->client_key . ":" . $zoomCredentials->client_secret);

        curl_setopt_array($curl1, array(
            CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $zoomCredentials->account_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $credentials1",
                "Content-Type: application/x-www-form-urlencoded"
            ),
        ));

        $response1 = curl_exec($curl1);
        $tokenss1 = null;

        if ($response1 === false) {
            Log::error('Curl error: ' . curl_error($curl1));
        } else {
            $response1 = json_decode($response1);
            $tokenss1 = $response1->access_token ?? null;
        }
        curl_close($curl1);

        return $tokenss1;
    }

    private function getZoomUserIdForToken()
    {
        // User ID 56 has universal Zoom API credentials
        $zoomApi = ZoomApi::where('user_id', 56)->first();

        if (!$zoomApi) {
            // Fallback to any ZoomApi record with a client_key
            $zoomApi = ZoomApi::whereNotNull('client_key')
                ->where('client_key', '!=', '')
                ->first();
        }

        return $zoomApi ? $zoomApi->user_id : null;
    }

    public function downloadurls($callid)
    {
        $zoomUserId = $this->getZoomUserIdForToken();
        if (!$zoomUserId) {
            return response()->json(['file_url' => '', 'download_url' => ''], 200);
        }

        $url = 'https://api.zoom.us/v2/phone/call_logs/' . $callid . '/recordings';

        $response = Http::withoutVerifying()->withHeaders([
            'Authorization' => 'Bearer ' . $this->newTokenGenerate($zoomUserId),
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($response->successful()) {
            $data = $response->json();
            $file_url = $data['file_url'];
            $download_url = $data['download_url'];

            return response()->json(['file_url' => $file_url, 'download_url' => $download_url], 200);

        } else {
            return response()->json(['file_url' => '', 'download_url' => ''], 200);
        }
    }

    public function getCallTranscript(Request $request)
    {
        $zoomUserId = $this->getZoomUserIdForToken();
        if (!$zoomUserId) {
            return response()->json(['data' => ''], 200);
        }

        $recordingId = $request->recordingId;
        
        if ($recordingId === 'missing' && $request->callId) {
            // Fetch recordingId dynamically
            $url = 'https://api.zoom.us/v2/phone/call_logs/' . $request->callId . '/recordings';
            $resp = Http::withoutVerifying()->withHeaders([
                'Authorization' => 'Bearer ' . $this->newTokenGenerate($zoomUserId),
                'Content-Type' => 'application/json',
            ])->get($url);
            
            if ($resp->successful()) {
                $respData = $resp->json();
                if (isset($respData['id'])) {
                    $recordingId = str_replace('-', '', $respData['id']);
                    // Update database
                    CallLog::where('call_id', $request->callId)->update(['recording_id' => $recordingId]);
                } else {
                    return response()->json(['data' => ''], 200);
                }
            } else {
                return response()->json(['data' => ''], 200);
            }
        }

        $url = 'https://zoom.us/v2/phone/recording_transcript/download/' . $recordingId;

        $response = Http::withoutVerifying()->withHeaders([
            'Authorization' => 'Bearer ' . $this->newTokenGenerate($zoomUserId),
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($response->successful()) {
            $data = $response->json();

            return response()->json(['data' => $data], 200);

        } else {
            return response()->json(['data' => ''], 200);
        }
    }
}
