<?php

namespace App\Models;

use App\Http\Resources\BambooBrandResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use MongoDB\Laravel\Eloquent\Model;

#[UseResource(BambooBrandResource::class)]
class BambooBrand extends Model
{
    protected $connection = 'mongodb';
    protected $fillable = [
        'brand_id',
        'name',
        'country',
        'currency',
        'image',
        'products',
    ];

}
