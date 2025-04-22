<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCompanyContact extends Model
{
    use HasFactory;
    protected $table = 'master_company_contacts';

    protected $primaryKey = 'contact_id';

    public $incrementing = true;

    // Add the new fillable fields
    protected $fillable = [
        'company_id',
        'contact_name',
        'contact_type',
        'contact_email',
        'contact_mobile',
        'contact_phone',
        'disabled',
        'deleted',
        'deleted_ts',
        'doe',
        'deb_user_id',
        'co_id',
        'is_active', // New field
        'deleted_by', // New field
        'deleted_at', // New field
        'updated_by', // New field
        'created_by', // New field
    ];

    protected $dates = ['deleted_at']; // To handle soft deletes

    public $timestamps = true; // Automatically manage created_at and updated_at

    // Define relationships if needed...
    
}
