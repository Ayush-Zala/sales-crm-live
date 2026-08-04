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
use App\Models\Client;
use App\Models\ClientsEmail;
use App\Models\ClientsPhone;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccountMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:account-migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate account data from old crm to new crm';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = DB::connection('second_db')->table('company_masters')
        ->where('acc_status', '!=', '')
            // ->where('id', '=', 176323)
            ->get();

        foreach ($data as $key => $val) {
            //   if ($data[$key]->id == 51954) {
            // echo count($data);
            $companyPhone = DB::connection('second_db')->table('company_phone_numbers')->where('company_id', '=', $data[$key]->id)->get();
            $companyemail = DB::connection('second_db')->table('company_email_addresses')->where('company_id', '=', $data[$key]->id)->get();
            $companyaddress = DB::connection('second_db')->table('company_addresses')->where('company_id', '=', $data[$key]->id)->get();
            $companydisposition = DB::connection('second_db')->table('company_dispositions')->where('company_id', '=', $data[$key]->id)->get();
            $data1 = DB::connection('second_db')->table('client_masters')->select(
                'client_masters.id',
                'client_masters.client_name',
                'client_masters.salutation_name',
                'client_masters.client_first_name',
                'client_masters.client_last_name',
                'client_masters.linkedin_url',
                'client_masters.clientfaxno',
                'client_masters.client_designation',
                'client_masters.created_at as clientcreated_at',
                'client_masters.updated_at as clientupdated_at',
                'client_masters.lead_description',
                'client_masters.clientlastdisposition',
                'client_masters.client_last_disposition_id',
                'client_masters.company_id as clcoid',
                'client_masters.lead_id',
                'client_masters.user_assign_id',
                'client_masters.created_user_id',
                'client_masters.clientconverted',
                'client_masters.deleted',
                'client_phone_numbers.id as cpid',
                'client_phone_numbers.client_id as cpcid',
                'client_phone_numbers.client_phone',
                'client_phone_numbers.phone_type',
                'client_phone_numbers.deleted',
                'client_phone_numbers.dammy',
                'client_phone_numbers.created_at  as cpcreated_at',
                'client_phone_numbers.updated_at as cpupdated_at',
                'client_email_addresses.id as ceid',
                'client_email_addresses.client_id as cecid',
                'client_email_addresses.client_email',
                'client_email_addresses.email_type',
                'client_email_addresses.deleted',
                'client_email_addresses.dammy',
                'client_email_addresses.created_at as clcreated_at',
                'client_email_addresses.updated_at as clupdated_at',
                'client_dispositions.id as cdid',
                'client_dispositions.client_id as cdclid',
                'client_dispositions.company_id as cdcoid',
                'client_dispositions.user_id',
                'client_dispositions.phonenumber',
                'client_dispositions.phonenumbertype',
                'client_dispositions.status',
                'client_dispositions.follow_up_date',
                'client_dispositions.description',
                'client_dispositions.created_at as cdcreated_at',
                'client_dispositions.updated_at as cdupdated_at',
                'client_dispositions.created_atus'
            )
                ->leftjoin('client_phone_numbers', 'client_phone_numbers.client_id', '=', 'client_masters.id')
                ->leftjoin('client_email_addresses', 'client_email_addresses.client_id', '=', 'client_masters.id')
                ->leftjoin('client_dispositions', 'client_dispositions.client_id', '=', 'client_masters.id')

                ->where('client_masters.company_id', '=', $data[$key]->id)->get();

            $company = [
                "id" => $data[$key]->id,
                "company_name" => $data[$key]->company_name,
                "website_address" => $data[$key]->website_address,
                "created_At" => $data[$key]->created_at,
                "updated_At" => $data[$key]->updated_at,
                "vendor_type" => $data[$key]->vendor_type,
                "industry" => $data[$key]->industry,
                "type_business" => $data[$key]->type_business,
                "lead_description" => $data[$key]->lead_description,
                "last_disposition" => $data[$key]->last_disposition,
                "last_disposition_id" => $data[$key]->last_disposition_id,
                "source" => $data[$key]->source,
                "user_assign_id" => $data[$key]->user_assign_id,
                "assign_by" => $data[$key]->assign_by,
                "create_user_id" => $data[$key]->created_user_id,
                "lead_id" => $data[$key]->lead_id,
                "converted" => $data[$key]->converted,
                "assignstatus" => $data[$key]->assignstatus,
                "seat_id" => $data[$key]->seat_id,
                "create_atus" => $data[$key]->created_atus,
                "phone_no" => $data[$key]->phone_no,
                "company_email" => $data[$key]->company_email,
                "user_assign_name" => $data[$key]->user_assign_name,
                "acc_status" => $data[$key]->acc_status,
                "companyphone" => $companyPhone,
                "companyemail" => $companyemail,
                "companyaddress" => $companyaddress,
                "companydisposition" => $companydisposition,
                "clientdetail" => $data1
            ];

            // echo json_encode($company);

            if (
                ($data[$key]->assign_by == "No user") || ($data[$key]->assign_by == "")
            ) {
                $assign_nys = "";
                $usernamess = "6";


                if ($data[$key]->user_assign_id != "") {
                    $userassids = $data[$key]->user_assign_id;
                } else {
                    $userassids = "";
                }
                if ($data[$key]->created_user_id != "") {
                    $createusernamesidss = $data[$key]->created_user_id;
                } else {
                    $createusernamesidss = "6";
                }




            } else {
                $usernames = User::select("id")->where('name', 'like', $data[$key]->assign_by)->get();
                if (count($usernames) > 0) {
                    $usernamess = $usernames[0]->id;
                } else {
                    $usernamess = "";
                }

                $oldusers = DB::connection('second_db')->table('users')->select("name")
                    ->where('id', '=', $data[$key]->created_user_id)->get();
                if (count($oldusers) > 0) {

                    $createusernames = User::select("id")->where('name', 'like', $oldusers[0]->name)->get();
                    $oldusers1 = DB::connection('second_db')->table('users')->select("name")
                        ->where('id', '=', $data[$key]->user_assign_id)->get();

                    $user_assign_id = User::select("id")->where('name', 'like', $oldusers1[0]->name)->get();
                    if (count($user_assign_id) > 0) {
                        $userassids = $user_assign_id[0]->id;
                    } else {
                        $userassids = "";
                    }


                    if (
                        count($createusernames) > 0
                    ) {
                        $createusernamesidss = $createusernames[0]->id;
                    } else {
                        $createusernamesidss = "";
                    }
                } else {
                    $createusernamesidss = "";
                    //   $usernamess = "";
                    //  $userassids = "";


                    $oldusers1 = DB::connection('second_db')->table('users')->select("name")
                    ->where('id', '=', $data[$key]->user_assign_id)->get();

                    $user_assign_id = User::select("id")->where('name', 'like', $oldusers1[0]->name)->get();
                    if (count($user_assign_id) > 0) {
                        $userassids = $user_assign_id[0]->id;
                    } else {
                    $userassids = "";
                }
                }
            }

            if (
                $data[$key]->assignstatus == "Assign"
            ) {
                $assignstatus = 1;
            } else {
                $assignstatus = 0;
            }
            if (($userassids != "") && ($createusernamesidss != "")) {
                $inputs = [
                    'name' => $data[$key]->company_name,
                    'website' => $data[$key]->website_address,
                    'fax' => '',
                    'converted' => $data[$key]->converted,
                    'assign_by' => $usernamess,
                    'create_user_id' => $createusernamesidss,
                    'assign_to' => $userassids,
                    // 'assign' => $assignstatus,
                    'created_at' => $data[$key]->created_at,
                    'updated_at' => $data[$key]->updated_at
                ];
            }
            if (($userassids == "") && ($createusernamesidss != "")) {
                $inputs = [
                    'name' => $data[$key]->company_name,
                    'website' => $data[$key]->website_address,
                    'fax' => '',
                    'converted' => $data[$key]->converted,
                    'assign_by' => $usernamess,
                    'create_user_id' => $createusernamesidss,
                    // 'assign_to' => $userassids,
                    // 'assign' => $assignstatus,
                    'created_at' => $data[$key]->created_at,
                    'updated_at' => $data[$key]->updated_at
                ];
            }

            if (($userassids != "") && ($createusernamesidss == "")) {
                $inputs = [
                    'name' => $data[$key]->company_name,
                    'website' => $data[$key]->website_address,
                    'fax' => '',
                    'converted' => $data[$key]->converted,
                    'assign_by' => $usernamess,
                    // 'create_user_id' => $createusernamesidss,
                    'assign_to' => $userassids,
                    // 'assign' => $assignstatus,
                    'created_at' => $data[$key]->created_at,
                    'updated_at' => $data[$key]->updated_at
                ];
            }
            if (($userassids == "") && ($createusernamesidss == "")) {
                $inputs = [
                    'name' => $data[$key]->company_name,
                    'website' => $data[$key]->website_address,
                    'fax' => '',
                    'converted' => $data[$key]->converted,
                    'assign_by' => $usernamess,
                    // 'create_user_id' => $createusernamesidss,
                    // 'assign_to' => $userassids,
                    // 'assign' => $assignstatus,
                    'created_at' => $data[$key]->created_at,
                    'updated_at' => $data[$key]->updated_at
                ];
            }
            $coins = Company::create($inputs);
            if ($coins) {
                if ($userassids != "") {
                    $assign_co = AssignCompanies::create([
                        'company_id' => $coins->id,
                        'user_id' => $userassids,
                        'assign_by' => $usernamess
                    ]);
                    echo "company id " . $coins->id . " migrated successfully \n";
                }


                if (count($companyPhone) > 0) {
                    foreach ($companyPhone as $key => $value) {
                        if ($companyPhone[$key]->phone_type == "") {
                            $cphonetype = "primary";
                        } else {
                            $cphonetype = $companyPhone[$key]->phone_type;
                        }
                        $cphoneinputs = [
                            'company_id' => $coins->id,
                            'phone' => $companyPhone[$key]->company_phone,
                            'type' => $cphonetype,
                            'created_at' => $companyPhone[$key]->created_at,
                            'updated_at' => $companyPhone[$key]->updated_at
                        ];

                        $cophoness = CompanyPhone::create($cphoneinputs);
                        echo "company id  " . $coins->id . " phone has been migrated successfully \n";
                    }
                }


                if (count($companyemail) > 0) {
                    foreach ($companyemail as $key => $value) {
                        if ($companyemail[$key]->email_type == "") {
                            $cmailtype = "primary";
                        } else {
                            $cmailtype = $companyemail[$key]->email_type;
                        }
                        $cmailinputs = [
                            'company_id' => $coins->id,
                            'email' => $companyemail[$key]->company_email,
                            'type' => $cmailtype,
                            'created_at' => $companyemail[$key]->created_at,
                            'updated_at' => $companyemail[$key]->updated_at
                        ];

                        CompanyEmail::create($cmailinputs);
                        echo "company id  " . $coins->id . " email has been migrated successfully \n";
                    }
                }


                /////   disposition ///////
                if (
                    count($companydisposition) > 0
                ) {

                    foreach ($companydisposition as $key => $value) {
                        $disuser = DB::connection('second_db')->table('users')->select("name")
                            ->where('id', '=', $companydisposition[$key]->user_id)->get();
                        $disuser1 = User::select("id")->where('name', 'like', $disuser[0]->name)->get();
                        $companydispositioninputs = [
                            'company_id' => $coins->id,
                            'phone' => $companydisposition[$key]->phonenumber,
                            'status' => $companydisposition[$key]->status,
                            'follow_up_date' => $companydisposition[$key]->follow_up_date,
                            'description' => $companydisposition[$key]->description,
                            'user_id' => $disuser1[0]->id,
                            'created_at' => $companydisposition[$key]->created_at,
                            'updated_at' => $companydisposition[$key]->updated_at
                        ];

                        Disposition::create($companydispositioninputs);
                    }

                    echo "company id  " . $coins->id . " disposition has been migrated successfully \n";
                }




                //////  disposition   /////


                /////      address  /////


                if (
                    count($companyaddress) > 0
                ) {

                    foreach ($companyaddress as $key => $value) {
                        $coaddress = DB::connection('second_db')->table('company_addresses')->select("*")
                            ->where(
                                'company_id',
                                '=',
                                $data[$key]->id
                            )->get();


                        if (count($coaddress) > 0) {

                            $cocountry = Country::select("id")->where(
                                'iso2',
                                'like',
                                $coaddress[0]->Country
                            )->get();

                            $costate = State::select("id")->where(
                                'name',
                                'like',
                                $coaddress[0]->state
                            )->get();

                            $cocity = City::select("id")->where('name', 'like', $coaddress[0]->County)->get();


                            if (count($cocountry) > 0) {
                                $cocountryname = $cocountry[0]->id;
                            } else {
                                $cocountryname = 253;
                            }

                            if (
                                count($costate) > 0
                            ) {
                                $costatename = $costate[0]->id;
                            } else {
                                $costatename = 5235;
                            }

                            if (
                                count($cocity) > 0
                            ) {
                                $cocityname = $cocity[0]->id;
                            } else {
                                $cocityname = 153775;
                            }

                            $companyaddressess1 = [
                                'company_id' => $coins->id,
                                'country_id' => $cocountryname,
                                'state_id' => $costatename,
                                'city_id' => $cocityname,
                                'zip' => $coaddress[0]->zip_code,
                                'block' => $coaddress[0]->house_no,
                                'timezone' => $coaddress[0]->time_zone,
                                'street' => $coaddress[0]->street_name,
                                'address' => $coaddress[0]->address_line_2,
                                'created_at' => $coaddress[0]->created_at,
                                'updated_at' => $coaddress[0]->updated_at
                            ];

                            CompanyAddress::create($companyaddressess1);
                        }

                        echo "company id  " . $coins->id . " address has been migrated successfully \n";
                    }
                }


                ////  address  /////

                ///   client detail  ////


                if (
                    count($data1) > 0
                ) {

                    foreach ($data1 as $key => $value) {


                        $clientdatadetail = [
                            'companyId' => $coins->id,
                            'linkdinurl' => $data1[$key]->linkedin_url,
                            'designation' => $data1[$key]->client_designation,
                            'lname' => $data1[$key]->client_last_name,
                            'fname' => $data1[$key]->client_first_name,
                            'created_at' => $data1[$key]->clientcreated_at,
                            'updated_at' => $data1[$key]->clientupdated_at
                        ];

                        $clins = Client::create($clientdatadetail);

                        if ((!is_null($data1[$key]->client_phone)) && ($data1[$key]->client_phone != "")) {
                            if ($data1[$key]->phone_type == "") {
                                $datcphonetype = "primary";
                            } else {
                                $datcphonetype = $data1[$key]->phone_type;
                            }
                            $cphdata = [
                                'clients_id' => $clins->id,
                                'phone' => $data1[$key]->client_phone,
                                'type' => $datcphonetype,
                                'created_at' => $data1[$key]->cpcreated_at,
                                'updated_at' => $data1[$key]->cpupdated_at
                            ];

                            $cpdata = ClientsPhone::create($cphdata);
                            echo "client  id  " . $clins->id . " phone has been created " . $data1[$key]->client_phone . " has been migrated successfully \n";
                        }


                        if ((!is_null($data1[$key]->client_email)) && ($data1[$key]->client_email != "")) {
                            if ($data1[$key]->email_type == "") {
                                $datecphonetype = "primary";
                            } else {
                                $datecphonetype = $data1[$key]->email_type;
                            }
                            $cehdata = [
                                'clients_id' => $clins->id,
                                'mail' => $data1[$key]->client_email,
                                'type' => $datecphonetype,
                                'created_at' => $data1[$key]->clcreated_at,
                                'updated_at' => $data1[$key]->clupdated_at
                            ];

                            $cedata = ClientsEmail::create($cehdata);
                            echo "client  id  " . $clins->id . " email has been created " . $data1[$key]->client_email . " has been migrated successfully \n";
                        }


                        echo "company id  " . $coins->id . " client with id " . $clins->id . " has been migrated successfully \n";

                        if ((!is_null($data1[$key]->clientlastdisposition)) && ($data1[$key]->clientlastdisposition != "")) {
                            $olduserssecdata = DB::connection('second_db')->table('users')->select("email", "name")
                                ->where('id', '=', $data1[$key]->user_id)->get();

                            $usernamesdata11 = User::select("id")->where('email', 'like', $olduserssecdata[0]->email)->get();


                            $companydispositioninputs = [
                                'company_id' => $coins->id,
                                'client_id' => $clins->id,
                                'phone' => $data1[$key]->phonenumber,
                                'status' => $data1[$key]->status,
                                'follow_up_date' => $data1[$key]->follow_up_date,
                                'description' => $data1[$key]->description,
                                'user_id' => $usernamesdata11[0]->id,
                                'created_at' => $data1[$key]->cdcreated_at,
                                'updated_at' => $data1[$key]->cdupdated_at
                            ];

                            Disposition::create($companydispositioninputs);
                            echo "disposition migated for client id " . $clins->id . "";
                        }
                    }
                }
                /////    client detail /////




            }
            // }

            // print_R($data);
            echo "company id  " . $coins->id . " migrated successfully \n";
            echo    $userassids . "-------------" . $usernamess . " \n";
       //     echo $usernames[0]->id;
        }
        //   echo json_encode($company);
    }
}
