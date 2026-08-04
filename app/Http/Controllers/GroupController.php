<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{

    public $user;

    public function __construct()
    {
        $this->user = Auth::check() ? Auth::user()->name : null;
    }


    public function index(Request $request)
    {
        $search = $request->search;
        $groups = DB::table('groups as g')
            ->select(
                'g.id',
                'g.name',
                'g.description',
                'g.created_at',
                'g.updated_at',
            )
            ->where(function ($query) use ($search) {
                $query->where('g.name', 'like', '%' . $search . '%')
                    ->orWhere('g.description', 'like', '%' . $search . '%');
            })
            ->groupBy(
                'g.id',
                'g.name',
                'g.description',
                'g.created_at',
                'g.updated_at',
            );
        if ($request->perPage == null) {
            $groups = $groups->paginate(10);
        } else {
            $groups = $groups->paginate($request->perPage);
        }
        return Inertia::render('Groups/Index', [
            'groups' => $groups
        ]);
    }

    public function create()
    {
        $permissions = DB::table('permissions as p')->select('p.id', 'p.name')->get();

        return Inertia::render('Groups/CreateGroup', [
            'permissions' => $permissions
        ]);
    }

    public function store(Request $request)
    {
        // validations
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
        ]);

        $group = DB::table('groups')->insert([
            'name' => $request->name,
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $properties = [
            'attributes' => [
                'name' => $request->name,
                'description' => $request->description,
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->user . " has created permission group",
            'subject_type' => 'App\Models\Group',
            'event' => 'created',
            'subject_id' => $group,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Group created successfully']);
    }

    public function update(Request $request)
    {
        // validations
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string',
            'description' => 'required|string',
        ]);

        $groupdetails = DB::table('groups')->where('id', $request->id)->first();

        $group = DB::table('groups')->where('id', $request->id)->update([
            'name' => $request->name,
            'description' => $request->description,
            'updated_at' => now()
        ]);

        $properties = [
            'old' => [
                'name' => $groupdetails->name,
                'description' => $groupdetails->description,
            ],
            'attributes' => [
                'name' => $request->name,
                'description' => $request->description,
            ]
        ];

        $activityLog = ActivityLog::create([
            'log_name' => 'default',
            'description' => $this->user . " has updated group",
            'subject_type' => 'App\Models\Group',
            'event' => 'updated',
            'subject_id' => $request->id,
            'causer_type' => 'App\Models\User',
            'causer_id' => Auth::id(),
            'properties' => json_encode($properties),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Group updated successfully']);
    }
}
