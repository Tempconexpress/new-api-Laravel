<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CTUGelSub extends Model
{
    use HasFactory;

    // Define the table name explicitly if it's not following Laravel's plural naming convention
    protected $table = 'CTU_gel_sub';

    // Primary key (if not 'id', explicitly define it)
    protected $primaryKey = 'id';

    // Disable timestamps if not needed
    public $timestamps = true;

    // Define fillable columns to allow mass assignment
    protected $fillable = [
        'CTUName',
        'Pro_id',
        'product_id',
        'sc_tracking_name',
        'cur_dates',
        'curr_time',
        'Gel_Name',
        'gel_code',
        'rackloc',
        'gelpack_discription',
        'inctu',
        'flag'
    ];
}
