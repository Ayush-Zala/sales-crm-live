<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientsEmail;
use App\Models\ClientsPhone;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ClientsController extends Controller
{
    public $loggedinUser;

    public function __construct()
    {
        $this->loggedinUser = Auth::check() ? Auth::user()->name : null;
    }

    public function index(Request $request)
    {
        $search = $request->search;

        // Get authenticated user's roles and ID
        $user = Auth::user();
        $roles = $user->getRoleNames()->toArray();
        $userId = $user->id;

        // Check if the user has Admin or Business Development Manager role
        $isAdminOrBDM = in_array('Admin', $roles) || in_array('Business Development Manager', $roles);

        $clients = DB::table('clients as e')
            ->select(
                'e.id',
                'e.fname',
                'e.lname',
                DB::raw('CONCAT(e.fname, " ", IFNULL(e.lname, "")) as fullname'),
                'e.designation',
                'e.companyId',
                'e.linkdinurl',
                'companies.name as cname',
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(cp.phone, "-", cp.type) ORDER BY cp.type ASC SEPARATOR ", ") as clientPhone'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(cm.mail, "-", cm.type) ORDER BY cp.type ASC SEPARATOR ", ") as ClientEmail')
            )
            ->leftJoin('companies', 'e.companyId', '=', 'companies.id')
            ->leftJoin('clients_phones as cp', 'e.id', '=', 'cp.clients_id')
            ->leftJoin('clients_emails as cm', 'e.id', '=', 'cm.clients_id')
            ->where(function ($query) use ($search) {
                $query->where('e.fname', 'like', '%' . $search . '%')
                    ->orWhere('e.lname', 'like', '%' . $search . '%')
                    ->orWhere('e.designation', 'like', '%' . $search . '%')
                    ->orWhere('companies.name', 'like', '%' . $search . '%');
            })
            ->groupBy(
                'e.id',
                'e.fname',
                'e.lname',
                'fullname',
                'e.designation',
                'e.companyId',
                'e.linkdinurl',
                'companies.name'
            );

        // Apply filtering based on user roles and company association
        if (!$isAdminOrBDM) {
            // If the user is not Admin or Business Development Manager,
            // we filter clients based on companyId associated with the authenticated user
            $clients = $clients->whereIn('e.companyId', function ($query) use ($userId) {
                $query->select('id')
                    ->from('companies')
                    ->where('assign_to', $userId); // Assuming 'user_id' in 'companies' refers to the authenticated user
            });
        }

        if ($request->perPage == null) {
            $clients = $clients->paginate(10);
        } else {
            $clients = $clients->paginate($request->perPage);
        }
        return Inertia::render('Clients/Index', [
            'clients' => $clients
        ]);
    }

    public function store(Request $request)
    {
        // validations
        $request->validate([
            'fname' => 'required',
            'lname' => 'required',
            'designation' => 'required',
            'company' => 'required',
            'linkdinurl' => 'nullable',
            'clientPhone' => 'nullable',
            'clientEmail' => 'nullable'
        ]);

        if ($request->company == null) {
            return response()->json(['message' => 'Please select a company']);
        }

        $company = DB::table('companies')->select('id', 'name')->where('id', $request->company)->first();

        $inst = Client::create([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'designation' => $request->designation,
            'companyId' => $request->company,
            'linkdinurl' => $request->linkdinurl
        ]);

        if ($request->clientPhone != null) {
            foreach ($request->clientPhone as $phone) {
                ClientsPhone::create([
                    'clients_id' => $inst->id,
                    'phone' => $phone['phone'],
                    'type' => $phone['type']
                ]);
            }
        }

        if ($request->clientEmail != null) {
            foreach ($request->clientEmail as $email) {
                ClientsEmail::create([
                    'clients_id' => $inst->id,
                    'mail' => $email['email'],
                    'type' => $email['type']
                ]);
            }
        }

        $properties = [
            'attributes' => [
                'fname' => $request->fname,
                'lname' => $request->lname,
                'designation' => $request->designation,
                'companyId' => $company->id,
                'companyName' => $company->name,
                'linkdinurl' => $request->linkdinurl
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has created a new client",
            'subject_type' => 'App\Models\Client',
            'event' => 'create',
            'subject_id' => $inst->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Client Created Successfully']);
    }

    public function edit($id)
    {
        $client = Client::select('id', 'fname', 'lname', 'designation', 'companyId', 'linkdinurl')
            ->where('id', $id)
            ->first();

        $clientPhone = ClientsPhone::select('id as phoneId', 'phone', 'type', 'clients_id as clientId')
            ->where('clients_id', $id)
            ->get();

        $clientEmail = ClientsEmail::select('id as emailId', 'mail as email', 'type', 'clients_id as clientId')
            ->where('clients_id', $id)
            ->get();

        return Inertia::render('Clients/EditClient', [
            'client' => $client,
            'clientPhone' => $clientPhone,
            'clientEmail' => $clientEmail
        ]);
    }

    public function update(Request $request)
    {
        // validations
        $request->validate([
            'id' => 'required',
            'fname' => 'required',
            'lname' => 'required',
            'designation' => 'required',
            'linkdinurl' => 'nullable',
            'clientPhone' => 'nullable',
            'clientEmail' => 'nullable'
        ]);

        Client::where('id', $request->id)->update([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'designation' => $request->designation,
            'linkdinurl' => $request->linkdinurl,
            'updated_at' => now()
        ]);

        if ($request->clientPhone != null) {
            foreach ($request->clientPhone as $phone) {

                $ifExist = ClientsPhone::where('clients_id', $request->id)
                    ->where('phone', $phone['phone'])
                    ->where('type', $phone['type'])
                    ->first();

                if ($ifExist) {
                    ClientsPhone::where('clients_id', $request->id)->update([
                        'phone' => $phone['phone'],
                        'type' => $phone['type'],
                        'updated_at' => now()
                    ]);
                } else {
                    ClientsPhone::create([
                        'clients_id' => $request->id,
                        'phone' => $phone['phone'],
                        'type' => $phone['type']
                    ]);
                }
            }
        }

        if ($request->clientEmail != null) {
            foreach ($request->clientEmail as $email) {

                // $emailsClient = DB::table('clients_emails')->where('clients_id', $request->id)->update([
                //     'mail' => $email['email'],
                //     'type' => $email['type'],
                //     'updated_at' => now()
                // ]);

                $isExist = ClientsEmail::where('clients_id', $request->id)
                    ->where('mail', $email['email'])
                    ->where('type', $email['type'])
                    ->first();

                if ($isExist) {
                    ClientsEmail::where('clients_id', $request->id)->update([
                        'mail' => $email['email'],
                        'type' => $email['type'],
                        'updated_at' => now()
                    ]);
                } else {
                    ClientsEmail::create([
                        'clients_id' => $request->id,
                        'mail' => $email['email'],
                        'type' => $email['type']
                    ]);
                }
            }
        }

        $properties = [
            'attributes' => [
                'fname' => $request->fname,
                'lname' => $request->lname,
                'designation' => $request->designation,
                'linkdinurl' => $request->linkdinurl
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has updated a client",
            'subject_type' => 'App\Models\Client',
            'event' => 'update',
            'subject_id' => $request->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Client Updated Successfully']);
    }

    public function getClientDetails($id)
    {
        $client = Client::select('id', 'fname', 'lname', 'designation', 'companyId', 'linkdinurl')->where('id', $id)->first();
        $clientPhone = ClientsPhone::select('phone', 'type')->where('clients_id', $id)->get();
        $clientEmail = ClientsEmail::select('mail as email', 'type')->where('clients_id', $id)->get();


        return response()->json(['client' => $client, 'clientPhone' => $clientPhone, 'clientEmail' => $clientEmail]);
    }

    public function deleteClientPhone(Request $request)
    {
        // There should be atleast one phone number in the client
        $clientPhoneCount = ClientsPhone::where('clients_id', $request->clientId)->count();

        if ($clientPhoneCount < 2) {
            return response()->json(['error' => 'Atleast one phone number is required for this client.'], 400);
        }

        $ClientPhone = ClientsPhone::where('id', $request->phoneId)->first();

        if ($ClientPhone) {
            $ClientPhone->delete();

            $properties = [
                'attributes' => [
                    'client_id' => $request->clientId,
                    'phone_id' => $ClientPhone->id,
                    'phone' => $ClientPhone->phone,
                    'type' => $ClientPhone->type
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has deleted a phone of a client",
                'subject_type' => 'App\Models\ClientsPhone',
                'event' => 'delete',
                'subject_id' => $ClientPhone->id,
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

    public function deleteClientEmail(Request $request)
    {
        // There should be atleast one email in the client
        $clientEmailCount = ClientsEmail::where('clients_id', $request->clientId)->count();

        if ($clientEmailCount < 2) {
            return response()->json(['error' => 'Atleast one email is required for this client.'], 400);
        }

        $ClientEmail = ClientsEmail::where('id', $request->emailId)->first();

        if ($ClientEmail) {
            $ClientEmail->delete();

            $properties = [
                'attributes' => [
                    'client_id' => $request->clientId,
                    'email_id' => $ClientEmail->id,
                    'email' => $ClientEmail->mail,
                    'type' => $ClientEmail->type
                ]
            ];

            $activityLog = ActivityLog::create([
                'log_name' => 'default',
                'description' => $this->loggedinUser . " has deleted an email of a client",
                'subject_type' => 'App\Models\ClientsEmail',
                'event' => 'delete',
                'subject_id' => $ClientEmail->id,
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
        $client = Client::where('id', $request->id)->first();

        if (!$client) {
            return redirect()->back()->with('error', 'Client not found');
        }

        $client->update([
            'blacklisted' => !$client->blacklisted
        ]);

        $properties = [
            'attributes' => [
                'client_id' => $client->id,
                'name' => $client->fname . ' ' . $client->lname,
                'blacklisted' => $client->blacklisted
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has updated blacklist status of a client",
            'subject_type' => 'App\Models\Client',
            'event' => 'update',
            'subject_id' => $client->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Blacklist status updated successfully');
    }

    public function deleteClient(Request $request)
    {
        $client = Client::where('id', $request->id)->first();

        if (!$client) {
            return redirect()->back()->with('error', 'Client not found');
        }

        // Check if the client has any associated phone numbers or emails
        $clientPhoneCount = ClientsPhone::where('clients_id', $client->id)->count();
        $clientEmailCount = ClientsEmail::where('clients_id', $client->id)->count();

        if ($clientPhoneCount > 0) {
            ClientsPhone::where('clients_id', $client->id)->delete();
        }

        if ($clientEmailCount > 0) {
            ClientsEmail::where('clients_id', $client->id)->delete();
        }

        // delete notification where client is matched
        $notification = Notification::where('client_id', $client->id);

        if ($notification->exists()) {
            $notification->delete();
        }

        $client->delete();

        $properties = [
            'attributes' => [
                'client_id' => $client->id,
                'name' => $client->fname . ' ' . $client->lname
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->loggedinUser . " has deleted a client",
            'subject_type' => 'App\Models\Client',
            'event' => 'delete',
            'subject_id' => $client->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Client deleted successfully');
    }
}
