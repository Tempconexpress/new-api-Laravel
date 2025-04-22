<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderAttachment extends Model
{
    use HasFactory, SoftDeletes;

    // Specify the table name
    protected $table = 'purchase_order_attachments';

    // Define the primary key
    protected $primaryKey = 'attachment_id';

    // Auto-incrementing primary key
    public $incrementing = true;

    // Data type of the primary key
    protected $keyType = 'int';

    // Enable timestamps (created_at, updated_at)
    public $timestamps = true;

    // Define fillable fields
    protected $fillable = [
        'po_id',              // Foreign key to PurchaseOrder (updated to order_id)
        'file_name',          // Name of the uploaded file
        'file_path',          // Path to the stored file
        'file_type',          // File type (e.g., pdf, jpg, png)
        'is_active',          // Active status
        'deleted_by',         // User who deleted
        'deleted_at',         // Soft delete timestamp
        'updated_by',         // User who updated
        'created_by',         // User who created
    ];

    // Handle soft delete timestamp
    protected $dates = ['deleted_at'];

    // Relationship with PurchaseOrder
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'order_id');
    }
}