<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PlanModule extends Pivot
{
    protected $table = 'plan_module';

    protected $fillable = ['plan_id', 'module_type', 'module_id', 'is_active', 'parent_module_id'];

    public $incrementing = true;

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function module()
    {
        return $this->morphTo('module', 'module_type', 'module_id');
    }
}