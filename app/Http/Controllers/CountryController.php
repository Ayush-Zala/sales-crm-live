<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public $search;

    public function index(Request $request)
    {

        $this->search = $request->search;

        $query = DB::table('countries as c')->
            select('c.id', 'c.name', 'c.iso3', 'c.numeric_code', 'c.iso2', 'c.phonecode', 'c.capital', 'c.currency', 'c.currency_name', 'c.currency_symbol', 'c.tld', 'c.native', 'c.region', 'c.subregion', 'c.nationality', 'c.timezones', 'c.translations', 'c.latitude', 'c.longitude', 'c.emoji', 'c.emojiU', 'c.flag', 'c.created_at', 'c.updated_at', 'c.wikiDataId', 'c.region_id', 'c.subregion_id')->where(function ($query) {
                $searchTerm = '%' . $this->search . '%';

                $query->where('c.name', 'like', $searchTerm);
            });

        if ($request->per_page == null) {
            $countries = $query->paginate(10);
        } else {
            $countries = $query->paginate($request->per_page);
        }

        return inertia::render('Country/Index', [
            'countries' => $countries
        ]);
    }
}
