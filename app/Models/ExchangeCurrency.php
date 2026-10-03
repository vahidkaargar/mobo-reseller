<?php

namespace App\Models;

use App\Http\Resources\BambooBrandResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use MongoDB\Laravel\Eloquent\Model;

class ExchangeCurrency extends Model
{
    protected $connection = 'mongodb';
    protected $fillable = [
        'currency',
        'rates',
    ];

}
