<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalStatusCheck extends Model
{
    use HasFactory;
    
    protected $table = 'approval_status_check';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    protected $fillable = [
        'order-id',
        'Level_check',
        'user_id1',
        'status1',
        'user_id2',
        'status2',
        'user_id3',
        'status3',
        'check_final_status'
    ];
    
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order-id');
    }
}
