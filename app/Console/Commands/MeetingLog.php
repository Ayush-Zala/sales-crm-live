<?php

namespace App\Console\Commands;

use App\Http\Controllers\ZoomController;
use Illuminate\Console\Command;

class MeetingLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:meeting-log';

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
        $controller->getMeetingList();
        $this->info('Meeting logs fetched and saved successfully!');
    }
}
