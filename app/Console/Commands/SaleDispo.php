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
use App\Models\Calendar;
use Illuminate\Console\Command;

use Maatwebsite\Excel\Facades\Excel;

class SaleDispo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sale-dispo';

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
        $filePath = public_path('uploads/jack-dispo.xlsx'); // Adjust the path as needed
        $string = "";
        $data = Excel::toArray([], $filePath);
        $userid = 137;
        $companyarr = [];
        $clientidarr = [];
        foreach ($data[0] as $key => $row) {
            if ($key != 0) {


                $date = $row[2]; // Original date
                //    $date = Carbon::createFromFormat('jS F Y', $date);

                //  Add the current year if needed
                //  $date->year = now()->year;

                // Format the date as yyyy-mm-dd
                //   $formattedDate = $date->format('Y-m-d');


                $formattedDate = Carbon::create(1900, 1, 1)->addDays($date - 2)->format('Y-m-d');


                echo $formattedDate . "----" . $row[3];

                //    $timestamp = ($date - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                //  Format the timestamp as a readable date
                //    $formattedDate = date("Y-m-d", $date);
                ///    $timestamp = ($date - 25569) * 86400;

                // Format the date
                //$formattedDate = date('Y-m-d', $timestamp);
                //   $formattedDate = Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');

                if ($row[3] != "") {
                    $phone = preg_replace('/\b(\d+)(st|nd|rd|th)\b/', '$1', $row[0]);

                    $clientPhoneExists = ClientsPhone::where('phone', $phone)->exists();

                    if ($clientPhoneExists) {
                        $sel = ClientsPhone::where('phone', $phone)->get();
                        if ($row[3] != "") {
                            echo $row[2];
                            $sel = ClientsPhone::where('phone', $phone)->get();

                            $disposition_id = DispositionStatus::where('name', $row[3])->get();
                            foreach ($sel as $value) {
                                $cli = Client::where('id', $sel[0]['clients_id'])->get();

                                $dispositions = new Disposition();
                                $dispositions->phone = $row[0];
                                $dispositions->status_id = $disposition_id[0]['id'];
                                $dispositions->status = $row[3];
                                $dispositions->followup_description = $row[2];
                                $dispositions->user_id = $userid;
                                $dispositions->description = $row[5];

                                //                 $excelDate = $row[7];

                                // // Convert Excel date to a UNIX timestamp
                                //              $timestamp = ($excelDate - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                // // Format the timestamp as a readable date
                                //             $readableDate = date("Y-m-d H:i:s", $timestamp);
                                //             $formattedDate = date("Y-m-d", $timestamp);
                                //             $formattedDate1 =date("h:i", $timestamp);

                                //             $dispositions->followup_date = $formattedDate;

                                //             $dispositions->followup_time =$formattedDate1;

                                // $excelDate111 = $row[9];
                                // $timestamp111 = ($excelDate111 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01
                                // $excelDate222 = $row[10];
                                // $timestamp222 = ($excelDate222 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                // // Format the timestamp as a readable date
                                //             $readableDate111 = date("Y-m-d H:i:s", $timestamp111);
                                //             $readableDate222 = date("Y-m-d H:i:s", $timestamp222);
                                if ($row[4] != "") {
                                    $followdate = $row[4]; // Original date
                                    //   $followdate = Carbon::createFromFormat('jS F Y', $followdate);
                                    //  $date->year = now()->year;

                                    // $formattedfollowDate = $followdate->format('Y-m-d');

                                    // $formattedfollowDate = Carbon::createFromFormat('j-M', $followdate)
                                    //     ->year(2024) // Set the year manually
                                    //     ->format('Y-m-d');
                                    $formattedfollowDate = Carbon::create(1900, 1, 1)->addDays($followdate - 2)->format('Y-m-d');


                                    $dispositions->followup_date = $formattedfollowDate;
                                }
                                $dispositions->timestamps = false;
                                $dispositions->created_at =  $formattedDate;
                                $dispositions->updated_at =  $formattedDate;
                                $dispositions->client_id = $cli[0]['id'];
                                $dispositions->timezone = $row[1];
                                $dispositions->company_id = $cli[0]['companyId'];
                                $dispositions->save();

                                if ($row[4] != "") {
                                    if (($row[3] == "Follow Up") || ($row[3] == "Call Back")) {

                                        $calendarsss = new Calendar();
                                        $coname = Company::where('id', $cli[0]['companyId'])->get();
                                        if ($row[3] == "Call Back") {
                                            $calendarsss->title = "Call Back : " . $coname[0]['name'];
                                        } else {
                                            $calendarsss->title = "follow up call: " . $coname[0]['name'];
                                        }
                                        $calendarsss->colors = "#8E33FF";
                                        $calendarsss->start_date = $formattedfollowDate;
                                        $calendarsss->end_date = $formattedfollowDate;
                                        $calendarsss->description = $row[2];
                                        $calendarsss->created_by = $userid;
                                        $calendarsss->updated_by = $userid;
                                        $calendarsss->all_day = 1;
                                        $calendarsss->save();
                                    }
                                }
                                echo $phone . "disposition entered \n";
                            }
                        }
                    }

                    $companyPhoneExists = CompanyPhone::where('phone', $phone)->exists();

                    if ($companyPhoneExists) {

                        $sel = CompanyPhone::where('phone', $phone)->get();
                        if ($row[3] != "") {
                            echo $row[2];
                            $disposition_id = DispositionStatus::where('name', $row[3])->get();
                            foreach ($sel as $value) {
                                $dispositions = new Disposition();
                                $dispositions->phone = $row[0];
                                $dispositions->status_id = $disposition_id[0]['id'];
                                $dispositions->status = $row[3];
                                $dispositions->user_id = $userid;
                                $dispositions->description = $row[5];
                                //             if($row[7]!="")
                                //             {
                                //                 $excelDate = $row[7];

                                // // Convert Excel date to a UNIX timestamp
                                //              $timestamp = ($excelDate - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                // // Format the timestamp as a readable date
                                //             $readableDate = date("Y-m-d H:i:s", $timestamp);
                                //             $formattedDate = date("Y-m-d", $timestamp);
                                //             $formattedDate1 =date("h:i", $timestamp);

                                //             $dispositions->followup_date = $formattedDate;

                                //             $dispositions->followup_time =$formattedDate1;

                                //             }
                                //             $excelDate111 = $row[9];
                                //             $timestamp111 = ($excelDate111 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01
                                //             $excelDate222 = $row[10];
                                //             $timestamp222 = ($excelDate222 - 25569) * 86400; // 25569 is the number of days between 1900-01-01 and 1970-01-01

                                // Format the timestamp as a readable date
                                // $readableDate111 = date("Y-m-d H:i:s", $timestamp111);
                                // $readableDate222 = date("Y-m-d H:i:s", $timestamp222);
                                if ($row[4] != "") {
                                    $followdate = $row[4]; // Original date
                                    //    $followdate = Carbon::createFromFormat('jS F Y', $followdate);
                                    // $date->year = now()->year;

                                    //   $formattedfollowDate = $followdate->format('Y-m-d');

                                    $formattedfollowDate = Carbon::create(1900, 1, 1)->addDays($followdate - 2)->format('Y-m-d');

                                    $dispositions->followup_date = $formattedfollowDate;
                                }
                                $dispositions->timestamps = false;
                                $dispositions->timezone = $row[1];
                                $dispositions->followup_description = $row[2];
                                $dispositions->created_at =  $formattedDate;
                                $dispositions->updated_at =  $formattedDate;
                                $dispositions->company_id = $sel[0]['company_id'];
                                $dispositions->save();

                                if ($row[4] != "") {
                                    if (($row[3] == "Follow Up") || ($row[3] == "Call Back")) {

                                        $calendarsss = new Calendar();
                                        $coname = Company::where('id', $sel[0]['company_id'])->get();
                                        if ($row[3] == "Call Back") {
                                            $calendarsss->title = "Call Back : " . $coname[0]['name'];
                                        } else {
                                            $calendarsss->title = "follow up call: " . $coname[0]['name'];
                                        }
                                        $calendarsss->colors = "#8E33FF";
                                        $calendarsss->start_date = $formattedfollowDate;
                                        $calendarsss->end_date = $formattedfollowDate;
                                        $calendarsss->description = $row[2];
                                        $calendarsss->created_by = $userid;
                                        $calendarsss->updated_by = $userid;
                                        $calendarsss->all_day = 1;
                                        $calendarsss->save();
                                    }
                                }
                                echo $phone . "disposition entered \n";
                            }
                        }
                    }
                }
            }
        }
    }
}
