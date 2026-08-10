<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncZoomRecordingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $logId;
    protected $startDate;
    protected $endDate;

    public function __construct($logId, $startDate = null, $endDate = null)
    {
        $this->logId = $logId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1024M');
        
        $log = \App\Models\ZoomSyncLog::find($this->logId);
        if (!$log) return;
        
        $log->update(['status' => 'processing']);

        try {
            $controller = new \App\Http\Controllers\ZoomController();
            $controller->getRecId($this->startDate, $this->endDate);
            
            $log->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Zoom Recordings Sync Failed: ' . $e->getMessage());
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
