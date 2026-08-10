<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\CallLog;
use App\Models\Disposition;
use App\Models\MeetingLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use App\Models\ZoomApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Jubaer\Zoom\Zoom;

class ZoomController extends Controller
{
    public function tokenGenerate()
    {
        $zoomCredentials = ZoomApi::select('*')
            ->where('user_id', Auth::id())
            ->first();

        if ($zoomCredentials) {
            // config([
            //     'ZOOM_CLIENT_KEY' => $zoomCredentials->client_key,
            //     'ZOOM_CLIENT_SECRET' => $zoomCredentials->client_secret,
            //     'ZOOM_ACCOUNT_ID' => $zoomCredentials->account_id,
            // ]);

            putenv('ZOOM_CLIENT_KEY=' . $zoomCredentials->client_key);
            putenv('ZOOM_CLIENT_SECRET=' . $zoomCredentials->client_secret);
            putenv('ZOOM_ACCOUNT_ID=' . $zoomCredentials->account_id);
        }


        $curl = curl_init();
        $credentials = base64_encode(getenv('ZOOM_CLIENT_KEY') . ":" . getenv('ZOOM_CLIENT_SECRET'));

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . getenv('ZOOM_ACCOUNT_ID'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                //     'Authorization: Basic R3NyblhtSmpReFdxbmQ1QlNiQ2llUTpCQkNzREhmcHVhaW80Vk9rdExaT0tVYldZZmJtVnR4VA==',
                //     'Cookie: __cf_bm=yhEnx7x65z9JKF3w2kkhk3xIUFgtJC_WW3hMKb.LRvQ-1732890112-1.0.1.1-68Zh4L19hveT7vXoZaOK6RumlUP38Jv1Rhs9T_rJ0.v0N8qKZd0MaBPT6XF0JYg4rOM0HXb_Qc3wJt7i2GxwrA; _zm_chtaid=683; _zm_ctaid=OMCifyTuRuCT31N3a4g7Dg.1732890112337.256460f09d13447adfa8a9291e304b07; _zm_mtk_guid=cf6290eb4eab49a590f989df15fd9827; _zm_page_auth=us04_c_l0__pjfpRfqTCmKSF4tQPw; _zm_ssid=us04_c_kos7qvxhTv6VI5l3W-zSaA; _zm_visitor_guid=6a6f666e62304aa9b4d1662c11bd232d; cred=05E790C74D8AEFCB90DA4ABCA2583380'
                // ),
                "Authorization: Basic $credentials",
                "Content-Type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);


        $tokenss = null;
        if ($response === false) {
            echo 'Curl error: ' . curl_error($curl);
        } else {
            $response = json_decode($response);
            $tokenss = $response->access_token ?? null;
        }
        curl_close($curl);

        if ($tokenss) {
            // Store token and timestamp in session
            $timestamp = Carbon::now();
            Session::put('zoom_token', $tokenss);
            Session::put('zoom_token_timestamp', $timestamp);
        }


        echo Session::get('zoom_token');
    }

    public function validateZoomToken()
    {
        $storedToken = Session::get('zoom_token');
        $storedTimestamp = Session::get('zoom_token_timestamp');

        // Check if token or timestamp exists
        if (!$storedToken || !$storedTimestamp) {
            return $this->tokenGenerate(); // Generate a new token if not found
        }

        // Compare stored timestamp with current time
        $storedTimestamp = Carbon::parse($storedTimestamp);
        $now = Carbon::now();

        if ($storedTimestamp->diffInMinutes($now) >= 60) { // Token valid for 60 minutes
            return $this->refreshZoomToken(); // Refresh token if expired
        }

        return $storedToken; // Return the valid token
    }


    public function refreshZoomToken()
    {
        $zoomCredentials = ZoomApi::where('user_id', Auth::id())->first();

        if ($zoomCredentials) {
            putenv('ZOOM_CLIENT_KEY=' . $zoomCredentials->client_key);
            putenv('ZOOM_CLIENT_SECRET=' . $zoomCredentials->client_secret);
            putenv('ZOOM_ACCOUNT_ID=' . $zoomCredentials->account_id);
        }

        $curl = curl_init();
        $credentials = base64_encode(getenv('ZOOM_CLIENT_KEY') . ":" . getenv('ZOOM_CLIENT_SECRET'));

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . getenv('ZOOM_ACCOUNT_ID'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $credentials",
                "Content-Type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);

        $newAccessToken = null;
        if ($response === false) {
            echo 'Curl error: ' . curl_error($curl);
            curl_close($curl);
            return;
        } else {
            $response = json_decode($response);
            $newAccessToken = $response->access_token ?? null;
        }
        curl_close($curl);

        if ($newAccessToken) {
            // Update session with new token and timestamp
            $timestamp = Carbon::now();
            Session::put('zoom_token', $newAccessToken);
            Session::put('zoom_token_timestamp', $timestamp);
        }

        return $newAccessToken;
    }


    public function createMeeting(Request $request)
    {
        // Validate or refresh the Zoom token
        $token = $this->validateZoomToken();

        // Map of common abbreviations to valid timezones
        $timezoneMap = [
            'MST' => 'America/Denver',       // Mountain Standard Time
            'PST' => 'America/Los_Angeles',  // Pacific Standard Time
            'CST' => 'America/Chicago',      // Central Standard Time
            'EST' => 'America/New_York',     // Eastern Standard Time
            'AST' => 'America/Puerto_Rico',      // Atlantic Standard Time
            'HST' => 'Pacific/Honolulu',     // Hawaii Standard Time
            'UTC' => 'UTC',                  // Coordinated Universal Time
        ];

        $inputTimezone = $request->input('timezone', 'UTC'); // Default to UTC if not provided
        $timezone = $timezoneMap[$inputTimezone] ?? $inputTimezone;

        if (!in_array($timezone, timezone_identifiers_list())) {
            return response()->json(['error' => 'Invalid timezone provided', 'valid_timezones' => array_keys($timezoneMap)], 400);
        }

        // Parse start_time from the request
        $startTimeInput = $request->input('start');
        try {
            $startTime = new \DateTime($startTimeInput, new \DateTimeZone($timezone));
            $isoStartTime = $startTime->format('Y-m-d\TH:i:s'); // ISO 8601 format
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid start time provided. Expected format: ISO 8601 or parsable date string.', 'details' => $e->getMessage()], 400);
        }

        // Prepare Zoom meeting data
        $zoomPayload = [
            "topic" => $request->input('title', 'No Title'),
            "type" => 2,
            "start_time" => $isoStartTime,
            "duration" => $request->input('duration', 60),
            "timezone" => $timezone,
            "password" => $request->input('password', '123456'),
            "agenda" => $request->input('description', ''),
            "settings" => [
                "host_video" => true,
                "participant_video" => true,
                "join_before_host" => false,
                "mute_upon_entry" => true,
                "approval_type" => 2,
                "registration_type" => 1,
                "registrants_email_notification" => true,
            ],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.zoom.us/v2/users/me/meetings',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($zoomPayload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if (!$response || $httpCode >= 400) {
            return response()->json(['error' => 'Failed to create Zoom meeting', 'details' => json_decode($response, true)], $httpCode ?: 500);
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['id']) && isset($responseData['join_url'])) {
            $request->merge([
                'zoom_meeting_id' => $responseData['id'],
                'zoom_meeting_url' => $responseData['join_url'],
                'zoom_meeting_details' => $responseData,
            ]);
        } else {
            return response()->json(['error' => 'Invalid response from Zoom API'], 500);
        }

        // Call the function create from CalendarController
        $calendarController = new \App\Http\Controllers\CalendarController();
        $res = $calendarController->Create($request);

        return response()->json($res->original);



        die();

        $zoomCredentials = ZoomApi::select('*')
            ->where('id', 2)
            ->first();

        if ($zoomCredentials) {
            // config([
            //     'ZOOM_CLIENT_KEY' => $zoomCredentials->client_key,
            //     'ZOOM_CLIENT_SECRET' => $zoomCredentials->client_secret,
            //     'ZOOM_ACCOUNT_ID' => $zoomCredentials->account_id,
            // ]);

            putenv('ZOOM_CLIENT_KEY=' . $zoomCredentials->client_key);
            putenv('ZOOM_CLIENT_SECRET=' . $zoomCredentials->client_secret);
            putenv('ZOOM_ACCOUNT_ID=' . $zoomCredentials->account_id);
        }

        $zoom = new Zoom();
        // return $meetings = $zoom->getAllMeeting();
        $meetings = $zoom->createMeeting([
            "agenda" => 'test my meeting',
            "topic" => 'test phase23',
            "type" => 2, // 1 => instant, 2 => scheduled, 3 => recurring with no fixed time, 8 => recurring with fixed time
            "duration" => 60, // in minutes
            "timezone" => 'Asia/Mumbai', // set your timezone
            "password" => 'Konu@1992',
            "start_time" => '18:05', // set your start time
            "template_id" => '151', // set your template id  Ex: "Dv4YdINdTk+Z5RToadh5ug==" from https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetingtemplates
            "pre_schedule" => false,  // set true if you want to create a pre-scheduled meeting
            "schedule_for" => $zoomCredentials->email, // set your schedule for
            "settings" => [
                'join_before_host' => true, // if you want to join before host set true otherwise set false
                'host_video' => true, // if you want to start video when host join set true otherwise set false
                'participant_video' => true, // if you want to start video when participants join set true otherwise set false
                'mute_upon_entry' => false, // if you want to mute participants when they join the meeting set true otherwise set false
                'waiting_room' => true, // if you want to use waiting room for participants set true otherwise set false
                'audio' => 'both', // values are 'both', 'telephony', 'voip'. default is both.
                'auto_recording' => 'none', // values are 'none', 'local', 'cloud'. default is none.
                'approval_type' => 0, // 0 => Automatically Approve, 1 => Manually Approve, 2 => No Registration Required
            ],
        ]);

        return $meetings;
    }

    public function updateZoomMeeting(Request $request)
    {
        // Validate or refresh the Zoom token
        $token = $this->validateZoomToken();

        $meetingId = $request->zoomMeetingId;

        if (empty($meetingId)) {
            return response()->json(['error' => 'Meeting ID is required'], 400);
        }

        // Map of common abbreviations to valid timezones
        $timezoneMap = [
            'MST' => 'America/Denver',
            'PST' => 'America/Los_Angeles',
            'CST' => 'America/Chicago',
            'EST' => 'America/New_York',
            'AST' => 'America/Halifax',
            'HST' => 'Pacific/Honolulu',
            'UTC' => 'UTC',
        ];

        $inputTimezone = $request->input('timezone', 'UTC'); // Default to UTC if not provided
        $timezone = $timezoneMap[$inputTimezone] ?? $inputTimezone;

        if (!in_array($timezone, timezone_identifiers_list())) {
            return response()->json(['error' => 'Invalid timezone provided', 'valid_timezones' => array_keys($timezoneMap)], 400);
        }

        try {
            // Parse start_time with timezone
            $startTimeInput = $request->input('start');
            $startTime = new \DateTime($startTimeInput, new \DateTimeZone($timezone));
            $isoStartTime = $startTime->format('Y-m-d\TH:i:s'); // ISO 8601 format
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid start time provided. Expected format: ISO 8601 or parsable date string.',
                'details' => $e->getMessage(),
            ], 400);
        }

