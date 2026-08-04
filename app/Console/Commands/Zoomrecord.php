<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\ZoomController;

class Zoomrecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:zoomrecord';

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
        //    $controller->getDevCallList();
        $controller->getRecId();
        $this->info('call transcript logs fetched and saved successfully!');
    }
}
