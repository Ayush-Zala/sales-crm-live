<?php

namespace App\Console\Commands;

use App\Services\RetentionImportService;
use Illuminate\Console\Command;

class RetentionImportCommand extends Command
{
    protected $signature   = 'retention:import {--months=6}';
    protected $description  = 'Import clients with no order in the last N months into the retention tables';

    public function handle(RetentionImportService $service): int
    {
        $this->info('Running retention import (months='.$this->option('months').') ...');

        $summary = $service->run((int) $this->option('months'));

        $this->table(array_keys($summary), [array_values($summary)]);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
