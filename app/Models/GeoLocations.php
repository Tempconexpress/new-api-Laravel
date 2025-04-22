<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\LinkBranchLocation;

class GeoLocations extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'geo_locations';

    // Define the primary key
    protected $primaryKey = 'location_id';

    // Disable timestamps if not used in the table
    public $timestamps = false;

    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        
        'city',
        'state',
        'pincode',
        'country',
        'country_code',
        'iata',
        'gst_state_code',
        'unique1',
        'created_by' ,
        'updated_by',
        'deleted_by', 
        'is_active' ,
        'created_at' , 
        'updated_at' , 
        'deleted_at'
    ];

    // public function getTotalLocationsLinked()
    // {
    //     $query = DB::table('link_branch_location as lbl')
    //     ->select('gl.city', 'lbl.location_id')
    //     ->join('geo_locations as gl', 'gl.location_id', '=', 'lbl.location_id')
    //     ->groupBy('lbl.location_id', 'gl.city')
    //     ->orderBy('gl.city')
    //     ->get();
    

    
    // }
}
