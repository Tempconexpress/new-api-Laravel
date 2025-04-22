<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CTURacksCapacity extends Model
{
    use HasFactory;

    protected $table = 'CTU_Racks_Capacity'; // Define custom table name

    protected $fillable = [
        'cnnect_id',
        'tracking_code',
        'CTU_product_code',
        'Racks_name',
        'Capacity',
        'CTU_Temperature',
        'flag'
    ];

    public $timestamps = true; // Enable created_at & updated_at timestamps

    /**
     * Relationship example (if needed)
     * Assuming a `cnnect_id` is related to a `CTUMasterDetails` model
     */
    public function masterDetails()
    {
        return $this->belongsTo(CTUMasterDetails::class, 'cnnect_id');
    }
}
