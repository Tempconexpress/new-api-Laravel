<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'icon', 'order', 'is_active', 'extra_config', 'created_by', 'updated_by', 'deleted_by'
    ];
    public function planModules()
    {
        return $this->hasMany(PlanModule::class, 'module_id')
            ->where('module_type', 'module'); // Filter for top-level modules
    }
    public function subModules()
    {
        return $this->hasMany(SubModule::class);
    }

    public static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        $model->created_by = auth()->id();
    });

    static::updating(function ($model) {
        $model->updated_by = auth()->id();
    });

    static::deleting(function ($model) {
        $model->deleted_by = auth()->id();
    });
}

}
