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
            $zoomCallLogs = $zoomCallLogs->where('users.name', 'like', '%' . $search . '%')
                ->orWhere('call_logs.caller_name', 'like', '%' . $search . '%')
                ->orWhere('call_logs.calle_name', 'like', '%' . $search . '%')
                ->orWhere('call_logs.caller_email', 'like', '%' . $search . '%')
                ->orWhere('call_logs.calle_email', 'like', '%' . $search . '%')
                ->orWhere('call_logs.caller_number', 'like', '%' . $search . '%')
                ->orWhere('call_logs.callee_number', 'like', '%' . $search . '%');
        }

        if ($filter == "outbound") {
            $zoomCallLogs = $zoomCallLogs->where('call_logs.direction', 'outbound');
        } elseif ($filter == "inbound") {
            $zoomCallLogs = $zoomCallLogs->where('call_logs.direction', 'inbound');
        }

        if ($userFilter) {
            $zoomCallLogs = $zoomCallLogs->where('call_logs.user_id', $userFilter);
        }

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
