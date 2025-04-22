<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubModule extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'module_id', 'name', 'slug', 'icon', 'order', 'is_active', 'extra_config', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function childModules()
    {
        return $this->hasMany(ChildModule::class, 'sub_module_id');
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

