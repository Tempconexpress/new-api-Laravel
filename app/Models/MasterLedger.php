<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterLedger extends Model
{
    protected $table = 'master_ledgers';

    protected $primaryKey ='ledger_id';

    public $incrementing = true;

    // Add the new fillable fields
    protected $fillable = [
        
        'ledger_urn',
        'ledger_name',
        'ledger_name',
        'print_name',
        'group_code',
        'parent_id',
        'parent_urn',
        'voucher_type',
        'ac_category',
        'tags',
        'linked_id',
        'linked_type',
        'disabled'
    ];

    protected $dates = ['deleted_at']; // To handle soft deletes

    public $timestamps = true; // Automatically manage created_at and updated_at

    // Define relationships if needed...
}
