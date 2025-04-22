<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\LinkBranchLocation;

class InventoryTable extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'Inventory_table';

    // Define the primary key
    protected $primaryKey = 'ID';

    // Disable timestamps if not used in the table
    public $timestamps = false;

    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        
        'ID',
        'Order_id',
        'Product_id',
        'Product_code',
        'serial_number',
        'Tracking_ID',
        'Branch',
        'temperature_id',
        'Stock' ,
        'Created_Date',
        'Expiry_date', 
        'QR_code' ,
        'Usages_types' , 
        'other_packing' , 
        'packaging_name',
        'print_qr',
        'tracking_name'
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
