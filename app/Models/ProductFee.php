<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFee extends Model
{
    protected $fillable = [
        'supplier_name',
        'supplier_id',
        'fee_percentage',
    ];
}
