<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemperatureMaster extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'temp_range';

    // Define the primary key
    protected $primaryKey = 'id';

    // Disable timestamps if not used in the table
    public $timestamps = false;

    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        'id',
        'temperature_id',
        'temperature_from',
        'temperature_to',
        
    ];

    // public function counts()
    // {
    //     return $this->select('list_name')
    //         ->where('list_name', '<>', 'List Type')
    //         ->selectRaw('COUNT(list_name) AS count')
    //         ->groupBy('list_name')
    //         ->orderBy('list_name')
    //         ->get();
    // }
}
