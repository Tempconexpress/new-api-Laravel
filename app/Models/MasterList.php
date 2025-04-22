<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterList extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'master_lists';

    // Define the primary key
    protected $primaryKey = 'list_id';

    // Disable timestamps if not used in the table
    public $timestamps = false;

    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        'list_id',
        'list_code',
        'list_name',
        'item_name',
        'display_as',
        'abbrv',
        'group_tag',
        'default_option',
        'display_order',
        'list_description',
        'sw_fixed',
        'grouping_tags',
        'company_id',
        'disabled',
        'deleted'
    ];

    public function counts()
    {
        return $this->select('list_name')
            ->where('list_name', '<>', 'List Type')
            ->selectRaw('COUNT(list_name) AS count')
            ->groupBy('list_name')
            ->orderBy('list_name')
            ->get();
    }
}
