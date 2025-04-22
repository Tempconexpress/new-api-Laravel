<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectManagement extends Model
{
    protected $table = 'logistic_project_mgmt_1_main';
    protected $primaryKey = 'lpm_id';
    public $incrementing = true;
    
    public $timestamps = false;

    protected $fillable = [
        'study_no', 'client_id', 'protocol_no', 'sponsor', 'project_manager',
        'mobile', 'telephone', 'email', 'phase', 'therapeutic_area', 'commodity',
        'specimen_type', 'temperature_requirement_details', 'stability_issue',
        'study_start_date', 'study_end_date', 'shipment_frequency', 'doe_userid',
        'co_id', 'doe', 'remote_ip', 'deleted'
    ];

    public function lists()
    {
        return $this->hasMany(ProjectManagementList::class, 'lpm_id', 'lpm_id');
    }
}