<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'cost', 'billing_cycle', 'description', 'max_users', 'trial_period', 'status', 'whitelabeling',
    ];

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'plan_module')->withPivot('is_active')->withTimestamps();
    }
}