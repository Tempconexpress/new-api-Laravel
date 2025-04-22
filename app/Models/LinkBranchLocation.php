<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkBranchLocation extends Model
{
    use HasFactory;
    protected $table = 'link_branch_location';

    // Define the primary key
    protected $primaryKey = 'link_id';

    // Disable timestamps if not used in the table
    public $timestamps = false;

    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        'link_id',
        'company_urn',
        'location_id',
        'link_type',
        'co_loc',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_active',
        'created_by' ,
        'updated_by',
        'deleted_by', 
        'is_active' ,
        'created_at' , 
        'updated_at' , 
        'deleted_at'
    ];
}
