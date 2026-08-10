<?php

namespace App\Http\Controllers;
use App\Http\Resources\RetentionResource;
use App\Http\Resources\RetentionViewResource;
use App\Models\Cache;
use App\Models\DispositionStatus;
use App\Models\Retention;
use App\Models\RetentionClient;
use App\Models\RetentionClientEmail;
use App\Models\RetentionCLientPhone;
use App\Models\RetentionCompanyPhone;
use App\Models\RetentionCompanyEmail;
use App\Models\RetentionCompanyAddress;
use App\Models\ModelHasRole;
use App\Models\Notification;
use App\Models\RetentionAssignCompanies;
use App\Models\RetentionDisposition;
use App\Models\ActivityLog;
use App\Models\State;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Calendar;
use App\Models\CallRemark;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class RetentionController extends Controller
{
    public $loggedinUser;

    public function __construct()
    {
        $this->loggedinUser = Auth::check() ? Auth::user()->name : null;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = $user->getRoleNames();

        if ($request->has('columnFilters')) {
            $columnFiltersJson = urldecode($request->columnFilters);
            $columnFilters = json_decode($columnFiltersJson, true);
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $f) {
                    // Assign dynamically to request so that ->when($request->...) works
                    $request->{$f['id']} = $f['value'];
                }
            }
        }

        $usersList = User::where('is_active', true);

        $retentions = Retention::with([
            'retentionPhone',
            'retentionEmail',
            'assignBy',
            'assignTo',
            'clients.clientPhones',
            'clients.clientEmails',
            'RetentionDisposition.leadDispositionStatus:id,name',
            'RetentionCompanyAddress.country',
            'RetentionCompanyAddress.state',
            'RetentionCompanyAddress.city',
        ])->when($request->disposition, function ($query) use ($request) {
            $query->whereHas('RetentionDisposition', function ($query) use ($request) {
                $query->where('status_id', $request->disposition);
            });
        })->when($request->filter, function ($query) use ($request) {
            switch ($request->filter) {
                case 'new_retention':
                    $query->whereDoesntHave('RetentionDisposition');
                    break;
                case 'dialed_retention':
                    $query->whereHas('RetentionDisposition');
                    break;
            }
        })->when($request->country, function ($query) use ($request) {
            $query->whereHas('RetentionCompanyAddress.country', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->country . '%');
            });
        })->when($request->state, function ($query) use ($request) {
            $query->whereHas('RetentionCompanyAddress.state', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->state . '%');
            });
        })->when($request->assignTo, function ($query) use ($request) {
            $query->whereHas('assignTo', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->assignTo . '%');
            });
        })->when($request->assignBy, function ($query) use ($request) {
            $query->whereHas('assignBy', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->assignBy . '%');
            });
        })->when($request->name, function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->name . '%');
        })->when($request->retentionPhones, function ($query) use ($request) {
            $query->where(function ($query) use ($request) {
                $query->whereHas('retentionPhone', function ($query) use ($request) {
                    $query->where('phone', 'like', '%' . $request->retentionPhones . '%');
                })->orWhere('name', 'like', '%' . $request->retentionPhones . '%');
            });
        })->when($request->clientPhones, function ($query) use ($request) {
            $query->where(function ($query) use ($request) {
                $query->whereHas('clients.clientPhones', function ($query) use ($request) {
                    $query->where('phone', 'like', '%' . $request->clientPhones . '%');
                })->orWhereHas('clients', function ($query) use ($request) {
                    $query->where('fname', 'like', '%' . $request->clientPhones . '%')
                        ->orWhere('lname', 'like', '%' . $request->clientPhones . '%');
                });
            });
        })->when($request->retentionEmails, function ($query) use ($request) {
            $query->whereHas('retentionEmail', function ($query) use ($request) {
                $query->where('email', 'like', '%' . $request->retentionEmails . '%');
            });
        })->when($request->clientEmails, function ($query) use ($request) {
            $query->whereHas('clients.clientEmails', function ($query) use ($request) {
                $query->where('mail', 'like', '%' . $request->clientEmails . '%');
            });
        })->when($request->fax, function ($query) use ($request) {
            $query->where('fax', 'like', '%' . $request->fax . '%');
        })->when($request->industry, function ($query) use ($request) {
            $query->where('industry', 'like', '%' . $request->industry . '%');
        })->when($request->timezone, function ($query) use ($request) {
            $query->whereHas('RetentionCompanyAddress', function ($query) use ($request) {
                $query->where('timezone', $request->timezone);
            });
        })->when($request->user, function ($query) use ($request) {
            $query->where(function ($query) use ($request) {
                $query->whereHas('assignTo', function ($query) use ($request) {
                    $query->where('assign_to', $request->user);
                })->orWhereHas('assignBy', function ($query) use ($request) {
                    $query->where('assign_by', $request->user);
                });
            });
        })->when($request->industryType, function ($query) use ($request) {
            $query->where('industry', 'like', '%' . $request->industryType . '%');
        })->when($request->source, function ($query) use ($request) {
            $query->where('source', 'like', '%' . $request->source . '%');
        })->when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->search . '%');
        });

        $isAdminOrCSRM = $roles->contains('Admin') || $roles->contains('Customer Service Representative Manager');
        if (!$isAdminOrCSRM) {
            $retentions = $retentions->where('assign_to', $user->id);
        }

        $retentions = $retentions->orderBy('last_order_us_date', 'desc')->paginate($request->per_page ?? 50);

        $retentionData = RetentionResource::collection($retentions)->resolve();

        $userQuery = $usersList->get();
        $dispositionStatuses = DispositionStatus::all();

        $userQuery = $user->hasRole(['Admin', 'admin'])
            ? $usersList->where("is_active", "=", true)
                ->select('id', 'name', 'email')
                ->get()
            : $usersList = Role::where('name', 'Customer Service Representative')->first()->users;

        // add field isOnline for each user
        $userQuery = $userQuery->map(function ($user) {
            $isOnline = Cache::select('key')->where(
                'key',
                '=',
                'user-is-online-' . $user->id
            )->where('value', '=', 'b:1;')->exists(); // Use 'exists()' for better performance

            // Add the isOnline field
            $user->isOnline = $isOnline ? true : false;

            return $user;
        });

        return Inertia::render('Retention/Index', [
            'leadsData' => [
                ...$retentions->toArray(),
                'data' => $retentionData
            ],
            'users' => $userQuery,
            'dispositions' => $dispositionStatuses
        ]);
    }

    public function getLeadsCallHistory(Request $request)
    {
        $callHistory = RetentionDisposition::select(
            'retention_dispositions.id',
            'retention_dispositions.company_id',
            'retention_dispositions.status',
            'retention_dispositions.created_at',
            'retention_dispositions.updated_at',
            'retention_dispositions.description',
            'retention_dispositions.followup_date',
            'retention_dispositions.followup_time',
            'retention_dispositions.timezone',
            'retention_dispositions.phone',
            'retentions.name as company_name',
            'retention_clients.fname as client_name'
        )
            ->leftJoin('retentions', 'retentions.id', '=', 'retention_dispositions.company_id')
            ->leftJoin('retention_clients', 'retention_clients.id', '=', 'retention_dispositions.client_id')
            ->where('retention_dispositions.phone', $request->phone)
            ->orderBy('retention_dispositions.id', 'desc')
            ->get();

        // get lead company details by lead id with emails, phones, address etc
        $leadCompanyDetails = Retention::select(
            'retentions.name as name',
            'retentions.website',
            'retentions.fax',
            'retentions.industry',
            'retentions.description',
            'users.name as assigned_user',
            DB::raw('GROUP_CONCAT(DISTINCT countries.iso2) as countryname'),
            DB::raw('GROUP_CONCAT(DISTINCT states.name) as statename'),
            DB::raw('GROUP_CONCAT(DISTINCT cities.name) as cityname'),
            DB::raw('GROUP_CONCAT(DISTINCT retention_company_addresses.timezone) as timezone'),
            DB::raw('GROUP_CONCAT(DISTINCT CONCAT(retention_company_phones.phone, "-", retention_company_phones.type)) as company_phones'),
            DB::raw('GROUP_CONCAT(DISTINCT retention_company_emails.email) as email')
        )
            ->leftJoin('users', 'retentions.lead_provide_by', '=', 'users.id')
            ->leftJoin('retention_company_addresses', 'retentions.id', '=', 'retention_company_addresses.company_id')
            ->leftJoin('countries', 'retention_company_addresses.country_id', '=', 'countries.id')
            ->leftJoin('states', 'retention_company_addresses.state_id', '=', 'states.id')
            ->leftJoin('cities', 'retention_company_addresses.city_id', '=', 'cities.id')
            ->leftJoin('retention_company_phones', 'retentions.id', '=', 'retention_company_phones.company_id')
            ->leftJoin('retention_company_emails', 'retentions.id', '=', 'retention_company_emails.company_id')
            ->where('retentions.id', $request->companyId)
            ->groupBy('retentions.name', 'retentions.website', 'retentions.fax', 'retentions.industry', 'retentions.description', 'users.name')
            ->first();

        // get lead client details by lead id with emails, phones, address etc
        $leadClientDetails = DB::table('retention_clients')
            ->select(
                'retention_clients.id',
                'retention_clients.companyId',
                'retention_clients.fname',
                'retention_clients.lname',
                'retention_clients.designation',
                'retention_clients.linkdinurl',
                'retention_clients.created_at',
                'retention_clients.updated_at',
                'retention_client_phones.phone',
                'retention_client_phones.type as phone_type',
                'retention_client_emails.mail',
                'retention_client_emails.type as email_type'
            )
            ->leftJoin('retention_client_phones', 'retention_clients.id', '=', 'retention_client_phones.clients_id')
            ->leftJoin('retention_client_emails', 'retention_clients.id', '=', 'retention_client_emails.clients_id')
            ->where('retention_clients.companyId', $request->companyId)
            ->get();

        $callRemarks = CallRemark::select('call_remarks.id', 'call_remarks.type', 'call_remarks.remark', 'call_remarks.phone', 'call_remarks.created_at', 'call_remarks.updated_at', 'retentions.name as company_name', 'retention_clients.fname as client_name')
            ->leftJoin('retentions', 'call_remarks.retention_company_id', '=', 'retentions.id')
            ->leftJoin('retention_clients', 'call_remarks.retention_client_id', '=', 'retention_clients.id')
            ->where('call_remarks.retention_company_id', $request->companyId)
            ->get();


        return response()->json(['callHistory' => $callHistory, 'companyDetails' => [$leadCompanyDetails], 'clientDetails' => $leadClientDetails, 'callRemarks' => $callRemarks]);
    }

    public function windowRefreshDisposition(Request $request)
    {
        // validations
        // $request->validate([
        //     'leadId' => 'required',
        //     'phone' => 'required',
        // ]);

        $statusId = DB::table('disposition_statuses')->where('name', 'Window Refresh')->first()->id;

        $disposition = RetentionDisposition::create([
            'user_id' => Auth::id(),
            'company_id' => $request->companyId,
            'description' => "Window Refresh",
            'status' => "Window Refresh",
            'status_id' => $statusId,
            'phone' => $request->phone,
        ]);

        $properties = [
            'attributes' => [
                'retention_company_id' => $request->companyId,
                'phone' => $request->phone,
                'description' => "Window Refresh",
                'status' => "Window Refresh",
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => "Window Refresh",
            'lead_subject_type' => 'App\Models\Retention',
            'event' => 'added',
            'lead_subject_id' => $request->companyId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['disposition' => $disposition, 'activityLog' => $activityLog]);
    }

    public function submitLeadDisposition(Request $request)
    {
        // validations
        // $request->validate([
        //     'companyId' => 'required',
        //     'discription' => 'required',
        //     'dispositionType' => 'required',
        //     'phone' => 'required',
        // ]);

        $statusId = DB::table('disposition_statuses')->where('name', $request->dispositionType)->first()->id;

        $disposition = RetentionDisposition::create([
            'user_id' => Auth::id(),
            'company_id' => $request->companyId,
            'description' => $request->discription,
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
            'discription' => $request->discription,
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
            DB::table('targets')->where('user_id', $userid)->where('time', $date)->increment('target_achieved', 1);
        }

        if ($request->dispositionType == 'Follow Up' || $request->dispositionType == 'Call Back' || $request->dispositionType == 'Interested') {

            $clientName = "";
            $companyName = "";

            if ($request->clientId != null) {
                $clientName = RetentionClient::where('id', $request->clientId)->first()->fname;
            } else {
                $companyName = Retention::where('id', $request->companyId)->first()->name;
            }

            $description = "Call to " . ($clientName ? $clientName : $companyName) . " for " . $request->discription;

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
                'description' => $request->discription,
                'status' => $request->dispositionType,
                'followup_date' => $request->scheduleDate,
                'followup_time' => $request->scheduleTime,
                'timezone' => $request->timeZone,
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $request->discription,
            'subject_type' => $request->clientId ? 'App\Models\RetentionClient' : 'App\Models\Retention',
            'event' => 'added',
            'subject_id' => $request->clientId ?? $request->companyId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispositionData = RetentionDisposition::where('id', $request->dispositionId)->first();

        return response()->json(['message' => "Retention Disposition added successfully", 'notification' => $notification, 'disposition' => $dispositionData]);
    }

    public function create()
    {
        abort(403, 'Retentions can only be added manually.');
        $countries = DB::table('countries')->select('id', 'name', 'iso2', 'phonecode')->get();

        // get all the industries from the comapnies table withput null values
        $industries = Retention::select('industry')->whereNotNull('industry')->distinct()->get();

        return Inertia::render('Retention/CreateLead', ['countries' => $countries, 'industries' => $industries]);
    }

    public function store(Request $request)
    {
        abort(403, 'Retentions can only be added manually.');
        $lead = Retention::create([
            'name' => $request->leadName,
            'website' => $request->website,
            'fax' => $request->faxNo,
            'industry' => $request->industry,
            'description' => $request->description,
            'lead_provide_by' => Auth::id(),
            'source' => $request->leadSource,
            'status' => $request->leadStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($request->companyEmail[0]['email']) && !empty($request->companyEmail[0]['email'])) {
            foreach ($request->companyEmail as $key => $val) {
                $companyEmail = RetentionCompanyEmail::create([
                    'email' => $request->companyEmail[$key]['email'],
                    'type' => $request->companyEmail[$key]['type'],
                    'company_id' => $lead->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (isset($request->companyPhone[0]['phone']) && !empty($request->companyPhone[0]['phone'])) {
            foreach ($request->companyPhone as $key => $val) {
                $companyPhone = RetentionCompanyPhone::create([
                    'phone' => $request->companyPhone[$key]['phone'],
                    'type' => $request->companyPhone[$key]['type'],
                    'company_id' => $lead->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (isset($request->client[0]['fname']) && !empty($request->client[0]['fname'])) {
            foreach ($request->client as $value) {
                // dd($value['clientEmail']);
                $leadClient = RetentionClient::create([
                    'fname' => $value['fname'],
                    'lname' => $value['lname'],
                    'designation' => $value['designation'],
                    'linkdinurl' => $value['linkedin_url'],
                    'companyId' => $lead->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (isset($value['clientEmail'][0]['email']) && !empty($value['clientEmail'][0]['email'])) {
                    foreach ($value['clientEmail'] as $key => $val) {
                        $clientEmail = RetentionClientEmail::create([
                            'mail' => $value['clientEmail'][$key]['email'],
                            'type' => $value['clientEmail'][$key]['type'],
                            'clients_id' => $leadClient->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if (isset($value['clientPhone'][0]['phone']) && !empty($value['clientPhone'][0]['phone'])) {
                    foreach ($value['clientPhone'] as $key => $val) {
                        $clientPhone = RetentionCLientPhone::create([
                            'phone' => $value['clientPhone'][$key]['phone'],
                            'type' => $value['clientPhone'][$key]['type'],
                            'clients_id' => $leadClient->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // $leadCompanyBusinesses = RetentionCompanyBusiness::create([
        //     'lead_company_id' => $lead->id,
        //     'type' => $request->companyType,
        //     'business_type' => $request->businessType,
        //     'description' => $request->description,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        $properties = [
            'attributes' => [
                'lead_id' => $lead->id,
                'name' => $request->leadName,
                'website' => $request->website
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has created a new lead",
            'lead_subject_type' => 'App\Models\Retention',
            'event' => 'created',
            'lead_subject_id' => $lead->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Retention created successfully']);
    }

    public function checkLeadAssigned(Request $request)
    {
        foreach ($request->leadIds as $leadId) {
            $assign = RetentionAssignCompanies::where('company_id', $leadId)
                ->where('is_active', true)
                ->with('lead') // Assuming a relationship exists
                ->first();

            // dd($assign);

            if ($assign && $assign->lead) {
                return response()->json([
                    'error' => $assign->lead->name . ' is already assigned. Please unassign first.',
                ], 400);
            }
        }

        // call assignCompany method if no company is assigned to the user for the selected companies in the request body and return the response
        return $this->assignLead($request);
    }

    public function assignLead(Request $request)
    {
        foreach ($request->leadIds as $leadId) {
            $lead = Retention::where('id', $leadId)->first()->update([
                'assign_to' => $request->userId,
                'assign_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            RetentionAssignCompanies::create([
                'company_id' => $leadId,
                'user_id' => $request->userId,
                'assign_by' => Auth::id(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $properties = [
                'attributes' => [
                    'lead_id' => $leadId,
                    'assign_to' => $request->assign_to,
                    'assign_by' => Auth::id(),
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has assigned a retention",
                'lead_subject_type' => 'App\Models\Retention',
                'event' => 'assigned',
                'lead_subject_id' => $leadId,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Retention assigned successfully'], 201);
    }

    public function unassignLead(Request $request)
    {

        foreach ($request->leadIds as $leadId) {

            $leadName = Retention::where('id', $leadId)->first()->name;

            $lead = Retention::where('id', $leadId)->first()->update([
                'assign_to' => null,
                'assign_by' => null,
                'updated_at' => now(),
            ]);

            RetentionAssignCompanies::where('company_id', $leadId)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            $properties = [
                'attributes' => [
                    'lead_id' => $leadId,
                    'lead_name' => $leadName,
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has unassigned a retention",
                'lead_subject_type' => 'App\Models\Retention',
                'event' => 'unassigned',
                'lead_subject_id' => $leadId,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Lead unassigned successfully'], 201);
    }

    public function edit($id)
    {
        abort(403, 'Retentions can only be edited manually.');
        $leadInfo = Retention::select(
            'retentions.id',
            'retentions.name as company_name',
            'retentions.website',
            'retentions.fax',
            'retentions.industry',
            'retentions.description',
            'retentions.lead_provide_by',
            'retentions.source',
            'retentions.status',
            'retention_company_addresses.block',
            'retention_company_addresses.street',
            'retention_company_addresses.address',
            'retention_company_addresses.zip',
            'retention_company_addresses.country_id',
            'retention_company_addresses.state_id',
            'retention_company_addresses.city_id',
            'retention_company_addresses.timezone',
            // 'leads_company_businesses.type as vendor_type',
            // 'leads_company_businesses.business_type',
            // 'leads_company_businesses.description'
        )
            ->leftJoin('retention_company_addresses', 'retentions.id', '=', 'retention_company_addresses.company_id')
            //->leftJoin('retention_company_businesses', 'retentions.id', '=', 'retentions_company_businesses.lead_company_id')
            ->where('retentions.id', $id)
            ->get();

        // convert null values to empty string in leadInfo
        $leadInfo = collect($leadInfo)->map(function ($value) {
            return $value === null ? '' : $value;
        })->toArray();

        $leadPhoneInfo = DB::table('retentions')
            ->select('retentions.id as leadid', 'retention_company_phones.id as phoneId', 'retention_company_phones.phone as phone', 'retention_company_phones.type as type')
            ->leftjoin('retention_company_phones', 'retention_company_phones.company_id', '=', 'retentions.id')
            ->where('retentions.id', '=', $id)->get()->map(function ($phone) {
                return array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, (array) $phone);
            })->toArray();

        $leadEmailInfo = DB::table('retentions')
            ->select('retentions.id as leadid', 'retention_company_emails.id as emailId', 'retention_company_emails.email as email', 'retention_company_emails.type as type')
            ->leftjoin('retention_company_emails', 'retention_company_emails.company_id', '=', 'retentions.id')
            ->where('retentions.id', '=', $id)->get()->map(function ($email) {
                return array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, (array) $email);
            })->toArray();

        $clientInfo = DB::table('retentions')
            ->select(
                'retentions.id as leadid',
                'retention_clients.id as clientid',
                'retention_clients.fname as firstname',
                'retention_clients.lname as lastname',
                'retention_clients.designation as designation',
                'retention_clients.linkdinurl as linkedinurl',
                'retention_client_phones.id as phoneid',
                'retention_client_phones.phone as phone',
                'retention_client_phones.type as phonetype',
                'retention_client_emails.id as emailid',
                'retention_client_emails.mail as email',
                'retention_client_emails.type as emailtype'
            )
            ->leftJoin('retention_clients', 'retention_clients.companyId', '=', 'retentions.id')
            ->leftJoin('retention_client_phones', 'retention_client_phones.clients_id', '=', 'retention_clients.id')
            ->leftJoin('retention_client_emails', 'retention_client_emails.clients_id', '=', 'retention_clients.id')
            ->where('retentions.id', '=', $id)
            ->get()
            ->groupBy('clientid') // Group by client ID
            ->map(function ($groupedClients) {
                $firstClient = $groupedClients->first();

                // Consolidate phones and emails into arrays of objects
                return [
                    'leadid' => $firstClient->leadid,
                    'clientid' => $firstClient->clientid,
                    'firstname' => $firstClient->firstname,
                    'lastname' => $firstClient->lastname,
                    'designation' => $firstClient->designation,
                    'linkedinurl' => $firstClient->linkedinurl,
                    'phones' => $groupedClients->map(function ($item) {
                    return $item->phone ? ['phoneId' => $item->phoneid, 'phone' => $item->phone, 'type' => $item->phonetype] : ['phoneId' => "", 'phone' => '', 'type' => ''];
                })->filter()->unique()->values()->all(),
                    'emails' => $groupedClients->map(function ($item) {
                    return $item->email ? ["emailId" => $item->emailid, 'email' => $item->email, 'type' => $item->emailtype] : ['emailId' => "", 'email' => '', 'type' => ''];
                })->filter()->unique()->values()->all(),
                ];
            })
            ->values() // Reset array keys
            ->toArray();


        $leadDispositions = RetentionDisposition::select('id', 'status', 'created_at', 'updated_at')
            ->where('company_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $countries = DB::table('countries')->select('id', 'name', 'iso2', 'phonecode')->get()->toArray();


        // get all the industries from the comapnies table
        $industries = Retention::select('industry')->whereNotNull('industry')->distinct()->get();

        return Inertia::render('Retention/EditLead', [
            'details' => [
                'leadInfo' => $leadInfo,
                'leadPhone' => $leadPhoneInfo,
                'leadEmail' => $leadEmailInfo,
                'clientInfo' => $clientInfo,
                'leadDispositions' => $leadDispositions,
                'countries' => $countries,
                'industries' => $industries,
            ],
        ]);
    }

    public function update(Request $request)
    {
        abort(403, 'Retentions can only be edited manually.');

        $lead = Retention::where('id', $request->leadId)->first()->update([
            'name' => $request->leadName,
            'website' => $request->website,
            'fax' => $request->faxNo,
            'industry' => $request->industry,
            'description' => $request->description,
            'lead_provide_by' => Auth::id(),
            'source' => $request->leadSource,
            'status' => $request->leadStatus,
            'updated_at' => now(),
        ]);

        // $address = RetentionCompanyAddress::where('company_id', $request->leadId)->first()->update([
        //     'block' => $request->houseNo,
        //     'street' => $request->street,
        //     'address' => $request->addressline2,
        //     'zip' => $request->zipcode,
        //     'timezone' => $request->timezone,
        //     'country_id' => (int) $request->country ?: null,
        //     'state_id' => (int) $request->state ?: null,
        //     'city_id' => (int) $request->city ?: null,
        //     'updated_at' => now(),
        // ]);

        if (isset($request->companyEmail[0]['email']) && !empty($request->companyEmail[0]['email'])) {

            foreach ($request->companyEmail as $key => $val) {
                if (!empty($request->companyEmail[$key]['emailId'])) {
                    $companyEmail = RetentionCompanyEmail::where('id', $request->companyEmail[$key]['emailId'])->first();
                    $companyEmail->update([
                        'email' => $request->companyEmail[$key]['email'],
                        'type' => $request->companyEmail[$key]['type'],
                        'updated_at' => now(),
                    ]);
                } else {
                    RetentionCompanyEmail::create([
                        'email' => $request->companyEmail[$key]['email'],
                        'type' => $request->companyEmail[$key]['type'],
                        'company_id' => $request->leadId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }




        if (isset($request->companyPhone[0]['phone']) && !empty($request->companyPhone[0]['phone'])) {
            // foreach ($request->companyPhone as $key => $val) {
            //     if (!empty($request->companyPhone[$key]['phoneId'])) {

            //         $phone2[] = $request->companyPhone[$key]['phone'];

            //     }
            // }

            // $missingCLientPhone1 = RetentionCompanyPhone::whereNotIn('phone', $phone2)->get();
            // if (!empty($missingCLientPhone1)) {
            //     foreach ($missingCLientPhone1 as $missingCLientPhones1) {
            //         RetentionCompanyPhone::where('phone', $missingCLientPhones1->phone)->delete();
            //     }

            // }

            foreach ($request->companyPhone as $key => $val) {
                if (!empty($request->companyPhone[$key]['phoneId'])) {
                    $companyPhone = RetentionCompanyPhone::where('id', $request->companyPhone[$key]['phoneId'])->first();
                    $companyPhone->update([
                        'phone' => $request->companyPhone[$key]['phone'],
                        'type' => $request->companyPhone[$key]['type'],
                        'updated_at' => now(),
                    ]);
                } else {
                    RetentionCompanyPhone::create([
                        'phone' => $request->companyPhone[$key]['phone'],
                        'type' => $request->companyPhone[$key]['type'],
                        'company_id' => $request->leadId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (isset($request->client[0]['fname']) && !empty($request->client[0]['fname'])) {
            foreach ($request->client as $client) {

                $clientInfo = RetentionClient::where('companyId', $request->leadId)->first()->update([
                    'fname' => $client['fname'],
                    'lname' => $client['lname'],
                    'designation' => $client['designation'],
                    'linkdinurl' => $client['linkedin_url'],
                    'updated_at' => now(),
                ]);

                // $clientInfo = LeadsClient::where('lead_company_id', $request->leadId);

                // if ($clientInfo) {
                //     $clientInfo->update([
                //         'fname' => $client['fname'],
                //         'lname' => $client['lname'],
                //         'designation' => $client['designation'],
                //         'linkedin_url' => $client['linkedin_url'],
                //         'updated_at' => now(),
                //     ]);
                // } else {
                //     LeadsClient::create([
                //         'fname' => $client['fname'],
                //         'lname' => $client['lname'],
                //         'designation' => $client['designation'],
                //         'linkedin_url' => $client['linkedin_url'],
                //         'lead_company_id' => $request->leadId,
                //         'created_at' => now(),
                //         'updated_at' => now(),
                //     ]);
                // }


                if (isset($client['clientEmail'][0]['email']) && !empty($client['clientEmail'][0]['email'])) {
                    foreach ($client['clientEmail'] as $key => $val) {

                        if (!empty($client['clientEmail'][$key]['emailId'])) {
                            $clientEmail = RetentionClientEmail::where('id', $client['clientEmail'][$key]['emailId'])->first();
                            $clientEmail->update([
                                'mail' => $client['clientEmail'][$key]['email'],
                                'type' => $client['clientEmail'][$key]['type'],
                                'updated_at' => now(),
                            ]);
                        } else {
                            RetentionClientEmail::create([
                                'mail' => $client['clientEmail'][$key]['email'],
                                'type' => $client['clientEmail'][$key]['type'],
                                'clients_id' => $client['id'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                if (isset($client['clientPhone'][0]['phone']) && !empty($client['clientPhone'][0]['phone'])) {

                    // foreach ($client['clientPhone'] as $key => $val) {
                    //     if (!empty($client['clientPhone'][$key]['phoneId'])) {

                    //         $phone1[] = $client['clientPhone'][$key]['phone'];

                    //     }
                    // }

                    // $missingCLientPhone = RetentionClientPhone::whereNotIn('phone', $phone1)->get();
                    // if (!empty($missingCLientPhone)) {
                    //     foreach ($missingCLientPhone as $missingCLientPhones) {
                    //         RetentionClientPhone::where('phone', $missingCLientPhones->phone)->delete();
                    //     }

                    // }
                    foreach ($client['clientPhone'] as $key => $val) {
                        if (!empty($client['clientPhone'][$key]['phoneId'])) {
                            $clientPhone = RetentionClientPhone::where('id', $client['clientPhone'][$key]['phoneId'])->first();
                            $clientPhone->update([
                                'phone' => $client['clientPhone'][$key]['phone'],
                                'type' => $client['clientPhone'][$key]['type'],
                                'updated_at' => now(),
                            ]);
                        } else {
                            RetentionClientPhone::create([
                                'phone' => $client['clientPhone'][$key]['phone'],
                                'type' => $client['clientPhone'][$key]['type'],
                                'clients_id' => $client['id'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }

        // $leadCompanyBusinesses = LeadsCompanyBusiness::where('lead_company_id', $request->leadId);

        // if ($leadCompanyBusinesses) {
        //     $leadCompanyBusinesses->update([
        //         'type' => $request->companyType,
        //         'business_type' => $request->businessType,
        //         'description' => $request->description,
        //         'updated_at' => now(),
        //     ]);
        // } else {
        //     LeadsCompanyBusiness::create([
        //         'lead_company_id' => $request->leadId,
        //         'type' => $request->companyType,
        //         'business_type' => $request->businessType,
        //         'description' => $request->description,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }

        $properties = [
            'attributes' => [
                'lead_id' => $request->leadId,
                'name' => $request->leadName,
                'website' => $request->website
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has updated a retention",
            'lead_subject_type' => 'App\Models\Retention',
            'event' => 'updated',
            'lead_subject_id' => $request->leadId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Retention updated successfully']);
    }

    public function view($id)
    {
        $retention = Retention::with([
            'retentionPhone',
            'retentionEmail',
            'assignTo',
            'assignBy',
            'RetentionCompanyAddress.country',
            'RetentionCompanyAddress.state',
            'RetentionCompanyAddress.city',
            'clients',
            'clients.clientPhones',
            'clients.clientEmails',
            'RetentionDispositionHistory.company',
            'RetentionDispositionHistory.client',
            'RetentionDispositionHistory.user',
        ])->find($id);

        // return $retention;

        return Inertia::render(
            'Retention/ViewLead',
            ['retention' => new RetentionViewResource($retention)]
        );
    }

    public function addRemark(Request $request)
    {
        $callRemark = CallRemark::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'remark' => $request->remark,
            'phone' => $request->phone,
            'retention_company_id' => $request->companyId,
            'retention_client_id' => $request->clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Remark added successfully', 'callRemark' => $callRemark]);
    }

    public function import(Request $request)
    {
        abort(403, 'Retentions can only be added manually.');
        $filePath = public_path('uploads/retention.xlsx');

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach ($rows as $key => $row) {
            if ($key != 0) {
                $strtime = strtotime($row[1]);
                $retention = Retention::create([
                    'name' => $row[0],
                    'last_order_us_date' => date('Y-m-d h:i:s', $strtime),
                    'description' => $row[3] . " file type -" . $row[2]
                ]);
                if (str_contains($row[4], " ")) {
                    $name = explode(" ", $row[4]);
                    $fname = $name[0];
                    $lname = $name[1];
                } else {
                    $fname = $row[4];
                    $lname = "";
                }
                $client = RetentionClient::create([
                    'fname' => $fname,
                    'lname' => $lname,
                    'companyId' => $retention->id,
                ]);
                if ($row[5] != "") {
                    $clientEmail = RetentionClientEmail::create([
                        'mail' => $row[5],
                        'type' => 'work',
                        'clients_id' => $client->id,
                    ]);
                }
                if ($row[6] != "") {
                    if (str_contains($row[6], "@")) {
                        $clientEmail1 = RetentionClientEmail::create([
                            'mail' => $row[6],
                            'type' => 'work',
                            'clients_id' => $client->id,
                        ]);
                    } else {
                        if (str_contains($row[6], ".")) {
                            $phone = explode(".", $row[6]);
                            if (count($phone) > 0) {
                                foreach ($phone as $k => $v) {
                                    $clientPhone = RetentionClientPhone::create([
                                        'phone' => str_replace(" ", "", str_replace("-", "", str_replace(")", "", str_replace("(", "", $v)))),
                                        'type' => 'work',
                                        'clients_id' => $client->id,
                                    ]);
                                }
                            } else {
                                $clientPhone = RetentionClientPhone::create([
                                    'phone' => str_replace(".", "", str_replace(" ", "", str_replace("-", "", str_replace(")", "", str_replace("(", "", $row[6]))))),
                                    'type' => 'work',
                                    'clients_id' => $client->id,
                                ]);
                            }
                        } else {
                            $clientPhone = RetentionClientPhone::create([
                                'phone' => str_replace(".", "", str_replace(" ", "", str_replace("-", "", str_replace(")", "", str_replace("(", "", $row[6]))))),
                                'type' => 'work',
                                'clients_id' => $client->id,
                            ]);
                        }
                    }
                }
                if ($row[7] != "") {
                    if (str_contains($row[7], "@")) {
                        $clientEmail1 = RetentionClientEmail::create([
                            'mail' => $row[7],
                            'type' => 'work',
                            'clients_id' => $client->id,
                        ]);
                    } else {
                        if (str_contains($row[7], ".")) {
                            $phone = explode(".", $row[7]);
                            if (count($phone) > 0) {
                                foreach ($phone as $k => $v) {
                                    $clientPhone = RetentionClientPhone::create([
                                        'phone' => str_replace(" ", "", str_replace("-", "", str_replace(")", "", str_replace("(", "", $v)))),
                                        'type' => 'work',
                                        'clients_id' => $client->id,
                                    ]);
                                }
                            } else {
                                $clientPhone = RetentionClientPhone::create([
                                    'phone' => str_replace(".", "", str_replace(" ", "", str_replace("-", "", str_replace(")", "", str_replace("(", "", $row[7]))))),
                                    'type' => 'work',
                                    'clients_id' => $client->id,
                                ]);
                            }
                        } else {
                            $clientPhone = RetentionClientPhone::create([
                                'phone' => str_replace(".", "", str_replace(" ", "", str_replace("-", "", str_replace(")", "", str_replace("(", "", $row[7]))))),
                                'type' => 'work',
                                'clients_id' => $client->id,
                            ]);
                        }
                    }
                }
            }
        }
        return "done";
    }
}
