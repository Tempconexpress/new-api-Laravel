<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class UserMaster extends Authenticatable implements JWTSubject
{
    // Specify the table name
    protected $table = 'user_master';

    // Primary Key
    protected $primaryKey = 'user_id';

    // Specify the fillable fields
    protected $fillable = [
        'user_urn',
        'user_name',
        'login_id',
        'password',
        'user_level',
        'user_branches',
        'user_roles',
        'approvers',
        'PO_approvers',
        'QA_approvers',
        'login_allowed',
        'server_allowed',
        'email_id',
        'access_key',
        'last_login',
        'last_access',
        'login_timeout',
        'mobile_device_limit',
        'active',
        'doe',
        'co_id',
        
    ];

    // Hide sensitive fields like password
    protected $hidden = [
        'password',
        // 'access_key',
    ];

    // Implement JWTSubject methods for JWT support
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Returns the primary key (user_id)
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Automatically hash passwords when setting them
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    // Cast JSON fields to arrays
    protected $casts = [
        'user_branches' => 'array',
        'user_roles'    => 'array',
        'approvers'     => 'array',
        'QA_approvers'  => 'array',
        'server_allowed'=> 'array',
    ];

    // Dates that should be cast to Carbon instances
    protected $dates = [
        'last_login',
        'last_access',
        'doe',
    ];
}

