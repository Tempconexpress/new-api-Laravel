<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCompanyAddress extends Model
{
    use HasFactory;

    // Specify the table name if it's not the default plural form
    protected $table = 'master_company_address';

    // Define the primary key if it's not 'id'
    protected $primaryKey = 'address_id';

    // If the primary key is not auto-incrementing (although in your case it is)
    public $incrementing = true;

    // Specify the data type of the primary key if necessary
    protected $keyType = 'int';

    // Disable timestamps if the table does not have created_at and updated_at columns
    public $timestamps = true;

    // Define the fillable attributes (optional, for mass assignment)
    protected $fillable = [
        'company_id',
        'co_gmap_place_id',
        'co_placename',
        'co_hno',
        'co_locality',
        'co_locality2',
        'location_id',
        'co_city',
        'co_state',
        'co_pincode',
        'co_iata',
        'co_country',
        'co_country_code',
        'address_type_csv',
        'default',
        'co_id',
        'doe',
        'deb_user_id',
        'disabled',
        'deleted',
        'deleted_ts',
        'is_active',
        'deleted_by',
        'deleted_at',
        'updated_by',
        'created_by'
    ];

    // Optionally define any relationships (e.g., belongsTo or hasMany)
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