        $payload = [
            "topic" => $request->title,
            "type" => 2,
            "start_time" => $isoStartTime,
            "duration" => $request->input('duration', 60), // Default duration to 60 minutes
            "timezone" => $timezone,
            "password" => $request->input('password', '123456'),
            "agenda" => $request->description,
            "settings" => [
                "host_video" => true,
                "participant_video" => true,
                "join_before_host" => false,
                "mute_upon_entry" => true,
                "approval_type" => 2,
                "registration_type" => 1,
                "registrants_email_notification" => true,
            ],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.zoom.us/v2/meetings/' . $meetingId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $errorMessage = curl_error($curl);
            curl_close($curl);
            return response()->json(['error' => 'Curl error: ' . $errorMessage], 500);
        }

        curl_close($curl);

        if ($httpCode >= 400) {
            return response()->json(['error' => 'Zoom API error', 'details' => json_decode($response, true)], $httpCode);
        }

        // Database update
        $calendarController = new \App\Http\Controllers\CalendarController();
        $dbResponse = $calendarController->update($request);

        // Combine responses
        return response()->json([
            'message' => 'Zoom meeting updated successfully',
            'dbUpdate' => $dbResponse->original,
        ]);
    }

    public function deleteZoomMeeting(Request $request)
    {
        // Validate or refresh the Zoom token
        $token = $this->validateZoomToken();

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.zoom.us/v2/meetings/' . $request->meeting_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Get the HTTP status code
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return response()->json(['error' => 'Curl error: ' . $error], 500);
        }

        if ($httpCode === 204) {
            // Successfully deleted
            $calendarController = new \App\Http\Controllers\CalendarController();
            $res = $calendarController->delete($request);

            return response()->json(['message' => 'Zoom meeting deleted successfully', 'dbUpdate' => $res->original]);
        } elseif ($httpCode >= 400) {
            // Zoom API returned an error
            return response()->json(['error' => 'Zoom API error', 'status_code' => $httpCode, 'response' => $response], $httpCode);
        }

        // Fallback for unexpected response
        return response()->json(['error' => 'Unexpected response from Zoom API', 'status_code' => $httpCode, 'response' => $response], 500);
    }

    public function getZoomMeeting(Request $request)
    {
        // Validate or refresh the Zoom token
        $token = $this->validateZoomToken();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.zoom.us/v2/meetings/' . $request->meeting_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;

        if (!$response) {
            return response()->json(['error' => 'Failed to get Zoom meeting'], 500);
        }

        $responseData = json_decode($response, true);
        // dd($responseData);

        if (isset($responseData['id']) && isset($responseData['join_url'])) {
            // Add fields from the Zoom response to the request
            $request->merge([
                'zoom_meeting_url' => $responseData['join_url'],
                'zoom_meeting_details' => $responseData,
            ]);
        } else {
            return response()->json(['error' => 'Invalid response from Zoom API'], 500);
        }

        // Call the function create from CalendarController
        $calendarController = new \App\Http\Controllers\CalendarController();
        $res = $calendarController->get($request);

        return response()->json($res->original);
    }

    public function getZoomCredentials(Request $request)
    {
        $zoomDetails = ZoomApi::where('user_id', $request->user_id)
            ->select('client_key', 'client_secret', 'account_id', 'id', 'user_id', 'email_id')
            ->first();

        if ($zoomDetails) {
            return response()->json($zoomDetails, 200);
        }

        return response()->json(['error' => 'Zoom details not found'], 404);
    }

    public function updateUserZoomCredentials(Request $request)
    {
        $zoomDetails = ZoomApi::where('user_id', $request->userId)->first();

        if ($zoomDetails) {
            $zoomDetails->update([
                'client_key' => $request->zoomClientKey,
                'client_secret' => $request->zoomClientSecret,
                'account_id' => $request->zoomAccountId,
                'updated_at' => now(),
            ]);
        } else {
            $zoomDetails = ZoomApi::create([
                'user_id' => $request->userId,
                'client_key' => $request->zoomClientKey,
                'client_secret' => $request->zoomClientSecret,
                'account_id' => $request->zoomAccountId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($zoomDetails) {
            putenv('ZOOM_CLIENT_KEY=' . $request->zoomClientKey);
            putenv('ZOOM_CLIENT_SECRET=' . $request->zoomClientSecret);
            putenv('ZOOM_ACCOUNT_ID=' . $request->zoomAccountId);

            return redirect()->back()->with('success', 'Zoom details updated successfully');
        }

        return redirect()->back()->with('error', 'Failed to update Zoom details');
    }

    public function updateZoomDetails(Request $request)
    {
        // validations for the request data fields and return error response if any validation fails
        $request->validate([
            'clientKey' => 'required|string',
            'clientSecret' => 'required|string',
            'accountId' => 'required|string',
        ]);

        $zoomDetails = ZoomApi::where('user_id', Auth::id())->first();


        // update zoom details if exists, else create new record in the ZoomAPI table
        if ($zoomDetails) {
            $zoomDetails->update([
                'client_key' => $request->clientKey,
                'client_secret' => $request->clientSecret,
                'account_id' => $request->accountId,
                'updated_at' => now(),
            ]);
        } else {
            $zoomDetails = ZoomApi::create([
                'user_id' => Auth::id(),
                'email_id' => Auth::user()->email,
                'client_key' => $request->clientKey,
                'client_secret' => $request->clientSecret,
                'account_id' => $request->accountId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        if ($zoomDetails) {
            putenv('ZOOM_CLIENT_KEY=' . $request->clientKey);
            putenv('ZOOM_CLIENT_SECRET=' . $request->clientSecret);
            putenv('ZOOM_ACCOUNT_ID=' . $request->accountId);

            return response()->json(['message' => 'Zoom details updated successfully'], 200);
        }

        return response()->json(['error' => 'Failed to update Zoom details'], 500);
    }

    public function getZoomAndCRMCalls($userId)
    {
        $sel = CallLog::select('caller_number')->where('user_id', $userId)->pluck('caller_number');
        $zoomnumber = array_unique($sel->toArray());

        $disposition_new = Disposition::select('phone')->where('user_id', $userId)->pluck('phone')->toArray();

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

        $companyData = array_diff($cleanedNumbersArray2, $cleanedNumbersArray);


        return ['call_salecrm' => count($disposition_new), 'zoom_api' => count($cleanedNumbersArray)];
    }

    public function getZoomRecordings()
    {
        // Validate or refresh the Zoom token
        $token = $this->validateZoomToken();

        // Get the Zoom API credentials - user ID 56 has universal credentials
        $zoomApi = ZoomApi::where('user_id', 56)->first();

        if (!$zoomApi) {
            // Fallback to any ZoomApi record with a client_key
            $zoomApi = ZoomApi::whereNotNull('client_key')
                ->where('client_key', '!=', '')
                ->first();
        }

        if (!$zoomApi) {
            return ['error' => 'No Zoom API credentials found'];
        }

        $zoomUserId = $zoomApi->account_id;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.zoom.us/v2/users/' . urlencode($zoomUserId) . '/recordings?page_size=300&from=' . date('Y-01-01') . '&to=' . date('Y-12-31'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
            ),
        ));
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!$response || $httpCode >= 400) {
            Log::error('Zoom API getZoomRecordings failed', [
                'http_code' => $httpCode,
                'response' => $response,
                'zoom_user_id' => $zoomUserId,
            ]);
            return ['error' => 'Failed to fetch recordings', 'http_code' => $httpCode];
        }

        // Decode the JSON response into an associative array
        $responseData = json_decode($response, true);

        return $responseData;
    }

    public function getCallList($startDate = null, $endDate = null)
    {

        $userlist = $this->getZoomAdminUser();

        if (!$userlist) {
            return response()->json(['error' => 'No Zoom admin user found'], 500);
        }

        $this->tokenGenerate1($userlist->id);

        // Use the user's email as the Zoom user ID (original code used hardcoded email)
        $zoomUserId = $userlist->email;
        $url = 'https://api.zoom.us/v2/phone/users/' . $zoomUserId . '/call_history';
        
        $fromDate = $startDate ? date('Y-m-d', strtotime($startDate)) : date('Y-m-d');
        $toDate = $endDate ? date('Y-m-d', strtotime($endDate . ' +1 days')) : date('Y-m-d', strtotime("+1 days"));

        $dateChunks = $this->getDateChunks($fromDate, $toDate);

        foreach ($dateChunks as $chunk) {
            $nextPageToken = '';

            do {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . Session::get('zoom_token'),
                    'Content-Type' => 'application/json',
                ])->get($url, [
                            'page_size' => 30,
                            'from' => $chunk['from'],
                            'to' => $chunk['to'],
                            'next_page_token' => $nextPageToken,
                        ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['call_logs'])) {
                        $callLogs = $data['call_logs'];

                        // Save call logs to the database
                        foreach ($callLogs as $log) {
                            $findlog = CallLog::where('call_id', $log['id'])->count();
                            if ($findlog == 0) {

                                $furl = $this->downloadurls($log['id']);

                                CallLog::create([
                                    'call_id' => $log['id'],
                                    'caller_number' => isset($log['caller_did_number']) ? $log['caller_did_number'] : '',
                                    'callee_number' => isset($log['callee_did_number']) ? $log['callee_did_number'] : '',
                                    'start_time' => isset($log['start_time']) ? Carbon::parse($log['start_time'])->toDateTimeString() : NULL,
                                    'answer_time' => isset($log['answer_time']) ? Carbon::parse($log['answer_time'])->toDateTimeString() : NULL,
                                    'end_time' => isset($log['end_time']) ? Carbon::parse($log['end_time'])->toDateTimeString() : NULL,
                                    'direction' => isset($log['direction']) ? $log['direction'] : '',
                                    'department' => isset($log['department']) ? $log['department'] : '',
                                    'caller_name' => isset($log['caller_name']) ? $log['caller_name'] : '',
                                    'caller_email' => isset($log['caller_email']) ? $log['caller_email'] : '',
                                    'result' => isset($log['result']) ? $log['result'] : '',
                                    'international' => isset($log['international']) ? $log['international'] : '',
                                    'event' => isset($log['event']) ? $log['event'] : '',
                                    'caller_ext_number' => isset($log['caller_ext_number']) ? $log['caller_ext_number'] : '',
                                    'caller_ext_type' => isset($log['caller_ext_type']) ? $log['caller_ext_type'] : '',
                                    'caller_number_type' => isset($log['caller_number_type']) ? $log['caller_number_type'] : '',
                                    'caller_device_type' => isset($log['caller_device_type']) ? $log['caller_device_type'] : '',
                                    'group_id' => isset($log['group_id']) ? $log['group_id'] : '',
                                    'recording_id' => isset($log['recording_id']) ? $log['recording_id'] : '',
                                    'recording_type' => isset($log['recording_type']) ? $log['recording_type'] : '',
                                    'talk_time' => isset($log['talk_time']) ? $log['talk_time'] : '',
                                    'hold_time' => isset($log['hold_time']) ? $log['hold_time'] : '',
                                    'wait_time' => isset($log['wait_time']) ? $log['wait_time'] : '',
                                    'calle_name' => isset($log['callee_name']) ? $log['callee_name'] : '',
                                    'calle_email' => isset($log['callee_email']) ? $log['callee_email'] : '',
                                    'ai_call_summary_id' => isset($log['ai_call_summary_id']) ? $log['ai_call_summary_id'] : '',
                                    'operator_name' => isset($log['operator_name']) ? $log['operator_name'] : '',
                                    'operator_ext_number' => isset($log['operator_ext_number']) ? $log['operator_ext_number'] : '',
                                    'operator_ext_Type' => isset($log['operator_ext_Type']) ? $log['operator_ext_Type'] : '',
                                    'operator_ext_id' => isset($log['operator_ext_id']) ? $log['operator_ext_id'] : '',
                                    'user_id' => $userlist->id,
                                    'file_url' => $furl->original['file_url'],
                                    'download_url' => $furl->original['download_url'],
                                ]);
                            }
                        }
                    }

                    $nextPageToken = $data['next_page_token'] ?? '';
                } else {
                    \Illuminate\Support\Facades\Log::error('Failed to fetch call logs', ['response' => $response->body()]);
                    $nextPageToken = '';
                }
            } while (!empty($nextPageToken));
        }

    }

    /**
     * Find a user who has Zoom API credentials configured in the ZoomApi table.
     * Used as a fallback when no specific user context is available (e.g. CLI commands).
     */
    private function getZoomAdminUser()
    {
        // User ID 56 has universal Zoom API credentials that work for all users
        $zoomApi = ZoomApi::where('user_id', 56)->first();

        if ($zoomApi) {
            $user = User::find($zoomApi->user_id);
            if ($user) {
                return $user;
            }
        }

        // Fallback: find any ZoomApi record with a client_key
        $zoomApi = ZoomApi::whereNotNull('client_key')
            ->where('client_key', '!=', '')
            ->first();

        if ($zoomApi) {
            $user = User::find($zoomApi->user_id);
            if ($user) {
                return $user;
            }
        }

        // Last fallback: find any user with a zoom_user_id set
        $user = User::whereNotNull('zoom_user_id')
            ->where('zoom_user_id', '!=', '')
            ->first();

        return $user;
    }

    public function downloadurls($callid)
    {

        // Use the session token directly (set by tokenGenerate1 which is called before this)
        // validateZoomToken() requires an authenticated user and won't work in CLI context
        $token = Session::get('zoom_token');

        if (!$token) {
            Log::warning('downloadurls: No Zoom token available in session');
            return response()->json(['file_url' => '', 'download_url' => ''], 200);
        }

        $url = 'https://api.zoom.us/v2/phone/call_logs/' . $callid . '/recordings';


        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($response->successful()) {
            $data = $response->json();
            $file_url = $data['file_url'];
            $download_url = $data['download_url'];
            return response()->json(['file_url' => $file_url, 'download_url' => $download_url], 200);

        } else {
            // Log the failure for monitoring but return gracefully
            Log::warning('downloadurls: Zoom API call failed', [
                'call_id' => $callid,
                'http_status' => $response->status(),
            ]);
            return response()->json(['file_url' => '', 'download_url' => ''], 200);
        }

    }

    public function tokenGenerate1($userid)
    {
        // User ID 56 has universal Zoom API credentials that work for all users
        $zoomCredentials = ZoomApi::select('*')
            ->where('user_id', 56)
            ->first();

        if ($zoomCredentials) {
            // config([
            //     'ZOOM_CLIENT_KEY' => $zoomCredentials->client_key,
            //     'ZOOM_CLIENT_SECRET' => $zoomCredentials->client_secret,
            //     'ZOOM_ACCOUNT_ID' => $zoomCredentials->account_id,
            // ]);

            putenv('ZOOM_CLIENT_KEY=' . $zoomCredentials->client_key);
            putenv('ZOOM_CLIENT_SECRET=' . $zoomCredentials->client_secret);
            putenv('ZOOM_ACCOUNT_ID=' . $zoomCredentials->account_id);
        }


        $curl1 = curl_init();
        $credentials1 = base64_encode(getenv('ZOOM_CLIENT_KEY') . ":" . getenv('ZOOM_CLIENT_SECRET'));

        curl_setopt_array($curl1, array(
            CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . getenv('ZOOM_ACCOUNT_ID'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                //     'Authorization: Basic R3NyblhtSmpReFdxbmQ1QlNiQ2llUTpCQkNzREhmcHVhaW80Vk9rdExaT0tVYldZZmJtVnR4VA==',
                //     'Cookie: __cf_bm=yhEnx7x65z9JKF3w2kkhk3xIUFgtJC_WW3hMKb.LRvQ-1732890112-1.0.1.1-68Zh4L19hveT7vXoZaOK6RumlUP38Jv1Rhs9T_rJ0.v0N8qKZd0MaBPT6XF0JYg4rOM0HXb_Qc3wJt7i2GxwrA; _zm_chtaid=683; _zm_ctaid=OMCifyTuRuCT31N3a4g7Dg.1732890112337.256460f09d13447adfa8a9291e304b07; _zm_mtk_guid=cf6290eb4eab49a590f989df15fd9827; _zm_page_auth=us04_c_l0__pjfpRfqTCmKSF4tQPw; _zm_ssid=us04_c_kos7qvxhTv6VI5l3W-zSaA; _zm_visitor_guid=6a6f666e62304aa9b4d1662c11bd232d; cred=05E790C74D8AEFCB90DA4ABCA2583380'
                // ),
                "Authorization: Basic $credentials1",
                "Content-Type: application/x-www-form-urlencoded"
            ),
        ));

        $response1 = curl_exec($curl1);


        $tokenss1 = null;
        if ($response1 === false) {
            echo 'Curl error: ' . curl_error($curl1);
        } else {
            $response1 = json_decode($response1);
            $tokenss1 = $response1->access_token ?? null;
        }
        curl_close($curl1);

        if ($tokenss1) {
            // Store token and timestamp in session
            $timestamp1 = Carbon::now();
            Session::put('zoom_token', $tokenss1);
            Session::put('zoom_token_timestamp', $timestamp1);
        }


        // echo Session::get('zoom_token');
    }

    public function getMeetingList($startDate = null, $endDate = null)
    {

        $userlist = $this->getZoomAdminUser();
        if (!$userlist) {
            return response()->json(['error' => 'No Zoom admin user found'], 500);
        }
        $this->tokenGenerate1($userlist->id);
        $userlist2 = User::where('zoom_user_id', '!=', '')->get();
        
        $fromDate = $startDate ? date('Y-m-d', strtotime($startDate)) : date('Y-m-01');
        $toDate = $endDate ? date('Y-m-d', strtotime($endDate . ' +1 days')) : date('Y-m-d', strtotime("+1 days"));

        $dateChunks = $this->getDateChunks($fromDate, $toDate);

        foreach ($userlist2 as $userss) {
            //    $this->tokenGenerate1($userss->zoom_user_id);


            $url = 'https://api.zoom.us/v2/users/' . $userss->zoom_user_id . '/recordings';

            foreach ($dateChunks as $chunk) {
                $nextPageToken = '';

                do {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . Session::get('zoom_token'),
                        'Content-Type' => 'application/json',
                    ])->get($url, [
                                'page_size' => 50,
                                'from' => $chunk['from'],
                                'to' => $chunk['to'],
                                'next_page_token' => $nextPageToken,
                            ]);

                    if ($response->successful()) {
                        $data = $response->json();

                        if (isset($data['meetings'])) {
                            $callLogs = $data['meetings'];


                            foreach ($callLogs as $log) {

                                $findlog = MeetingLog::where('meeting_id', $log['id'])->count();
                                if ($findlog == 0) {
                                    $summary = "";
                                    $audiosummary = "";
                                    
                                    if (isset($log['recording_files'])) {
                                        foreach ($log['recording_files'] as $key => $file) {
                                            if (array_key_exists('recording_type', $file)) {
                                                if ($log['recording_files'][$key]['recording_type'] == 'summary') {
                                                    $summary = json_encode($this->transcript($log['recording_files'][$key]['download_url']));
                                                }

                                                if ($log['recording_files'][$key]['recording_type'] == 'audio_transcript') {
                                                    $audiosummary = $log['recording_files'][$key]['download_url'];
                                                }
                                            }
                                        }
                                    }
                                    if (!isset($audiosummary)) {
                                        $audiosummary = "";
                                    }
                                    if (!isset($summary)) {
                                        $summary = "";
                                    }
                                    // echo $log['recording_files'][$key]['download_url'];
                                    if ($audiosummary != "") {
                                        $furl = json_encode($this->audiotranscript($audiosummary, $log['id']));
                                        $darrfurl = json_decode($furl, true);

                                        if (isset($darrfurl['original']['file_url'])) {
                                            $fileUrlstorage = $darrfurl['original']['file_url'];

                                        } else {
                                            $fileUrlstorage = "";
                                        }
                                    } else {
                                        $fileUrlstorage = "";
                                    }
                                    MeetingLog::create([
                                        'meeting_id' => $log['id'],
                                        'account_id' => isset($log['account_id']) ? $log['account_id'] : '',
                                        'host_id' => isset($log['host_id']) ? $log['host_id'] : '',
                                        'topic' => isset($log['topic']) ? $log['topic'] : NULL,
                                        'type' => isset($log['type']) ? $log['type'] : NULL,
                                        'start_time' => isset($log['start_time']) ? Carbon::parse($log['start_time'])->toDateTimeString() : NULL,
                                        'timezone' => isset($log['timezone']) ? $log['timezone'] : '',
                                        'duration' => isset($log['duration']) ? $log['duration'] : '',
                                        'share_url' => isset($log['share_url']) ? $log['share_url'] : '',
                                        'record_start' => isset($log['recording_start'][0]['recording_start']) ? Carbon::parse($log['recording_start'][0]['recording_start'])->toDateTimeString() : '',
                                        'record_end' => isset($log['recording_end'][0]['recording_end']) ? Carbon::parse($log['recording_end'][0]['recording_end'])->toDateTimeString() : '',
                                        'user_id' => $userss->id,
                                        'meeting_key' => isset($log['uuid']) ? $log['uuid'] : '',
                                        'participants' => json_encode($this->participants($log['id'])),
                                        'transcript' => $summary,
                                        'audio_transcript' => $audiosummary,
                                        'audio_file_script_url' => $fileUrlstorage,
                                        'recording_play_passcode' => isset($log['recording_play_passcode']) ? $log['recording_play_passcode'] : '',
                                    ]);
                                }
                            }
                        }

                        $nextPageToken = $data['next_page_token'] ?? '';
                    } else {
                        \Illuminate\Support\Facades\Log::error('Failed to fetch meeting logs', ['response' => $response->body()]);
                        $nextPageToken = '';
                    }
                } while (!empty($nextPageToken));
            }
        }

    }
    public function getRecId($startDate = null, $endDate = null)
    {
        ini_set('memory_limit', '1024M');

        $userlist = $this->getZoomAdminUser();
        if (!$userlist) {
            return response()->json(['error' => 'No Zoom admin user found'], 500);
        }
        $this->tokenGenerate1($userlist->id);
        
        $query = CallLog::where('recording_id', '=', '');
        
        if ($startDate) {
            $query->where('start_time', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        } else {
            // Default to last 7 days if no start date is provided
            $query->where('start_time', '>=', Carbon::now()->subDays(7));
        }
        
        if ($endDate) {
            $query->where('start_time', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
        }

        $query->orderBy('id', 'desc')->chunk(200, function($call_log) {
            foreach ($call_log as $log) {
                $url = 'https://api.zoom.us/v2/phone/call_logs/' . $log->call_id;

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . Session::get('zoom_token'),
                    'Content-Type' => 'application/json',
                ])->get($url, [
                    'page_size' => 2000,
                    'page_number' => 1,
                    'status' => 'active'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $clog = CallLog::where('call_id', $log->call_id)->first();
                    if ($clog) {
                        // If there is no recording, set to 'NONE' to prevent checking again
                        $clog->recording_id = !empty($data['recording_id']) ? $data['recording_id'] : 'NONE';
                        $clog->save();
                    }
                }
            }
        });
    }

    public function getZoomUsersList()
    {

        $userlist = $this->getZoomAdminUser();
        if (!$userlist) {
            return response()->json(['error' => 'No Zoom admin user found'], 500);
        }
        $this->tokenGenerate1($userlist->id);


        $url = 'https://api.zoom.us/v2/users';
        $nextPageToken = '';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . Session::get('zoom_token'),
            'Content-Type' => 'application/json',
        ])->get($url, [
                    'page_size' => 2000,
                    'page_number' => 1,
                    'status' => 'active'
                    // 'from' => '2024-07-01',
                    // 'to' => '2025-08-10',
                    // 'next_page_token' => $nextPageToken,
                ]);

        if ($response->successful()) {
            $data = $response->json();
            foreach ($data['users'] as $val) {
                echo $val['email'] . '<br>';

                $userss = User::where('email', $val['email'])->first();
                if ($userss) {
                    $userss->zoom_user_id = $val['id'];
                    $userss->save();
                }
            }

        }

    }

    public function participants($id)
    {


        $url = 'https://api.zoom.us/v2/past_meetings/' . $id . '/participants';
        $nextPageToken = '';

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Session::get('zoom_token'),
                'Content-Type' => 'application/json',
            ])->get($url, [
                        'page_size' => 50,
                        'next_page_token' => $nextPageToken,
                    ]);

            if ($response->successful()) {
                $data = $response->json();

                $callLogs = $data['participants'];
                return $callLogs;

                //  foreach ($callLogs as $log) {
                //     $findlog=MeetingLog::where('meeting_id',$log['id'])->count();
                //     if($findlog==0){
                //         MeetingLog::create([
                //             'meeting_id' => $log['id'],
                //             'account_id' => isset($log['account_id']) ? $log['account_id'] : '',
                //             'host_id' => isset($log['host_id']) ? $log['host_id'] : '',
                //             'topic' =>isset($log['topic']) ? $log['topic'] : NULL,
                //             'type' =>isset($log['type']) ? $log['type'] : NULL,
                //             'start_time' => isset($log['start_time']) ? Carbon::parse($log['start_time'])->toDateTimeString() : NULL,
                //             'timezone' => isset($log['timezone']) ? $log['timezone'] : '',
                //             'duration' => isset($log['duration']) ? $log['duration'] : '',
                //             'share_url' => isset($log['share_url']) ? $log['share_url'] : '',
                //             'record_start'=> isset($log['recording_start'][0]['recording_start']) ? Carbon::parse($log['recording_start'][0]['recording_start'])->toDateTimeString() : '',
                //             'record_end' => isset($log['recording_end'][0]['recording_end']) ? Carbon::parse($log['recording_end'][0]['recording_end'])->toDateTimeString() : '',
                //             'user_id'  =>$userlist->id,
                //             'meeting_key' => isset($log['uuid']) ? $log['uuid'] : '',
                //             'recording_play_passcode'=> isset($log['recording_play_passcode']) ? $log['recording_play_passcode'] : '',
                //         ]);
                //  }
                // }

                $nextPageToken = $data['next_page_token'] ?? '';
            } else {
                return response()->json(['error' => 'Failed to fetch meeting  logs'], 500);
            }
        } while (!empty($nextPageToken));

    }

    public function transcript($id)
    {

        $url = $id;



        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . Session::get('zoom_token'),
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($response->successful()) {
            $data = $response->json();

            //  $callLogs = $data['participants'];
            return $data;



        } else {
            return response()->json(['error' => 'Failed to fetch meeting  logs'], 500);
        }


    }

    public function audiotranscript($url_Link, $id)
    {

        $url = $url_Link;


        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . Session::get('zoom_token'),
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($response->successful()) {
            $data = $response->json();
            // $fileContent = file_get_contents($response);
            $fileName = 'audio_' . $id . '.txt'; // Replace with the desired file name and extension
            $filePath = 'uploads/zoom/transcripts/' . $fileName; // You can change the folder structure as needed

            // Save the file to the storage folder
            Storage::disk('public')->put($filePath, $response);

            return response()->json(['file_url' => "/storage/uploads/zoom/transcripts/" . $fileName]);
            //  $callLogs = $data['participants'];



        } else {
            return response()->json(['error' => 'Failed to fetch meeting  logs'], 500);
        }


    }

    public function getDevCallList()
    {
        $userlist = $this->getZoomAdminUser();
        if (!$userlist) {
            return response()->json(['error' => 'No Zoom admin user found'], 500);
        }
        $this->tokenGenerate1($userlist->id);

        $url = 'https://api.zoom.us/v2/phone/call_history';

        $nextPageToken = '';

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Session::get('zoom_token'),
                'Content-Type' => 'application/json',
            ])->get($url, [
                        'page_size' => 300,
                       // 'from' => date('Y-m-23'),
                        'from' => date('Y-m-d'),
                        // 'to' => date('Y-m-20'),
                        'to' => date('Y-m-d', strtotime("+1 days")),
                        'department' => 'BDE',
                        'next_page_token' => $nextPageToken,
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                $callLogs = $data['call_logs'];

                // Save call logs to the database
                foreach ($callLogs as $log) {
                    $findlog = CallLog::where('call_id', $log['call_id'])->count();
                    if ($findlog == 0) {

                        $furl = $this->downloadurls($log['call_id']);

                        $url21 = 'https://api.zoom.us/v2/phone/call_history_detail/' . $log['id'];

                        $response21 = Http::withHeaders([
                            'Authorization' => 'Bearer ' . Session::get('zoom_token'),
                            'Content-Type' => 'application/json',
                        ])->get($url21);

                        if ($response21->successful()) {
                            $data21 = $response21->json();
                            $talk_time = isset($data21['talk_time']) ? $data21['talk_time'] : '';
                            $hold_time = isset($data21['hold_time']) ? $data21['hold_time'] : '';
                            $wait_time = isset($data21['wait_time']) ? $data21['wait_time'] : '';
                            $callee_name = isset($data21['callee_name']) ? $data21['callee_name'] : '';
                            $callee_email = isset($data21['callee_email']) ? $data21['callee_email'] : '';

                            $caller_name = isset($data21['caller_name']) ? $data21['caller_name'] : '';
                            $caller_email = isset($data21['caller_email']) ? $data21['caller_email'] : '';

                        } else {
                            $talk_time = '';
                            $hold_time = '';
                            $wait_time = '';
                        }

                        if (isset($log['caller_email'])) {
                            if (($log['caller_email'] != '')) {
                                if ($log['caller_email'] == 'shubhangi.rajora@patterns247.net') {
                                    $usersids = 107;
                                } else if ($log['caller_email'] == 'sanket.chauhan@patterns247.net') {
                                    $usersids = 102;
                                } else if ($log['caller_email'] == 'biraj.patel@patterns247.net') {
                                    $usersids = 103;
                                } else {
                                    $userlists = User::where('email', $log['caller_email'])->get();
                                    if (count($userlists) > 0) {
                                        $usersids = $userlists[0]->id;
                                    } else {
                                        $usersids = 6;
                                    }
                                }

                            } else if (($log['caller_email'] == '') && ($log['calle_email'] != '')) {
                                if ($log['calle_email'] == 'shubhangi.rajora@patterns247.net') {
                                    $usersids = 107;
                                } else if ($log['calle_email'] == 'sanket.chauhan@patterns247.net') {
                                    $usersids = 102;
                                } else if ($log['calle_email'] == 'biraj.patel@patterns247.net') {
                                    $usersids = 103;
                                } else {
                                    $userlists = User::where('email', $log['calle_email'])->get();
                                    if (count($userlists) > 0) {
                                        $usersids = $userlists[0]->id;
                                    } else {
                                        $usersids = 6;
                                    }
                                }
                            } else {
                                $usersids = 6;
                            }

                        } else {
                            if (isset($log['caller_email']) && isset($log['calle_email'])) {
                                if (($log['caller_email'] == '') && ($log['calle_email'] != '')) {
                                    if ($log['calle_email'] == 'shubhangi.rajora@patterns247.net') {
                                        $usersids = 107;
                                    } else if ($log['calle_email'] == 'sanket.chauhan@patterns247.net') {
                                        $usersids = 103;
                                    } else if ($log['calle_email'] == 'biraj.patel@patterns247.net') {
                                        $usersids = 102;
                                    } else {
                                        $userlists = User::where('email', $log['calle_email'])->get();
                                        if (count($userlists) > 0) {
                                            $usersids = $userlists[0]->id;
                                        } else {
                                            $usersids = 6;
                                        }
                                    }
                                }
                            } else {
                                $usersids = 6;
                            }
                        }

                        CallLog::create([
                            'call_id' => $log['call_id'],
                            'general_id' => $log['id'],
                            'caller_number' => isset($log['caller_did_number']) ? $log['caller_did_number'] : '',
                            'callee_number' => isset($log['callee_did_number']) ? $log['callee_did_number'] : '',
                            'start_time' => isset($log['start_time']) ? Carbon::parse($log['start_time'])->toDateTimeString() : NULL,
                            'answer_time' => isset($log['answer_time']) ? Carbon::parse($log['answer_time'])->toDateTimeString() : NULL,
                            'end_time' => isset($log['end_time']) ? Carbon::parse($log['end_time'])->toDateTimeString() : NULL,
                            'direction' => isset($log['direction']) ? $log['direction'] : '',
                            'department' => isset($log['department']) ? $log['department'] : '',
                            'caller_name' => isset($caller_name) ? $caller_name : '',
                            'caller_email' => isset($caller_email) ? $caller_email : '',
                            'result' => isset($log['call_result']) ? $log['call_result'] : '',
                            'international' => isset($log['international']) ? $log['international'] : '',
                            'event' => isset($log['event']) ? $log['event'] : '',
                            'caller_ext_number' => isset($log['caller_ext_number']) ? $log['caller_ext_number'] : '',
                            'caller_ext_type' => isset($log['caller_ext_type']) ? $log['caller_ext_type'] : '',
                            'caller_number_type' => isset($log['caller_number_type']) ? $log['caller_number_type'] : '',
                            'caller_device_type' => isset($log['caller_device_type']) ? $log['caller_device_type'] : '',
                            'group_id' => isset($log['group_id']) ? $log['group_id'] : '',
                            'recording_id' => isset($log['recording_id']) ? $log['recording_id'] : '',
                            'recording_type' => isset($log['recording_status']) ? $log['recording_status'] : '',
                            'talk_time' => $talk_time,
                            'hold_time' => $hold_time,
                            'wait_time' => $wait_time,
                            'calle_name' => isset($callee_name) ? $callee_name : '',
                            'calle_email' => isset($callee_email) ? $callee_email : '',
                            'ai_call_summary_id' => isset($log['ai_call_summary_id']) ? $log['ai_call_summary_id'] : '',
                            'operator_name' => isset($log['operator_name']) ? $log['operator_name'] : '',
                            'operator_ext_number' => isset($log['operator_ext_number']) ? $log['operator_ext_number'] : '',
                            'operator_ext_Type' => isset($log['operator_ext_Type']) ? $log['operator_ext_Type'] : '',
                            'operator_ext_id' => isset($log['operator_ext_id']) ? $log['operator_ext_id'] : '',
                            //'user_id' => $userlist->id,
                            //  'call_result' => isset($log['call_result']) ? $log['call_result'] : '',
                            'user_id' => $usersids,
                            'file_url' => $furl->original['file_url'],
                            'download_url' => $furl->original['download_url'],
                        ]);
                    }
                }

                $nextPageToken = $data['next_page_token'] ?? '';
            } else {
                return response()->json(['error' => 'Failed to fetch call logs'], 500);
            }
        } while (!empty($nextPageToken));
    }

    /**
     * Helper to break a date range into maximum 30-day chunks for Zoom API limits.
     */
    private function getDateChunks($startDate, $endDate)
    {
        $currentDate = new \DateTime($startDate);
        $endDateObj = new \DateTime($endDate);
        $chunks = [];
        
        while ($currentDate <= $endDateObj) {
            $chunkStart = $currentDate->format("Y-m-d");
            $chunkEndObj = clone $currentDate;
            $chunkEndObj->modify("+30 days"); 
            
            if ($chunkEndObj > $endDateObj) {
                $chunkEndObj = clone $endDateObj;
            }
            
            $chunkEnd = $chunkEndObj->format("Y-m-d");
            $chunks[] = ["from" => $chunkStart, "to" => $chunkEnd];
            
            $currentDate = clone $chunkEndObj;
            $currentDate->modify("+1 day");
        }
        
        return $chunks;
    }
}
