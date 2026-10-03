<?php

namespace App\Http\Resources;

use App\Enums\SuppliersEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BambooBrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->brand_id,
            'supplier' => SuppliersEnum::BAMBOO->value,
            'name' => $this->name,
            'country' => $this->country,
            'currency' => $this->currency,
            'image' => $this->image,
        ];
    }
}
