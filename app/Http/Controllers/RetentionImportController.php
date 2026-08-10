<?php

namespace App\Http\Controllers;

use App\Jobs\RunRetentionImport;
use App\Models\RetentionImportLog;
use Illuminate\Http\Request;

class RetentionImportController extends Controller
{
    /**
     * Triggered by the React button.
     * Enqueues the import job and returns a log id the frontend can poll.
     */
    public function trigger(Request $request)
    {
        // Prevent overlapping runs (double-clicks / concurrent users).
        if (RetentionImportLog::whereIn('status', ['queued', 'running'])->exists()) {
            return response()->json(['message' => 'An import is already in progress.'], 409);
        }

        $log = RetentionImportLog::create([
            'status'       => 'queued',
            'months'       => 6,
            'triggered_by' => optional($request->user())->id,
        ]);

        RunRetentionImport::dispatch($log->id, 6);

        return response()->json(['id' => $log->id, 'status' => $log->status], 202);
    }

    /** Polled by the frontend to show progress / final summary. */
    public function status(int $id)
    {
        return response()->json(RetentionImportLog::findOrFail($id));
    }
}
