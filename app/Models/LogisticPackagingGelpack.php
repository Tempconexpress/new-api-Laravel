<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticPackagingGelpack extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'logistic_packaging_gelpacks';
    protected $fillable = ['packaging_id', 'tic_gelpack_name', 'tic_gelpack_code', 'required_gelpack_qty'];
}