<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StateController extends Controller
{

    public $search;

    public function index(Request $request)
    {

        $this->search = $request->search;

        $query = DB::table('states as s')
            ->select('s.id', 's.name', 's.fips_code', 's.iso2', 's.type', 's.longitude', 's.latitude', 's.flag', 's.wikiDataId', 's.created_at', 's.updated_at', 's.country_id')
            ->where('s.country_id', $request->country_id)
            ->where(function ($query) {
                $searchTerm = '%' . $this->search . '%';

                $query->where('s.name', 'like', $searchTerm);
            });


        if ($request->per_page == null) {
            $states = $query->paginate(10);
        } else {
            $states = $query->paginate($request->per_page);
        }


        return Inertia::render('States/Index', ['states' => $states]);
    }
}
