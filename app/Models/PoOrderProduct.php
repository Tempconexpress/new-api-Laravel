<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoOrderProduct extends Model
{
    use HasFactory;

    protected $table = 'po_order_products';

    protected $fillable = [
        'order_id',
        'row_no',
        'item_id',
        'item_name',
        'manufacturer',
        'quentity',
        'received_quantity',
        'unit_id',
        'rate',
        'amount',
        'CGST',
        'SGST',
        'IGST',
        'tax_amount',
        'hsn_code',
        'reference',
        'row_total',
        'product_type',
    ];

    public function order()
    {
        return $this->belongsTo(PoOrder::class, 'order_id');
    }

    public function packaging()
    {
        return $this->belongsTo(Packaging::class, 'packaging_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
