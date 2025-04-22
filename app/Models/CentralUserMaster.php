<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentralUserMaster extends Model
{
    use SoftDeletes;

    protected $table = 'central_user_master';
    protected $fillable = [
        'name',
        'user_id',
        'org_id',
        'password',
        'email',
        'mobile',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $dates = ['deleted_at']; // For SoftDeletes
}