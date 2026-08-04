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

class Clean2Account extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean2-account';

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
        // $dateString =  "Jan 10th" . " 2024";


        // // Remove any ordinal suffixes ("st", "nd", "rd", "th") from the day part
        // $dateString = preg_replace('/\b(\d+)(st|nd|rd|th)\b/', '$1', $dateString);


        // $datess = Carbon::parse($dateString)->format('Y-m-d');
        // echo $datess;

        // die();
        $filePath = public_path('uploads/clean2.xlsx'); // Adjust the path as needed
        $string = "";
        $data = Excel::toArray([], $filePath);

        $companyarr = [];
        $clientidarr = [];
        foreach ($data[0] as $key => $row) {
            if ($key != 0) {
                if ($row[1] != "") {

                    $companyListing = Company::where('name', $row[1])->where('industry', $row[25])->get();
                    if (count($companyListing) < 1) {
                        $company = new Company();
                        $company->name = $row[1];
                        $company->website = $row[2];
                        $company->industry = $row[25];
                        $company->source = $row[24];
                        $company->fax = $row[7];
                        $company->converted = 'converted';
                        $company->created_at = carbon::now();
                        $company->updated_at = carbon::now();
                        $company->save();
                        $companyid = $company->id;
                        $companyarr[] = $company->id;
                    } else {
                        $company = Company::where('name', $row[1])->first();
                        $companyid = $company->id;
                        $companyarr[] = $company->id;
                    }
                } else {
                    $companyid = end($companyarr);
                }
                if ($row[3] != "") {
                    if (strpos($row[3], ';') !== false) {
                        $compharr = explode(";", $row[3]);
                        foreach ($compharr as $key => $value) {
                            $companyphonecount = CompanyPhone::where('phone', $value)->get();
                            if (count($companyphonecount) < 1) {
                                $companyphone = new CompanyPhone();
                                $companyphone->company_id = $companyid;
                                $companyphone->phone = $value;
                                $companyphone->type = 'Personal';
                                $companyphone->created_at = carbon::now();
                                $companyphone->updated_at = carbon::now();
                                $companyphone->save();
                            }
                        }
                    } else {
                        $companyphonecount = CompanyPhone::where('phone', $row[3])->get();
                        if (count($companyphonecount) < 1) {
                            $companyphone = new CompanyPhone();
                            $companyphone->company_id = $companyid;
                            $companyphone->phone = $row[3];
                            $companyphone->type = 'Personal';
                            $companyphone->created_at = carbon::now();
                            $companyphone->updated_at = carbon::now();
                            $companyphone->save();
                        }
                    }
                }


                if ($row[5] != "") {
                    if (strpos($row[5], ';') !== false) {
                        $comphsecarr = explode(";", $row[5]);
                        foreach ($comphsecarr as $key => $value) {
                            $companyemailcount = CompanyEmail::where('email', $value)->get();
                            if (count($companyemailcount) < 1) {
                                $companyemail = new CompanyEmail();
                                $companyemail->company_id = $companyid;
                                $companyemail->email = $value;
                                $companyemail->type = 'Work';
                                $companyemail->created_at = carbon::now();
                                $companyemail->updated_at = carbon::now();
                                $companyemail->save();
                            }
                        }
                    } else {
                        $companyemailcount = CompanyEmail::where('email', $row[5])->get();
                        if (count($companyemailcount) < 1) {
                            $companyemail = new CompanyEmail();
                            $companyemail->company_id = $companyid;
                            $companyemail->email = $row[5];
                            $companyemail->type = 'Work';
                            $companyemail->created_at = carbon::now();
                            $companyemail->updated_at = carbon::now();
                            $companyemail->save();
                        }
                    }
                }

                if ($row[9] != "") {
                    // Ensure client record exists before creating phone
                    $clientr = Client::where('fname', $row[9])
                        ->where('lname', $row[10])
                        ->where('companyId', $companyid)
                        ->where('designation', $row[12])
                        ->get();

                    if (count($clientr) < 1) {
                        // Create a new client record
                        $client1 = new Client();
                        $client1->companyId = $companyid;
                        $client1->linkdinurl = $row[11];
                        $client1->designation = $row[12];
                        $client1->lname = $row[10];
                        $client1->fname = $row[9];
                        $client1->created_at = Carbon::now();
                        $client1->updated_at = Carbon::now();
                        $client1->save();
                        $clientid = $client1->id;
                        echo $clientid;
                    } else {
                        $clientid = $clientr[0]['id'];
                        echo $clientid;
                    }
                }

                if (($row[13] != "") && ($row[9] != "")) {

                    // Explode phone numbers if there are multiple
                    $clientPhoneNumbers = strpos($row[13], ';') !== false ? explode(";", $row[13]) : [$row[13]];

                    foreach ($clientPhoneNumbers as $key => $phoneNumber) {
                        // Check if the phone already exists
                        $clientPhoneExists = ClientsPhone::where('phone', $phoneNumber)->exists();

                        if (!$clientPhoneExists) {

                            $clientr12 = Client::where('fname', $row[9])
                                ->where('lname', $row[10])
                                ->where('companyId', $companyid)
                                ->where('designation', $row[12])
                                ->get();
                            echo ",,,,,," . $clientr12[0]['id'];
                            $clientPhoneNumberstype = strpos($row[14], ';') !== false ? explode(";", $row[14]) : [$row[14]];


                            // Create the phone record
                            $clientPhone = new ClientsPhone();
                            $clientPhone->clients_id = $clientr12[0]['id'];
                            $clientPhone->phone = $phoneNumber;
                            if (array_key_exists($key, $clientPhoneNumberstype)) {
                                $clientPhone->type = $clientPhoneNumberstype[$key];
                            } else {
                                $clientPhone->type = 'work';
                            }
                            $clientPhone->created_at = Carbon::now();
                            $clientPhone->updated_at = Carbon::now();
                            $clientPhone->save();
                        }
                    }
                }


                if (($row[13] != "") && ($row[9] == "")) {
                    if (strpos($row[13], ';') !== false) {
                        $compharrr = explode(";", $row[13]);
                        foreach ($compharrr as $key => $value) {
                            $companyphonecountrr = CompanyPhone::where('phone', $value)->get();
                            if (count($companyphonecountrr) < 1) {
                                $companyphonerr = new CompanyPhone();
                                $companyphonerr->company_id = $companyid;
                                $companyphonerr->phone = $value;
                                $companyphonerr->type = 'Personal';
                                $companyphonerr->created_at = carbon::now();
                                $companyphonerr->updated_at = carbon::now();
                                $companyphonerr->save();
                            }
                        }
                    } else {
                        $companyphonecountrrr = CompanyPhone::where('phone', $row[13])->get();
                        if (count($companyphonecountrrr) < 1) {
                            $companyphonerrr = new CompanyPhone();
                            $companyphonerrr->company_id = $companyid;
                            $companyphonerrr->phone = $row[13];
                            $companyphonerrr->type = 'personal';
                            $companyphonerrr->created_at = carbon::now();
                            $companyphonerrr->updated_at = carbon::now();
                            $companyphonerrr->save();
                        }
                    }
                }

                if ($row[15] != "") {

                    // Explode phone numbers if there are multiple
                    $clientEmailNumbers = strpos($row[15], ';') !== false ? explode(";", $row[15]) : [$row[15]];

                    foreach ($clientEmailNumbers as $key => $emailNumber) {
                        // Check if the phone already exists
                        $clientEmailExists = ClientsEmail::where('mail', $emailNumber)->exists();

                        if (!$clientEmailExists) {

                            $clientr122 = Client::where('fname', $row[9])
                                ->where('lname', $row[10])
                                ->where('companyId', $companyid)
                                ->where('designation', $row[12])
                                ->get();
                            echo ",,,,,," . $clientr122[0]['id'];
                            // Create the phone record
                            $clientEmailNumberstype = strpos($row[16], ';') !== false ? explode(";", $row[16]) : [$row[16]];

                            $clientEmail = new ClientsEmail();
                            $clientEmail->clients_id = $clientr122[0]['id'];
                            $clientEmail->mail = $emailNumber;
                            if (array_key_exists($key, $clientEmailNumberstype)) {
                                $clientEmail->type = $clientEmailNumberstype[$key];
                            } else {
                                $clientEmail->type = 'work';
                            }

                            $clientEmail->created_at = Carbon::now();
                            $clientEmail->updated_at = Carbon::now();
                            $clientEmail->save();
                        }
                    }
                }


                if ($row[1] != "") {
                    if (count($companyListing) < 1) {
                        if ($row[20] != "") {
                            $city = City::where('name', $row[20])->first();
                            if ($city) {
                                $cityid = $city->id;
                            } else {
                                $cityid = 153775;
                            }
                        } else {
                            $cityid = 153775;
                        }


                        if ($row[21] != "") {
                            $state = State::where('iso2', $row[21])->first();
                            if ($state) {
                                $stateid = $state->id;
                                $countryid = $state->country_id;
                            } else {
                                $stateid = 5235;
                                $countryid = 253;
                            }
                        } else {
                            $stateid = 5235;
                            $countryid = 253;
                        }

                        $companyaddressins = CompanyAddress::create([
                            'company_id' => $companyid,
                            'block' => $row[17],
                            'street' => $row[18],
                            'address' => $row[19],
                            'zip' => $row[22],
                            'timezone' => $row[23],
                            'city_id' => $cityid,
                            'state_id' => $stateid,
                            'country_id' => $countryid,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }
                }
            }
        }
    }
}
