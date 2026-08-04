<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Calendar;
use App\Models\CallRemark;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadsAssignCompany;
use App\Models\LeadsClient;
use App\Models\LeadsClientsEmail;
use App\Models\LeadsClientsPhone;
use App\Models\LeadsCompanyAddress;
use App\Models\LeadsCompanyBusiness;
use App\Models\LeadsCompanyEmail;
use App\Models\LeadsCompanyPhone;
use App\Models\LeadsDisposition;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public $search;
    public $filter;
    public $timezone;
    public $useridfilter;
    public $loggedinUser;

    public function __construct()
    {
        $this->loggedinUser = Auth::check() ? Auth::user()->name : null;
    }

    public function index(Request $request)
    {
        // Decode the search query and split into words
        // $search = urldecode(trim($request->get('search', ''))); // Automatically decodes %20 to space
        // $searchWords = array_filter(preg_split('/\s+/', $search)); // Split into individual words
        $user = auth()->user();

        $roles = $user->roles->pluck('name')->toArray(); // Assuming roles are stored as an array
        $userId = $user->id;

        $filter = $request->filter;
        $timezone = $request->timezone;
        $useridfilter = $request->user;
        $disposition = $request->disposition;

        $leadName = $request->name;
        $fax = $request->fax;
        $leadPhones = $request->leadPhones;
        $leadEmails = $request->leadEmails;
        $clientEmails = $request->clientEmails;
        $clientPhones = $request->clientPhones;
        $leadByFilter = $request->leadBy;
        $assignToFilter = $request->assignTo;
        $assignByFilter = $request->assignBy;
        $dispositionType = $request->dispositionType;
        $timezoneFilter = $request->timezoneFilter;
        $industryType = $request->industry;
        $leadSource = $request->lead_source;
        $leadSourceFilterDropdown = $request->leadSourceFilter;

        if ($request->has('columnFilters')) {
            $columnFiltersJson = urldecode($request->columnFilters);
            $columnFilters = json_decode($columnFiltersJson, true);
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $f) {
                    switch ($f['id']) {
                        case 'name': $leadName = $f['value']; break;
                        case 'fax': $fax = $f['value']; break;
                        case 'leadPhones': $leadPhones = $f['value']; break;
                        case 'leadEmails': $leadEmails = $f['value']; break;
                        case 'clientEmails': $clientEmails = $f['value']; break;
                        case 'clientPhones': $clientPhones = $f['value']; break;
                        case 'leadBy': $leadByFilter = $f['value']; break;
                        case 'assignTo': $assignToFilter = $f['value']; break;
                        case 'assignBy': $assignByFilter = $f['value']; break;
                        case 'dispositionType': $dispositionType = $f['value']; break;
                        case 'timezoneFilter': $timezoneFilter = $f['value']; break;
                        case 'industry': $industryType = $f['value']; break;
                        case 'lead_source': $leadSource = $f['value']; break;
                    }
                }
            }
        }

        // Check if the user has an Admin or Business Development Manager role
        $isAdminOrBDM = in_array('Admin', $roles) || in_array('Business Development Manager', $roles);
        $isAdmin = in_array('Admin', $roles);
        $isBDM = in_array('Business Development Manager', $roles);
        $isBDTeamLead = in_array('Business Development Team Lead', $roles);

        $leadsQuery = Lead::with([
            'users',
            'reporting_manager',
            'clients.leadClientPhones',
            'clients.leadClientEmails',
            'leadAddress.country:id,name',
            'leadAddress.state:id,name',
            'leadAddress.city:id,name',
            'leadPhones',
            'leadEmails',
            'leadusers:id,name',
            'leadauthorityusers:id,name',
            'disposition.leadDispositionStatus:id,name',
        ]);


        // Role-based filtering
        if (!$isAdmin) {
            if ($isBDM) {
                // BDM: Companies assigned by them or newly created
                $userList = DB::table('users')
                    ->select('users.id')
                    ->where('users.reporting_authority_id', '=', $userId)
                    ->where('users.is_active', '=', true)
                    ->pluck('users.id')
                    ->toArray();

                //  $modleroles = ModelHasRole::where('role_id', 18)->get();
                $leadsQuery->where(function ($query) use ($userId, $userList) {
                    $query->whereHas('assignLeads', function ($subQuery) use ($userId, $userList) {
                        $subQuery->whereIn('assign_by', array_merge([$userId], $userList))
                            ->orWhere('assign_to', $userId);
                    })
                        ->orwhereDoesntHave('disposition')
                        ->WhereDoesntHave('assignLeads');
                });
            } else if ($isBDTeamLead) {
                // Get reporting authority ID
                $reportingAuthorityId = User::where('id', $userId)->value('reporting_authority_id');

                // BDTL: Companies assigned by their BDMs or newly created or assigned by them
                $leadsQuery->where(function ($query) use ($userId, $reportingAuthorityId) {
                    $query->whereHas('assignLeads', function ($subQuery) use ($userId, $reportingAuthorityId) {
                        $subQuery->whereIn('assign_by', [$userId, $reportingAuthorityId])
                            ->orWhere('user_id', $userId);
                    })
                        ->orWhereDoesntHave('disposition')
                        ->WhereDoesntHave('assignLeads');
                });


            } else {
                // Sales Executives: Only companies assigned to them
                $leadsQuery->whereHas('assignLeads', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                });
            }
        }

        // // Apply search filter on 'name'
        // if (!empty($searchWords)) {
        //     // Apply search filter on company name and phone numbers in leadPhones
        //     $leadsQuery->where(function ($query) use ($search, $isAdminOrBDM) {
        //         // Search for company name
        //         $query->where('name', 'like', "%$search%")
        //             ->orWhereHas('leadPhones', function ($query) use ($search) {
        //                 // Search for phone number in leadPhones
        //                 $query->where('phone', 'like', "$search%");
        //             })->orWhereHas('leadEmails', function ($query) use ($search) {
        //                 // Search for email in leadEmails
        //                 $query->where('email', 'like', "$search%");
        //             })->orWhereHas('clients.leadClientPhones', function ($query) use ($search) {
        //                 // Search for email in leadEmails
        //                 $query->where('phone', 'like', "$search%");
        //             })->orWhereHas('clients.leadClientEmails', function ($query) use ($search) {
        //                 // Search for email in leadEmails
        //                 $query->where('mail', 'like', "$search%");
        //             });
        //     });
        // }

        // Apply filters
        $search = $request->get('search');
        if ($search) {
            $searchWords = array_filter(preg_split('/\s+/', $search));
            // dd($searchWords);
            $leadsQuery->where(function ($query) use ($searchWords) {
                $word = implode(" ", $searchWords);
                $query->orWhere('name', 'like', "%$word%")
                    ->orWhereHas('leadPhones', fn($q) => $q->where('phone', 'like', "%$word%"))
                    ->orWhereHas('leadEmails', fn($q) => $q->where('email', 'like', "%$word%"))
                    ->orWhereHas('clients.leadClientPhones', fn($q) => $q->where('phone', 'like', "%$word%"))
                    ->orWhereHas('clients.leadClientEmails', fn($q) => $q->where('mail', 'like', "%$word%"));
            });
        }


        if (!empty($leadName)) {
            // Split the company name into individual words
            $leadNameWords = explode(' ', $leadName);

            // Apply filters based on roles
            if (!$isAdmin && !$isBDM && !$isBDTeamLead) {
                $leadsQuery->where(function ($query) use ($leadNameWords) {
                    foreach ($leadNameWords as $word) {
                        $query->where('name', 'like', "%$word%");
                    }
                })->whereHas('assignLeads', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                });
            } else {
                $leadsQuery->where(function ($query) use ($leadNameWords) {
                    foreach ($leadNameWords as $word) {
                        $query->where('name', 'like', "%$word%");
                    }
                });
            }
        }

        if (!empty($industryType)) {
            $leadsQuery->where(function ($query) use ($industryType) {
                $query->where('industry', 'like', "%$industryType%");
            });
        }

        if (!empty($leadSource)) {
            $leadsQuery->where(function ($query) use ($leadSource) {
                $query->where('lead_source', 'like', "%$leadSource%");
            });
        }

        if (!empty($fax)) {
            $leadsQuery->where(function ($query) use ($fax) {
                $query->where('fax', 'like', "%$fax%");
            });
        }

        if (!empty($leadPhones)) {
            $leadsQuery->whereHas('leadPhones', function ($query) use ($leadPhones) {
                $query->where('phone', 'like', "%$leadPhones%");
            });
        }

        if (!empty($leadEmails)) {
            $leadsQuery->whereHas('leadEmails', function ($query) use ($leadEmails) {
                $query->where('email', 'like', "%$leadEmails%");
            });
        }

        if (!empty($clientEmails)) {
            $leadsQuery->whereHas('clients.leadClientEmails', function ($query) use ($clientEmails) {
                $query->where('mail', 'like', "%$clientEmails%");
            });
        }

        if (!empty($clientPhones)) {
            $leadsQuery->whereHas('clients.leadClientPhones', function ($query) use ($clientPhones) {
                $query->where('phone', 'like', "%$clientPhones%");
            });
        }

        if ($leadByFilter) {
            // get all the user ids where the name is like the search query
            $userIds = User::select('id')->where('name', 'like', "%$leadByFilter%")->get()->pluck('id')->toArray();

            $leadsQuery->whereIn('lead_provide_by', $userIds);
        }

        if ($assignToFilter) {
            // get all the user ids where the name is like the search query
            $userIds = User::select('id')->where('name', 'like', "%$assignToFilter%")->get()->pluck('id')->toArray();

            $leadsQuery->whereIn('assign_to', $userIds);
        }

        if ($assignByFilter) {
            // get all the user ids where the name is like the search query
            $userIds = User::select('id')->where('name', 'like', "%$assignByFilter%")->get()->pluck('id')->toArray();

            $leadsQuery->whereIn('assign_by', $userIds);
        }

        if ($dispositionType) {
            $leadsQuery->whereHas('disposition', function ($query) use ($dispositionType) {
                $query->where('status', 'like', "%$dispositionType%");
            });
        }

        if ($timezoneFilter) {
            $leadsQuery->whereHas('leadAddress', function ($query) use ($timezoneFilter) {
                $query->where('timezone', 'like', "%$timezoneFilter%");
            });
        }

        if ($filter == "new_lead") {
            if (!$isAdmin && !$isBDM && !$isBDTeamLead) {
                $leadsQuery->whereDoesntHave('disposition')
                    ->whereHas('assignLeads', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            } else {
                $leadsQuery->whereDoesntHave('disposition')->whereDoesntHave('assignLeads');
            }
        } else if ($filter == "dialed_lead") {
            if (!$isAdmin && !$isBDM && !$isBDTeamLead) {
                $leadsQuery->whereHas('disposition')
                    ->whereHas('assignLeads', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            } else if ($isBDM) {
                $leadsQuery->whereHas('disposition')
                    ->whereHas('assignLeads', function ($query) use ($userId) {
                        $query->where('assign_by', $userId);
                    });
            } else {
                $leadsQuery->whereHas('disposition');
            }
        }

        if ($request->filled('timezone')) {
            $timezone = $request->get('timezone');
            $leadsQuery->whereHas('leadAddress', function ($query) use ($timezone) {
                $query->where('timezone', $timezone);
            });
        }

        if ($useridfilter) {
            $leadsQuery->where(function ($query) use ($useridfilter) {
                $query->orWhereHas('assignLeads', function ($subQuery) use ($useridfilter) {
                    $subQuery->where('user_id', $useridfilter);
                })->orWhereHas('assignLeads', function ($subQuery) use ($useridfilter) {
                    $subQuery->where('assign_to', $useridfilter);
                });
            });
        }

        // filter companies by latest disposition status id from dispositions table
        if ($disposition) {
            $leadsQuery->whereHas('latestDisposition', function ($query) use ($disposition) {
                $query->where('status_id', $disposition);
            });

            $leadsQuery->where(function ($query) use ($userId, $isAdmin, $isBDM, $isBDTeamLead) {
                if (!$isAdmin && !$isBDM && !$isBDTeamLead) {
                    $query->whereHas('assignLeads', fn($q) => $q->where('user_id', $userId));
                } else if ($isBDM) {

                    $usersList = User::where('reporting_authority_id', $userId)->pluck('id')->toArray();

                    $query->whereHas('assignLeads', fn($q) => $q->whereIn('assign_by', array_merge([$userId], $usersList)));
                } else if ($isBDTeamLead) {
                    $reportingAuthorityId = User::where('id', $userId)->value('reporting_authority_id');

                    $query->whereHas('assignLeads', fn($q) => $q->whereIn('assign_by', [$userId, $reportingAuthorityId]));
                }
            });
        }

        if ($leadSourceFilterDropdown) {
            $leadsQuery->where('lead_source', $leadSourceFilterDropdown);
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $leads = $leadsQuery->paginate($perPage);

        // add search, filter, timezone, and user id to the query string
        $custom = collect(['timezone' => $timezone, 'filter' => $filter, 'search' => $search, 'useridfilter' => $useridfilter]);

        $leads = $custom->merge($leads);

        if ((Auth::user()->hasPermissionTo('Can Edit Lead Assign User')) && (!Auth::user()->hasRole(['Admin', 'Business Development Manager']))) {
            $reporterid = User::find(Auth::id());

            $userQuery = User::select('id', 'name', 'email')
                ->where("is_active", "=", true)
                ->where('reporting_authority_id', $reporterid->reporting_authority_id)
                ->where("id", "!=", Auth::id())->get();

        } else if (Auth::user()->hasRole(['Business Development Manager'])) {
            $userQuery = User::select('id', 'name', 'email')
                ->where("is_active", "=", true)
                ->where("id", "!=", Auth::id())
                ->where('reporting_authority_id', $userId)->get();

        } else {
            $userQuery = in_array('Admin', $roles) || in_array('admin', $roles)
                ? User::select('id', 'name', 'email')->where("is_active", "=", true)->where("id", "!=", Auth::id())->get()
                : User::select('id', 'name', 'email')
                    ->where("is_active", "=", true)
                    ->where("id", "!=", Auth::id())
                    ->where('reporting_authority_id', $userId)->get();
        }


        $dispositionStatuses = DB::table('disposition_statuses')->select('id', 'name')->get();

        $leadSources = DB::table('leads')
            ->select('lead_source as name')
            ->whereNotNull('lead_source')
            ->where('lead_source', '!=', '')
            ->distinct()
            ->orderBy('name')
            ->get();

        return Inertia::render('Leads/Index', [
            'leadsData' => $leads, 
            'users' => $userQuery, 
            'dispositions' => $dispositionStatuses,
            'leadSources' => $leadSources
        ]);
    }

    public function getLeadsCallHistory(Request $request)
    {

        $callHistory = LeadsDisposition::select('leads_dispositions.id', 'leads_dispositions.lead_company_id', 'leads_dispositions.status', 'leads_dispositions.created_at', 'leads_dispositions.updated_at', 'leads_dispositions.description', 'leads_dispositions.followup_date', 'leads_dispositions.followup_time', 'leads_dispositions.timezone', 'leads_dispositions.phone', 'leads.name as company_name', 'leads_clients.fname as client_name')
            ->leftJoin('leads', 'leads.id', '=', 'leads_dispositions.lead_company_id')
            ->leftJoin('leads_clients', 'leads_clients.id', '=', 'leads_dispositions.lead_client_id')
            ->where('leads_dispositions.phone', $request->phone)
            ->orderBy('leads_dispositions.id', 'desc')
            ->get();

        // get lead company details by lead id with emails, phones, address etc
        $leadCompanyDetails = Lead::select(
            'leads.name as name',
            'leads.website',
            'leads.fax',
            'leads.industry',
            'leads.description',
            'users.name as assigned_user',
            DB::raw('GROUP_CONCAT(DISTINCT countries.iso2) as countryname'),
            DB::raw('GROUP_CONCAT(DISTINCT states.name) as statename'),
            DB::raw('GROUP_CONCAT(DISTINCT cities.name) as cityname'),
            DB::raw('GROUP_CONCAT(DISTINCT leads_company_addresses.timezone) as timezone'),
            DB::raw('GROUP_CONCAT(DISTINCT CONCAT(leads_company_phones.phone, "-", leads_company_phones.type)) as company_phones'),
            DB::raw('GROUP_CONCAT(DISTINCT leads_company_emails.email) as email')
        )
            ->leftJoin('users', 'leads.lead_provide_by', '=', 'users.id')
            ->leftJoin('leads_company_addresses', 'leads.id', '=', 'leads_company_addresses.lead_company_id')
            ->leftJoin('countries', 'leads_company_addresses.country_id', '=', 'countries.id')
            ->leftJoin('states', 'leads_company_addresses.state_id', '=', 'states.id')
            ->leftJoin('cities', 'leads_company_addresses.city_id', '=', 'cities.id')
            ->leftJoin('leads_company_phones', 'leads.id', '=', 'leads_company_phones.lead_company_id')
            ->leftJoin('leads_company_emails', 'leads.id', '=', 'leads_company_emails.lead_company_id')
            ->where('leads.id', $request->companyId)
            ->groupBy('leads.name', 'leads.website', 'leads.fax', 'leads.industry', 'leads.description', 'users.name')
            ->first();

        // get lead client details by lead id with emails, phones, address etc
        $leadClientDetails = DB::table('leads_clients')
            ->select('leads_clients.id', 'leads_clients.lead_company_id', 'leads_clients.fname', 'leads_clients.lname', 'leads_clients.designation', 'leads_clients.linkedin_url', 'leads_clients.created_at', 'leads_clients.updated_at', 'leads_clients_phones.phone', 'leads_clients_phones.type as phone_type', 'leads_clients_emails.mail', 'leads_clients_emails.type as email_type')
            ->leftJoin('leads_clients_phones', 'leads_clients.id', '=', 'leads_clients_phones.lead_client_id')
            ->leftJoin('leads_clients_emails', 'leads_clients.id', '=', 'leads_clients_emails.lead_client_id')
            ->where('leads_clients.lead_company_id', $request->companyId)
            ->get();

        $callRemarks = CallRemark::select('call_remarks.id', 'call_remarks.type', 'call_remarks.remark', 'call_remarks.phone', 'call_remarks.created_at', 'call_remarks.updated_at', 'leads.name as company_name', 'leads_clients.fname as client_name')
            ->leftJoin('leads', 'call_remarks.lead_company_id', '=', 'leads.id')
            ->leftJoin('leads_clients', 'call_remarks.lead_client_id', '=', 'leads_clients.id')
            ->where('call_remarks.lead_company_id', $request->companyId)
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

        $disposition = LeadsDisposition::create([
            'user_id' => Auth::id(),
            'lead_company_id' => $request->companyId,
            'description' => "Window Refresh",
            'status' => "Window Refresh",
            'status_id' => $statusId,
            'phone' => $request->phone,
        ]);

        $properties = [
            'attributes' => [
                'lead_company_id' => $request->companyId,
                'phone' => $request->phone,
                'description' => "Window Refresh",
                'status' => "Window Refresh",
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => "Window Refresh",
            'lead_subject_type' => 'App\Models\Lead',
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
        //     'leadId' => 'required',
        //     'description' => 'required',
        //     'dispositionType' => 'required',
        //     'phone' => 'required',
        // ]);

        $statusId = DB::table('disposition_statuses')->where('name', $request->dispositionType)->first()->id;

        $disposition = LeadsDisposition::where('id', '=', $request->dispositionId)->first()?->update([
            'user_id' => Auth::id(),
            'lead_company_id' => $request->companyId,
            'description' => $request->description,
            'status' => $request->dispositionType,
            'status_id' => $statusId,
            'followup_date' => $request->scheduleDate,
            'followup_time' => $request->scheduleTime,
            'timezone' => $request->timeZone,
            'phone' => $request->phone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notification = Notification::create([
            'user_id' => Auth::id(),
            'lead_company_id' => $request->companyId,
            'lead_client_id' => $request->clientId,
            'description' => $request->description,
            'title' => "disposition added -" . $request->dispositionType,
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

        if ($request->dispositionType == 'Follow Up') {
            $companyName = Lead::where('id', $request->companyId)->first()->name;

            $description = "Follow Up for <a href='/lead/view/" . $request->companyId . "' target='_blank'>" . $companyName . "</a> on " . $request->scheduleDate . " at " . $request->scheduleTime . "<br>Phone- " . $request->phone . "<br>Other Details: " . $request->description;

            $formatStartDate = Carbon::parse($request->scheduleDate . ' ' . $request->scheduleTime, $request->timeZone)->format('Y-m-d H:i:s');

            // End of the day
            $formatEndDate = Carbon::parse($request->scheduleDate . ' ' . $request->scheduleTime, $request->timeZone)->endOfDay()->format('Y-m-d H:i:s');

            $calendarEvent = Calendar::create([
                'title' => $request->dispositionType,
                'start_date' => $formatStartDate,
                'end_date' => $formatEndDate,
                'timezone' => $request->timeZone,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $properties = [
            'attributes' => [
                'lead_company_id' => $request->companyId,
                'lead_client_id' => $request->clientId,
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
            'lead_subject_type' => 'App\Models\Lead',
            'event' => 'added',
            'lead_subject_id' => $request->companyId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispositionData = LeadsDisposition::where('id', $request->dispositionId)->first();

        return response()->json(['message' => "Disposition added successfully", 'notification' => $notification, 'disposition' => $dispositionData]);
    }

    public function create()
    {
        $countries = DB::table('countries')->select('id', 'name', 'iso2', 'phonecode')->get();

        // get all the industries from the comapnies table withput null values
        $industries = Company::select('industry')->whereNotNull('industry')->distinct()->get();

        return Inertia::render('Leads/CreateLead', ['countries' => $countries, 'industries' => $industries]);
    }

    public function store(Request $request)
    {
        $lead = Lead::create([
            'name' => $request->leadName,
            'website' => $request->website,
            'fax' => $request->faxNo,
            'industry' => $request->industry,
            'description' => $request->description,
            'lead_provide_by' => Auth::id(),
            'lead_source' => $request->leadSource,
            'lead_status' => $request->leadStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $address = LeadsCompanyAddress::create([
            'house_no' => $request->houseNo,
            'street' => $request->street,
            'addressline2' => $request->addressline2,
            'zip' => $request->zipcode,
            'timezone' => $request->timezone,
            'lead_company_id' => $lead->id,
            'country_id' => (int) $request->country ?: null,
            'state_id' => (int) $request->state ?: null,
            'city_id' => (int) $request->city ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($request->companyEmail[0]['email']) && !empty($request->companyEmail[0]['email'])) {
            foreach ($request->companyEmail as $key => $val) {
                $companyEmail = LeadsCompanyEmail::create([
                    'email' => $request->companyEmail[$key]['email'],
                    'type' => $request->companyEmail[$key]['type'],
                    'lead_company_id' => $lead->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (isset($request->companyPhone[0]['phone']) && !empty($request->companyPhone[0]['phone'])) {
            foreach ($request->companyPhone as $key => $val) {
                $companyPhone = LeadsCompanyPhone::create([
                    'phone' => $request->companyPhone[$key]['phone'],
                    'type' => $request->companyPhone[$key]['type'],
                    'lead_company_id' => $lead->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (isset($request->client[0]['fname']) && !empty($request->client[0]['fname'])) {
            foreach ($request->client as $value) {
                // dd($value['clientEmail']);
                $leadClient = LeadsClient::create([
                    'fname' => $value['fname'],
                    'lname' => $value['lname'],
                    'designation' => $value['designation'],
                    'linkedin_url' => $value['linkedin_url'],
                    'lead_company_id' => $lead->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (isset($value['clientEmail'][0]['email']) && !empty($value['clientEmail'][0]['email'])) {
                    foreach ($value['clientEmail'] as $key => $val) {
                        $clientEmail = LeadsClientsEmail::create([
                            'mail' => $value['clientEmail'][$key]['email'],
                            'type' => $value['clientEmail'][$key]['type'],
                            'lead_client_id' => $leadClient->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if (isset($value['clientPhone'][0]['phone']) && !empty($value['clientPhone'][0]['phone'])) {
                    foreach ($value['clientPhone'] as $key => $val) {
                        $clientPhone = LeadsClientsPhone::create([
                            'phone' => $value['clientPhone'][$key]['phone'],
                            'type' => $value['clientPhone'][$key]['type'],
                            'lead_client_id' => $leadClient->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        $leadCompanyBusinesses = LeadsCompanyBusiness::create([
            'lead_company_id' => $lead->id,
            'type' => $request->companyType,
            'business_type' => $request->businessType,
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
            'lead_subject_type' => 'App\Models\Lead',
            'event' => 'created',
            'lead_subject_id' => $lead->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Lead created successfully']);
    }

    public function checkLeadAssigned(Request $request)
    {
        foreach ($request->leadIds as $leadId) {
            $assign = LeadsAssignCompany::where('lead_company_id', $leadId)
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
            $lead = Lead::where('id', $leadId)->first()->update([
                'assign_to' => $request->userId,
                'assign_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            LeadsAssignCompany::create([
                'lead_company_id' => $leadId,
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
                'description' => $this->loggedinUser . " has assigned a lead",
                'lead_subject_type' => 'App\Models\Lead',
                'event' => 'assigned',
                'lead_subject_id' => $leadId,
                'causer_type' => 'App\Models\User',
                'causer_id' => Auth::id(),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Lead assigned successfully'], 201);
    }

    public function unassignLead(Request $request)
    {

        foreach ($request->leadIds as $leadId) {

            $leadName = Lead::where('id', $leadId)->first()->name;

            $lead = Lead::where('id', $leadId)->first()->update([
                'assign_to' => null,
                'assign_by' => null,
                'updated_at' => now(),
            ]);

            LeadsAssignCompany::where('lead_company_id', $leadId)->update([
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
                'description' => $this->loggedinUser . " has unassigned a lead",
                'lead_subject_type' => 'App\Models\Lead',
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
        $leadInfo = Lead::select(
            'leads.id',
            'leads.name as company_name',
            'leads.website',
            'leads.fax',
            'leads.industry',
            'leads.description',
            'leads.lead_provide_by',
            'leads.lead_source',
            'leads.lead_status',
            'leads_company_addresses.house_no',
            'leads_company_addresses.street',
            'leads_company_addresses.addressline2',
            'leads_company_addresses.zip',
            'leads_company_addresses.country_id',
            'leads_company_addresses.state_id',
            'leads_company_addresses.city_id',
            'leads_company_addresses.timezone',
            'leads_company_businesses.type as vendor_type',
            'leads_company_businesses.business_type',
            'leads_company_businesses.description'
        )
            ->leftJoin('leads_company_addresses', 'leads.id', '=', 'leads_company_addresses.lead_company_id')
            ->leftJoin('leads_company_businesses', 'leads.id', '=', 'leads_company_businesses.lead_company_id')
            ->where('leads.id', $id)
            ->get();

        // convert null values to empty string in leadInfo
        $leadInfo = collect($leadInfo)->map(function ($value) {
            return $value === null ? '' : $value;
        })->toArray();

        $leadPhoneInfo = DB::table('leads')
            ->select('leads.id as leadid', 'leads_company_phones.id as phoneId', 'leads_company_phones.phone as phone', 'leads_company_phones.type as type')
            ->leftjoin('leads_company_phones', 'leads_company_phones.lead_company_id', '=', 'leads.id')
            ->where('leads.id', '=', $id)->get()->map(function ($phone) {
                return array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, (array) $phone);
            })->toArray();

        $leadEmailInfo = DB::table('leads')
            ->select('leads.id as leadid', 'leads_company_emails.id as emailId', 'leads_company_emails.email as email', 'leads_company_emails.type as type')
            ->leftjoin('leads_company_emails', 'leads_company_emails.lead_company_id', '=', 'leads.id')
            ->where('leads.id', '=', $id)->get()->map(function ($email) {
                return array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, (array) $email);
            })->toArray();

        $clientInfo = DB::table('leads')
            ->select(
                'leads.id as leadid',
                'leads_clients.id as clientid',
                'leads_clients.fname as firstname',
                'leads_clients.lname as lastname',
                'leads_clients.designation as designation',
                'leads_clients.linkedin_url as linkedinurl',
                'leads_clients_phones.id as phoneid',
                'leads_clients_phones.phone as phone',
                'leads_clients_phones.type as phonetype',
                'leads_clients_emails.id as emailid',
                'leads_clients_emails.mail as email',
                'leads_clients_emails.type as emailtype'
            )
            ->leftJoin('leads_clients', 'leads_clients.lead_company_id', '=', 'leads.id')
            ->leftJoin('leads_clients_phones', 'leads_clients_phones.lead_client_id', '=', 'leads_clients.id')
            ->leftJoin('leads_clients_emails', 'leads_clients_emails.lead_client_id', '=', 'leads_clients.id')
            ->where('leads.id', '=', $id)
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


        $leadDispositions = LeadsDisposition::select('id', 'status', 'created_at', 'updated_at')
            ->where('lead_company_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $countries = DB::table('countries')->select('id', 'name', 'iso2', 'phonecode')->get()->toArray();


        // get all the industries from the comapnies table
        $industries = Company::select('industry')->whereNotNull('industry')->distinct()->get();

        return Inertia::render('Leads/EditLead', [
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
        $lead = Lead::where('id', $request->leadId)->first()->update([
            'name' => $request->leadName,
            'website' => $request->website,
            'fax' => $request->faxNo,
            'industry' => $request->industry,
            'description' => $request->description,
            'lead_provide_by' => Auth::id(),
            'lead_source' => $request->leadSource,
            'lead_status' => $request->leadStatus,
            'updated_at' => now(),
        ]);

        $address = LeadsCompanyAddress::where('lead_company_id', $request->leadId)->first()->update([
            'house_no' => $request->houseNo,
            'street' => $request->street,
            'addressline2' => $request->addressline2,
            'zip' => $request->zipcode,
            'timezone' => $request->timezone,
            'country_id' => (int) $request->country ?: null,
            'state_id' => (int) $request->state ?: null,
            'city_id' => (int) $request->city ?: null,
            'updated_at' => now(),
        ]);

        if (isset($request->companyEmail[0]['email']) && !empty($request->companyEmail[0]['email'])) {

            foreach ($request->companyEmail as $key => $val) {
                if (!empty($request->companyEmail[$key]['emailId'])) {
                    $companyEmail = LeadsCompanyEmail::where('id', $request->companyEmail[$key]['emailId'])->first();
                    $companyEmail->update([
                        'email' => $request->companyEmail[$key]['email'],
                        'type' => $request->companyEmail[$key]['type'],
                        'updated_at' => now(),
                    ]);
                } else {
                    LeadsCompanyEmail::create([
                        'email' => $request->companyEmail[$key]['email'],
                        'type' => $request->companyEmail[$key]['type'],
                        'lead_company_id' => $request->leadId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (isset($request->companyPhone[0]['phone']) && !empty($request->companyPhone[0]['phone'])) {

            foreach ($request->companyPhone as $key => $val) {
                if (!empty($request->companyPhone[$key]['phoneId'])) {
                    $companyPhone = LeadsCompanyPhone::where('id', $request->companyPhone[$key]['phoneId'])->first();
                    $companyPhone->update([
                        'phone' => $request->companyPhone[$key]['phone'],
                        'type' => $request->companyPhone[$key]['type'],
                        'updated_at' => now(),
                    ]);
                } else {
                    LeadsCompanyPhone::create([
                        'phone' => $request->companyPhone[$key]['phone'],
                        'type' => $request->companyPhone[$key]['type'],
                        'lead_company_id' => $request->leadId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (isset($request->client[0]['fname']) && !empty($request->client[0]['fname'])) {
            foreach ($request->client as $client) {

                $clientInfo = LeadsClient::where('lead_company_id', $request->leadId)->first()->update([
                    'fname' => $client['fname'],
                    'lname' => $client['lname'],
                    'designation' => $client['designation'],
                    'linkedin_url' => $client['linkedin_url'],
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
                            $clientEmail = LeadsClientsEmail::where('id', $client['clientEmail'][$key]['emailId'])->first();
                            $clientEmail->update([
                                'mail' => $client['clientEmail'][$key]['email'],
                                'type' => $client['clientEmail'][$key]['type'],
                                'updated_at' => now(),
                            ]);
                        } else {
                            LeadsClientsEmail::create([
                                'mail' => $client['clientEmail'][$key]['email'],
                                'type' => $client['clientEmail'][$key]['type'],
                                'lead_client_id' => $client['id'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                if (isset($client['clientPhone'][0]['phone']) && !empty($client['clientPhone'][0]['phone'])) {
                    foreach ($client['clientPhone'] as $key => $val) {
                        if (!empty($client['clientPhone'][$key]['phoneId'])) {
                            $clientPhone = LeadsClientsPhone::where('id', $client['clientPhone'][$key]['phoneId'])->first();
                            $clientPhone->update([
                                'phone' => $client['clientPhone'][$key]['phone'],
                                'type' => $client['clientPhone'][$key]['type'],
                                'updated_at' => now(),
                            ]);
                        } else {
                            LeadsClientsPhone::create([
                                'phone' => $client['clientPhone'][$key]['phone'],
                                'type' => $client['clientPhone'][$key]['type'],
                                'lead_client_id' => $client['id'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }

        $leadCompanyBusinesses = LeadsCompanyBusiness::where('lead_company_id', $request->leadId);

        if ($leadCompanyBusinesses) {
            $leadCompanyBusinesses->update([
                'type' => $request->companyType,
                'business_type' => $request->businessType,
                'description' => $request->description,
                'updated_at' => now(),
            ]);
        } else {
            LeadsCompanyBusiness::create([
                'lead_company_id' => $request->leadId,
                'type' => $request->companyType,
                'business_type' => $request->businessType,
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $properties = [
            'attributes' => [
                'lead_id' => $request->leadId,
                'name' => $request->leadName,
                'website' => $request->website
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has updated a lead",
            'lead_subject_type' => 'App\Models\Lead',
            'event' => 'updated',
            'lead_subject_id' => $request->leadId,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Lead updated successfully']);
    }

    public function view($id)
    {

        $leadInfo = Lead::select(
            'leads.id',
            'leads.name as company_name',
            'leads.website',
            'leads.fax',
            'leads.industry',
            'leads.description',
            'leads.lead_provide_by',
            'leads.lead_source',
            'leads.lead_status',
            'leads_company_addresses.house_no',
            'leads_company_addresses.street',
            'leads_company_addresses.addressline2',
            'leads_company_addresses.zip',
            'leads_company_addresses.country_id',
            'leads_company_addresses.state_id',
            'leads_company_addresses.city_id',
            'leads_company_addresses.timezone',
            'leads_company_businesses.type as vendor_type',
            'leads_company_businesses.business_type',
            'leads_company_businesses.description'
        )
            ->leftJoin('leads_company_addresses', 'leads.id', '=', 'leads_company_addresses.lead_company_id')
            ->leftJoin('leads_company_businesses', 'leads.id', '=', 'leads_company_businesses.lead_company_id')
            ->where('leads.id', $id)
            ->get();

        // convert null values to empty string in leadInfo
        $leadInfo = collect($leadInfo)->map(function ($value) {
            return $value === null ? '' : $value;
        })->toArray();

        $leadPhoneInfo = DB::table('leads')
            ->select('leads.id as leadid', 'leads_company_phones.phone as phone', 'leads_company_phones.type as type')
            ->leftjoin('leads_company_phones', 'leads_company_phones.lead_company_id', '=', 'leads.id')
            ->where('leads.id', '=', $id)->get()->map(function ($phone) {
                return array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, (array) $phone);
            })->toArray();

        $leadEmailInfo = DB::table('leads')
            ->select('leads.id as leadid', 'leads_company_emails.email as email', 'leads_company_emails.type as type')
            ->leftjoin('leads_company_emails', 'leads_company_emails.lead_company_id', '=', 'leads.id')
            ->where('leads.id', '=', $id)->get()->map(function ($email) {
                return array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, (array) $email);
            })->toArray();

        $clientInfo = DB::table('leads')
            ->select(
                'leads.id as leadid',
                'leads_clients.id as clientid',
                'leads_clients.fname as firstname',
                'leads_clients.lname as lastname',
                'leads_clients.designation as designation',
                'leads_clients.linkedin_url as linkedinurl',
                'leads_clients_phones.phone as phone',
                'leads_clients_phones.type as phonetype',
                'leads_clients_emails.mail as email',
                'leads_clients_emails.type as emailtype'
            )
            ->leftJoin('leads_clients', 'leads_clients.lead_company_id', '=', 'leads.id')
            ->leftJoin('leads_clients_phones', 'leads_clients_phones.lead_client_id', '=', 'leads_clients.id')
            ->leftJoin('leads_clients_emails', 'leads_clients_emails.lead_client_id', '=', 'leads_clients.id')
            ->where('leads.id', '=', $id)
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
                    return $item->phone ? ['phone' => $item->phone, 'type' => $item->phonetype] : ['phone' => '', 'type' => ''];
                })->filter()->unique()->values()->all(),
                    'emails' => $groupedClients->map(function ($item) {
                    return $item->email ? ['email' => $item->email, 'type' => $item->emailtype] : ['phone' => '', 'type' => ''];
                })->filter()->unique()->values()->all(),
                ];
            })
            ->values() // Reset array keys
            ->toArray();


        $leadDispositions = LeadsDisposition::select('id', 'status', 'created_at', 'updated_at')
            ->where('lead_company_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $countries = DB::table('countries')->select('id', 'name', 'iso2', 'phonecode')->get()->toArray();



        return Inertia::render('Leads/ViewLead', ['details' => ['leadInfo' => $leadInfo, 'leadDispositions' => $leadDispositions, 'countries' => $countries, 'leadPhone' => $leadPhoneInfo, 'leadEmail' => $leadEmailInfo, 'clientInfo' => $clientInfo]]);
    }

    public function addRemark(Request $request)
    {
        $callRemark = CallRemark::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'remark' => $request->remark,
            'phone' => $request->phone,
            'lead_company_id' => $request->companyId,
            'lead_client_id' => $request->clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Remark added successfully', 'callRemark' => $callRemark]);
    }
}
