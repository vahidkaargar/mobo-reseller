<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'wallet_id',
        'status',
        'purchase_amount',
        'sale_amount',
        'paid_at',
        'completed_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => OrderStatusEnum::class,
    ];
}
