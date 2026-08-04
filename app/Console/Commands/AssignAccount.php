<?php

namespace App\Console\Commands;


use App\Models\AssignCompanies;
use App\Models\City;
use App\Models\State;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyEmail;
use App\Models\CompanyPhone;
use App\Models\Country;
use App\Models\Disposition;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\ClientsEmail;
use App\Models\DispositionStatus;
use App\Models\ClientsPhone;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Imports\PhoneImport;
use Illuminate\Console\Command;

use Maatwebsite\Excel\Facades\Excel;

class AssignAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-account';

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
        $filePath = public_path('uploads/assign.xlsx'); // Adjust the path as needed
        $string = "";
        $data = Excel::toArray([], $filePath);

        $companyarr = [];
        $userid = 137;
        $managerid = 88;
        $clientidarr = [];
        foreach ($data[0] as $key => $row) {
            if ($key != 0) {
                if ($row[0] != "") {
                    $phone = preg_replace('/\b(\d+)(st|nd|rd|th)\b/', '$1', $row[0]);
                    $clientPhoneExists = ClientsPhone::where('phone', $phone)->exists();

                    if ($clientPhoneExists) {
                        $sel = ClientsPhone::where('phone', $phone)->get();
                        $cli = Client::where('id', $sel[0]['clients_id'])->get();
                        $assign_co = AssignCompanies::create([
                            'company_id' => $cli[0]['companyId'],
                            'user_id' => $userid,
                            'assign_by' => $managerid
                        ]);
                        echo "company id " . $cli[0]['companyId'] . " asssigned successfully \n";
                    } else {
                        $coPhoneExists = CompanyPhone::where('phone', $phone)->exists();

                        if ($coPhoneExists) {
                            $sel = CompanyPhone::where('phone', $phone)->get();
                            $assign_co = AssignCompanies::create([
                                'company_id' => $sel[0]['id'],
                                'user_id' => $userid,
                                'assign_by' => $managerid
                            ]);
                            echo "company id " . $sel[0]['id'] . " asssigned successfully \n";
                        }
                    }
                }
            }
        }
    }
}
