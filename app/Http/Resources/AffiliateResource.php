<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'payment_info' => $this->payment_info,
            'status' => $this->status,
            'total_earnings' => $this->total_earnings,
            'total_sales' => $this->total_sales,
            'commission_rate' => $this->commission_rate,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
