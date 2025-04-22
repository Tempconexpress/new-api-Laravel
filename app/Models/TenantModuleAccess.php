<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantModuleAccess extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'child_module_id', 'tenant_id', 'access', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function childModule()
    {
        return $this->belongsTo(ChildModule::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

