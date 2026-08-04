<?php

namespace App\Console\Commands;

use App\Http\Controllers\ZoomController;
use Illuminate\Console\Command;

class CallLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:call-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $controller = new ZoomController();
        $controller->getDevCallList();
        $this->info('call logs fetched and saved successfully!');
    }
}
