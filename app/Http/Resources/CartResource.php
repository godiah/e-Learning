<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total_amount' => $this->total_amount,
            'discount_total' => $this->discount_total,
            'final_amount' => $this->final_amount,
            'status' => $this->status,
            'items' => CartItemResource::collection($this->items),
        ];
    }
}
