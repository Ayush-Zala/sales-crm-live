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
use App\Models\ClientPhone;
use Illuminate\Console\Command;

use Maatwebsite\Excel\Facades\Excel;

class DispositionTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:disposition-transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'disposition transfer description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = public_path('uploads/clientdispo.xlsx'); // Adjust the path as needed
        $string = "";
        $userid = 137;
        $data = Excel::toArray([], $filePath);

        $companyarr = [];
        $clientidarr = [];
        foreach ($data[0] as $key => $row) {
            if ($key != 0) {

                if ($row[3] != "") {
                    $phone = preg_replace('/\b(\d+)(st|nd|rd|th)\b/', '$1', $row[4]);

                    $clientPhoneExists = ClientsPhone::where('phone', $phone)->exists();

                    if ($clientPhoneExists) {
                        $sel = ClientsPhone::where('phone', $phone)->get();
                        if ($row[5] != "") {

                            $sel = ClientsPhone::where('phone', $phone)->get();

                            $disposition_id = DispositionStatus::where('name', $row[6])->get();
                            foreach ($sel as $value) {
                                $cli = Client::where('id', $sel[0]['clients_id'])->get();

                                $dispositions = new Disposition();
                                $dispositions->phone = $row[4];
                                $dispositions->status_id = $disposition_id[0]['id'];
                                $dispositions->status = $row[6];
                                $dispositions->user_id = $userid;
                                $dispositions->description = $row[8];
                                if ($row[7] != "") {
                                    $excelDate = $row[7];

                                    // Convert Excel date to a UNIX timestamp
                                    $timestamp = ($excelDate - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                    // Format the timestamp as a readable date
                                    $readableDate = date("Y-m-d H:i:s", $timestamp);
                                    $formattedDate = date("Y-m-d", $timestamp);
                                    $formattedDate1 = date("h:i", $timestamp);

                                    $dispositions->followup_date = $formattedDate;

                                    $dispositions->followup_time = $formattedDate1;
                                }
                                $excelDate111 = $row[9];
                                $timestamp111 = ($excelDate111 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01
                                $excelDate222 = $row[10];
                                $timestamp222 = ($excelDate222 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                // Format the timestamp as a readable date
                                $readableDate111 = date("Y-m-d H:i:s", $timestamp111);
                                $readableDate222 = date("Y-m-d H:i:s", $timestamp222);
                                $dispositions->timestamps = false;
                                $dispositions->created_at =  $readableDate111;
                                $dispositions->updated_at =  $readableDate222;
                                $dispositions->client_id = $cli[0]['id'];
                                $dispositions->company_id = $cli[0]['companyId'];
                                $dispositions->save();
                                echo $phone . "disposition entered \n";
                            }
                        }
                    }

                    $companyPhoneExists = CompanyPhone::where('phone', $phone)->exists();

                    if ($companyPhoneExists) {

                        $sel = CompanyPhone::where('phone', $phone)->get();
                        if ($row[5] != "") {
                            $disposition_id = DispositionStatus::where('name', $row[6])->get();
                            foreach ($sel as $value) {
                                $dispositions = new Disposition();
                                $dispositions->phone = $row[4];
                                $dispositions->status_id = $disposition_id[0]['id'];
                                $dispositions->status = $row[6];
                                $dispositions->user_id = $userid;
                                $dispositions->description = $row[8];
                                if ($row[7] != "") {
                                    $excelDate = $row[7];

                                    // Convert Excel date to a UNIX timestamp
                                    $timestamp = ($excelDate - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                    // Format the timestamp as a readable date
                                    $readableDate = date("Y-m-d H:i:s", $timestamp);
                                    $formattedDate = date("Y-m-d", $timestamp);
                                    $formattedDate1 = date("h:i", $timestamp);

                                    $dispositions->followup_date = $formattedDate;

                                    $dispositions->followup_time = $formattedDate1;
                                }
                                $excelDate111 = $row[9];
                                $timestamp111 = ($excelDate111 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01
                                $excelDate222 = $row[10];
                                $timestamp222 = ($excelDate222 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                // Format the timestamp as a readable date
                                $readableDate111 = date("Y-m-d H:i:s", $timestamp111);
                                $readableDate222 = date("Y-m-d H:i:s", $timestamp222);
                                $dispositions->timestamps = false;
                                $dispositions->created_at =  $readableDate111;
                                $dispositions->updated_at =  $readableDate222;
                                $dispositions->company_id = $sel[0]['company_id'];
                                $dispositions->save();
                                echo $phone . "disposition entered \n";
                            }
                        }
                    }
                }
            }
        }
    }
}
