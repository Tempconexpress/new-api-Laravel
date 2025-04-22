<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkBankDetail extends Model
{
    use HasFactory, SoftDeletes;

    // Table name
    protected $table = 'link_bankdetails';

    // Primary key
    protected $primaryKey = 'bd_id';

    // Incrementing primary key
    public $incrementing = true;

    // Primary key type
    protected $keyType = 'int';

    // Fillable attributes for mass assignment
    protected $fillable = [
        'bd_urn',
        'account_name',
        'ledger_id',
        'ac_id',
        'ac_type',
        'paymode_code',
        'bank_ac_no',
        'bank_ac_type',
        'ifsc',
        'bank_name',
        'bank_ac_location',
        'swiftcode',
        'bank_address',
        'default_ac',
        'disabled',
        'deleted',
        'deleted_ts',
        'dou',
        'doe',
        'deb_user_id',
        'co_id',
        'is_active',
        'deleted_by',
        'updated_by',
        'created_by',
    ];

    // Date fields
    protected $dates = [
        'deleted_ts',
        'dou',
        'doe',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    // Define relationships
    public function company()
    {
        return $this->belongsTo(MasterCompany::class, 'ac_id', 'company_id');
    }
}
