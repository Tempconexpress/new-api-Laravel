<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildModule extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'sub_module_id', 'name', 'slug', 'icon', 'order', 'is_active', 'extra_config', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function subModule()
    {
        return $this->belongsTo(SubModule::class);
    }

    public function tenantModuleAccess()
    {
        return $this->hasMany(TenantModuleAccess::class);
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
