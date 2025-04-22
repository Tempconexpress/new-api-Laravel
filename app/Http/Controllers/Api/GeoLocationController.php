<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeoLocations;
use App\Models\LinkBranchLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GeoLocationController extends Controller
{
    public function add_update(Request $request)
    {  
       
        // Validate the input data
        $validatedData = $request->validate([
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'country_code' => 'nullable|string|max:255',
            'iata' => 'nullable|string|max:255',
        ]);
          
        try {
            // Insert the geolocation record
            $geoLocation = GeoLocations::create([
                'city' => $validatedData['city'],
                'state' => $validatedData['state'],
                'country' => $validatedData['country'],
                'country_code' => $validatedData['country_code'],
                'iata' => $validatedData['iata'],
            ]);
          
            // Call a stored procedure if needed
            \DB::statement("CALL geoUpdateWithGSTStateCode()");

            // Return success response
            return response()->json([
                'status' => 200,
                'msg' => 'Geolocation added successfully!',
                'data' => $geoLocation,
            ]);

        } catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'status' => 201,
                'msg' => 'Failed to add geolocation. Please try again.',
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function getlinkedlocations(Request $request)
{    
    // Get the selected branch/company_urn from the request
    $branch_selected = $request->input('branch_selected');

    // Retrieve linked locations based on the selected branch
    $query = DB::table('link_branch_location as lbl')
        ->select(
            'lbl.location_id', 
            'lbl.company_urn', 
            DB::raw('COUNT(lbl.company_urn) AS linked'),
            'gl.country', 
            'gl.state', 
            'gl.city', 
            'gl.country', 
            'gl.country_code'
        )
        ->join('geo_locations as gl', 'gl.location_id', '=', 'lbl.location_id')
        ->where('lbl.company_urn', $branch_selected)
        ->groupBy(
            'lbl.location_id', 
            'lbl.company_urn', 
            'gl.country', 
            'gl.state', 
            'gl.city', 
            'gl.country_code'
        )
        ->get();

    // Add 'toggle' field to each record in the query result
    $queryWithToggle = $query->map(function ($item) {
        $item->toggle = 1; // Add toggle field with a value of 1
        return $item;
    });

    return response()->json([
        'status' => 200,
        'data' => $queryWithToggle
    ]);
}



    public function getTotalLocationsLinked()
    {
        $query =DB::table('link_branch_location as lbl')
        ->select('lbl.location_id', 'lbl.company_urn', DB::raw('COUNT(lbl.company_urn) AS linked'))
        ->join('geo_locations as gl', 'gl.location_id', '=', 'lbl.location_id')
        ->groupBy('lbl.location_id', 'lbl.company_urn')

        ->orderBy('gl.city')
        ->get()  ;
    
       
        return response()->json([
            'status' => 200,
            'data' => $query
        ]);

    
    }
    public function geoLocation(array $arr = [])
{
    // Default to POST data if no input array is provided
    if (empty($arr)) {
        $arr = request()->all(); // Retrieves data from the request
    }

    // Build the query
    $query = DB::table('geo_locations as gl');

    // Add conditions dynamically
    if (!empty($arr['location_id'])) {
        $query->where('gl.location_id', '=', $arr['location_id']);
    }

    if (!empty($arr['whe'])) {
        // Assuming $arr['whe'] is a raw SQL condition
        $query->whereRaw($arr['whe']);
    }

    
    $query =DB::table('link_branch_location as lbl')
    ->select('gl.city','gl.state','gl.country','gl.country_code','gl.iata', 'lbl.location_id',  DB::raw('COUNT(lbl.company_urn) AS linked'))
    ->join('geo_locations as gl', 'gl.location_id', '=', 'lbl.location_id')
    ->groupBy('lbl.location_id','gl.city','gl.state','gl.country','gl.country_code','gl.iata')

    ->orderBy('gl.city')
    ->get()  ;
        

        return response()->json([
            'status' => 200,
            'data' => $query
        ]);
}
public function getLocations(Request $request)
{
    $branchSelected = $request->input('branch_selected');

    // Retrieve the location_ids for the selected branch
    $locations = LinkBranchLocation::select('location_id')
        ->where('company_urn', $branchSelected)
        ->get();

    // Create an array of location_ids
    $t = [];
    foreach ($locations as $k) {
        $t[] = $k['location_id'];

    }

    // Fetch geo locations and add the count of linked company_urns (with toggle)
    $ds = GeoLocations::select(
        'geo_locations.location_id',
        'geo_locations.city',
        'geo_locations.state',
        'geo_locations.country',
        'geo_locations.iata',
        DB::raw('COUNT(link_branch_location.company_urn) AS linked')
    )
    ->leftJoin('link_branch_location', 'link_branch_location.location_id', '=', 'geo_locations.location_id')
    ->groupBy(
        'geo_locations.location_id',
        'geo_locations.city',
        'geo_locations.state',
        'geo_locations.country',
        'geo_locations.iata' // Add this column
    )
    ->get()
    ->map(function($item) use ($t) {
        // Add toggle as 1 if location_id is found in branch locations, else 0
        $item->toggle = in_array($item->location_id, $t) ? 1 : 0;
        return $item;
    });

return response()->json([
    'status' => 200,
    'data' => $ds
]);
}






    
}
