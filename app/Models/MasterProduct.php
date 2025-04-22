<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterProduct extends Model
{
    use HasFactory, SoftDeletes; // Enable soft deletes

    protected $table = 'products'; // Specify the table name

    protected $primaryKey = 'id'; // Specify the primary key

    public $incrementing = true; // Enable auto-increment for the primary key

    // Add the new fillable fields
    protected $fillable = [
        'id',
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
        // 'deleted_by', // New field
        'deleted_at', // New field
    ];

    // protected $dates = ['deleted_at']; // Handle soft deletes by converting 'deleted_at' to a date

    public $timestamps = true; // Enable auto management of created_at and updated_at timestamps

    // Additional methods for relationships or custom logic can be added if necessary
}
