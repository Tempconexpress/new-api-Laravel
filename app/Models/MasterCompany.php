<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCompany extends Model
{
    use HasFactory, SoftDeletes; // Enable soft deletes

    protected $table = 'master_company';

    protected $primaryKey = 'company_id';
    public function contacts()
    {
        return $this->hasMany(MasterCompanyContact::class, 'company_id', 'company_id');
    }

    public function addresses()
    {
        return $this->hasMany(MasterCompanyAddress::class, 'company_id', 'company_id');
    }

    public function bankDetails()
    {
        return $this->hasMany(LinkBankDetail::class, 'ac_id', 'company_id');
    }
    public $incrementing = true;

    // Add the new fillable fields
    protected $fillable = [
        'company_urn',
        'company_name',
        'contact_id',
        'zoho_placeof_contact',
        'zohocurrency_id',
        'display_name',
        'trans_category',
        'company_type',
        'entity_type',
        'usagetags',
        'nick_name',
        'group_name',
        'parent_company_id',
        'industry_type_id',
        'shipment_category_id',
        'fuel_surcharge_india',
        'fuel_surcharge_intl',
        'cin',
        'pan',
        'gst_no',
        'tds_rate',
        'gst_treatment',
        'account_opened',
        'credit_status',
        'credit_period',
        'currency',
        'sales_rep_id',
        'notify_emails',
        'notify_mobiles',
        'notify_triggers',
        'suspend',
        'disabled',
        'doe',
        'doe_user_id',
        'billing_company_id',
        'ledger_id',
        'co_id',
        'disable_status',
        'address_id_temp',
        'is_active', // New field
        'deleted_by', // New field
        'deleted_at', // New field
        'updated_by', // New field
        'created_by', // New field
        'msme_udyam_type', // New field
        'msme_udyam_no', // New field
    ];
    public function entityType()
    {
        return $this->belongsTo(MasterList::class, 'entity_type', 'list_id');
    }
    public function gstTreatment()
    {
        return $this->belongsTo(MasterList::class, 'gst_treatment', 'list_id');
    }
    public function currency()
    {
        return $this->belongsTo(MasterList::class, 'currency', 'list_id');
    }
    protected $dates = ['deleted_at']; // To handle soft deletes

    public $timestamps = true; // Automatically manage created_at and updated_at

    // Define relationships if needed...
}



