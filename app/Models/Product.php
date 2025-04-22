<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{
    use HasFactory;
     // Enable soft deletes

    protected $table = 'products'; // Specify the table name

    protected $primaryKey = 'Product_id'; // Specify the primary key

    public $incrementing = true; // Enable auto-increment for the primary key

    // Add the new fillable fields
    protected $fillable = [
        'Product_id',
        'product_name',
        'supplier',
        'product_code',
        'hsn_sac_code',
        'usage',
        'price',
        'cgst',
        'sgst',
        'igst',
        'temperature',
        'product_type',
        'tags',
        'description',
        // 'is_active', // New field
        'created_at', // New field
        'updated_at', // New field
        'deleted_by',
        // 'deleted_at', // New field
    ];

}
