<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectManagementList extends Model
{
    protected $table = 'logistic_project_mgmt_2_lists';
    protected $primaryKey = null; // No primary key since it's a composite or no auto-increment
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'lpm_id', 'list_code', 'list_type_code', 'list_details'
    ];

    public function projectManagement()
    {
        return $this->belongsTo(ProjectManagement::class, 'lpm_id', 'lpm_id');
    }
}