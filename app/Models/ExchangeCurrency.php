<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ExchangeCurrency extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'currency',
        'rates',
    ];
}
