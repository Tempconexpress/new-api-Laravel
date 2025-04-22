<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticPackagingTemperatureControl extends Model
{
    use HasFactory;

    protected $table = 'logistic_packaging_temperature_control';
    protected $primaryKey = null; // No single primary key, composite key assumed
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'packaging_id',
        'temperature_control_id',
        'qty_required_in_booking',
        'disabled',
    ];

    protected $casts = [
        'qty_required_in_booking' => 'boolean',
        'disabled' => 'boolean',
    ];

    public function packaging()
    {
        return $this->belongsTo(LogisticPackaging::class, 'packaging_id', 'packaging_id');
    }
}
