<?php

namespace App\Jobs;

use App\Models\RetentionImportLog;
use App\Services\RetentionImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunRetentionImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Max run time in seconds. */
    public int $timeout = 1800; // 30 min
    public int $tries   = 1;

    public function __construct(public int $logId, public int $months = 6)
    {
    }

    public function handle(RetentionImportService $service): void
    {
        $log = RetentionImportLog::findOrFail($this->logId);
        $log->update(['status' => 'running', 'started_at' => now()]);

        try {
            $summary = $service->run($this->months);
            $log->update([
                'status'      => 'completed',
                'summary'     => $summary,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status'      => 'failed',
                'error'       => $e->getMessage(),
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }
}
