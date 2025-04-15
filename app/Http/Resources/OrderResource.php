<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return [
        //     'id' => $this->id,
        //     'user_id' => $this->user_id,
        //     'total_amount' => $this->total_amount,
        //     'discount_total' => $this->discount_total,
        //     'final_amount' => $this->final_amount,
        //     'created_at' => $this->created_at,
        //     'updated_at' => $this->updated_at,
        //     'enrollments' => EnrollmentResource::collection($this->enrollments),
        // ];
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total_amount' => $this->total_amount,
            'discount_total' => $this->discount_total,
            'final_amount' => $this->final_amount,
            'payment_id' => $this->payment_id,
            'is_paid' => !empty($this->payment_id),
            // 'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'enrollments' => EnrollmentResource::collection($this->whenLoaded('enrollments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
