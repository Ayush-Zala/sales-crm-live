<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ZoomSyncController extends Controller
{
    public function trigger(Request $request)
    {
        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $log = \App\Models\ZoomSyncLog::create([
            'type' => $type,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        if ($type === 'call_logs') {
            \App\Jobs\SyncZoomCallLogsJob::dispatch($log->id, $startDate, $endDate);
        } elseif ($type === 'meetings') {
            \App\Jobs\SyncZoomMeetingsJob::dispatch($log->id, $startDate, $endDate);
        } elseif ($type === 'recordings') {
            \App\Jobs\SyncZoomRecordingsJob::dispatch($log->id, $startDate, $endDate);
        } else {
            $log->update(['status' => 'failed', 'error_message' => 'Invalid sync type', 'completed_at' => now()]);
            return response()->json(['error' => 'Invalid sync type'], 400);
        }

        return response()->json(['log_id' => $log->id, 'status' => 'pending']);
    }

    public function status($id)
    {
        $log = \App\Models\ZoomSyncLog::findOrFail($id);
        return response()->json($log);
    }
}
