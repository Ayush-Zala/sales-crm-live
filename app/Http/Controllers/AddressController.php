<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AddressController extends Controller
{
    public function getstates(Request $request)
    {
        // validations
        // $request->validate([
        //     'country_id' => 'required|integer|exists:countries,id',
        // ]);

        $states = DB::table('states')
            ->select('id', 'name', 'longitude', 'latitude')
            ->where('country_id', $request->country_id)
            ->get();


        return response()->json($states);
    }

    public function getcities(Request $request)
    {
        // validations
        // $request->validate([
        //     'state_id' => 'required|integer|exists:states,id',
        // ]);

        $cities = DB::table('cities', 'longitude', 'latitude')
            ->select('id', 'name')
            ->where('state_id', $request->state_id)
            ->get();

        return response()->json($cities);
    }
}
