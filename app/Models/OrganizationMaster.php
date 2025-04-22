<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationMaster extends Model
{
    use SoftDeletes;

    protected $table = 'organization_master';
    protected $fillable = [
        'org_id',
        'org_name',
        'org_industry',
        'org_address',
        'org_website',
        'plan_id',
        'enable_2fa',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at'
    ];
    protected $dates = ['deleted_at']; // For SoftDeletes
}