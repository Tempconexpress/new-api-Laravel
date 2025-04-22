<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPackagingComponent extends Model
{
    use HasFactory, SoftDeletes; // Enable soft deletes

    protected $table = 'master_packaging_component';

    protected $primaryKey = 'packaging_id';

    public $incrementing = true;

    protected $fillable = [
        'packaging_name', // Name of the packaging
        'shipment_temp', // Shipment temperature
        'gelpack_names', // Names of the gelpacks used
        'gelpack_count', // Number of gelpacks
        'cond_temp', // Conditional temperature
        'cond_time', // Conditional time
        'is_active', // New field for activation status
        'deleted_by', // New field for tracking who deleted the record
        'deleted_at', // New field for soft delete timestamp
        'updated_by', // New field for tracking who updated the record
        'created_by', // New field for tracking who created the record
    ];

    protected $dates = ['deleted_at']; // To handle soft deletes

    public $timestamps = true; // Automatically manage created_at and updated_at

    // Define relationships if needed...
}
