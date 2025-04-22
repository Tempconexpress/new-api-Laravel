<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    // use HasFactory;
    protected $table = 'purchase_order';
    protected $primaryKey = 'order_id';
    public $timestamps = true;

    protected $fillable = [
       'order_urn','supplier_id', 'invoice_to', 'delivery_to', 'delivery_address', 'delivery_state',
        'delivery_statecode', 'delivery_pin', 'order_date', 'po_number', 'vendor_reference',
        'other_reference', 'payment_mode', 'payment_terms', 'delivery_date', 'dispatch_through',
        'remarks', 'conditions', 'non_taxable', 'taxable', 'total', 'doe', 'created_at', 'updated_at'
    ];

    // Relationship for supplier_id -> master_company.company_id
    public function vendor()
    {
        return $this->belongsTo(MasterCompany::class, 'supplier_id', 'company_id');
    }

    // Relationship for invoice_to -> master_company.company_id
    public function invoiceTo()
    {
        return $this->belongsTo(MasterCompany::class, 'invoice_to', 'company_id');
    }

    // Relationship for delivery_to -> master_company.company_id
    public function deliveryTo()
    {
        return $this->belongsTo(MasterCompany::class, 'delivery_to', 'company_id');
    }

    // Relationship for particulars (one-to-many)
    public function particulars()
    {
        return $this->hasMany(PurchaseOrderParticular::class, 'po_id', 'order_id');
    }

    // Relationship for attachments (one-to-many)
    public function attachments()
    {
        return $this->hasMany(PurchaseOrderAttachment::class, 'po_id', 'order_id');
    }
    public static function generatePoNumber()
    {
        $existingNumbers = self::pluck('order_urn')
            ->map(fn($urn) => substr($urn, -5))
            ->toArray();

        do {
            $randomNumber = mt_rand(10000, 99999);
        } while (in_array($randomNumber, $existingNumbers));

        $year = date('Y');
        $month = date('m');

        return "TEMP" . $year . $month . $randomNumber;
    }

}
