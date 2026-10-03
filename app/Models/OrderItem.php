<?php

namespace App\Models;

use App\Enums\SuppliersEnum;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'name',
        'supplier',
        'relation',
        'quantity',
        'purchase_amount',
        'profit_percentage',
        'sale_amount',
        'cards',
    ];

    protected $casts = [
        'relation' => 'array',
        'cards' => 'encrypted:array',
        'supplier' => SuppliersEnum::class,
    ];

}
