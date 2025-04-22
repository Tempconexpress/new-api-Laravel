<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CTUMasterDetail extends Model
{
    use HasFactory;

    protected $table = 'CTU_master_details'; // Explicitly define table name

    protected $primaryKey = 'id'; // Define primary key

    public $timestamps = true; // Enable timestamps (created_at, updated_at)

    protected $fillable = [
        'cnnect_id', // Possible typo: Should this be "connect_id"?
        'tracking_code',
        'branch_id',
        'CTU_id',
        'CTU_product_code',
        'CTU_Temperature',
        'CTU_calibration_date',
        'CTU_Calibration_due_date',
        'CTU_Mapping_date',
        'CTU_Mapping_due_date',
        'Number_of_Racks',
        'barcode',
        'created_user',
        'remark',
        'flag',
        
    ];
}
