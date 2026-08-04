<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Cache;
use App\Models\Client;
use App\Models\Target;
use App\Models\Company;
use App\Models\Country;
use App\Models\Calendar;
use App\Models\Industry;
use App\Models\CallRemark;
use App\Models\ActivityLog;
use App\Models\Disposition;
use App\Models\ClientsEmail;
use App\Models\ClientsPhone;
use App\Models\CompanyEmail;
use App\Models\CompanyPhone;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\AssignCompanies;
use App\Models\CompanyBusiness;
use App\Models\DispositionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\AccountResource;
use App\Http\Resources\CompanyResource;

//use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\CompanyEditResource;

class AccountController extends Controller
{
    public $loggedinUser;

    public function __construct()
    {
        $this->loggedinUser = Auth::check() ? Auth::user()->name : null;
    }

    private function hasRestrictedRole($roles)
    {
        $restrictedRoles = [
            'Sales Executives',
            'Business Development Manager',
            'Business Development Team Lead'
        ];

        return $roles->contains(function ($role) use ($restrictedRoles) {
            return in_array($role, $restrictedRoles);
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = $user->getRoleNames();

        $industries = Industry::Select('id', 'name')->get();
        $dispositionStatuses = DispositionStatus::select('id', 'name')->get();

        $usersList = User::with(['reportingAuthority'])->where('is_active', true);

        if ($roles->contains('Business Development Team Lead')) {
            // Specific condition for user with ID 95
            $reportingAuthorityId = $usersList->where('id', Auth::id())->value('reporting_authority_id');

            $userQuery = User::select('id', 'name', 'email')
                ->where('reporting_authority_id', $reportingAuthorityId)
                ->get();
        } else {
            $userQuery = $user->hasRole(['Admin', 'admin', 'Data Entry Manager', 'Data Entry'])
                ? $usersList->where("is_active", "=", true)
                    ->select('id', 'name', 'email', 'reporting_authority_id')
                    ->get()
                : $usersList->where('reporting_authority_id', Auth::id())
                    ->select('id', 'name', 'email')
                    ->get();

        }

        // add field isOnline for each user
        $usersQuery = $userQuery->map(function ($user) {
            $isOnline = Cache::select('key')->where(
                'key',
                '=',
                'user-is-online-' . $user->id
            )->where('value', '=', 'b:1;')->exists(); // Use 'exists()' for better performance

            // Add the isOnline field
            $user->isOnline = $isOnline ? true : false;

            return $user;
        });

        $accounts = Company::with([
            'companyPhone',
            'companyEmail',
            'assignBy',
            'assignTo',
            'clients' => function ($query) use ($request, $roles) {
                $query->with('clientPhones', 'clientEmails');

                if ($roles->contains('Sales Executives') || $roles->contains('Business Development Manager') || $roles->contains('Business Development Team Lead')) {
                    $query->where('blacklisted', false);
                }
            },
            'disposition',
            'companyAddress.country',
            'companyAddress.state',
            'companyAddress.city',
            'companyBusiness',
        ])->when(!$user->hasRole(['Admin', 'Data Entry Manager', 'Data Entry']), function ($query) use ($user) {
            if ($user->hasRole('Business Development Manager')) {
                $query->where(function ($q) use ($user, ) {
                    $q->where('assign_to', $user->id)
                        ->orWhereNull('assign_to')
                        ->orWhereIn('assign_to', User::where('reporting_authority_id', $user->id)->pluck('id'));
                });
            } elseif ($user->hasRole('Business Development Team Lead')) {
                $query->where(function ($q) use ($user, ) {
                    $q->where('assign_to', $user->id)
                        ->orWhereNull('assign_to')
                        ->orWhereIn('assign_to', User::where('reporting_authority_id', $user->reporting_authority_id)->pluck('id'));
                });
            } else {
                $query->where('assign_to', $user->id);
            }
        });

        // Apply dynamic column filters
        if ($request->has('columnFilters')) {
            // Decode the URL-encoded string first
            $columnFiltersJson = urldecode($request->columnFilters);

            // Decode the JSON string into a PHP array
            $columnFilters = json_decode($columnFiltersJson, true);

            // Check if decoding was successful and the result is an array
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $filter) {
                    $column = $filter['id'];    // Column name
                    $value = $filter['value'];  // Filter value

                    switch ($column) {
                        case 'companyPhones':
                            $accounts->whereHas('companyPhone', function ($query) use ($value) {
                                $query->where('phone', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'companyEmails':
                            $accounts->whereHas('companyEmail', function ($query) use ($value) {
                                $query->where('email', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'clientPhones':
                            $accounts->whereHas('clients.clientPhones', function ($query) use ($value) {
                                $query->where('phone', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'clientEmails':
                            $accounts->whereHas('clients.clientEmails', function ($query) use ($value) {
                                $query->where('mail', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'assignTo':
                            $accounts->whereHas('assignTo', function ($query) use ($value) {
                                $query->where('name', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'assignBy':
                            $accounts->whereHas('assignBy', function ($query) use ($value) {
                                $query->where('name', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'country':
                            $accounts->whereHas('companyAddress.country', function ($query) use ($value) {
                                $query->where('name', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'state':
                            $accounts->whereHas('companyAddress.state', function ($query) use ($value) {
                                $query->where('name', 'like', '%' . $value . '%');
                            });
                            break;

                        case 'timezone':
                            $accounts->whereHas('companyAddress', function ($query) use ($value) {
                                $query->where('timezone', 'like', '%' . $value . '%');
                            });
                            break;

                        default:
                            $accounts->where($column, 'like', '%' . $value . '%');
                            break;
                    }
                }
            }
        }

        if ($request->has('disposition')) {
            $accounts->whereHas('disposition', function ($query) use ($request) {
                $query->where('status_id', $request->disposition);
            });
        }

        if ($request->has('user')) {
            $accounts->where(function ($query) use ($request) {
                $query->where('assign_to', $request->user)
                    ->orWhere('assign_by', $request->user);
            });
        }

        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'assign_account':
                    $accounts->whereNotNull('assign_to')
                        ->where('blacklisted', false);
                    break;
                case 'dialed_account':
                    $accounts->whereHas('disposition')
                        ->where('blacklisted', false);
                    break;
                case 'unassign_account':
                    $accounts->whereHas('disposition')
                        ->whereNull('assign_to')
                        ->where('blacklisted', false);
                    break;
                case 'new_account':
                    if ($user->hasRole('Sales Executives')) {
                        $accounts->whereDoesntHave('disposition');
                    } else {
                        $accounts->whereDoesntHave('disposition')
                            ->whereNull('assign_to');
                    }
                    $accounts->where('blacklisted', false);
                    break;
                case 'noc':
                    $accounts->whereDoesntHave('companyEmail')
                        ->whereDoesntHave('companyPhone')
                        ->whereDoesntHave('clients.clientEmails')
                        ->whereDoesntHave('clients.clientPhones');
                    break;
                case 'all':
                    $accounts->where('blacklisted', false);
                    break;
            }
        } else {
            $accounts->where('blacklisted', false);
        }

        if ($request->has('industry')) {
            $industryFilter = urldecode($request->industry);

            // if no industry is selected, then get all industries
            $accounts->where('industry', "=", $industryFilter == 'No Industry' ? null : $industryFilter);
        }

        if ($request->has('timezone')) {
            $accounts->whereHas('companyAddress', function ($query) use ($request) {
                $query->where('timezone', $request->timezone);
            });
        }

        if ($request->has('search')) {
            $accounts->where(function ($query) use ($request) {
                $search = urldecode($request->search);
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('companyPhone', function ($q) use ($search) {
                        $q->where('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('companyEmail', function ($q) use ($search) {
                        $q->where('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('clients', function ($q) use ($search) {
                        $q->where('fname', 'like', '%' . $search . '%')
                            ->orWhere('lname', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('clients.clientPhones', function ($q) use ($search) {
                        $q->where('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('clients.clientEmails', function ($q) use ($search) {
                        $q->where('mail', 'like', '%' . $search . '%');
                    });
            });
        }

        $accounts = $accounts->paginate($request->per_page ?? 50);
        $accountsData = AccountResource::collection($accounts)->resolve();

        return Inertia::render('Account/Index', [
            'accountsData' => [
                ...$accounts->toArray(),
                'data' => $accountsData,
            ],
            'users' => $usersQuery,
            'industries' => $industries,
            'dispositions' => $dispositionStatuses,
        ]);
    }

    public function getCallHistory(Request $request)
    {
        // get call history from phone number
        $callHistory = Disposition::select('dispositions.id', 'dispositions.description', 'dispositions.status', 'dispositions.followup_date', 'dispositions.followup_time', 'dispositions.timezone', 'dispositions.phone', 'dispositions.created_at', 'dispositions.updated_at', 'companies.name as company_name', 'clients.fname as client_name')
            ->leftJoin('companies', 'companies.id', '=', 'dispositions.company_id')
            ->leftJoin('clients', 'clients.id', '=', 'dispositions.client_id')
            ->where('dispositions.phone', $request->phone)
            ->orderBy('dispositions.id', 'DESC')
            ->get();

        // get company details from company_id in companies table, company_phones, company_emails, company_addresses, company_businesses tables
        $companyDetails = DB::table('companies')
            ->select('companies.id', 'companies.name', 'companies.website', 'companies.industry', 'companies.fax', 'company_phones.phone', 'company_phones.type', 'company_emails.email', 'company_emails.type', 'company_addresses.block', 'company_addresses.street', 'company_addresses.address', 'company_addresses.zip', 'company_addresses.timezone', 'company_businesses.type', 'company_businesses.business_type', 'company_businesses.description')
            ->leftJoin('company_phones', 'company_phones.company_id', '=', 'companies.id')
            ->leftJoin('company_emails', 'company_emails.company_id', '=', 'companies.id')
            ->leftJoin('company_addresses', 'company_addresses.company_id', '=', 'companies.id')
            ->leftJoin('company_businesses', 'company_businesses.company_id', '=', 'companies.id')
            ->where('companies.id', $request->companyId)
            ->get();


        // get client details from clients table, clients_phones, clients_emails tables also get last call date for this client number
        $clientDetails = DB::table('clients')
            ->select('clients.id', 'clients.fname', 'clients.lname', 'clients.designation', 'clients.linkdinurl', 'clients_phones.phone', 'clients_phones.type', 'clients_emails.mail', 'clients_emails.type', DB::raw('MAX(dispositions.updated_at) as last_call_date'))
            ->leftJoin('clients_phones', 'clients_phones.clients_id', '=', 'clients.id')
            ->leftJoin('clients_emails', 'clients_emails.clients_id', '=', 'clients.id')
            ->leftJoin('dispositions', 'dispositions.phone', '=', 'clients_phones.phone')
            ->where('clients.companyId', $request->companyId)
            ->groupBy(
                'clients.id',
                'clients.fname',
                'clients.lname',
                'clients.designation',
                'clients.linkdinurl',
                'clients_phones.phone',
                'clients_phones.type',
                'clients_emails.mail',
                'clients_emails.type'
            )
            ->get();

        // get call remarks from call_remarks table with company name and client name
        $callRemarks = DB::table('call_remarks')
            ->select('call_remarks.id', 'call_remarks.type', 'call_remarks.remark', 'call_remarks.phone', 'call_remarks.created_at', 'call_remarks.updated_at', 'companies.name as company_name', 'clients.fname as client_name')
            ->leftJoin('companies', 'companies.id', '=', 'call_remarks.company_id')
            ->leftJoin('clients', 'clients.id', '=', 'call_remarks.client_id')
            ->where('call_remarks.company_id', $request->companyId)
            ->orderBy('call_remarks.id', 'DESC')
            ->get();

        return response()->json(['callHistory' => $callHistory, 'companyDetails' => $companyDetails, 'clientDetails' => $clientDetails, 'callRemarks' => $callRemarks]);
    }

    public function apiData(Request $request)
    {
        // validations
        $request->validate([
            'companyId' => 'required',
            'phone' => 'required',
        ]);

        $statusId = DispositionStatus::where('name', 'Window Refresh')->first()->id;

        $disposition = Disposition::create([
            'user_id' => Auth::id(),
            'company_id' => $request->companyId,
            'description' => "Window Refresh",
            'status' => "Window Refresh",
            'status_id' => $statusId,
            'phone' => $request->phone,
            'client_id' => $request->clientId ?? null,
        ]);

        $properties = [
            'attributes' => [
                'company_id' => $request->companyId,
                'phone' => $request->phone,
                'description' => "Window Refresh",
                'status' => "Window Refresh",
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => "Window Refresh",
            'subject_type' => 'App\Models\Company',
            'event' => 'added',
            'subject_id' => $request->companyId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['disposition' => $disposition, 'activityLog' => $activityLog]);
    }

    public function submitDisposition(Request $request)
    {
        // validations
        $request->validate([
            'companyId' => 'required',
            'description' => 'required',
            'dispositionType' => 'required',
            'phone' => 'required',
        ]);

        $statusId = DispositionStatus::where('name', $request->dispositionType)->first()->id;

        // $disposition = Disposition::where('id', '=', $request->dispositionId)->first()?->update([
        //     'user_id' => Auth::id(),
        //     'company_id' => $request->companyId,
        //     'description' => $request->description,
        //     'status' => $request->dispositionType,
        //     'status_id' => $statusId,
        //     'followup_date' => $request->scheduleDate,
        //     'followup_time' => $request->scheduleTime,
        //     'timezone' => $request->timeZone,
        //     'phone' => $request->phone,
        //     'client_id' => $request->clientId ?? null,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
        $disposition = Disposition::create([
            'user_id' => Auth::id(),
            'company_id' => $request->companyId,
            'description' => $request->description,
            'status' => $request->dispositionType,
            'status_id' => $statusId,
            'followup_date' => $request->scheduleDate,
            'followup_time' => $request->scheduleTime,
            'timezone' => $request->timeZone,
            'phone' => $request->phone,
            'client_id' => $request->clientId ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notification = Notification::create([
            'user_id' => Auth::id(),
            'company_id' => $request->companyId,
            'client_id' => $request->clientId,
            'title' => "disposition added -" . $request->dispositionType,
            'description' => $request->description,
            'followup_date' => $request->scheduleDate,
            'followup_time' => $request->scheduleTime,
            'time_zone' => $request->timeZone,
            'flag' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->dispositionType == 'Sale') {
            $date = strtoupper(date('M-Y'));
            $userid = Auth::id();

            // increment the target_achieved column by 1 in the targets table where the user_id and time is equal to the current month
            Target::where('user_id', $userid)->where('time', $date)->increment('target_achieved', 1);
        }

        if ($request->dispositionType == 'Follow Up' || $request->dispositionType == 'Call Back' || $request->dispositionType == 'Interested') {

            $clientName = "";
            $companyName = "";

            if ($request->clientId != null) {
                $clientName = Client::where('id', $request->clientId)->first()->fname;
            } else {
                $companyName = Company::where('id', $request->companyId)->first()->name;
            }

            // dd($clientName, $companyName);

            $description = "Call to " . ($clientName ? $clientName : $companyName) . " for " . $request->description;

            $formatStartDate = Carbon::parse($request->scheduleDate . ' ' . $request->scheduleTime, $request->timeZone)->format('Y-m-d H:i:s');

            // End of the day
            $formatEndDate = Carbon::parse($request->scheduleDate . ' ' . $request->scheduleTime, $request->timeZone)->endOfDay()->format('Y-m-d H:i:s');

            $calendarEvent = Calendar::create([
                'title' => $request->dispositionType,
                'start_date' => $formatStartDate,
                'end_date' => $formatEndDate,
                'phone' => $request->phone,
                'timezone' => $request->timeZone,
                'description' => $description,
                'colors' => '#8E33FF',
                'company_id' => $request->companyId,
                'client_id' => $request->clientId,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $properties = [
            'attributes' => [
                'company_id' => $request->companyId,
                'client_id' => $request->clientId,
                'phone' => $request->phone,
                'title' => $request->dispositionType,
                'description' => $request->description,
                'status' => $request->dispositionType,
                'followup_date' => $request->scheduleDate,
                'followup_time' => $request->scheduleTime,
                'timezone' => $request->timeZone,
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $request->description,
            'subject_type' => $request->clientId ? 'App\Models\Client' : 'App\Models\Company',
            'event' => 'added',
            'subject_id' => $request->clientId ?? $request->companyId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispositionData = Disposition::where('id', $request->dispositionId)->first();

        return response()->json(['message' => "Disposition added successfully", 'notification' => $notification, 'disposition' => $dispositionData]);
    }

    public function create()
    {
        $countries = DB::table('countries')
            ->select('id', 'name as label', 'iso2 as code', 'phonecode as phone')
            ->get();

        // get all the industries
        $industries = Industry::all();


        return Inertia::render('Account/CreateAccount', ['countries' => $countries, 'industries' => $industries]);
    }

    public function store(Request $request)
    {
        $company = Company::insertGetId([
            'name' => $request->companyName,
            'website' => $request->website,
            'industry' => $request->industry,
            'fax' => $request->faxNo,
            'source' => $request->source,
            'create_user_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $companyLocation = CompanyAddress::insert([
            'company_id' => $company,
            'country_id' => $request->country,
            'state_id' => $request->state,
            'city_id' => $request->city,
            'block' => $request->houseNo,
            'street' => $request->addressline1,
            'address' => $request->addressline2,
            'zip' => $request->zipcode,
            'timezone' => $request->timezone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($request->companyEmail[0]['email']) && !empty($request->companyEmail[0]['email'])) {
            foreach ($request->companyEmail as $key => $val) {
                $companyEmail = CompanyEmail::insert([
                    'company_id' => $company,
                    'email' => $request->companyEmail[$key]['email'],
                    'type' => $request->companyEmail[$key]['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (isset($request->companyPhone[0]['phone']) && !empty($request->companyPhone[0]['phone'])) {
            foreach ($request->companyPhone as $key => $val) {
                $companyPhone = CompanyPhone::insert([
                    'company_id' => $company,
                    'phone' => $request->companyPhone[$key]['phone'],
                    'type' => $request->companyPhone[$key]['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $companyBusinesses = CompanyBusiness::insert([
            'company_id' => $company,
            'type' => $request->vendorType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($request->client[0]['fname']) && !empty($request->client[0]['fname'])) {
            foreach ($request->client as $key => $val) {
                $client = Client::insertGetId([
                    'companyId' => $company,
                    'fname' => $val['fname'],
                    'lname' => $val['lname'],
                    'designation' => $val['designation'],
                    'linkdinurl' => $val['linkdinurl'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert Client Phones
                if (isset($val['clientPhone']) && is_array($val['clientPhone']) && !empty($val['clientPhone'])) {
                    foreach ($val['clientPhone'] as $phoneData) {
                        if (!empty($phoneData['phone'])) {
                            ClientsPhone::insert([
                                'clients_id' => $client,
                                'phone' => $phoneData['phone'],
                                'type' => $phoneData['type'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // Insert Client Emails
                if (isset($val['clientEmail']) && is_array($val['clientEmail']) && !empty($val['clientEmail'])) {
                    foreach ($val['clientEmail'] as $emailData) {
                        if (!empty($emailData['email'])) {
                            ClientsEmail::insert([
                                'clients_id' => $client,
                                'mail' => $emailData['email'],
                                'type' => $emailData['type'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // if (isset($request->client[$key]->clientPhone[0]['phone']) && !empty($request->client[$key]->clientPhone[0]['phone'])) {
                //     foreach ($request->clientPhone[$key]['phone'] as $k => $v) {
                //         $clientPhone = ClientsPhone::insert([
                //             'clients_id' => $client,
                //             'phone' => $request->client[$key]->clientPhone[$k]['phone'],
                //             'type' => $request->client[$key]->clientPhone[$k]['type'],
                //             'created_at' => now(),
                //             'updated_at' => now(),
                //         ]);
                //     }
                // }

                // if (isset($request->client[$key]->clientEmail[0]['email']) && !empty($request->client[$key]->clientEmail[0]['email'])) {
                //     foreach ($request->clientEmail[$key]['email'] as $k => $v) {
                //         $clientEmail = ClientsEmail::insert([
                //             'clients_id' => $client,
                //             'mail' => $request->client[$key]->clientEmail[$k]['email'],
                //             'type' => $request->client[$key]->clientEmail[$k]['type'],
                //             'created_at' => now(),
                //             'updated_at' => now(),
                //         ]);
                //     }
                // }
            }
        }

        $properties = [
            'attributes' => [
                'company_id' => $company,
                'name' => $request->companyName,
                'website' => $request->website
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has created company",
            'subject_type' => 'App\Models\Company',
            'event' => 'created',
            'subject_id' => $company,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Company created Successfully'], 201);
    }

    public function assignCompany(Request $request)
    {
        // foreach ($request->company_id as $companyId) {
        //     $existingRecord = DB::table('assign_companies')
        //         ->where('company_id', $companyId)
        //         ->get();

        //     if (count($existingRecord) > 0) {
        //         // Update the existing record
        //         AssignCompanies::where('company_id', $companyId)
        //             ->update([
        //                 'user_id' => $request->user_id,
        //                 'assign_by' => Auth::id(),
        //                 'updated_at' => now(),
        //             ]);
        //     } else {
        //         // Insert a new record
        //         AssignCompanies::insert([
        //             'company_id' => $companyId,
        //             'user_id' => $request->user_id,
        //             'assign_by' => Auth::id(),
        //             'created_at' => now(),
        //             'updated_at' => now(),
        //         ]);
        //     }
        // }

        foreach ($request->companyIds as $companyId) {
            DB::table('companies')->where('id', $companyId)->update([
                'assign_to' => $request->userId,
                'assign_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            AssignCompanies::insert([
                'company_id' => $companyId,
                'user_id' => $request->userId,
                'assign_by' => Auth::id(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $properties = [
                'attributes' => [
                    'company_id' => $companyId,
                    'user_id' => $request->userId,
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has assigned company",
                'subject_type' => 'App\Models\Company',
                'event' => 'assigned',
                'subject_id' => $companyId,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        return response()->json(['message' => 'Company assigned Successfully'], 201);
    }

    public function unassignCompany(Request $request)
    {
        foreach ($request->companyIds as $companyId) {
            $companyName = Company::where('id', $companyId)->first()->name;

            DB::table('companies')->where('id', $companyId)->update([
                'assign_to' => null,
                'assign_by' => null,
                'updated_at' => now(),
            ]);

            AssignCompanies::where('company_id', $companyId)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            $properties = [
                'attributes' => [
                    'company_id' => $companyId,
                    'company_name' => $companyName,
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has unassigned company",
                'subject_type' => 'App\Models\Company',
                'event' => 'unassigned',
                'subject_id' => $companyId,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }
        return response()->json(['message' => 'Company unassigned successfully'], 201);

    }

    public function edit($id)
    {
        $company = Company::with([
            'companyEmail',
            'companyPhone',
            'assignTo',
            'assignBy',
            'companyAddress.country',
            'companyAddress.state',
            'companyAddress.city',
            'companyBusiness',
            'clients',
            'clients.clientPhones',
            'clients.clientEmails',
            'dispositionHistory.company',
            'dispositionHistory.client',
            'dispositionHistory.user',
        ])->find($id);

        // get all the countries
        $countries = Country::select('id', 'name', 'iso2')->get();

        // get all the industries
        $industries = Industry::all();

        return Inertia::render(
            'Account/EditAccount',
            ['company' => new CompanyEditResource($company), 'countries' => $countries, 'industries' => $industries],
        );
    }

    public function update(Request $request)
    {
        // validations
        // $request->validate([
        //     'companyid' => 'required',
        //     'companyName' => 'required',
        //     'website' => 'required',
        //     'industry' => 'required',
        //     'faxNo' => 'required',
        //     'addressline2' => 'required',
        //     'country' => 'required',
        //     'state' => 'required',
        //     'city' => 'required',
        //     'houseNo' => 'required',
        //     'street' => 'required',
        //     'zipcode' => 'required',
        //     'timezone' => 'required',
        //     'companyEmail' => 'required|array',
        //     'companyEmail.*.email' => 'required|email',
        //     'companyEmail.*.type' => 'required|string',
        //     'companyPhone' => 'required|array',
        //     'companyPhone.*.phone' => 'required|numeric',
        //     'companyPhone.*.type' => 'required|string',
        //     'vendorType' => 'required',
        //     'businessType' => 'required',
        //     'description' => 'required',
        // ])

        $company = Company::where('id', $request->companyid)
            ->update([
                'name' => $request->companyName,
                'website' => $request->website,
                'industry' => $request->industry,
                'fax' => $request->faxNo,
                'source' => $request->source,
                'updated_at' => now(),
            ]);

        CompanyAddress::where('company_id', $request->companyid)->update([
            'country_id' => $request->country,
            'state_id' => $request->state,
            'city_id' => $request->city,
            'block' => $request->houseNo,
            'street' => $request->addressline1,
            'address' => $request->addressline2,
            'zip' => $request->zipcode,
            'timezone' => $request->timezone,
        ]);

        if (isset($request->companyEmail) && !empty($request->companyEmail)) {
            foreach ($request->companyEmail as $key => $val) {
                // Skip if email is empty
                if (empty($request->companyEmail[$key]['email'])) {
                    continue;
                }

                // If emailId exists and is not empty, try to update existing record
                if (!empty($request->companyEmail[$key]['emailId'])) {
                    $companyEmail = CompanyEmail::where('id', $request->companyEmail[$key]['emailId'])->first();

                    if ($companyEmail) {
                        $companyEmail->update([
                            'email' => $request->companyEmail[$key]['email'],
                            'type' => $request->companyEmail[$key]['type'],
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    CompanyEmail::create([
                        'company_id' => $request->companyid,
                        'email' => $request->companyEmail[$key]['email'],
                        'type' => $request->companyEmail[$key]['type'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (isset($request->companyPhone) && !empty($request->companyPhone)) {
            foreach ($request->companyPhone as $key => $val) {
                // Skip if phone number is empty
                if (empty($request->companyPhone[$key]['phone'])) {
                    continue;
                }

                // If phoneId exists and is not empty, try to update existing record
                if (!empty($request->companyPhone[$key]['phoneId'])) {
                    $companyPhone = CompanyPhone::where('id', $request->companyPhone[$key]['phoneId'])->first();

                    if ($companyPhone) {
                        $companyPhone->update([
                            'phone' => $request->companyPhone[$key]['phone'],
                            'type' => $request->companyPhone[$key]['type'],
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    CompanyPhone::create([
                        'company_id' => $request->companyid,
                        'phone' => $request->companyPhone[$key]['phone'],
                        'type' => $request->companyPhone[$key]['type'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // add or update company_businesses table if company id exists
        CompanyBusiness::where('company_id', $request->companyid)->update([
            'type' => $request->vendorType ?? "",
        ]);

        $properties = [
            'attributes' => [
                'company_id' => $request->companyid,
                'name' => $request->companyName,
                'website' => $request->website
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has updated company",
            'subject_type' => 'App\Models\Company',
            'event' => 'updated',
            'subject_id' => $request->companyid,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Company updated Successfully']);
    }

    public function view($id)
    {
        $company = Company::with([
            'companyEmail',
            'companyPhone',
            'assignTo',
            'assignBy',
            'companyAddress.country',
            'companyAddress.state',
            'companyAddress.city',
            'companyBusiness',
            'clients',
            'clients.clientPhones',
            'clients.clientEmails',
            'dispositionHistory.company',
            'dispositionHistory.client',
            'dispositionHistory.user',
        ])->find($id);

        return Inertia::render(
            'Account/ViewAccount',
            ['company' => new CompanyResource($company)],
        );
    }

    public function addRemark(Request $request)
    {
        $callRemark = CallRemark::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'remark' => $request->remark,
            'phone' => $request->phone,
            'company_id' => $request->companyId,
            'client_id' => $request->clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $properties = [
            'attributes' => [
                'company_id' => $request->companyId,
                'client_id' => $request->clientId,
                'phone' => $request->phone,
                'type' => $request->type,
                'remark' => $request->remark,
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has added remark",
            'subject_type' => $request->clientId ? 'App\Models\Client' : 'App\Models\Company',
            'event' => 'added',
            'subject_id' => $request->clientId ?? $request->companyId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Remark added successfully', 'callRemark' => $callRemark]);
    }

    public function checkCompanyAssigned(Request $request)
    {
        foreach ($request->companyIds as $companyId) {
            $assign = AssignCompanies::where('company_id', $companyId)
                ->where('is_active', true)
                ->with('company') // Assuming a relationship exists
                ->first();

            // dd($assign);

            if ($assign && $assign->company) {
                return response()->json([
                    'error' => $assign->company->name . ' is already assigned. Please unassign first.',
                ], 400);
            }
        }

        // call assignCompany method if no company is assigned to the user for the selected companies in the request body and return the response
        return $this->assignCompany($request);
    }

    public function getCompaniesList(Request $request)
    {
        $companies = Company::select('id', 'name')->get();

        return response()->json(['companies' => $companies]);
    }

    public function deleteCompanyPhone(Request $request)
    {
        // There should be atleast one phone number in the company
        $companyPhoneCount = CompanyPhone::where('company_id', $request->companyId)->count();

        if ($companyPhoneCount < 2) {
            return response()->json(['error' => 'Atleast one phone number is required for this company.'], 400);
        }

        $companyPhone = CompanyPhone::where('id', $request->phoneId)->first();

        if ($companyPhone) {
            $companyPhone->delete();

            $properties = [
                'attributes' => [
                    'company_id' => $request->companyId,
                    'phone_id' => $request->phoneId,
                    'phone' => $companyPhone->phone,
                    'type' => $companyPhone->type,
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has deleted company phone",
                'subject_type' => 'App\Models\CompanyPhone',
                'event' => 'deleted',
                'subject_id' => $request->phoneId,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Phone deleted successfully']);
        }

        return response()->json(['error' => 'Phone not found'], 404);
    }

    public function deleteCompanyEmail(Request $request)
    {
        // There should be atleast one email in the company
        $companyEmailCount = CompanyEmail::where('company_id', $request->companyId)->count();

        if ($companyEmailCount < 2) {
            return response()->json(['error' => 'Atleast one email is required for this company.'], 400);
        }

        $companyEmail = CompanyEmail::where('id', $request->emailId)->first();

        if ($companyEmail) {
            $companyEmail->delete();

            $properties = [
                'attributes' => [
                    'company_id' => $request->companyId,
                    'email_id' => $request->emailId,
                    'email' => $companyEmail->email,
                    'type' => $companyEmail->type,
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has deleted company email",
                'subject_type' => 'App\Models\CompanyEmail',
                'event' => 'deleted',
                'subject_id' => $request->emailId,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Email deleted successfully']);
        }

        return response()->json(['error' => 'Email not found'], 404);
    }

    public function toggleBlacklist(Request $request)
    {
        $company = Company::find($request->id);

        if (!$company)
            return redirect()->back()->with('error', 'Company not found');

        $company->assign_to = null;
        $company->assign_by = null;
        $company->blacklisted = !$company->blacklisted;
        $company->updated_at = now();

        // blacklist client also if exists
        $client = Client::where('companyId', $request->id)->update(['blacklisted' => $company->blacklisted]);

        // if (!$client)
        //     return response()->json(['error' => 'Failed to blacklist clients of this company'], 400);

        $assignCompany = AssignCompanies::where('company_id', $request->id)->update(['is_active' => false]);

        // if (!$assignCompany)
        //     return response()->json(['error' => 'Failed to unassign company'], 400);

        $company->save();

        $properties = [
            'attributes' => [
                'company_id' => $request->id,
                'name' => $company->name,
                'blacklisted' => $company->blacklisted,
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has updated company blacklist status",
            'subject_type' => 'App\Models\Company',
            'event' => 'updated',
            'subject_id' => $request->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Company blacklist status updated successfully');
    }
}
