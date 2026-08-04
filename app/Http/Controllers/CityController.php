<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CityController extends Controller
{

    public $search;

    public function index(Request $request)
    {

        $this->search = $request->search;

        $query = DB::table('cities as c')
            ->select('c.id', 'c.name', 'c.state_code', 'c.country_code', 'c.latitude', 'c.longitude', 'c.created_at', 'c.updated_at', 'c.flag', 'c.wikiDataId', 'c.state_id', 'c.country_id')
            ->where('c.state_id', $request->state_id)
            ->where(function ($query) {
                $searchTerm = '%' . $this->search . '%';

                $query->where('c.name', 'like', $searchTerm);
            });

        if ($request->per_page == null) {
            $cities = $query->paginate(10);
        } else {
            $cities = $query->paginate($request->per_page);
        }

        return Inertia::render('Cities/Index', ['cities' => $cities]);
    }
}
