<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticPackaging extends Model
{
    use HasFactory;

    protected $table = 'logistic_packaging';
    protected $primaryKey = 'packaging_id';
    public $timestamps = false;

    protected $fillable = [
        'packaging_name',
        'require_billing_check',
        'stock_tracking',
        'search_tags',
        'manufacturer',
        'usage',
        'editable_in_booking',
        'external_dimension_length',
        'external_dimension_width',
        'external_dimension_height',
        'internal_dimension_length',
        'internal_dimension_width',
        'internal_dimension_height',
        'actual_weight',
        'volumetric_weight',
        'volumetric_divisor',
        'validation_time',
        'default_packaging_supplier',
        'disabled',
        'doe',
        'remote_ip',
        'Required_Gelpack',
        'Capacity',
        'Rate_inpt',
        'Shipper_pack_out',
        'qr_code',
    ];

    protected $casts = [
        'require_billing_check' => 'boolean',
        'stock_tracking' => 'boolean',
        'editable_in_booking' => 'boolean',
        'validation_time' => 'boolean',
        'disabled' => 'boolean',
        'external_dimension_length' => 'decimal:2',
        'external_dimension_width' => 'decimal:2',
        'external_dimension_height' => 'decimal:2',
        'internal_dimension_length' => 'decimal:2',
        'internal_dimension_width' => 'decimal:2',
        'internal_dimension_height' => 'decimal:2',
        'actual_weight' => 'decimal:3',
        'volumetric_weight' => 'decimal:3',
        'doe' => 'datetime',
    ];

    public function temperatureControls()
    {
        return $this->hasMany(LogisticPackagingTemperatureControl::class, 'packaging_id', 'packaging_id');
    }
}
