<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = ['plan_name', 'price', 'description'];

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'plan_modules', 'plan_id', 'module_id')
                    ->withPivot('access_level'); // Access control for modules
    }
}
